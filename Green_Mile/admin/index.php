<?php
include_once '../api/config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../login.php");
    exit();
}

$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_user'])) {
        $id = $_POST['id'];
        $conn->query("DELETE FROM users WHERE id_user = $id");
        header("Location: ?page=users&msg=deleted");
        exit;
    }
    
    if (isset($_POST['delete_transport'])) {
        $id = $_POST['id'];
        $conn->query("DELETE FROM transport WHERE id_transport = $id");
        header("Location: ?page=transport&msg=deleted");
        exit;
    }
    
    if (isset($_POST['delete_order'])) {
        $id = $_POST['id'];
        $conn->query("DELETE FROM orders WHERE id_order = $id");
        header("Location: ?page=orders&msg=deleted");
        exit;
    }
    
    if (isset($_POST['add_user'])) {
        $login = $_POST['login'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $role = $_POST['role'];
        $password = $_POST['password'];
        
        $conn->query("INSERT INTO users (Login, First_Name, Last_Name, role, Password) 
                     VALUES ('$login', '$first_name', '$last_name', $role, '$password')");
        header("Location: ?page=users&msg=added");
        exit;
    }
    if (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $login = $_POST['login'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $role = $_POST['role'];
        
        $conn->query("UPDATE users SET 
                     Login='$login', 
                     First_Name='$first_name', 
                     Last_Name='$last_name', 
                     role=$role 
                     WHERE id_user=$id");
        header("Location: ?page=users&msg=updated");
        exit;
    }
    
    if (isset($_POST['add_transport'])) {
        $gos_n = $_POST['gos_n'];
        $model = $_POST['model'];
        $driver_id = $_POST['driver_id'] ?: 'NULL';
        
        $conn->query("INSERT INTO transport (Gos_N, Model_transport, id_user) 
                     VALUES ('$gos_n', '$model', $driver_id)");
        header("Location: ?page=transport&msg=added");
        exit;
    }
    
    if (isset($_POST['edit_transport'])) {
        $id = $_POST['id'];
        $gos_n = $_POST['gos_n'];
        $model = $_POST['model'];
        $driver_id = $_POST['driver_id'] ?: 'NULL';
        
        $conn->query("UPDATE transport SET 
                     Gos_N='$gos_n', 
                     Model_transport='$model', 
                     id_user=$driver_id 
                     WHERE id_transport=$id");
        header("Location: ?page=transport&msg=updated");
        exit;
    }
    if (isset($_POST['add_order'])) {
        $address = $_POST['address'];
        $material = $_POST['material'];
        $volume = $_POST['volume'];
        $date = $_POST['date'];
        
        $conn->query("INSERT INTO orders (addres, id_materials, volume, data_Time, status) 
                     VALUES ('$address', $material, '$volume', '$date', 1)");
        header("Location: ?page=orders&msg=added");
        exit;
    }
    if (isset($_POST['edit_order'])) {
        $id = $_POST['id'];
        $address = $_POST['address'];
        $material = $_POST['material'];
        $volume = $_POST['volume'];
        $date = $_POST['date'];
        $status = $_POST['status'];
        
        $conn->query("UPDATE orders SET 
                     addres='$address', 
                     id_materials=$material, 
                     volume='$volume', 
                     data_Time='$date',
                     status=$status
                     WHERE id_order=$id");
        header("Location: ?page=orders&msg=updated");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель - Green Mile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f5f7fa, #e8f5e9); min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .dashboard-header {
            background: linear-gradient(135deg, #2E7D32, #1976D2);
            color: white; padding: 15px 25px; border-radius: 15px;
            margin-bottom: 25px; display: flex; justify-content: space-between;
            align-items: center; box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .admin-container { display: flex; gap: 20px; }
        
        .admin-sidebar {
            width: 260px; background: linear-gradient(180deg, #0D47A1, #1976D2);
            color: white; border-radius: 15px; padding: 20px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .admin-nav a {
            display: block; padding: 15px 25px; color: rgba(255,255,255,0.8);
            text-decoration: none; border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .admin-nav a:hover, .admin-nav a.active {
            background: rgba(76,175,80,0.2); border-left-color: #4CAF50; color: white;
        }
        
        .admin-nav i { margin-right: 10px; width: 20px; }
        
        .admin-content {
            flex: 1; background: white; border-radius: 15px; padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .admin-section { display: none; }
        .admin-section.active { display: block; animation: fadeIn 0.5s; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .stat-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin: 30px 0;
        }
        
        .stat-card {
            background: white; padding: 20px; border-radius: 10px;
            text-align: center; border-left: 4px solid #4CAF50;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #2E7D32; margin: 10px 0; }
        
        table {
            width: 100%; border-collapse: collapse; margin: 20px 0;
        }
        
        th {
            background: #4CAF50; color: white; padding: 12px;
        }
        
        td {
            padding: 12px; border-bottom: 1px solid #eee;
        }
        
        tr:hover { background: #BBDEFB; }
        
        .btn {
            display: inline-block; padding: 8px 16px; border-radius: 5px;
            border: none; cursor: pointer; transition: all 0.3s;
            text-decoration: none; font-size: 0.9rem;
        }
        
        .btn-primary { background: #4CAF50; color: white; }
        .btn-primary:hover { background: #2E7D32; }
        .btn-secondary { background: #2196F3; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white; margin: 50px auto; padding: 20px;
            border-radius: 10px; width: 90%; max-width: 600px;
            max-height: 80vh; overflow-y: auto;
        }
        
        .close { float: right; font-size: 28px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        
        .badge {
            display: inline-block; padding: 4px 8px; border-radius: 12px;
            font-size: 0.85rem; font-weight: bold;
        }
        .badge-success { background: #C8E6C9; color: #2E7D32; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #BBDEFB; color: #0D47A1; }
        .badge-primary { background: #007bff; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        
        .btn-icon { padding: 5px 10px; margin: 0 2px; }
        .filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        
        .message {
            padding: 10px; border-radius: 5px; margin-bottom: 20px;
        }
        .message-success { background: #C8E6C9; color: #2E7D32; border: 1px solid #2E7D32; }
        
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
            background: #4CAF50;
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
            width: 250px;
        }

        .chat-input input {
            flex: 1;
        }

        .chat-input input:focus,
        .chat-input select:focus {
            border-color: #4CAF50;
            outline: none;
        }

        .chat-input button {
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .chat-input button:hover {
            background: #2E7D32;
        }

        .user-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-online { background: #4CAF50; }
        .status-busy { background: #FF9800; }
        .status-offline { background: #9E9E9E; }
        
        footer { margin-top: 30px; text-align: center; color: #666; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1><i class="fas fa-crown"></i> Админ панель</h1>
            <div>
                <span><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?> (Директор)</span>
                <a href="../api/logout.php" class="btn" style="background: rgba(255,255,255,0.2); color: white; margin-left: 15px;">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </header>
        
        <!-- Сообщения -->
        <?php if (isset($_GET['msg'])): ?>
        <div class="message message-success">
            <?php 
            $msgs = [
                'added' => 'Запись успешно добавлена!',
                'updated' => 'Запись успешно обновлена!',
                'deleted' => 'Запись успешно удалена!'
            ];
            echo $msgs[$_GET['msg']] ?? 'Действие выполнено';
            ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-container">
            <nav class="admin-sidebar">
                <div class="admin-nav">
                    <ul>
                        <li><a href="?page=dashboard" class="<?php echo $current_page=='dashboard'?'active':''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Аналитика
                        </a></li>
                        <li><a href="?page=users" class="<?php echo $current_page=='users'?'active':''; ?>">
                            <i class="fas fa-users"></i> Пользователи
                        </a></li>
                        <li><a href="?page=transport" class="<?php echo $current_page=='transport'?'active':''; ?>">
                            <i class="fas fa-truck"></i> Транспорт
                        </a></li>
                        <li><a href="?page=orders" class="<?php echo $current_page=='orders'?'active':''; ?>">
                            <i class="fas fa-clipboard-list"></i> Заказы
                        </a></li>
                        <li><a href="?page=chat" class="<?php echo $current_page=='chat'?'active':''; ?>">
                            <i class="fas fa-comments"></i> Чат
                        </a></li>
                    </ul>
                </div>
            </nav>
            
            <main class="admin-content">
                <?php if($current_page == 'dashboard'): ?>
                <section class="admin-section active">
                    <h2>Аналитика</h2>
                    <div class="stat-grid">
                        <?php
                        $total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
                        $total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
                        $total_transport = $conn->query("SELECT COUNT(*) as total FROM transport")->fetch_assoc()['total'];
                        $completed_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status=5")->fetch_assoc()['total'];
                        ?>
                        <div class="stat-card"><div class="stat-number"><?php echo $total_users; ?></div><div>Пользователей</div></div>
                        <div class="stat-card"><div class="stat-number"><?php echo $total_orders; ?></div><div>Заказов всего</div></div>
                        <div class="stat-card"><div class="stat-number"><?php echo $total_transport; ?></div><div>Транспорт</div></div>
                        <div class="stat-card"><div class="stat-number"><?php echo $completed_orders; ?></div><div>Выполнено</div></div>
                    </div>
                    
                    <h3>Последние заказы</h3>
                    <table>
                        <thead><tr><th>ID</th><th>Дата</th><th>Адрес</th><th>Статус</th></tr></thead>
                        <tbody>
                            <?php
                            $recent = $conn->query("SELECT * FROM orders ORDER BY data_Time DESC LIMIT 5");
                            while($order = $recent->fetch_assoc()):
                                $status = ['','Новый','Подтвержден','В работе','Завершен','Выполнен'][$order['status']] ?? 'Неизвестно';
                                $status_class = ['','badge-warning','badge-info','badge-primary','badge-success','badge-success'][$order['status']] ?? 'badge-secondary';
                            ?>
                            <tr>
                                <td>#<?php echo $order['id_order']; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></td>
                                <td><?php echo $order['addres']; ?></td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </section>
                <?php elseif($current_page == 'users'): ?>
                <section class="admin-section active">
                    <h2>Управление пользователями</h2>
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="showModal('addUserModal')">
                            <i class="fas fa-plus"></i> Добавить пользователя
                        </button>

                    </div>
                    
                    <table>
                        <thead><tr><th>ID</th><th>Логин</th><th>Имя</th><th>Роль</th><th>Статус</th><th>Действия</th></tr></thead>
                        <tbody>
                            <?php
                            $users = $conn->query("SELECT * FROM users ORDER BY id_user");
                            while($user = $users->fetch_assoc()):
                                $role = ['','Директор','Диспетчер','Водитель','Клиент','Бухгалтер'][$user['role']];
                                $role_class = ['','badge-danger','badge-info','badge-warning','badge-success','badge-primary'][$user['role']];
                                $status_color = $user['driver_status'] ?? 'offline';
                                $status_text = ['free' => 'Свободен', 'busy' => 'Занят', 'offline' => 'Не в сети'][$status_color];
                            ?>
                            <tr>
                                <td><?php echo $user['id_user']; ?></td>
                                <td><?php echo $user['Login']; ?></td>
                                <td><?php echo $user['First_Name'].' '.$user['Last_Name']; ?></td>
                                <td><span class="badge <?php echo $role_class; ?>"><?php echo $role; ?></span></td>
                                <td>
                                    <?php if($user['role'] == 3): ?>
                                        <span class="user-status status-<?php echo $status_color; ?>"></span>
                                        <?php echo $status_text; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-icon btn-warning" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить пользователя <?php echo $user['Login']; ?>?')">
                                        <input type="hidden" name="id" value="<?php echo $user['id_user']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-icon btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </section>
                <?php elseif($current_page == 'transport'): ?>
                <section class="admin-section active">
                    <h2>Управление транспортом</h2>
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="showModal('addTransportModal')">
                            <i class="fas fa-plus"></i> Добавить транспорт
                        </button>
                    </div>
                    <table>
                        <thead><tr><th>ID</th><th>Гос номер</th><th>Модель</th><th>Водитель</th><th>Статус водителя</th><th>Действия</th></tr></thead>
                        <tbody>
                            <?php
                            $transport = $conn->query("SELECT t.*, u.First_Name, u.Last_Name, u.driver_status 
                                                      FROM transport t LEFT JOIN users u ON t.id_user = u.id_user");
                            while($t = $transport->fetch_assoc()):
                                $driver = $t['First_Name'] ? $t['First_Name'].' '.$t['Last_Name'] : '<span style="color:#999;">Не назначен</span>';
                                $status_color = $t['driver_status'] ?? 'offline';
                            ?>
                            <tr>
                                <td>#<?php echo $t['id_transport']; ?></td>
                                <td><?php echo $t['Gos_N']; ?></td>
                                <td><?php echo $t['Model_transport']; ?></td>
                                <td><?php echo $driver; ?></td>
                                <td>
                                    <?php if($t['First_Name']): ?>
                                        <span class="user-status status-<?php echo $status_color; ?>"></span>
                                        <?php echo ['free' => 'Свободен', 'busy' => 'Занят', 'offline' => 'Не в сети'][$status_color]; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-icon btn-warning" onclick="editTransport(<?php echo htmlspecialchars(json_encode($t)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить транспорт <?php echo $t['Gos_N']; ?>?')">
                                        <input type="hidden" name="id" value="<?php echo $t['id_transport']; ?>">
                                        <button type="submit" name="delete_transport" class="btn btn-icon btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </section>
                <?php elseif($current_page == 'orders'): ?>
                <section class="admin-section active">
                    <h2>Все заказы</h2>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                            <input type="hidden" name="page" value="orders">
                            
                            <div style="min-width: 150px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Статус:</label>
                                <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                                    <option value="0" <?php echo (!isset($_GET['status']) || $_GET['status']==0) ? 'selected' : ''; ?>>Все статусы</option>
                                    <option value="1" <?php echo (isset($_GET['status']) && $_GET['status']==1) ? 'selected' : ''; ?>>Новый</option>
                                    <option value="2" <?php echo (isset($_GET['status']) && $_GET['status']==2) ? 'selected' : ''; ?>>Подтвержден</option>
                                    <option value="3" <?php echo (isset($_GET['status']) && $_GET['status']==3) ? 'selected' : ''; ?>>В работе</option>
                                    <option value="5" <?php echo (isset($_GET['status']) && $_GET['status']==4) ? 'selected' : ''; ?>>Завершен</option>
                                </select>
                            </div>
                            
                            <div style="min-width: 150px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Дата с:</label>
                                <input type="date" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            
                            <div style="min-width: 150px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Дата по:</label>
                                <input type="date" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            
                            <div style="min-width: 200px;">
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Поиск по адресу:</label>
                                <input type="text" name="search" value="<?php echo $_GET['search'] ?? ''; ?>" 
                                       placeholder="Введите адрес..." 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Применить
                                </button>
                                <a href="?page=orders" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Сбросить
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="showModal('addOrderModal')">
                            <i class="fas fa-plus"></i> Добавить заказ
                        </button>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Дата</th>
                                <th>Адрес</th>
                                <th>Материал</th>
                                <th>Объем</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT o.*, m.Name_Mat as material 
                                    FROM orders o 
                                    LEFT JOIN materials m ON o.id_materials = m.id_material 
                                    WHERE 1=1";
                            
                            if (isset($_GET['status']) && $_GET['status'] > 0) {
                                $status = intval($_GET['status']);
                                $sql .= " AND o.status = $status";
                            }
                            
                            if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                                $date_from = $conn->real_escape_string($_GET['date_from']);
                                $sql .= " AND DATE(o.data_Time) >= '$date_from'";
                            }
                            
                            if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                                $date_to = $conn->real_escape_string($_GET['date_to']);
                                $sql .= " AND DATE(o.data_Time) <= '$date_to'";
                            }
                            
                            if (isset($_GET['search']) && !empty($_GET['search'])) {
                                $search = $conn->real_escape_string($_GET['search']);
                                $sql .= " AND o.addres LIKE '%$search%'";
                            }
                            
                            $sql .= " ORDER BY o.id_order DESC";
                            
                            $orders = $conn->query($sql);
                            
                            if ($orders && $orders->num_rows > 0):
                                while($order = $orders->fetch_assoc()):
                                   $status = ['','Новый','Подтвержден','В работе','Выполнен','Выполнен'][$order['status']] ?? 'Неизвестно';
                                   $status_class = ['','badge-warning','badge-info','badge-primary','badge-success','badge-success'][$order['status']] ?? 'badge-secondary';
                            ?>
                            <tr>
                                <td><strong>#<?php echo $order['id_order']; ?></strong></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></td>
                                <td><?php echo $order['addres']; ?></td>
                                <td><?php echo $order['material']; ?></td>
                                <td><?php echo $order['volume']; ?> кг</td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                                <td>
                                    <button class="btn btn-icon btn-warning" onclick="editOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить заказ #<?php echo $order['id_order']; ?>?')">
                                        <input type="hidden" name="id" value="<?php echo $order['id_order']; ?>">
                                        <button type="submit" name="delete_order" class="btn btn-icon btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px;">
                                    <i class="fas fa-box-open" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                    <p>Заказы не найдены</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div style="margin-top: 20px; color: #666;">
                        <i class="fas fa-info-circle"></i> 
                        Всего заказов: <?php echo $orders ? $orders->num_rows : 0; ?>
                    </div>
                <?php elseif($current_page == 'chat'): ?>
                <section class="admin-section active">
                    <h2><i class="fas fa-comments"></i> Чат</h2>
                    
                    <div class="chat-container">
                        <div class="chat-messages" id="chatMessages"></div>
                        
                        <div class="chat-input">
                            <select id="chatRecipient">
                                <option value="0">Всем сотрудникам</option>
                                <?php
                                $users = $conn->query("SELECT id_user, First_Name, Last_Name, role, driver_status 
                                                      FROM users 
                                                      WHERE role IN (2,3) 
                                                      ORDER BY 
                                                          CASE 
                                                              WHEN role = 2 THEN 1 
                                                              WHEN role = 3 THEN 2 
                                                          END,
                                                          driver_status = 'free' DESC,
                                                          Last_Name");
                                while($u = $users->fetch_assoc()):
                                    $role_text = $u['role'] == 2 ? 'Диспетчер' : 'Водитель';
                                    $status_icon = '';
                                    $status_color = '';
                                    
                                    if ($u['role'] == 3) {
                                        $status_color = $u['driver_status'] ?? 'offline';
                                        $status_icon = $status_color == 'free' ? '✓' : ($status_color == 'busy' ? '⏳' : '○');
                                    }
                                ?>
                                <option value="<?php echo $u['id_user']; ?>" 
                                        style="color: <?php echo $u['role'] == 2 ? '#2196F3' : ($status_color == 'free' ? '#4CAF50' : ($status_color == 'busy' ? '#FF9800' : '#9E9E9E')); ?>;">
                                    <?php echo $status_icon . ' ' . $u['First_Name'] . ' ' . $u['Last_Name'] . ' (' . $role_text . ')'; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="text" id="chatMessageInput" placeholder="Введите сообщение..." onkeypress="if(event.key === 'Enter') sendMessage()">
                            <button onclick="sendMessage()">
                                <i class="fas fa-paper-plane"></i> Отправить
                            </button>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            </main>
        </div>

        <footer>&copy; 2026 Green Mile</footer>
    </div>
    <div id="addUserModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addUserModal')">&times;</span>
        <h3>Добавить пользователя</h3>
        <form method="POST" onsubmit="return validateAddUserForm()">
            <div class="form-group">
                <label>Логин:</label>
                <input type="text" name="login" class="form-control" required 
                       pattern="[a-zA-Z0-9_]+" 
                       title="Только латинские буквы, цифры и знак подчеркивания">
                <small style="color: #666; display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Только латинские буквы, цифры и знак подчеркивания
                </small>
            </div>
            <div class="form-group">
                <label>Имя:</label>
                <input type="text" name="first_name" class="form-control" 
                       pattern="[a-яА-Яa-zA-Z]+" 
                       title="Только буквы (русские или латинские)">
                <small style="color: #666; display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Только буквы (русские или латинские)
                </small>
            </div>
            <div class="form-group">
                <label>Фамилия:</label>
                <input type="text" name="last_name" class="form-control" 
                       pattern="[a-яА-Яa-zA-Z]+" 
                       title="Только буквы (русские или латинские)">
                <small style="color: #666; display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Только буквы (русские или латинские)
                </small>
            </div>
            <div class="form-group">
                <label>Роль:</label>
                <select name="role" class="form-control">
                    <option value="1">Директор</option>
                    <option value="2">Диспетчер</option>
                    <option value="3">Водитель</option>
                    <option value="4">Клиент</option>
                    <option value="5">Бухгалтер</option>
                </select>
            </div>
            <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="password" class="form-control" required 
                       pattern="[a-zA-Z0-9_]+" 
                       title="Только латинские буквы, цифры и знак подчеркивания">
                <small style="color: #666; display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Только латинские буквы, цифры и знак подчеркивания
                </small>
            </div>
            <button type="submit" name="add_user" class="btn btn-primary">Сохранить</button>
        </form>
    </div>
</div>

<!-- Модальное окно редактирования пользователя -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editUserModal')">&times;</span>
        <h3>Редактировать пользователя</h3>
        <form method="POST" onsubmit="return validateEditUserForm()">
            <input type="hidden" name="id" id="edit_user_id">
            <div class="form-group">
                <label>Логин:</label>
                <input type="text" name="login" id="edit_user_login" class="form-control" required 
                       pattern="[a-zA-Z0-9_]+" 
                       title="Только латинские буквы, цифры и знак подчеркивания">
                <small style="color: #666; display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Только латинские буквы, цифры и знак подчеркивания
                </small>
            </div>
            <div class="form-group">
                <label>Имя:</label>
                <input type="text" name="first_name" id="edit_user_firstname" class="form-control" 
                       pattern="[a-яА-Яa-zA-Z]+" 
                       title="Только буквы (русские или латинские)">
                <small style="color: #666; display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Только буквы (русские или латинские)
                </small>
            </div>
            <div class="form-group">
                <label>Фамилия:</label>
                <input type="text" name="last_name" id="edit_user_lastname" class="form-control" 
                       pattern="[a-яА-Яa-zA-Z]+" 
                       title="Только буквы (русские или латинские)">
                <small style="color: #666; display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Только буквы (русские или латинские)
                </small>
            </div>
            <div class="form-group">
                <label>Роль:</label>
                <select name="role" id="edit_user_role" class="form-control">
                    <option value="1">Директор</option>
                    <option value="2">Диспетчер</option>
                    <option value="3">Водитель</option>
                    <option value="4">Клиент</option>
                    <option value="5">Бухгалтер</option>
                </select>
            </div>
            <button type="submit" name="edit_user" class="btn btn-primary">Обновить</button>
        </form>
    </div>
</div>
    <div id="editTransportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editTransportModal')">&times;</span>
            <h3>Редактировать транспорт</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_transport_id">
                <div class="form-group">
                    <label>Гос. номер:</label>
                    <input type="text" name="gos_n" id="edit_transport_gos" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Модель:</label>
                    <input type="text" name="model" id="edit_transport_model" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Водитель:</label>
                    <select name="driver_id" id="edit_transport_driver" class="form-control">
                        <option value="">Без водителя</option>
                        <?php
                        $drivers = $conn->query("SELECT id_user, First_Name, Last_Name FROM users WHERE role=3");
                        while($d = $drivers->fetch_assoc()):
                        ?>
                        <option value="<?php echo $d['id_user']; ?>">
                            <?php echo $d['First_Name'].' '.$d['Last_Name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="edit_transport" class="btn btn-primary">Обновить</button>
            </form>
        </div>
    </div>
    <div id="addOrderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addOrderModal')">&times;</span>
            <h3>Добавить заказ</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Адрес:</label>
                    <input type="text" name="address" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Материал:</label>
                    <select name="material" class="form-control">
                        <?php
                        $materials = $conn->query("SELECT * FROM materials");
                        while($m = $materials->fetch_assoc()):
                        ?>
                        <option value="<?php echo $m['id_material']; ?>">
                            <?php echo $m['Name_Mat']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Объем (кг):</label>
                    <input type="number" name="volume" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Дата и время:</label>
                    <input type="datetime-local" name="date" class="form-control" required>
                </div>
                <button type="submit" name="add_order" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div>
    <div id="editOrderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editOrderModal')">&times;</span>
            <h3>Редактировать заказ</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_order_id">
                <div class="form-group">
                    <label>Адрес:</label>
                    <input type="text" name="address" id="edit_order_address" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Материал:</label>
                    <select name="material" id="edit_order_material" class="form-control">
                        <?php
                        $materials = $conn->query("SELECT * FROM materials");
                        while($m = $materials->fetch_assoc()):
                        ?>
                        <option value="<?php echo $m['id_material']; ?>">
                            <?php echo $m['Name_Mat']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Объем (кг):</label>
                    <input type="number" name="volume" id="edit_order_volume" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Дата и время:</label>
                    <input type="datetime-local" name="date" id="edit_order_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Статус:</label>
                    <select name="status" id="edit_order_status" class="form-control">
                        <option value="1">Новый</option>
                        <option value="2">Подтвержден</option>
                        <option value="3">В работе</option>
                        <option value="4">Выполнен</option>
                    </select>
                </div>
                <button type="submit" name="edit_order" class="btn btn-primary">Обновить</button>
            </form>
        </div>
    </div>

    <script>
function validateLogin(login) {
    const regex = /^[a-zA-Z0-9_]+$/;
    return regex.test(login);
}

function validateName(name) {
    const regex = /^[a-яА-Яa-zA-Z]+$/;
    return regex.test(name);
}

function validatePassword(password) {
    const regex = /^[a-zA-Z0-9_]+$/;
    return regex.test(password);
}

function showValidationError(message) {
    alert('Ошибка валидации: ' + message);
    return false;
}

function validateAddUserForm() {
    const login = document.querySelector('#addUserModal input[name="login"]').value;
    const firstName = document.querySelector('#addUserModal input[name="first_name"]').value;
    const lastName = document.querySelector('#addUserModal input[name="last_name"]').value;
    const password = document.querySelector('#addUserModal input[name="password"]').value;
    
    if (!validateLogin(login)) {
        showValidationError('Логин может содержать только латинские буквы, цифры и символ подчеркивания');
        return false;
    }
    
    if (firstName && !validateName(firstName)) {
        showValidationError('Имя может содержать только буквы (русские или латинские)');
        return false;
    }
    
    if (lastName && !validateName(lastName)) {
        showValidationError('Фамилия может содержать только буквы (русские или латинские)');
        return false;
    }
    
    if (!validatePassword(password)) {
        showValidationError('Пароль может содержать только латинские буквы, цифры и символ подчеркивания');
        return false;
    }
    
    return true;
}
function validateEditUserForm() {
    const login = document.querySelector('#editUserModal input[name="login"]').value;
    const firstName = document.querySelector('#editUserModal input[name="first_name"]').value;
    const lastName = document.querySelector('#editUserModal input[name="last_name"]').value;
    
    if (!validateLogin(login)) {
        showValidationError('Логин может содержать только латинские буквы, цифры и символ подчеркивания');
        return false;
    }
    
    if (firstName && !validateName(firstName)) {
        showValidationError('Имя может содержать только буквы (русские или латинские)');
        return false;
    }
    
    if (lastName && !validateName(lastName)) {
        showValidationError('Фамилия может содержать только буквы (русские или латинские)');
        return false;
    }
    
    return true;
}
        function showModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id_user;
            document.getElementById('edit_user_login').value = user.Login;
            document.getElementById('edit_user_firstname').value = user.First_Name || '';
            document.getElementById('edit_user_lastname').value = user.Last_Name || '';
            document.getElementById('edit_user_role').value = user.role;
            showModal('editUserModal');
        }

        function editTransport(transport) {
            document.getElementById('edit_transport_id').value = transport.id_transport;
            document.getElementById('edit_transport_gos').value = transport.Gos_N;
            document.getElementById('edit_transport_model').value = transport.Model_transport;
            document.getElementById('edit_transport_driver').value = transport.id_user || '';
            showModal('editTransportModal');
        }

        function editOrder(order) {
            document.getElementById('edit_order_id').value = order.id_order;
            document.getElementById('edit_order_address').value = order.addres;
            document.getElementById('edit_order_material').value = order.id_materials;
            document.getElementById('edit_order_volume').value = order.volume;
            let date = new Date(order.data_Time);
            let year = date.getFullYear();
            let month = String(date.getMonth() + 1).padStart(2, '0');
            let day = String(date.getDate()).padStart(2, '0');
            let hours = String(date.getHours()).padStart(2, '0');
            let minutes = String(date.getMinutes()).padStart(2, '0');
            document.getElementById('edit_order_date').value = `${year}-${month}-${day}T${hours}:${minutes}`;
            
            document.getElementById('edit_order_status').value = order.status;
            showModal('editOrderModal');
        }

        function exportUsers() {
            window.location.href = '?page=users&export=csv';
        }

        function exportFinanceReport() {
            alert('Скачивание финансового отчета');
        }

        function exportStaffReport() {
            alert('Отчет по персоналу');
        }

        function showAllOnMap() {
            window.open('https://www.google.com/maps', '_blank');
        }
        let chatInterval;

        function loadMessages() {
            fetch('../api/chat_api.php?action=get_messages&user_id=<?php echo $_SESSION['user_id']; ?>')
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
                messageDiv.className = `message ${msg.user_id == <?php echo $_SESSION['user_id']; ?> ? 'sent' : 'received'}`;
                
                const time = new Date(msg.created_at).toLocaleTimeString('ru-RU', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const roleText = msg.role == 1 ? 'Директор' : (msg.role == 2 ? 'Диспетчер' : 'Водитель');
                
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
            
            fetch('../api/chat_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'send_message',
                    user_id: <?php echo $_SESSION['user_id']; ?>,
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

        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes('page=chat')) {
                loadMessages();
                chatInterval = setInterval(loadMessages, 3000);
            }
        });
        document.querySelectorAll('.admin-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').includes('page=chat')) {
                    setTimeout(() => {
                        loadMessages();
                        if (chatInterval) clearInterval(chatInterval);
                        chatInterval = setInterval(loadMessages, 3000);
                    }, 100);
                } else {
                    if (chatInterval) clearInterval(chatInterval);
                }
            });
        });
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
    </script>
</body>
</html>