<?php
// dispatcher.php
include_once 'api/config.php';

// Проверяем и добавляем колонку driver_status если её нет
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'driver_status'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN driver_status ENUM('free', 'busy', 'offline') DEFAULT 'free' AFTER role");
    $conn->query("UPDATE users SET driver_status = 'free' WHERE role = 3");
}

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user = $user_query->fetch_assoc();

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Подтверждение заказа
    if (isset($_POST['confirm_order'])) {
        $order_id = intval($_POST['order_id']);
        $conn->query("UPDATE orders SET status = 2 WHERE id_order = $order_id");
        header("Location: dispatcher.php?tab=orders&success=order_confirmed");
        exit;
    }
    
    // Удаление заказа
    if (isset($_POST['delete_order'])) {
        $order_id = intval($_POST['order_id']);
        // Сначала удаляем связанные записи
        $conn->query("DELETE FROM complete_orders WHERE id_order = $order_id");
        $conn->query("DELETE FROM orders WHERE id_order = $order_id");
        header("Location: dispatcher.php?tab=orders&success=order_deleted");
        exit;
    }
    
    // Назначение водителя
    if (isset($_POST['assign_driver'])) {
        $order_id = intval($_POST['order_id']);
        $driver_id = intval($_POST['driver_id']);
        
        // Проверяем статус водителя
        $driver_check = $conn->query("SELECT driver_status FROM users WHERE id_user = $driver_id")->fetch_assoc();
        
        if ($driver_check && $driver_check['driver_status'] == 'free') {
            // Находим транспорт водителя
            $transport = $conn->query("SELECT id_transport FROM transport WHERE id_user = $driver_id")->fetch_assoc();
            
            if ($transport) {
                // Назначаем заказ
                $conn->query("INSERT INTO complete_orders (id_order, id_transport, comments) 
                             VALUES ($order_id, {$transport['id_transport']}, 'Назначено диспетчером')");
                $conn->query("UPDATE orders SET status = 2 WHERE id_order = $order_id");
                $conn->query("UPDATE users SET driver_status = 'busy' WHERE id_user = $driver_id");
                
                header("Location: dispatcher.php?tab=orders&success=driver_assigned");
                exit;
            } else {
                header("Location: dispatcher.php?tab=orders&error=no_transport&driver=$driver_id");
                exit;
            }
        } else {
            header("Location: dispatcher.php?tab=orders&error=driver_busy&driver=$driver_id");
            exit;
        }
    }
    
    // Редактирование заказа
    if (isset($_POST['edit_order'])) {
        $order_id = intval($_POST['order_id']);
        $status = intval($_POST['status']);
        $material = intval($_POST['material']);
        $volume = $conn->real_escape_string($_POST['volume']);
        $address = $conn->real_escape_string($_POST['address']);
        $datetime = $conn->real_escape_string($_POST['datetime']);
        $comments = $conn->real_escape_string($_POST['comments']);
        
        $sql = "UPDATE orders SET 
                status = $status,
                id_materials = $material,
                volume = '$volume',
                addres = '$address',
                data_Time = '$datetime',
                comments = '$comments'
                WHERE id_order = $order_id";
        
        if ($conn->query($sql)) {
            header("Location: dispatcher.php?tab=orders&success=order_updated");
        } else {
            header("Location: dispatcher.php?tab=orders&error=update_failed");
        }
        exit;
    }
}

// Определяем текущую вкладку
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';

// Создаем таблицу для чата если её нет
$conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    recipient_id INT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель диспетчера - Green Mile</title>
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
            background: var(--gradient-mix);
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

        .btn-success {
            background: #4CAF50;
            color: white;
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

        /* ===== ОСНОВНОЙ КОНТЕЙНЕР ===== */
        .dispatcher-container {
            display: flex;
            flex-direction: row;
            gap: 20px;
            min-height: calc(100vh - 150px);
        }

        /* ===== БОКОВАЯ ПАНЕЛЬ СЛЕВА ===== */
        .dispatcher-sidebar {
            width: 280px;
            background: var(--gradient-blue);
            color: white;
            border-radius: 15px;
            padding: 20px 0;
            flex-shrink: 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .dispatcher-nav ul {
            list-style: none;
            padding: 0;
        }

        .dispatcher-nav li {
            margin: 0;
        }

        .dispatcher-nav a {
            display: block;
            padding: 15px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }

        .dispatcher-nav a:hover {
            background: rgba(76, 175, 80, 0.3);
            border-left-color: var(--secondary-green);
            color: white;
        }

        .dispatcher-nav a.active {
            background: rgba(76, 175, 80, 0.2);
            border-left-color: var(--secondary-green);
            color: var(--light-green);
            font-weight: bold;
        }

        .dispatcher-nav i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* ===== КОНТЕНТ СПРАВА ===== */
        .dispatcher-content {
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
            border-left: 4px solid var(--secondary-green);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-green);
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }

        .filters select,
        .filters input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 150px;
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
            border-left: 4px solid var(--secondary-green);
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            color: var(--primary-green);
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
        .status-4 { background: #d4edda; color: #155724; }
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
            color: var(--secondary-blue);
            width: 20px;
        }

        .order-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .order-actions button,
        .order-actions form button {
            flex: 1;
            min-width: 60px;
            padding: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .order-actions button:hover,
        .order-actions form button:hover {
            opacity: 0.8;
        }

        .btn-view { background: #9C27B0; color: white; }
        .btn-edit { background: #2196F3; color: white; }
        .btn-assign { background: #FF9800; color: white; }
        .btn-delete { background: #f44336; color: white; }
        .btn-confirm { background: #4CAF50; color: white; }

        /* Стили для чата */
        .chat-container {
            height: 500px;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
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
            background: var(--secondary-green);
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

        .chat-input select,
        .chat-input input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .chat-input select {
            width: 200px;
        }

        .chat-input input {
            flex: 1;
        }

        .chat-input input:focus,
        .chat-input select:focus {
            border-color: var(--secondary-green);
            outline: none;
        }

        .chat-input button {
            padding: 12px 24px;
            background: var(--secondary-green);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .chat-input button:hover {
            background: var(--primary-green);
        }

        /* Стили для списка водителей */
        .drivers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .driver-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .driver-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .driver-card.free {
            border-left-color: #4CAF50;
            opacity: 1;
        }

        .driver-card.busy {
            border-left-color: #FF9800;
            opacity: 0.8;
        }

        .driver-card.offline {
            border-left-color: #9E9E9E;
            opacity: 0.6;
            background: #f5f5f5;
        }

        .driver-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            color: white;
            font-size: 0.85rem;
        }

        .driver-status.free {
            background: #4CAF50;
        }

        .driver-status.busy {
            background: #FF9800;
        }

        .driver-status.offline {
            background: #9E9E9E;
        }

        .btn-assign:disabled,
        .btn-assign.disabled {
            background: #cccccc;
            cursor: not-allowed;
            opacity: 0.5;
            pointer-events: none;
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
            max-width: 600px;
            border-radius: 10px;
            max-height: 80vh;
            overflow-y: auto;
            border-top: 5px solid var(--secondary-green);
        }

        .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #333;
        }

        .edit-form input,
        .edit-form select,
        .edit-form textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .edit-form label {
            font-weight: 600;
            display: block;
            margin-top: 15px;
            color: var(--primary-green);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: #4CAF50;
            color: white;
            border-radius: 5px;
            display: none;
            z-index: 1001;
            animation: slideIn 0.3s;
        }

        .notification.error {
            background: #f44336;
        }

        .notification.warning {
            background: #FF9800;
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

        .notification.show {
            display: block;
        }

        footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            padding: 20px;
            border-top: 1px solid #ddd;
        }

        @media (max-width: 768px) {
            .dispatcher-container {
                flex-direction: column;
            }
            
            .dispatcher-sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1><i class="fas fa-tasks"></i> Панель диспетчера</h1>
            <div class="user-info">
                <span>
                    <i class="fas fa-user"></i> 
                    <?php echo $user['First_Name'] . ' ' . $user['Last_Name']; ?> 
                    (Диспетчер)
                </span>
                <a href="/124/api/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </header>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="notification show success" id="notification">
                <?php 
                $messages = [
                    'order_confirmed' => '✓ Заказ подтвержден!',
                    'order_deleted' => '✓ Заказ удален!',
                    'driver_assigned' => '✓ Водитель назначен!',
                    'order_updated' => '✓ Заказ обновлен!'
                ];
                echo $messages[$_GET['success']] ?? '✓ Действие выполнено';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="notification show error" id="notification">
                <?php 
                $errors = [
                    'no_transport' => '✗ У водителя нет транспорта!',
                    'driver_busy' => '✗ Водитель занят или не в сети!',
                    'update_failed' => '✗ Ошибка обновления заказа!'
                ];
                $error_msg = $errors[$_GET['error']] ?? '✗ Ошибка';
                if (isset($_GET['driver'])) {
                    $error_msg .= " (ID: " . $_GET['driver'] . ")";
                }
                echo $error_msg;
                ?>
            </div>
        <?php endif; ?>
        
        <div class="dispatcher-container">
            <!-- БОКОВАЯ ПАНЕЛЬ СЛЕВА -->
            <nav class="dispatcher-sidebar">
                <div class="dispatcher-nav">
                    <ul>
                        <li><a href="?tab=orders" class="<?php echo $current_tab == 'orders' ? 'active' : ''; ?>">
                            <i class="fas fa-tasks"></i> Управление заказами
                        </a></li>
                        <li><a href="?tab=routes" class="<?php echo $current_tab == 'routes' ? 'active' : ''; ?>">
                            <i class="fas fa-route"></i> Планирование маршрутов
                        </a></li>
                        <li><a href="?tab=drivers" class="<?php echo $current_tab == 'drivers' ? 'active' : ''; ?>">
                            <i class="fas fa-truck"></i> Назначение водителям
                        </a></li>
                        <li><a href="?tab=map" class="<?php echo $current_tab == 'map' ? 'active' : ''; ?>">
                            <i class="fas fa-map-marked-alt"></i> Карта и мониторинг
                        </a></li>
                        <li><a href="?tab=chat" class="<?php echo $current_tab == 'chat' ? 'active' : ''; ?>">
                            <i class="fas fa-comments"></i> Чат с водителями
                        </a></li>
                        <li><a href="?tab=reports" class="<?php echo $current_tab == 'reports' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i> Отчеты
                        </a></li>
                    </ul>
                </div>
            </nav>
            
            <!-- КОНТЕНТ СПРАВА -->
            <main class="dispatcher-content">
                <!-- Управление заказами -->
                <div id="tab-orders" class="tab-content <?php echo $current_tab == 'orders' ? 'active' : ''; ?>">
                    <h2><i class="fas fa-tasks"></i> Управление заказами</h2>
                    
                    <div class="filters">
                        <select id="status-filter" onchange="filterOrders()">
                            <option value="all">Все статусы</option>
                            <option value="1">Новые</option>
                            <option value="2">Подтвержденные</option>
                            <option value="3">В пути</option>
                            <option value="4">На месте</option>
                            <option value="5">Завершенные</option>
                        </select>
                        <input type="date" id="date-filter" onchange="filterOrders()">
                        <input type="text" id="search-filter" placeholder="Поиск по адресу..." onkeyup="filterOrders()">
                    </div>
                    
                    <div class="stats-grid">
                        <?php
                        $new_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 1")->fetch_assoc()['total'];
                        $active_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status IN (2,3,4)")->fetch_assoc()['total'];
                        $completed_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 5")->fetch_assoc()['total'];
                        ?>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #FF9800;"><?php echo $new_orders; ?></div>
                            <div class="stat-label">Новых заказов</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #2196F3;"><?php echo $active_orders; ?></div>
                            <div class="stat-label">В работе</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #4CAF50;"><?php echo $completed_orders; ?></div>
                            <div class="stat-label">Завершено</div>
                        </div>
                    </div>
                    
                    <div class="orders-grid" id="orders-container">
                        <?php
                        $orders = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                                                FROM orders o 
                                                LEFT JOIN materials m ON o.id_materials = m.id_material 
                                                ORDER BY o.data_Time DESC");
                        
                        while($order = $orders->fetch_assoc()):
                            $status_text = [
                                1 => 'Новый', 
                                2 => 'Подтвержден', 
                                3 => 'В пути', 
                                4 => 'На месте', 
                                5 => 'Завершен'
                            ][$order['status']];
                            $status_class = [
                                1 => 'status-1', 
                                2 => 'status-2', 
                                3 => 'status-3', 
                                4 => 'status-4', 
                                5 => 'status-5'
                            ][$order['status']];
                            
                            // Получаем назначенного водителя
                            $assigned_driver = $conn->query("SELECT u.First_Name, u.Last_Name 
                                                            FROM complete_orders co 
                                                            JOIN transport t ON co.id_transport = t.id_transport 
                                                            JOIN users u ON t.id_user = u.id_user 
                                                            WHERE co.id_order = {$order['id_order']}")->fetch_assoc();
                        ?>
                        <div class="order-card" data-order-id="<?php echo $order['id_order']; ?>" data-status="<?php echo $order['status']; ?>">
                            <div class="order-header">
                                <h3><i class="fas fa-box"></i> Заказ #<?php echo $order['id_order']; ?></h3>
                                <span class="order-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                            
                            <div class="order-info">
                                <p><i class="fas fa-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></p>
                                <p><i class="fas fa-box"></i> <?php echo $order['material_name']; ?> (<?php echo $order['volume']; ?> кг)</p>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo $order['addres']; ?></p>
                                <?php if ($assigned_driver): ?>
                                    <p><i class="fas fa-user"></i> Водитель: <?php echo $assigned_driver['First_Name'] . ' ' . $assigned_driver['Last_Name']; ?></p>
                                <?php endif; ?>
                                <?php if ($order['comments']): ?>
                                    <p><i class="fas fa-comment"></i> <?php echo $order['comments']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="order-actions">
                                <button class="btn-view" onclick="viewOrder(<?php echo $order['id_order']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <button class="btn-edit" onclick="editOrder(<?php echo $order['id_order']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <?php if ($order['status'] == 1): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Подтвердить заказ?')">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                        <button type="submit" name="confirm_order" class="btn-confirm">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <button class="btn-assign" onclick="showAssignDriverModal(<?php echo $order['id_order']; ?>)">
                                    <i class="fas fa-truck"></i>
                                </button>
                                
                                <?php if ($order['status'] != 5): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить заказ?')">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id_order']; ?>">
                                        <button type="submit" name="delete_order" class="btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Планирование маршрутов -->
                <div id="tab-routes" class="tab-content <?php echo $current_tab == 'routes' ? 'active' : ''; ?>">
                    <h2><i class="fas fa-route"></i> Планирование маршрутов</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Активных маршрутов</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Завершено сегодня</div>
                        </div>
                    </div>
                    <p style="text-align: center; color: #666; padding: 50px;">Функция планирования маршрутов в разработке</p>
                </div>

                <!-- Назначение водителям - ИСПРАВЛЕНО (без дублирования) -->
                <div id="tab-drivers" class="tab-content <?php echo $current_tab == 'drivers' ? 'active' : ''; ?>">
                    <h2><i class="fas fa-truck"></i> Назначение водителям</h2>
                    
                    <div class="stats-grid">
                        <?php
                        $free_drivers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role=3 AND driver_status='free'")->fetch_assoc()['total'];
                        $busy_drivers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role=3 AND driver_status='busy'")->fetch_assoc()['total'];
                        $offline_drivers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role=3 AND driver_status='offline'")->fetch_assoc()['total'];
                        $total_drivers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role=3")->fetch_assoc()['total'];
                        ?>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #4CAF50;"><?php echo $free_drivers; ?></div>
                            <div class="stat-label">Свободные</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #FF9800;"><?php echo $busy_drivers; ?></div>
                            <div class="stat-label">Занятые</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #9E9E9E;"><?php echo $offline_drivers; ?></div>
                            <div class="stat-label">Не в сети</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: var(--primary-blue);"><?php echo $total_drivers; ?></div>
                            <div class="stat-label">Всего водителей</div>
                        </div>
                    </div>
                    
                    <div class="drivers-grid">
                        <?php
                        // Уникальные водители с их транспортом (исправлено)
                        $drivers = $conn->query("SELECT u.*, 
                                                        GROUP_CONCAT(CONCAT(t.Gos_N, ' (', t.Model_transport, ')') SEPARATOR ', ') as transport_list,
                                                        COUNT(t.id_transport) as transport_count
                                                FROM users u 
                                                LEFT JOIN transport t ON u.id_user = t.id_user 
                                                WHERE u.role = 3 
                                                GROUP BY u.id_user
                                                ORDER BY FIELD(u.driver_status, 'free', 'busy', 'offline'), u.Last_Name");
                        
                        while($driver = $drivers->fetch_assoc()):
                            $status_colors = [
                                'free' => ['bg' => '#4CAF50', 'text' => 'Свободен', 'icon' => 'fa-check-circle'],
                                'busy' => ['bg' => '#FF9800', 'text' => 'Занят', 'icon' => 'fa-clock'],
                                'offline' => ['bg' => '#9E9E9E', 'text' => 'Не в сети', 'icon' => 'fa-power-off']
                            ];
                            $status = $status_colors[$driver['driver_status'] ?? 'offline'];
                            
                            $active_orders = $conn->query("SELECT COUNT(*) as total FROM orders o 
                                                          JOIN complete_orders co ON o.id_order = co.id_order 
                                                          JOIN transport t ON co.id_transport = t.id_transport 
                                                          WHERE t.id_user = {$driver['id_user']} AND o.status IN (2,3,4)")->fetch_assoc()['total'];
                        ?>
                        <div class="driver-card <?php echo $driver['driver_status']; ?>" data-status="<?php echo $driver['driver_status']; ?>">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="margin: 0;"><?php echo $driver['First_Name'] . ' ' . $driver['Last_Name']; ?></h3>
                                <span class="driver-status <?php echo $driver['driver_status']; ?>">
                                    <i class="fas <?php echo $status['icon']; ?>"></i>
                                    <?php echo $status['text']; ?>
                                </span>
                            </div>
                            
                            <div style="margin: 10px 0; color: #666;">
                                <p><i class="fas fa-truck"></i> 
                                    <?php 
                                    if ($driver['transport_count'] > 0) {
                                        echo $driver['transport_list'];
                                    } else {
                                        echo 'Нет транспорта';
                                    }
                                    ?>
                                </p>
                                <p><i class="fas fa-clipboard-list"></i> Активных заказов: <?php echo $active_orders; ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Карта и мониторинг -->
                <div id="tab-map" class="tab-content <?php echo $current_tab == 'map' ? 'active' : ''; ?>">
                    <h2><i class="fas fa-map-marked-alt"></i> Карта и мониторинг</h2>
                    <div style="background: #f8f9fa; height: 400px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <p style="color: #666;">Карта будет доступна в следующей версии</p>
                    </div>
                </div>

                <!-- Чат с водителями -->
                <div id="tab-chat" class="tab-content <?php echo $current_tab == 'chat' ? 'active' : ''; ?>">
                    <h2><i class="fas fa-comments"></i> Чат с водителями</h2>
                    
                    <div class="chat-container">
                        <div class="chat-messages" id="chatMessages"></div>
                        
                        <div class="chat-input">
                            <select id="chatRecipient">
                                <option value="0">Всем водителям</option>
                                <?php
                                $drivers = $conn->query("SELECT id_user, First_Name, Last_Name, driver_status FROM users WHERE role = 3 GROUP BY id_user ORDER BY driver_status = 'free' DESC, Last_Name");
                                while($driver = $drivers->fetch_assoc()):
                                    $status_icon = $driver['driver_status'] == 'free' ? '✓' : ($driver['driver_status'] == 'busy' ? '⏳' : '○');
                                ?>
                                <option value="<?php echo $driver['id_user']; ?>" 
                                        style="color: <?php echo $driver['driver_status'] == 'free' ? '#4CAF50' : ($driver['driver_status'] == 'busy' ? '#FF9800' : '#9E9E9E'); ?>;">
                                    <?php echo $status_icon . ' ' . $driver['First_Name'] . ' ' . $driver['Last_Name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="text" id="chatMessageInput" placeholder="Введите сообщение..." onkeypress="if(event.key === 'Enter') sendMessage()">
                            <button onclick="sendMessage()">
                                <i class="fas fa-paper-plane"></i> Отправить
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Отчеты -->
                <div id="tab-reports" class="tab-content <?php echo $current_tab == 'reports' ? 'active' : ''; ?>">
                    <h2><i class="fas fa-chart-bar"></i> Отчеты</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Дневной отчет</h3>
                            <button class="btn btn-primary" style="margin-top: 15px;" onclick="alert('Отчет будет доступен в следующей версии')">Сформировать</button>
                        </div>
                        <div class="stat-card">
                            <h3>Недельный отчет</h3>
                            <button class="btn btn-primary" style="margin-top: 15px;" onclick="alert('Отчет будет доступен в следующей версии')">Сформировать</button>
                        </div>
                        <div class="stat-card">
                            <h3>Месячный отчет</h3>
                            <button class="btn btn-primary" style="margin-top: 15px;" onclick="alert('Отчет будет доступен в следующей версии')">Сформировать</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        
        <footer>
            <p>&copy; 2026 Green Mile. Панель диспетчера</p>
        </footer>
    </div>
    
    <!-- Модальное окно для редактирования заказа -->
    <div id="edit-order-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2><i class="fas fa-edit"></i> Редактирование заказа</h2>
            <form method="POST" class="edit-form">
                <input type="hidden" name="order_id" id="edit-order-id">
                <input type="hidden" name="edit_order" value="1">
                
                <label>Статус:</label>
                <select name="status" id="edit-status">
                    <option value="1">Новый</option>
                    <option value="2">Подтвержден</option>
                    <option value="3">В пути</option>
                    <option value="4">На месте</option>
                    <option value="5">Завершен</option>
                </select>
                
                <label>Материал:</label>
                <select name="material" id="edit-material">
                    <?php
                    $materials = $conn->query("SELECT id_material, Name_Mat FROM materials");
                    while($mat = $materials->fetch_assoc()):
                    ?>
                    <option value="<?php echo $mat['id_material']; ?>"><?php echo $mat['Name_Mat']; ?></option>
                    <?php endwhile; ?>
                </select>
                
                <label>Объем (кг):</label>
                <input type="number" name="volume" id="edit-volume" required>
                
                <label>Дата и время:</label>
                <input type="datetime-local" name="datetime" id="edit-datetime" required>
                
                <label>Адрес:</label>
                <input type="text" name="address" id="edit-address" required>
                
                <label>Комментарий:</label>
                <textarea name="comments" id="edit-comments" rows="3"></textarea>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-save"></i> Сохранить
                </button>
            </form>
        </div>
    </div>
    
    <!-- Модальное окно для назначения водителя -->
    <div id="assign-driver-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignModal()">&times;</span>
            <h2><i class="fas fa-truck"></i> Назначение водителя</h2>
            <form method="POST" class="edit-form">
                <input type="hidden" name="order_id" id="assign-order-id">
                <input type="hidden" name="assign_driver" value="1">
                
                <label>Выберите водителя:</label>
                <select name="driver_id" id="driver-select" required>
                    <option value="">-- Выберите водителя --</option>
                    <?php
                    $drivers = $conn->query("SELECT id_user, First_Name, Last_Name, driver_status 
                                            FROM users 
                                            WHERE role = 3 
                                            GROUP BY id_user
                                            ORDER BY driver_status = 'free' DESC, Last_Name");
                    while($driver = $drivers->fetch_assoc()):
                        $status_text = $driver['driver_status'] == 'free' ? '✓ Свободен' : ($driver['driver_status'] == 'busy' ? '⏳ Занят' : '○ Не в сети');
                        $color = $driver['driver_status'] == 'free' ? '#4CAF50' : ($driver['driver_status'] == 'busy' ? '#FF9800' : '#9E9E9E');
                        $disabled = $driver['driver_status'] != 'free' ? 'disabled' : '';
                    ?>
                    <option value="<?php echo $driver['id_user']; ?>" style="color: <?php echo $color; ?>;" <?php echo $disabled; ?>>
                        <?php echo $driver['First_Name'] . ' ' . $driver['Last_Name'] . ' (' . $status_text . ')'; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                
                <p style="color: #FF9800; margin-top: 10px; <?php echo $free_drivers > 0 ? 'display: none;' : ''; ?>">
                    <i class="fas fa-info-circle"></i> Нет свободных водителей
                </p>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;" <?php echo $free_drivers == 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-check"></i> Назначить
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification show ${type}`;
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
        
        function filterOrders() {
            const status = document.getElementById('status-filter').value;
            const date = document.getElementById('date-filter').value;
            const search = document.getElementById('search-filter').value.toLowerCase();
            
            document.querySelectorAll('.order-card').forEach(card => {
                let show = true;
                
                if (status !== 'all') {
                    const cardStatus = card.dataset.status;
                    if (cardStatus != status) {
                        show = false;
                    }
                }
                
                if (show && date) {
                    const cardDate = card.querySelector('.order-info p:first-child').textContent.split(' ')[1];
                    if (!cardDate.includes(date)) {
                        show = false;
                    }
                }
                
                if (show && search) {
                    const text = card.textContent.toLowerCase();
                    if (!text.includes(search)) {
                        show = false;
                    }
                }
                
                card.style.display = show ? 'block' : 'none';
            });
        }
        
        function viewOrder(orderId) {
            alert('Просмотр заказа #' + orderId + '\nДетальная информация будет доступна в следующей версии');
        }
        
        function editOrder(orderId) {
            fetch(`api/get_order.php?id=${orderId}`)
                .then(response => response.json())
                .then(order => {
                    if (order.success) {
                        document.getElementById('edit-order-id').value = order.data.id_order;
                        document.getElementById('edit-status').value = order.data.status;
                        document.getElementById('edit-material').value = order.data.id_materials;
                        document.getElementById('edit-volume').value = order.data.volume;
                        document.getElementById('edit-address').value = order.data.addres;
                        document.getElementById('edit-comments').value = order.data.comments || '';
                        
                        // Форматируем дату
                        if (order.data.data_Time) {
                            const date = new Date(order.data.data_Time);
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');
                            const hours = String(date.getHours()).padStart(2, '0');
                            const minutes = String(date.getMinutes()).padStart(2, '0');
                            document.getElementById('edit-datetime').value = `${year}-${month}-${day}T${hours}:${minutes}`;
                        }
                        
                        document.getElementById('edit-order-modal').style.display = 'block';
                    } else {
                        alert('Ошибка загрузки заказа');
                    }
                });
        }
        
        function showAssignDriverModal(orderId) {
            document.getElementById('assign-order-id').value = orderId;
            document.getElementById('assign-driver-modal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('edit-order-modal').style.display = 'none';
        }
        
        function closeAssignModal() {
            document.getElementById('assign-driver-modal').style.display = 'none';
        }
        
        // Функции для чата
        let chatInterval;
        
        function loadMessages() {
            fetch('api/chat_api.php?action=get_messages&user_id=<?php echo $user_id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages);
                    }
                })
                .catch(error => console.error('Ошибка загрузки сообщений:', error));
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
                
                const roleText = msg.role == 2 ? 'Диспетчер' : 'Водитель';
                
                messageDiv.innerHTML = `
                    <div class="message-content">${msg.message}</div>
                    <div class="message-info">
                        <span>${msg.First_Name} ${msg.Last_Name} (${roleText})</span>
                        <span>${time}</span>
                    </div>
                `;
                
                container.appendChild(messageDiv);
            });
            
            container.scrollTop = container.scrollHeight;
        }
        
        function sendMessage() {
            const input = document.getElementById('chatMessageInput');
            const recipient = document.getElementById('chatRecipient');
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
                    recipient_id: recipient.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages();
                }
            })
            .catch(error => console.error('Ошибка отправки:', error));
        }
        
        // Автоматическая загрузка сообщений при открытии чата
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes('tab=chat')) {
                loadMessages();
                chatInterval = setInterval(loadMessages, 3000);
            }
        });
        
        // Обработка переключения вкладок
        document.querySelectorAll('.dispatcher-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('?')) {
                    if (this.getAttribute('href').includes('tab=chat')) {
                        setTimeout(() => {
                            loadMessages();
                            if (chatInterval) clearInterval(chatInterval);
                            chatInterval = setInterval(loadMessages, 3000);
                        }, 100);
                    } else {
                        if (chatInterval) clearInterval(chatInterval);
                    }
                }
            });
        });
        
        window.onclick = function(event) {
            const editModal = document.getElementById('edit-order-modal');
            const assignModal = document.getElementById('assign-driver-modal');
            
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            if (event.target === assignModal) {
                assignModal.style.display = 'none';
            }
        }
        
        setTimeout(() => {
            document.getElementById('notification')?.classList.remove('show');
        }, 3000);
    </script>
</body>
</html>