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

$materials_list = $conn->query("SELECT * FROM materials ORDER BY Name_Mat");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Загрузка фото для заказа
    if (isset($_POST['upload_photos'])) {
        $order_id = intval($_POST['order_id']);
        $target_dir = "uploads/";
        
        // Создаем папку если её нет
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $photos = [];
        // Обрабатываем до 3 фото
        for ($i = 1; $i <= 3; $i++) {
            $field_name = "photo" . ($i == 1 ? '' : $i);
            if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] == 0) {
                $file = $_FILES[$field_name];
                $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
                $filename = time() . "_" . $i . "_" . $order_id . "." . $extension;
                $target_file = $target_dir . $filename;
                
                if (move_uploaded_file($file["tmp_name"], $target_file)) {
                    $photos[$i] = $filename;
                }
            }
        }
        
        // Проверяем есть ли уже запись в complete_orders
        $check = $conn->query("SELECT id_C_orders FROM complete_orders WHERE id_order = $order_id");
        
        if ($check->num_rows > 0) {
            // Обновляем существующую запись
            $sql = "UPDATE complete_orders SET ";
            $updates = [];
            if (isset($photos[1])) $updates[] = "photo = '" . $photos[1] . "'";
            if (isset($photos[2])) $updates[] = "photo1 = '" . $photos[2] . "'";
            if (isset($photos[3])) $updates[] = "photo2 = '" . $photos[3] . "'";
            
            if (!empty($updates)) {
                $sql .= implode(", ", $updates);
                $sql .= " WHERE id_order = $order_id";
                $conn->query($sql);
            }
        } else {
            // Создаем новую запись
            $photo1 = $photos[1] ?? 'NULL';
            $photo2 = $photos[2] ?? 'NULL';
            $photo3 = $photos[3] ?? 'NULL';
            
            $sql = "INSERT INTO complete_orders (id_order, photo, photo1, photo2, comments) 
                    VALUES ($order_id, " . ($photo1 != 'NULL' ? "'$photo1'" : "NULL") . ", 
                            " . ($photo2 != 'NULL' ? "'$photo2'" : "NULL") . ", 
                            " . ($photo3 != 'NULL' ? "'$photo3'" : "NULL") . ", '')";
            $conn->query($sql);
        }
        
        header("Location: dispatcher.php?tab=orders&success=photos_uploaded");
        exit;
    }
    
    if (isset($_POST['confirm_order'])) {
        $order_id = intval($_POST['order_id']);
        $conn->query("UPDATE orders SET status = 2 WHERE id_order = $order_id");
        header("Location: dispatcher.php?tab=orders&success=order_confirmed");
        exit;
    }
    
    if (isset($_POST['delete_order'])) {
        $order_id = intval($_POST['order_id']);
        $conn->query("DELETE FROM complete_orders WHERE id_order = $order_id");
        $conn->query("DELETE FROM orders WHERE id_order = $order_id");
        header("Location: dispatcher.php?tab=orders&success=order_deleted");
        exit;
    }
    
    if (isset($_POST['assign_driver'])) {
        $order_id = intval($_POST['order_id']);
        $driver_id = intval($_POST['driver_id']);
        
        $driver_check = $conn->query("SELECT driver_status FROM users WHERE id_user = $driver_id")->fetch_assoc();
        
        if ($driver_check && $driver_check['driver_status'] == 'free') {
            $transport = $conn->query("SELECT id_transport FROM transport WHERE id_user = $driver_id")->fetch_assoc();
            
            if ($transport) {
                $conn->query("DELETE FROM complete_orders WHERE id_order = $order_id");
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
    
    if (isset($_POST['save_edited_order'])) {
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
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';
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
            background: linear-gradient(135deg, #2E7D32, #1976D2);
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
            background: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background: #2E7D32;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: black;
        }

        .dispatcher-container {
            display: flex;
            flex-direction: row;
            gap: 20px;
            min-height: calc(100vh - 150px);
        }

        .dispatcher-sidebar {
            width: 250px;
            background: linear-gradient(135deg, #0D47A1, #1976D2);
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
            border-left-color: #4CAF50;
            color: white;
        }

        .dispatcher-nav a.active {
            background: rgba(76, 175, 80, 0.2);
            border-left-color: #4CAF50;
            color: white;
            font-weight: bold;
        }

        .dispatcher-nav i {
            margin-right: 10px;
            width: 20px;
        }

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
            border-left: 4px solid #4CAF50;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2E7D32;
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
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .order-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
            border-left: 4px solid #4CAF50;
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
            color: #2E7D32;
            font-size: 1.2rem;
        }

        .order-status {
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
            color: #1976D2;
            width: 20px;
        }

        .driver-badge {
            background: #FF9800;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            display: inline-block;
            margin-top: 5px;
        }

        .photo-badge {
            background: #9C27B0;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            display: inline-block;
            margin-top: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .photo-badge:hover {
            background: #7B1FA2;
            transform: scale(1.05);
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
            min-width: 40px;
            padding: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-view { background: #9C27B0; color: white; }
        .btn-edit { background: #2196F3; color: white; }
        .btn-assign-driver { background: #FF9800; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-confirm { background: #28a745; color: white; }
        .btn-photo { background: #9C27B0; color: white; }

        /* Стили для модального окна просмотра фото */
        .modal-photo {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            animation: fadeIn 0.3s;
        }

        .modal-photo-content {
            position: relative;
            margin: 30px auto;
            padding: 20px;
            width: 90%;
            max-width: 1200px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-photo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4CAF50;
        }

        .modal-photo-header h2 {
            color: #2E7D32;
            font-size: 1.5rem;
        }

        .modal-photo-header .close {
            font-size: 30px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }

        .modal-photo-header .close:hover {
            color: #f44336;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .photo-item {
            text-align: center;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 300px;
            display: flex;
            flex-direction: column;
        }

        .photo-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 3px solid #4CAF50;
            transition: transform 0.3s;
        }

        .photo-item img:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .photo-item .empty-photo {
            width: 100%;
            height: 200px;
            background: #e0e0e0;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            border: 3px dashed #4CAF50;
        }

        .photo-item .empty-photo i {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .photo-item p {
            margin-top: 10px;
            color: #666;
            font-weight: 500;
        }

        .photo-upload-form {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-top: 2px solid #4CAF50;
        }

        .photo-upload-form h3 {
            color: #2E7D32;
            margin-bottom: 15px;
        }

        .photo-inputs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 15px;
        }

        .photo-input-item {
            text-align: center;
        }

        .photo-input-item label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2E7D32;
        }

        .photo-input-item input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .fullscreen-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 3000;
            cursor: pointer;
        }

        .fullscreen-modal img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 95%;
            max-height: 95%;
            object-fit: contain;
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
            background: white;
            border: 1px solid #e0e0e0;
        }

        .message.sent .message-content {
            background: #4CAF50;
            color: white;
            border: none;
        }

        .message-info {
            font-size: 0.75rem;
            color: #999;
            margin-top: 5px;
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

        .chat-input select { width: 200px; }
        .chat-input input { flex: 1; }
        .chat-input button {
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
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
            animation: fadeInModal 0.3s;
        }

        @keyframes fadeInModal {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            margin: 30px auto;
            padding: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, #2E7D32, #1976D2);
            color: white;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            color: white;
            font-size: 1.5rem;
            margin: 0;
        }

        .modal-header .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .modal-header .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 25px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #eee;
            text-align: right;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2E7D32;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-block {
            width: 100%;
            padding: 14px;
            font-size: 1.1rem;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            display: none;
            z-index: 2000;
            animation: slideInRight 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .notification.success { background: #4CAF50; }
        .notification.error { background: #f44336; }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .notification.show { display: block; }

        footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            padding: 20px;
            border-top: 1px solid #ddd;
        }

        @media (max-width: 768px) {
            .dispatcher-container { flex-direction: column; }
            .dispatcher-sidebar { width: 100%; }
            .form-row { grid-template-columns: 1fr; }
            .photo-grid { grid-template-columns: 1fr; }
            .photo-inputs { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1><i class="fas fa-tasks"></i> Панель диспетчера</h1>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo $user['First_Name'] . ' ' . $user['Last_Name']; ?> (Диспетчер)</span>
                <a href="Login.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </div>
        </header>
        
        <div id="notification" class="notification"></div>
        
        <div class="dispatcher-container">
            <nav class="dispatcher-sidebar">
                <div class="dispatcher-nav">
                    <ul>
                        <li><a href="?tab=orders" class="<?php echo $current_tab == 'orders' ? 'active' : ''; ?>">
                            <i class="fas fa-tasks"></i> Заказы
                        </a></li>
                        <li><a href="?tab=chat" class="<?php echo $current_tab == 'chat' ? 'active' : ''; ?>">
                            <i class="fas fa-comments"></i> Чат
                        </a></li>
                    </ul>
                </div>
            </nav>
            
            <main class="dispatcher-content">
                <div id="tab-orders" class="tab-content <?php echo $current_tab == 'orders' ? 'active' : ''; ?>">
                    <h2><i class="fas fa-tasks"></i> Заказы</h2>
                    
                    <div class="stats-grid">
                        <?php
                        $new_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 1")->fetch_assoc()['total'];
                        $active_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status IN (2,3,4)")->fetch_assoc()['total'];
                        $completed_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 5")->fetch_assoc()['total'];
                        ?>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #FF9800;"><?php echo $new_orders; ?></div>
                            <div class="stat-label">Новых</div>
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
                    
                    <div class="filters">
                        <select id="status-filter" onchange="filterOrders()">
                            <option value="all">Все статусы</option>
                            <option value="1">Новые</option>
                            <option value="2">Подтвержденные</option>
                            <option value="3">В пути</option>
                            <option value="4">На месте</option>
                            <option value="5">Завершенные</option>
                        </select>
                        <input type="text" id="search-filter" placeholder="Поиск по адресу..." onkeyup="filterOrders()">
                    </div>
                    
                    <div class="orders-grid" id="orders-container">
                        <?php
                        $orders = $conn->query("SELECT o.*, m.Name_Mat as material_name,
                                                       co.photo, co.photo1, co.photo2
                                                FROM orders o 
                                                LEFT JOIN materials m ON o.id_materials = m.id_material 
                                                LEFT JOIN complete_orders co ON o.id_order = co.id_order
                                                ORDER BY o.data_Time DESC");
                        
                        while($order = $orders->fetch_assoc()):
                            $status_text = [1 => 'Новый', 2 => 'Подтвержден', 3 => 'В пути', 4 => 'На месте', 5 => 'Завершен'][$order['status']];
                            $status_class = [1 => 'status-1', 2 => 'status-2', 3 => 'status-3', 4 => 'status-4', 5 => 'status-5'][$order['status']];
                            
                            $assigned_driver = $conn->query("SELECT u.First_Name, u.Last_Name FROM complete_orders co JOIN transport t ON co.id_transport = t.id_transport JOIN users u ON t.id_user = u.id_user  WHERE co.id_order = {$order['id_order']}")->fetch_assoc();
                            $has_photos = !empty(trim($order['photo'] ?? '')) || !empty(trim($order['photo1'] ?? '')) || !empty(trim($order['photo2'] ?? ''));
                        ?>
                        <div class="order-card" data-status="<?php echo $order['status']; ?>">
                            <div class="order-header">
                                <h3>Заказ #<?php echo $order['id_order']; ?></h3>
                                <span class="order-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                            
                            <div class="order-info">
                                <p><i class="fas fa-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></p>
                                <p><i class="fas fa-box"></i> <?php echo $order['material_name']; ?> (<?php echo $order['volume']; ?> кг)</p>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo $order['addres']; ?></p>
                                
                                <?php if ($assigned_driver): ?>
                                    <span class="driver-badge">
                                        <i class="fas fa-user"></i> <?php echo $assigned_driver['First_Name'] . ' ' . $assigned_driver['Last_Name']; ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($has_photos): ?>
                                    <span class="photo-badge" onclick="viewOrderPhotos(
                                        <?php echo $order['id_order']; ?>, 
                                        '<?php echo addslashes(trim($order['photo'] ?? '')); ?>',
                                        '<?php echo addslashes(trim($order['photo1'] ?? '')); ?>',
                                        '<?php echo addslashes(trim($order['photo2'] ?? '')); ?>'
                                    )">
                                        <i class="fas fa-images"></i> Фото
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($order['comments']): ?>
                                    <p><i class="fas fa-comment"></i> <?php echo $order['comments']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="order-actions">
                                <button class="btn-view" onclick="viewOrderPhotos(
                                    <?php echo $order['id_order']; ?>, 
                                    '<?php echo addslashes(trim($order['photo'] ?? '')); ?>',
                                    '<?php echo addslashes(trim($order['photo1'] ?? '')); ?>',
                                    '<?php echo addslashes(trim($order['photo2'] ?? '')); ?>'
                                )">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <button class="btn-photo" onclick="showPhotoUploadModal(<?php echo $order['id_order']; ?>)">
                                    <i class="fas fa-camera"></i>
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
                                
                                <button class="btn-assign-driver" onclick="showAssignDriverModal(<?php echo $order['id_order']; ?>)">
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
                <div id="tab-chat" class="tab-content <?php echo $current_tab == 'chat' ? 'active' : ''; ?>">
                    <h2><i class="fas fa-comments"></i> Чат с водителями</h2>
                    
                    <div class="chat-container">
                        <div class="chat-messages" id="chatMessages"></div>
                        
                        <div class="chat-input">
                            <select id="chatRecipient">
                                <option value="0">Всем водителям</option>
                                <?php
                                $drivers = $conn->query("SELECT id_user, First_Name, Last_Name FROM users WHERE role = 3");
                                while($driver = $drivers->fetch_assoc()):
                                ?>
                                <option value="<?php echo $driver['id_user']; ?>">
                                    <?php echo $driver['First_Name'] . ' ' . $driver['Last_Name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="text" id="chatMessageInput" placeholder="Введите сообщение..." onkeypress="if(event.key === 'Enter') sendMessage()">
                            <button onclick="sendMessage()">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        
        <footer>
            <p>&copy; 2026 Green Mile. Панель диспетчера</p>
        </footer>
    </div>
    <div id="photoViewModal" class="modal-photo">
        <div class="modal-photo-content">
            <div class="modal-photo-header">
                <h2><i class="fas fa-images"></i> Фото заказа #<span id="photo-order-id"></span></h2>
                <span class="close" onclick="closePhotoModal()">&times;</span>
            </div>
            <div id="photo-container" class="photo-grid">
            </div>
        </div>
    </div>
    <div id="photoUploadModal" class="modal-photo">
        <div class="modal-photo-content">
            <div class="modal-photo-header">
                <h2><i class="fas fa-camera"></i> Загрузка фото для заказа #<span id="upload-order-id"></span></h2>
                <span class="close" onclick="closePhotoUploadModal()">&times;</span>
            </div>
            <div class="photo-upload-form">
                <form method="POST" enctype="multipart/form-data" id="photoUploadForm">
                    <input type="hidden" name="order_id" id="form-order-id">
                    <input type="hidden" name="upload_photos" value="1">
                    
                    <div class="photo-inputs">
                        <div class="photo-input-item">
                            <label>Фото 1</label>
                            <input type="file" name="photo" accept="image/*">
                        </div>
                        <div class="photo-input-item">
                            <label>Фото 2</label>
                            <input type="file" name="photo2" accept="image/*">
                        </div>
                        <div class="photo-input-item">
                            <label>Фото 3</label>
                            <input type="file" name="photo3" accept="image/*">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-upload"></i> Загрузить фото
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div id="fullscreenModal" class="fullscreen-modal" onclick="closeFullscreen()">
        <img id="fullscreenImage" src="" alt="Полноэкранное фото">
    </div>
    <div id="assign-driver-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-truck"></i> Назначить водителя</h2>
                <span class="close" onclick="closeAssignModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="order_id" id="assign-order-id">
                    <input type="hidden" name="assign_driver" value="1">
                    
                    <div class="form-group">
                        <label>Выберите водителя:</label>
                        <select name="driver_id" id="driver-select" required>
                            <option value="">-- Выберите водителя --</option>
                            <?php
                            $drivers = $conn->query("SELECT id_user, First_Name, Last_Name, driver_status 
                                                    FROM users WHERE role = 3 AND driver_status = 'free' ORDER BY Last_Name");
                            while($driver = $drivers->fetch_assoc()):
                            ?>
                            <option value="<?php echo $driver['id_user']; ?>">
                                <?php echo $driver['First_Name'] . ' ' . $driver['Last_Name']; ?> (свободен)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-check"></i> Назначить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="edit-order-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Редактирование заказа</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="edit-order-form">
                    <input type="hidden" name="order_id" id="edit-order-id">
                    <input type="hidden" name="save_edited_order" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Статус:</label>
                            <select name="status" id="edit-status">
                                <option value="1">Новый</option>
                                <option value="2">Подтвержден</option>
                                <option value="3">В пути</option>
                                <option value="4">На месте</option>
                                <option value="5">Завершен</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Материал:</label>
                            <select name="material" id="edit-material">
                                <?php 
                                $materials_list->data_seek(0);
                                while($mat = $materials_list->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $mat['id_material']; ?>"><?php echo $mat['Name_Mat']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Объем (кг):</label>
                            <input type="number" name="volume" id="edit-volume" required min="1">
                        </div>
                        
                        <div class="form-group">
                            <label>Дата и время:</label>
                            <input type="datetime-local" name="datetime" id="edit-datetime" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Адрес:</label>
                        <input type="text" name="address" id="edit-address" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Комментарий:</label>
                        <textarea name="comments" id="edit-comments" rows="4" placeholder="Дополнительная информация..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="margin-right: 10px;">Отмена</button>
                <button type="submit" form="edit-order-form" class="btn btn-primary">
                    <i class="fas fa-save"></i> Сохранить изменения
                </button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_GET['success'])): ?>
                showNotification('<?php echo $messages[$_GET['success']] ?? "Действие выполнено"; ?>', 'success');
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                showNotification('<?php echo $errors[$_GET['error']] ?? "Ошибка"; ?>', 'error');
            <?php endif; ?>
            
            if (window.location.search.includes('tab=chat')) {
                loadMessages();
                chatInterval = setInterval(loadMessages, 3000);
            }
        });
        function viewOrderPhotos(orderId, photo, photo1, photo2) {
            document.getElementById('photo-order-id').textContent = orderId;
            
            const photos = [photo, photo1, photo2];
            const container = document.getElementById('photo-container');
            
            container.innerHTML = '';
            
            for (let i = 0; i < 3; i++) {
                const photoPath = photos[i] ? photos[i].trim().replace(/[\n\r]/g, '') : '';
                const photoDiv = document.createElement('div');
                photoDiv.className = 'photo-item';
                
                if (photoPath) {
                    let fullPath;
                    if (photoPath.startsWith('http') || photoPath.startsWith('https') || photoPath.startsWith('/')) {
                        fullPath = photoPath;
                    } else {
                        fullPath = 'uploads/' + photoPath;
                    }
                    
                    photoDiv.innerHTML = `
                        <img src="${fullPath}" 
                             alt="Фото ${i + 1}" 
                             onclick="openFullscreen('${fullPath}')"
                             onerror="this.onerror=null; this.parentElement.innerHTML=getEmptyPhoto(${i + 1})">
                        <p>Фото ${i + 1}</p>
                    `;
                } else {
                    photoDiv.innerHTML = getEmptyPhoto(i + 1);
                }
                
                container.appendChild(photoDiv);
            }
            
            document.getElementById('photoViewModal').style.display = 'block';
        }

        function getEmptyPhoto(index) {
            return `
                <div class="empty-photo">
                    <i class="fas fa-camera"></i>
                    <p>Нет фото</p>
                </div>
                <p>Фото ${index}</p>
            `;
        }

        function showPhotoUploadModal(orderId) {
            document.getElementById('upload-order-id').textContent = orderId;
            document.getElementById('form-order-id').value = orderId;
            document.getElementById('photoUploadModal').style.display = 'block';
        }

        function closePhotoModal() {
            document.getElementById('photoViewModal').style.display = 'none';
        }

        function closePhotoUploadModal() {
            document.getElementById('photoUploadModal').style.display = 'none';
            document.getElementById('photoUploadForm').reset();
        }

        function openFullscreen(src) {
            document.getElementById('fullscreenImage').src = src;
            document.getElementById('fullscreenModal').style.display = 'block';
        }

        function closeFullscreen() {
            document.getElementById('fullscreenModal').style.display = 'none';
        }

        function filterOrders() {
            const status = document.getElementById('status-filter').value;
            const search = document.getElementById('search-filter').value.toLowerCase();
            
            document.querySelectorAll('.order-card').forEach(card => {
                let show = true;
                if (status !== 'all' && card.dataset.status != status) show = false;
                if (search && !card.textContent.toLowerCase().includes(search)) show = false;
                card.style.display = show ? 'block' : 'none';
            });
        }

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type} show`;
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        function showAssignDriverModal(orderId) {
            document.getElementById('assign-order-id').value = orderId;
            document.getElementById('assign-driver-modal').style.display = 'block';
        }

        function closeAssignModal() {
            document.getElementById('assign-driver-modal').style.display = 'none';
        }

        function closeEditModal() {
            document.getElementById('edit-order-modal').style.display = 'none';
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
                        showNotification('Ошибка загрузки заказа', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Ошибка загрузки заказа', 'error');
                });
        }

        let chatInterval;

        function loadMessages() {
            fetch('api/chat_api.php?action=get_messages&user_id=<?php echo $user_id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) displayMessages(data.messages);
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        function displayMessages(messages) {
            const container = document.getElementById('chatMessages');
            if (!container) return;
            
            container.innerHTML = '';
            messages.reverse().forEach(msg => {
                const time = new Date(msg.created_at).toLocaleTimeString('ru-RU', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.user_id == <?php echo $user_id; ?> ? 'sent' : 'received'}`;
                messageDiv.innerHTML = `
                    <div class="message-content">${msg.message}</div>
                    <div class="message-info">
                        <span>${msg.First_Name} ${msg.Last_Name}</span>
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
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'send_message',
                    user_id: <?php echo $user_id; ?>,
                    message: message,
                    recipient_id: document.getElementById('chatRecipient').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages();
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }

        document.querySelectorAll('.dispatcher-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href, window.location.origin);
                const tab = url.searchParams.get('tab');
                
                document.querySelectorAll('.dispatcher-nav a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(`tab-${tab}`).classList.add('active');
                
                history.pushState({}, '', `?tab=${tab}`);
                
                setTimeout(() => {
                    if (tab === 'chat') {
                        loadMessages();
                        if (chatInterval) clearInterval(chatInterval);
                        chatInterval = setInterval(loadMessages, 3000);
                    } else if (chatInterval) {
                        clearInterval(chatInterval);
                    }
                }, 100);
            });
        });
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
            if (event.target.classList.contains('modal-photo')) {
                event.target.style.display = 'none';
            }
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('photoViewModal').style.display = 'none';
                document.getElementById('photoUploadModal').style.display = 'none';
                document.getElementById('assign-driver-modal').style.display = 'none';
                document.getElementById('edit-order-modal').style.display = 'none';
                document.getElementById('fullscreenModal').style.display = 'none';
            }
        });
    </script>
</body>
</html>