<?php
// driver.php
include_once 'api/config.php';

// Проверяем и добавляем колонку driver_status если её нет
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'driver_status'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN driver_status ENUM('free', 'busy', 'offline') DEFAULT 'free' AFTER role");
    $conn->query("UPDATE users SET driver_status = 'free' WHERE role = 3");
}

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Функция для обновления статуса водителя
function updateDriverStatus($conn, $user_id, $status) {
    $conn->query("UPDATE users SET driver_status = '$status' WHERE id_user = $user_id");
}

// Функция для проверки активных заказов
function getActiveOrdersCount($conn, $user_id) {
    $result = $conn->query("SELECT COUNT(*) as total FROM orders o 
                            JOIN complete_orders co ON o.id_order = co.id_order 
                            JOIN transport t ON co.id_transport = t.id_transport 
                            WHERE t.id_user = $user_id AND o.status IN (2,3,4)");
    return $result->fetch_assoc()['total'];
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['take_order'])) {
        // Водитель берет заказ
        $order_id = $_POST['order_id'];
        
        // Проверяем, свободен ли водитель
        $current_status = $conn->query("SELECT driver_status FROM users WHERE id_user = $user_id")->fetch_assoc()['driver_status'];
        
        if ($current_status == 'free') {
            // Находим транспорт водителя
            $transport = $conn->query("SELECT id_transport FROM transport WHERE id_user = $user_id")->fetch_assoc();
            
            if ($transport) {
                // Назначаем заказ водителю
                $conn->query("INSERT INTO complete_orders (id_order, id_transport, comments) 
                             VALUES ($order_id, {$transport['id_transport']}, 'Взят в работу')");
                $conn->query("UPDATE orders SET status = 2 WHERE id_order = $order_id");
                updateDriverStatus($conn, $user_id, 'busy');
                
                header("Location: driver.php?success=order_taken");
                exit;
            } else {
                header("Location: driver.php?error=no_transport");
                exit;
            }
        } else {
            header("Location: driver.php?error=already_busy");
            exit;
        }
    }
    
    if (isset($_POST['start_route'])) {
        $order_id = $_POST['order_id'];
        $conn->query("UPDATE orders SET status = 2 WHERE id_order = $order_id");
        updateDriverStatus($conn, $user_id, 'busy');
        header("Location: driver.php?success=started");
        exit;
    }
    
    if (isset($_POST['arrived'])) {
        $order_id = $_POST['order_id'];
        $conn->query("UPDATE orders SET status = 3 WHERE id_order = $order_id");
        updateDriverStatus($conn, $user_id, 'busy');
        header("Location: driver.php?success=arrived");
        exit;
    }
    
    if (isset($_POST['loaded'])) {
        $order_id = $_POST['order_id'];
        $conn->query("UPDATE orders SET status = 4 WHERE id_order = $order_id");
        updateDriverStatus($conn, $user_id, 'busy');
        header("Location: driver.php?success=loaded");
        exit;
    }
    
    if (isset($_POST['complete'])) {
        $order_id = $_POST['order_id'];
        $conn->query("UPDATE orders SET status = 5 WHERE id_order = $order_id");
        
        // Проверяем, есть ли еще активные заказы
        $active_orders = getActiveOrdersCount($conn, $user_id);
        
        if ($active_orders == 0) {
            updateDriverStatus($conn, $user_id, 'free');
        }
        
        header("Location: driver.php?success=completed");
        exit;
    }
    
    if (isset($_POST['problem'])) {
        $order_id = $_POST['order_id'];
        $problem = $_POST['problem_description'];
        $conn->query("UPDATE orders SET comments = CONCAT(IFNULL(comments,''), ' [ПРОБЛЕМА: $problem]') WHERE id_order = $order_id");
        updateDriverStatus($conn, $user_id, 'busy');
        header("Location: driver.php?success=problem");
        exit;
    }
    
    if (isset($_POST['set_status'])) {
        // Ручное изменение статуса
        $new_status = $_POST['new_status'];
        if (in_array($new_status, ['free', 'busy', 'offline'])) {
            updateDriverStatus($conn, $user_id, $new_status);
            header("Location: driver.php?success=status_changed");
            exit;
        }
    }
    
    if (isset($_FILES['photo'])) {
        $order_id = $_POST['order_id'];
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $filename = time() . '_' . basename($_FILES["photo"]["name"]);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $conn->query("UPDATE complete_orders SET photo = '$filename' WHERE id_order = $order_id");
            header("Location: driver.php?success=photo_uploaded");
            exit;
        }
    }
}

// При загрузке страницы проверяем статус
$user_query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user = $user_query->fetch_assoc();

// Проверяем активные заказы при загрузке
$active_orders_count = getActiveOrdersCount($conn, $user_id);

// Автоматически устанавливаем статус при загрузке
if ($active_orders_count > 0 && $user['driver_status'] != 'busy') {
    updateDriverStatus($conn, $user_id, 'busy');
    $user['driver_status'] = 'busy';
} elseif ($active_orders_count == 0 && $user['driver_status'] != 'free') {
    updateDriverStatus($conn, $user_id, 'free');
    $user['driver_status'] = 'free';
}

// Получаем обновленные данные
$user_query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user = $user_query->fetch_assoc();

// Получаем транспорт водителя
$transport_query = $conn->query("SELECT * FROM transport WHERE id_user = $user_id");
$transport = $transport_query->num_rows > 0 ? $transport_query->fetch_assoc() : null;

// Получаем активные заказы водителя
$active_orders = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                               FROM orders o 
                               LEFT JOIN materials m ON o.id_materials = m.id_material 
                               LEFT JOIN complete_orders co ON o.id_order = co.id_order 
                               LEFT JOIN transport t ON co.id_transport = t.id_transport 
                               WHERE t.id_user = $user_id AND o.status IN (2,3,4)
                               ORDER BY o.data_Time");

// Получаем доступные заказы (которые можно взять)
$available_orders = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                                  FROM orders o 
                                  LEFT JOIN materials m ON o.id_materials = m.id_material 
                                  WHERE o.status = 1 
                                  AND o.id_order NOT IN (SELECT id_order FROM complete_orders)
                                  ORDER BY o.data_Time");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель водителя - Green Mile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-green: #2E7D32;
            --secondary-green: #4CAF50;
            --light-green: #C8E6C9;
            --primary-blue: #0D47A1;
            --secondary-blue: #1976D2;
            --light-blue: #BBDEFB;
            --gradient-green: linear-gradient(135deg, #2E7D32, #4CAF50);
            --gradient-blue: linear-gradient(135deg, #0D47A1, #1976D2);
            --gradient-orange: linear-gradient(135deg, #FF9800, #FF5722);
            --gradient-mix: linear-gradient(135deg, #2E7D32, #1976D2);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: var(--gradient-orange);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .dashboard-header h1 {
            color: white;
            margin: 0;
            font-size: 1.8rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-small {
            padding: 5px 12px;
            font-size: 0.85rem;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }

        .btn-primary {
            background: var(--secondary-green);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-green);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: black;
        }

        .btn-icon {
            padding: 6px 12px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-icon i {
            font-size: 1rem;
        }

        .btn-start { background: #4CAF50; color: white; }
        .btn-arrive { background: #2196F3; color: white; }
        .btn-load { background: #FF9800; color: white; }
        .btn-complete { background: #9C27B0; color: white; }
        .btn-problem { background: #f44336; color: white; }
        .btn-photo { background: #607D8B; color: white; }
        .btn-take { background: #4CAF50; color: white; }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .status-indicator:hover {
            opacity: 0.9;
        }
        
        .status-indicator i {
            font-size: 1rem;
        }
        
        .status-free {
            background: #4CAF50;
        }
        
        .status-busy {
            background: #FF9800;
        }
        
        .status-offline {
            background: #9E9E9E;
        }

        .status-menu {
            position: absolute;
            top: 70px;
            right: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
            overflow: hidden;
        }

        .status-menu.show {
            display: block;
        }

        .status-option {
            padding: 12px 24px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #eee;
        }

        .status-option:hover {
            background: #f5f5f5;
        }

        .status-option.free { color: #4CAF50; }
        .status-option.busy { color: #FF9800; }
        .status-option.offline { color: #9E9E9E; }

        .driver-container {
            display: flex;
            flex-direction: row-reverse;
            gap: 20px;
            min-height: calc(100vh - 150px);
        }

        .driver-sidebar {
            width: 260px;
            background: var(--gradient-orange);
            color: white;
            border-radius: 15px;
            padding: 20px 0;
            flex-shrink: 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .driver-nav ul {
            list-style: none;
            padding: 0;
        }

        .driver-nav li {
            margin: 0;
        }

        .driver-nav a {
            display: block;
            padding: 15px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }

        .driver-nav a:hover {
            background: rgba(255,255,255,0.2);
            border-left-color: white;
            color: white;
        }

        .driver-nav a.active {
            background: rgba(255,255,255,0.2);
            border-left-color: white;
            color: white;
            font-weight: bold;
        }

        .driver-nav i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .driver-content {
            flex: 1;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #FF9800;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #FF9800;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .transport-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #FF9800;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .order-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .order-card.active {
            border-left-color: #FF9800;
        }

        .order-card.available {
            border-left-color: #4CAF50;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .order-header h3 {
            color: #333;
            font-size: 1.1rem;
        }

        .order-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-1 { background: #fff3cd; color: #856404; }
        .status-2 { background: #cce5ff; color: #004085; }
        .status-3 { background: #d4edda; color: #155724; }
        .status-4 { background: #fff3cd; color: #856404; }
        .status-5 { background: #d4edda; color: #155724; }

        .order-info {
            margin-bottom: 15px;
        }

        .order-info p {
            margin: 8px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-info i {
            color: #FF9800;
            width: 20px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .action-buttons button,
        .action-buttons form button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
            width: 100%;
        }

        .action-buttons button:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }

        .no-orders {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
        }

        .no-orders i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .chat-container {
            height: 500px;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            display: flex;
            flex-direction: column;
            max-width: 70%;
        }

        .message.sent {
            align-self: flex-end;
        }

        .message.received {
            align-self: flex-start;
        }

        .message-content {
            padding: 12px 16px;
            border-radius: 15px;
            position: relative;
            word-wrap: break-word;
        }

        .message.sent .message-content {
            background: #FF9800;
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message.received .message-content {
            background: white;
            color: #333;
            border: 1px solid #e0e0e0;
            border-bottom-left-radius: 5px;
        }

        .message-info {
            display: flex;
            gap: 10px;
            font-size: 0.75rem;
            margin-top: 5px;
            color: #999;
        }

        .message.sent .message-info {
            justify-content: flex-end;
        }

        .chat-input {
            display: flex;
            gap: 10px;
            padding: 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }

        .chat-input input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .chat-input input:focus {
            border-color: #FF9800;
            outline: none;
        }

        .chat-input button {
            padding: 12px 24px;
            background: #FF9800;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .chat-input button:hover {
            background: #F57C00;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            border-top: 5px solid #FF9800;
        }

        .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 100px;
        }

        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            display: none;
            z-index: 1001;
            animation: slideIn 0.3s;
        }

        .notification.show {
            display: block;
        }

        .notification.error {
            background: #f44336;
        }

        .notification.success {
            background: #4CAF50;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            padding: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1><i class="fas fa-truck"></i> Панель водителя</h1>
            <div class="user-info" style="position: relative;">
                <span>
                    <i class="fas fa-user"></i> 
                    <?php echo $user['First_Name'] . ' ' . $user['Last_Name']; ?> 
                    (Водитель)
                </span>
                
                <!-- Индикатор статуса (кликабельный) -->
                <div class="status-indicator status-<?php echo $user['driver_status']; ?>" id="statusIndicator" onclick="toggleStatusMenu()">
                    <i class="fas <?php echo $user['driver_status'] == 'free' ? 'fa-check-circle' : ($user['driver_status'] == 'busy' ? 'fa-clock' : 'fa-power-off'); ?>"></i>
                    <span id="statusText">
                        <?php echo $user['driver_status'] == 'free' ? 'Свободен' : ($user['driver_status'] == 'busy' ? 'Занят' : 'Не в сети'); ?>
                    </span>
                </div>
                
                <!-- Меню выбора статуса -->
                <div class="status-menu" id="statusMenu">
                    <form method="POST" id="statusForm">
                        <input type="hidden" name="set_status" value="1">
                        <div class="status-option free" onclick="setStatus('free')">
                            <i class="fas fa-check-circle"></i> Свободен
                        </div>
                        <div class="status-option busy" onclick="setStatus('busy')">
                            <i class="fas fa-clock"></i> Занят
                        </div>
                        <div class="status-option offline" onclick="setStatus('offline')">
                            <i class="fas fa-power-off"></i> Не в сети
                        </div>
                    </form>
                </div>
                
                <a href="/124/api/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </header>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="notification show success" id="successNotification">
                <?php
                $messages = [
                    'order_taken' => '✓ Заказ принят! Статус: Занят',
                    'started' => '✓ Маршрут начат!',
                    'arrived' => '✓ Отмечено прибытие!',
                    'loaded' => '✓ Загружено!',
                    'completed' => '✓ Заказ завершен!',
                    'problem' => '⚠ Проблема отмечена',
                    'photo_uploaded' => '✓ Фото загружено',
                    'status_changed' => '✓ Статус изменен'
                ];
                echo $messages[$_GET['success']] ?? '✓ Действие выполнено';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="notification show error" id="errorNotification">
                <?php
                $errors = [
                    'no_transport' => '✗ У вас нет транспорта!',
                    'already_busy' => '✗ Вы уже заняты!'
                ];
                echo $errors[$_GET['error']] ?? '✗ Ошибка';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="driver-container">
            <main class="driver-content">
                <!-- Вкладка: Мои активные заказы -->
                <div id="tab-active" class="tab-content active">
                    <h2><i class="fas fa-tasks"></i> Мои активные заказы</h2>
                    
                    <!-- Информация о транспорте -->
                    <?php if ($transport): ?>
                    <div class="transport-card">
                        <h3><i class="fas fa-truck"></i> Ваш транспорт</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px;">
                            <div>
                                <div style="color: #666;">Гос. номер</div>
                                <div style="font-weight: bold;"><?php echo $transport['Gos_N']; ?></div>
                            </div>
                            <div>
                                <div style="color: #666;">Модель</div>
                                <div style="font-weight: bold;"><?php echo $transport['Model_transport']; ?></div>
                            </div>
                            <div>
                                <div style="color: #666;">Ваш статус</div>
                                <div style="font-weight: bold; color: <?php echo $user['driver_status'] == 'free' ? '#4CAF50' : '#FF9800'; ?>">
                                    <?php echo $user['driver_status'] == 'free' ? 'Свободен' : 'Занят'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Статистика -->
                    <div class="stats-grid">
                        <?php
                        $active_count = $active_orders->num_rows;
                        $completed_today = $conn->query("SELECT COUNT(*) as total FROM orders o 
                                                        JOIN complete_orders co ON o.id_order = co.id_order 
                                                        JOIN transport t ON co.id_transport = t.id_transport 
                                                        WHERE t.id_user = $user_id AND o.status = 5 AND DATE(o.data_Time) = CURDATE()")->fetch_assoc()['total'];
                        ?>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #FF9800;"><?php echo $active_count; ?></div>
                            <div class="stat-label">Активных заказов</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #4CAF50;"><?php echo $completed_today; ?></div>
                            <div class="stat-label">Выполнено сегодня</div>
                        </div>
                    </div>
                    
                    <!-- Список активных заказов -->
                    <?php if ($active_count > 0): ?>
                        <div class="orders-grid">
                            <?php while($order = $active_orders->fetch_assoc()): ?>
                            <div class="order-card active">
                                <div class="order-header">
                                    <h3><i class="fas fa-box"></i> Заказ #<?php echo $order['id_order']; ?></h3>
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php 
                                        $statuses = [
                                            2 => 'В пути', 
                                            3 => 'На месте', 
                                            4 => 'Загружено'
                                        ];
                                        echo $statuses[$order['status']] ?? 'Неизвестно'; 
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="order-info">
                                    <p><i class="fas fa-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></p>
                                    <p><i class="fas fa-box"></i> <?php echo $order['material_name']; ?> (<?php echo $order['volume']; ?> кг)</p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo $order['addres']; ?></p>
                                </div>
                                
                                <div class="action-buttons">
                                    <?php if ($order['status'] == 2): ?>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                            <button type="submit" name="arrived" class="btn-arrive">
                                                <i class="fas fa-map-marker-alt"></i> Приехал
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] == 3): ?>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                            <button type="submit" name="loaded" class="btn-load">
                                                <i class="fas fa-box"></i> Загружено
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] == 4): ?>
                                        <form method="POST" style="width: 100%;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                            <button type="submit" name="complete" class="btn-complete">
                                                <i class="fas fa-check-circle"></i> Завершить
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn-photo" onclick="showPhotoForm(<?php echo $order['id_order']; ?>)">
                                        <i class="fas fa-camera"></i> Фото
                                    </button>
                                    
                                    <button type="button" class="btn-problem" onclick="showProblemForm(<?php echo $order['id_order']; ?>)">
                                        <i class="fas fa-exclamation-triangle"></i> Проблема
                                    </button>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-check-circle"></i>
                            <h3>Нет активных заказов</h3>
                            <p>Перейдите во вкладку "Доступные заказы" чтобы взять работу</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Вкладка: Доступные заказы -->
                <div id="tab-available" class="tab-content">
                    <h2><i class="fas fa-box-open"></i> Доступные заказы</h2>
                    
                    <?php if ($user['driver_status'] == 'free'): ?>
                        <?php if ($available_orders->num_rows > 0): ?>
                            <div class="orders-grid">
                                <?php while($order = $available_orders->fetch_assoc()): ?>
                                <div class="order-card available">
                                    <div class="order-header">
                                        <h3><i class="fas fa-box"></i> Заказ #<?php echo $order['id_order']; ?></h3>
                                        <span class="order-status status-1">Новый</span>
                                    </div>
                                    
                                    <div class="order-info">
                                        <p><i class="fas fa-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></p>
                                        <p><i class="fas fa-box"></i> <?php echo $order['material_name']; ?> (<?php echo $order['volume']; ?> кг)</p>
                                        <p><i class="fas fa-map-marker-alt"></i> <?php echo $order['addres']; ?></p>
                                    </div>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                        <button type="submit" name="take_order" class="btn-take" style="width: 100%; padding: 12px;">
                                            <i class="fas fa-hand-holding"></i> Взять заказ
                                        </button>
                                    </form>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-orders">
                                <i class="fas fa-box-open"></i>
                                <h3>Нет доступных заказов</h3>
                                <p>Попробуйте зайти позже</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-clock"></i>
                            <h3>Вы заняты</h3>
                            <p>Завершите текущие заказы чтобы взять новые</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Вкладка: Чат с диспетчером -->
                <div id="tab-chat" class="tab-content">
                    <h2><i class="fas fa-comments"></i> Чат с диспетчером</h2>
                    
                    <div class="chat-container">
                        <div class="chat-messages" id="chatMessages">
                            <!-- Сообщения будут загружаться через AJAX -->
                        </div>
                        
                        <div class="chat-input">
                            <input type="text" id="chatMessageInput" placeholder="Введите сообщение..." onkeypress="if(event.key === 'Enter') sendMessage()">
                            <button onclick="sendMessage()">
                                <i class="fas fa-paper-plane"></i> Отправить
                            </button>
                        </div>
                    </div>
                </div>
            </main>
            
            <nav class="driver-sidebar">
                <div class="driver-nav">
                    <ul>
                        <li><a href="#" onclick="switchTab('active')" id="tab-active-link" class="active">
                            <i class="fas fa-tasks"></i> Мои заказы
                        </a></li>
                        <li><a href="#" onclick="switchTab('available')" id="tab-available-link">
                            <i class="fas fa-box-open"></i> Доступные
                        </a></li>
                        <li><a href="#" onclick="switchTab('chat')" id="tab-chat-link">
                            <i class="fas fa-comments"></i> Чат
                        </a></li>
                    </ul>
                </div>
            </nav>
        </div>
        
        <footer>
            <p>&copy; 2026 Green Mile. Панель водителя</p>
        </footer>
    </div>
    
    <!-- Модальное окно для фото -->
    <div id="photo-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('photo-modal')">&times;</span>
            <h3><i class="fas fa-camera"></i> Загрузить фото</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" id="photo-order-id" name="order_id">
                <input type="file" name="photo" accept="image/*" required style="margin: 20px 0; width: 100%;">
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="upload_photo" class="btn btn-primary" style="flex: 1;">Загрузить</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('photo-modal')" style="flex: 1;">Отмена</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Модальное окно для проблемы -->
    <div id="problem-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('problem-modal')">&times;</span>
            <h3><i class="fas fa-exclamation-triangle"></i> Опишите проблему</h3>
            <form method="POST">
                <input type="hidden" id="problem-order-id" name="order_id">
                <textarea name="problem_description" placeholder="Опишите проблему..." required></textarea>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="problem" class="btn btn-danger" style="flex: 1;">Отправить</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('problem-modal')" style="flex: 1;">Отмена</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let currentTab = 'active';
        let chatInterval;
        
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.remove('active');
            });
            
            document.querySelectorAll('.driver-nav a').forEach(el => {
                el.classList.remove('active');
            });
            
            document.getElementById(`tab-${tab}`).classList.add('active');
            document.getElementById(`tab-${tab}-link`).classList.add('active');
            
            currentTab = tab;
            
            if (tab === 'chat') {
                loadMessages();
                if (window.chatInterval) clearInterval(window.chatInterval);
                window.chatInterval = setInterval(loadMessages, 3000);
            } else {
                if (window.chatInterval) clearInterval(window.chatInterval);
            }
        }
        
        function toggleStatusMenu() {
            const menu = document.getElementById('statusMenu');
            menu.classList.toggle('show');
        }
        
        function setStatus(status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="set_status" value="1">
                <input type="hidden" name="new_status" value="${status}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Закрыть меню статуса при клике вне
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('statusMenu');
            const indicator = document.getElementById('statusIndicator');
            
            if (!indicator.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.remove('show');
            }
        });
        
        function loadMessages() {
            fetch('api/chat_api.php?action=get_messages&user_id=<?php echo $user_id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                    }
                });
        }
        
        function displayMessages(messages) {
            const container = document.getElementById('chatMessages');
            if (!container) return;
            
            container.innerHTML = '';
            
            messages.reverse().forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.user_id == <?php echo $user_id; ?> ? 'sent' : 'received'}`;
                
                const time = new Date(msg.created_at).toLocaleTimeString('ru-RU', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const userName = msg.user_id == <?php echo $user_id; ?> ? 'Вы' : (msg.First_Name + ' ' + msg.Last_Name);
                
                messageDiv.innerHTML = `
                    <div class="message-content">${msg.message}</div>
                    <div class="message-info">
                        <span>${userName}</span>
                        <span>${time}</span>
                    </div>
                `;
                
                container.appendChild(messageDiv);
            });
            
            container.scrollTop = container.scrollHeight;
        }
        
        function sendMessage() {
            const input = document.getElementById('chatMessageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            fetch('api/chat_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'send_message',
                    user_id: <?php echo $user_id; ?>,
                    message: message,
                    recipient_id: 0
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages();
                }
            });
        }
        
        function showPhotoForm(orderId) {
            document.getElementById('photo-order-id').value = orderId;
            document.getElementById('photo-modal').style.display = 'block';
        }
        
        function showProblemForm(orderId) {
            document.getElementById('problem-order-id').value = orderId;
            document.getElementById('problem-modal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        setTimeout(() => {
            document.getElementById('successNotification')?.classList.remove('show');
            document.getElementById('errorNotification')?.classList.remove('show');
        }, 3000);
    </script>
</body>
</html>