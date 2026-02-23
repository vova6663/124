<?php
include_once 'api/config.php';

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

function updateDriverStatus($conn, $user_id, $status) {
    $conn->query("UPDATE users SET driver_status = '$status' WHERE id_user = $user_id");
}

function getActiveOrdersCount($conn, $user_id) {
    $result = $conn->query("SELECT COUNT(*) as total FROM orders o 
                            JOIN complete_orders co ON o.id_order = co.id_order 
                            JOIN transport t ON co.id_transport = t.id_transport 
                            WHERE t.id_user = $user_id AND o.status IN (2,3,4)");
    return $result->fetch_assoc()['total'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Загрузка фото
    if (isset($_POST['upload_photos'])) {
        $order_id = intval($_POST['order_id']);
        $target_dir = "uploads/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $photos = [];
        // Обрабатываем до 3 фото
        $photo_fields = ['photo', 'photo2', 'photo3'];
        foreach ($photo_fields as $index => $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
                $file = $_FILES[$field];
                $extension = pathinfo($file["name"], PATHINFO_EXTENSION);
                $filename = time() . "_" . ($index + 1) . "_" . $order_id . "." . $extension;
                $target_file = $target_dir . $filename;
                
                if (move_uploaded_file($file["tmp_name"], $target_file)) {
                    $photos[$index + 1] = $filename;
                }
            }
        }
        
        // Проверяем есть ли уже запись в complete_orders
        $check = $conn->query("SELECT id_C_orders FROM complete_orders WHERE id_order = $order_id");
        
        if ($check->num_rows > 0) {
            // Обновляем существующую запись
            $updates = [];
            if (isset($photos[1])) $updates[] = "photo = '" . $photos[1] . "'";
            if (isset($photos[2])) $updates[] = "photo1 = '" . $photos[2] . "'";
            if (isset($photos[3])) $updates[] = "photo2 = '" . $photos[3] . "'";
            
            if (!empty($updates)) {
                $sql = "UPDATE complete_orders SET " . implode(", ", $updates) . " WHERE id_order = $order_id";
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
        
        header("Location: driver.php?success=photos_uploaded");
        exit;
    }
    
    if (isset($_POST['take_order'])) {
        $order_id = $_POST['order_id'];
        
        $current_status = $conn->query("SELECT driver_status FROM users WHERE id_user = $user_id")->fetch_assoc()['driver_status'];
        
        if ($current_status == 'free') {
            $transport = $conn->query("SELECT id_transport FROM transport WHERE id_user = $user_id")->fetch_assoc();
            
            if ($transport) {
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
        $new_status = $_POST['new_status'];
        if (in_array($new_status, ['free', 'busy', 'offline'])) {
            updateDriverStatus($conn, $user_id, $new_status);
            header("Location: driver.php?success=status_changed");
            exit;
        }
    }
}

$user_query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user = $user_query->fetch_assoc();

$active_orders_count = getActiveOrdersCount($conn, $user_id);

if ($active_orders_count > 0 && $user['driver_status'] != 'busy') {
    updateDriverStatus($conn, $user_id, 'busy');
    $user['driver_status'] = 'busy';
} elseif ($active_orders_count == 0 && $user['driver_status'] != 'free') {
    updateDriverStatus($conn, $user_id, 'free');
    $user['driver_status'] = 'free';
}

$user_query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user = $user_query->fetch_assoc();

$transport_query = $conn->query("SELECT * FROM transport WHERE id_user = $user_id");
$transport = $transport_query->num_rows > 0 ? $transport_query->fetch_assoc() : null;

// Получаем активные заказы с фото
$active_orders = $conn->query("SELECT o.*, m.Name_Mat as material_name,
                                      co.photo, co.photo1, co.photo2
                               FROM orders o 
                               LEFT JOIN materials m ON o.id_materials = m.id_material 
                               LEFT JOIN complete_orders co ON o.id_order = co.id_order 
                               LEFT JOIN transport t ON co.id_transport = t.id_transport 
                               WHERE t.id_user = $user_id AND o.status IN (2,3,4)
                               ORDER BY o.data_Time");

$available_orders = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                                  FROM orders o 
                                  LEFT JOIN materials m ON o.id_materials = m.id_material 
                                  WHERE o.status = 2 
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
            border-bottom: 2px solid #FF9800;
        }

        .modal-photo-header h2 {
            color: #FF9800;
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
            border: 3px solid #FF9800;
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
            border: 3px dashed #FF9800;
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
            border-top: 2px solid #FF9800;
        }

        .photo-upload-form h3 {
            color: #FF9800;
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
            color: #FF9800;
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
                <a href="Login.php" class="btn btn-danger">
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
                    'photos_uploaded' => '✓ Фото загружены',
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
                <div id="tab-active" class="tab-content active">
                    <h2><i class="fas fa-tasks"></i> Мои активные заказы</h2>
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
                    <?php if ($active_count > 0): ?>
                        <div class="orders-grid">
                            <?php while($order = $active_orders->fetch_assoc()): 
                                $has_photos = !empty(trim($order['photo'] ?? '')) || !empty(trim($order['photo1'] ?? '')) || !empty(trim($order['photo2'] ?? ''));
                            ?>
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
                                    <p><i class="fas fa-map-marker-alt" style="cursor: pointer; color: #2196F3;" onclick="openAddressInYandexMaps('<?php echo addslashes($order['addres']); ?>')" title="Открыть на карте"></i> 
                                        <span style="cursor: pointer; color: #2196F3; text-decoration: underline;" onclick="openAddressInYandexMaps('<?php echo addslashes($order['addres']); ?>')">
                                            <?php echo $order['addres']; ?>
                                        </span>
                                    </p>
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
                                    
                                    <button type="button" class="btn-photo" onclick="showPhotoUploadForm(<?php echo $order['id_order']; ?>)">
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
                                        <p><i class="fas fa-map-marker-alt" style="cursor: pointer; color: #2196F3;" onclick="openAddressInYandexMaps('<?php echo addslashes($order['addres']); ?>')" title="Открыть на карте"></i> 
                                            <span style="cursor: pointer; color: #2196F3; text-decoration: underline;" onclick="openAddressInYandexMaps('<?php echo addslashes($order['addres']); ?>')">
                                                <?php echo $order['addres']; ?>
                                            </span>
                                        </p>
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

                <div id="tab-chat" class="tab-content">
                    <h2><i class="fas fa-comments"></i> Чат с диспетчером</h2>
                    
                    <div class="chat-container">
                        <div class="chat-messages" id="chatMessages">
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

    <!-- Модальное окно для просмотра фото -->
    <div id="photoViewModal" class="modal-photo">
        <div class="modal-photo-content">
            <div class="modal-photo-header">
                <h2><i class="fas fa-images"></i> Фото заказа #<span id="photo-order-id"></span></h2>
                <span class="close" onclick="closePhotoModal()">&times;</span>
            </div>
            <div id="photo-container" class="photo-grid">
                <!-- Фото будут загружаться сюда -->
            </div>
        </div>
    </div>

    <!-- Модальное окно для загрузки фото -->
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

    <!-- Модальное окно для полноэкранного просмотра -->
    <div id="fullscreenModal" class="fullscreen-modal" onclick="closeFullscreen()">
        <img id="fullscreenImage" src="" alt="Полноэкранное фото">
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

        // Функции для работы с фото
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

        function showPhotoUploadForm(orderId) {
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
            if (event.target.classList.contains('modal-photo')) {
                event.target.style.display = 'none';
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('photoViewModal').style.display = 'none';
                document.getElementById('photoUploadModal').style.display = 'none';
                document.getElementById('problem-modal').style.display = 'none';
                document.getElementById('fullscreenModal').style.display = 'none';
            }
        });
        
        setTimeout(() => {
            document.getElementById('successNotification')?.classList.remove('show');
            document.getElementById('errorNotification')?.classList.remove('show');
        }, 3000);

        function openAddressInYandexMaps(address) {
            if (!address) {
                alert('Адрес не указан');
                return;
            }
            const encodedAddress = encodeURIComponent(address);
            const mapUrl = `https://yandex.ru/maps/?text=${encodedAddress}&z=17`;
            window.open(mapUrl, '_blank');
        }
    </script>
</body>
</html>