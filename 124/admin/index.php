<?php
// admin/index.php - УДАЛЯЕМ session_start() отсюда
include_once '../api/config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Определяем активный раздел
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Административная панель - Green Mile</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-container {
            display: flex;
            min-height: calc(100vh - 100px);
            margin-top: 20px;
        }
        
        .admin-sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            border-radius: 10px;
            padding: 20px 0;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .admin-content {
            flex: 1;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .admin-nav ul {
            list-style: none;
            padding: 0;
        }
        
        .admin-nav li {
            margin: 0;
        }
        
        .admin-nav a {
            display: block;
            padding: 15px 25px;
            color: #ecf0f1;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #4CAF50;
            color: white;
        }
        
        .admin-nav a.active {
            background: rgba(76, 175, 80, 0.2);
            border-left-color: #4CAF50;
            color: #4CAF50;
            font-weight: bold;
        }
        
        .admin-nav i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .admin-section {
            display: none;
        }
        
        .admin-section.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-top: 4px solid #4CAF50;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #4CAF50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .user-row {
            display: flex;
            align-items: center;
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
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .admin-nav ul {
                display: flex;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .admin-nav li {
                flex-shrink: 0;
            }
            
            .admin-nav a {
                padding: 15px;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .admin-nav a.active {
                border-left: none;
                border-bottom: 3px solid #4CAF50;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1>Административная панель</h1>
            <div class="user-info">
                <span><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></span>
                <span class="role">(Директор)</span>
                <a href="../dashboard.php" class="btn btn-small">В кабинет</a>
                <a href="../api/logout.php" class="btn btn-small btn-secondary">Выйти</a>
            </div>
        </header>
        
        <div class="admin-container">
            <!-- Боковая панель -->
            <nav class="admin-sidebar">
                <div class="admin-nav">
                    <ul>
                        <li>
                            <a href="?page=dashboard" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt"></i> Аналитика
                            </a>
                        </li>
                        <li>
                            <a href="?page=users" class="<?php echo $current_page == 'users' ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i> Управление пользователями
                            </a>
                        </li>
                        <li>
                            <a href="?page=transport" class="<?php echo $current_page == 'transport' ? 'active' : ''; ?>">
                                <i class="fas fa-truck"></i> Управление транспортом
                            </a>
                        </li>
                        <li>
                            <a href="?page=kpi" class="<?php echo $current_page == 'kpi' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-line"></i> KPI и метрики
                            </a>
                        </li>
                        <li>
                            <a href="?page=orders" class="<?php echo $current_page == 'orders' ? 'active' : ''; ?>">
                                <i class="fas fa-clipboard-list"></i> Все заказы
                            </a>
                        </li>
                        <li>
                            <a href="?page=reports" class="<?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                                <i class="fas fa-file-alt"></i> Отчеты
                            </a>
                        </li>
                        <li>
                            <a href="?page=settings" class="<?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                                <i class="fas fa-cog"></i> Настройки
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Основной контент -->
            <main class="admin-content">
                <?php if($current_page == 'dashboard'): ?>
                <section id="dashboard" class="admin-section active">
                    <h2><i class="fas fa-tachometer-alt"></i> Аналитика</h2>
                    <p>Общая статистика и показатели системы</p>
                    
                    <div class="stat-grid">
                        <?php
                        include '../api/config.php';
                        
                        // Статистика заказов
                        $total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
                        $today_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE DATE(data_Time) = CURDATE()")->fetch_assoc()['total'];
                        $active_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status IN (1,2)")->fetch_assoc()['total'];
                        $completed_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 3")->fetch_assoc()['total'];
                        
                        // Статистика пользователей
                        $total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
                        $total_drivers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 3")->fetch_assoc()['total'];
                        $total_clients = $conn->query("SELECT COUNT(*) as total FROM client")->fetch_assoc()['total'];
                        
                        // Статистика транспорта
                        $total_vehicles = $conn->query("SELECT COUNT(*) as total FROM transport")->fetch_assoc()['total'];
                        $active_vehicles = $conn->query("SELECT COUNT(DISTINCT id_transport) as total FROM complete_orders WHERE DATE(NOW()) = DATE(NOW())")->fetch_assoc()['total'];
                        ?>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_orders; ?></div>
                            <div class="stat-label">Всего заказов</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $today_orders; ?></div>
                            <div class="stat-label">Заказов сегодня</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $active_orders; ?></div>
                            <div class="stat-label">Активных заказов</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $completed_orders; ?></div>
                            <div class="stat-label">Выполнено</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_users; ?></div>
                            <div class="stat-label">Пользователей</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_drivers; ?></div>
                            <div class="stat-label">Водителей</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_clients; ?></div>
                            <div class="stat-label">Клиентов</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_vehicles; ?></div>
                            <div class="stat-label">Единиц транспорта</div>
                        </div>
                    </div>
                    
                    <h3>Последние заказы</h3>
                    <div class="table-container">
                        <?php
                        $recent_orders = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                                                        FROM orders o 
                                                        LEFT JOIN materials m ON o.id_materials = m.id_material 
                                                        ORDER BY o.data_Time DESC 
                                                        LIMIT 10");
                        ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Дата</th>
                                    <th>Адрес</th>
                                    <th>Материал</th>
                                    <th>Объем</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id_order']; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></td>
                                    <td><?php echo substr($order['addres'], 0, 30) . '...'; ?></td>
                                    <td><?php echo $order['material_name']; ?></td>
                                    <td><?php echo $order['volume']; ?> кг</td>
                                    <td>
                                        <?php 
                                        $status_badges = [
                                            1 => ['class' => 'badge-warning', 'text' => 'Новый'],
                                            2 => ['class' => 'badge-info', 'text' => 'В работе'],
                                            3 => ['class' => 'badge-success', 'text' => 'Завершен']
                                        ];
                                        $status = $order['status'] ?? 1;
                                        ?>
                                        <span class="badge <?php echo $status_badges[$status]['class']; ?>">
                                            <?php echo $status_badges[$status]['text']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                
                <?php elseif($current_page == 'users'): ?>
                <section id="users" class="admin-section active">
                    <h2><i class="fas fa-users"></i> Управление пользователями</h2>
                    <p>Управление сотрудниками и их ролями</p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <button class="btn btn-icon"><i class="fas fa-plus"></i> Добавить пользователя</button>
                            <button class="btn btn-icon btn-secondary"><i class="fas fa-download"></i> Экспорт</button>
                        </div>
                        <div>
                            <input type="text" placeholder="Поиск пользователей..." style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <?php
                        $users = $conn->query("SELECT * FROM users ORDER BY role, Last_Name");
                        $role_names = [
                            1 => 'Директор',
                            2 => 'Диспетчер/Бухгалтер',
                            3 => 'Водитель',
                            4 => 'Клиент'
                        ];
                        ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Пользователь</th>
                                    <th>Логин</th>
                                    <th>Роль</th>
                                    <th>Телефон/Email</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $users->fetch_assoc()): 
                                    $initials = substr($user['First_Name'], 0, 1) . substr($user['Last_Name'], 0, 1);
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-row">
                                            <div class="user-avatar"><?php echo $initials; ?></div>
                                            <div>
                                                <strong><?php echo $user['First_Name'] . ' ' . $user['Last_Name']; ?></strong><br>
                                                <small>ID: <?php echo $user['id_user']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $user['Login']; ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php echo $user['role'] == 1 ? 'badge-danger' : 
                                                   ($user['role'] == 2 ? 'badge-info' : 
                                                   ($user['role'] == 3 ? 'badge-warning' : 'badge-success')); ?>">
                                            <?php echo $role_names[$user['role']] ?? 'Неизвестно'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>Не указано</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">Активен</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-small btn-icon" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-small btn-icon btn-secondary" title="Просмотреть">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-small btn-icon" style="background: #dc3545;" title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                        <h3>Статистика по ролям</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                            <?php
                            $roles_stats = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
                            while($stat = $roles_stats->fetch_assoc()):
                            ?>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; font-weight: bold; color: #4CAF50;">
                                    <?php echo $stat['count']; ?>
                                </div>
                                <div style="color: #666;">
                                    <?php echo $role_names[$stat['role']] ?? 'Роль ' . $stat['role']; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </section>
                
                <?php elseif($current_page == 'transport'): ?>
                <section id="transport" class="admin-section active">
                    <h2><i class="fas fa-truck"></i> Управление транспортом</h2>
                    <p>Управление автомобилями и их водителями</p>
                    
                    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <button class="btn"><i class="fas fa-plus"></i> Добавить транспорт</button>
                        <button class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Обновить GPS</button>
                        <button class="btn btn-secondary"><i class="fas fa-map-marked-alt"></i> Показать на карте</button>
                    </div>
                    
                    <div class="table-container">
                        <?php
                        $transport = $conn->query("SELECT t.*, u.First_Name, u.Last_Name 
                                                   FROM transport t 
                                                   LEFT JOIN users u ON t.id_user = u.id_user 
                                                   ORDER BY t.id_transport");
                        ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Гос. номер</th>
                                    <th>Модель</th>
                                    <th>Водитель</th>
                                    <th>Статус</th>
                                    <th>Последний рейс</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($vehicle = $transport->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $vehicle['id_transport']; ?></td>
                                    <td><strong><?php echo $vehicle['Gos_N']; ?></strong></td>
                                    <td><?php echo $vehicle['Model_transport']; ?></td>
                                    <td>
                                        <?php if($vehicle['First_Name']): ?>
                                            <?php echo $vehicle['First_Name'] . ' ' . $vehicle['Last_Name']; ?>
                                        <?php else: ?>
                                            <span style="color: #dc3545;">Не назначен</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Проверяем, есть ли активные заказы у транспорта
                                        $active_order = $conn->query("SELECT COUNT(*) as active FROM complete_orders co 
                                                                     JOIN orders o ON co.id_order = o.id_order 
                                                                     WHERE co.id_transport = {$vehicle['id_transport']} 
                                                                     AND o.status IN (1,2)")->fetch_assoc();
                                        ?>
                                        <span class="badge <?php echo $active_order['active'] > 0 ? 'badge-warning' : 'badge-success'; ?>">
                                            <?php echo $active_order['active'] > 0 ? 'В рейсе' : 'Свободен'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $last_order = $conn->query("SELECT MAX(o.data_Time) as last_time 
                                                                   FROM complete_orders co 
                                                                   JOIN orders o ON co.id_order = o.id_order 
                                                                   WHERE co.id_transport = {$vehicle['id_transport']}")->fetch_assoc();
                                        if($last_order['last_time']):
                                            echo date('d.m.Y H:i', strtotime($last_order['last_time']));
                                        else:
                                            echo 'Нет данных';
                                        endif;
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-small btn-icon" title="Назначить водителя">
                                            <i class="fas fa-user-tie"></i>
                                        </button>
                                        <button class="btn btn-small btn-icon btn-secondary" title="История рейсов">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button class="btn btn-small btn-icon" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                
                <?php elseif($current_page == 'kpi'): ?>
                <section id="kpi" class="admin-section active">
                    <h2><i class="fas fa-chart-line"></i> KPI и метрики</h2>
                    <p>Ключевые показатели эффективности</p>
                    
                    <div class="stat-grid">
                        <div class="stat-card" style="border-top-color: #4CAF50;">
                            <div class="stat-number">85%</div>
                            <div class="stat-label">Выполнение заказов вовремя</div>
                        </div>
                        
                        <div class="stat-card" style="border-top-color: #2196F3;">
                            <div class="stat-number">92%</div>
                            <div class="stat-label">Удовлетворенность клиентов</div>
                        </div>
                        
                        <div class="stat-card" style="border-top-color: #FF9800;">
                            <div class="stat-number">76%</div>
                            <div class="stat-label">Загрузка транспорта</div>
                        </div>
                        
                        <div class="stat-card" style="border-top-color: #9C27B0;">
                            <div class="stat-number">94.5%</div>
                            <div class="stat-label">Точность маршрутов</div>
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 30px 0;">
                        <h3>Эффективность водителей (топ-5)</h3>
                        <div style="margin-top: 20px;">
                            <?php
                            // Пример данных для топ водителей
                            $top_drivers = [
                                ['name' => 'Иван Петров', 'completed' => 145, 'rating' => 4.9],
                                ['name' => 'Алексей Волков', 'completed' => 132, 'rating' => 4.8],
                                ['name' => 'Татьяна Николаева', 'completed' => 128, 'rating' => 4.7],
                                ['name' => 'Ольга Морозова', 'completed' => 121, 'rating' => 4.6],
                                ['name' => 'Михаил Фролов', 'completed' => 118, 'rating' => 4.5]
                            ];
                            
                            foreach($top_drivers as $index => $driver):
                            ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; 
                                        padding: 10px 15px; border-bottom: 1px solid #eee; 
                                        background: <?php echo $index % 2 == 0 ? 'white' : '#f8f9fa'; ?>;">
                                <div>
                                    <strong>#<?php echo $index + 1; ?> <?php echo $driver['name']; ?></strong><br>
                                    <small>Выполнено заказов: <?php echo $driver['completed']; ?></small>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.2rem; font-weight: bold; color: #4CAF50;">
                                        <?php echo $driver['rating']; ?>/5
                                    </div>
                                    <small>Рейтинг</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
                        <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #eee;">
                            <h4>📈 Тенденции заказов</h4>
                            <div style="height: 200px; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #666;">
                                График будет здесь
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #eee;">
                            <h4>📊 Распределение по материалам</h4>
                            <div style="height: 200px; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #666;">
                                Диаграмма будет здесь
                            </div>
                        </div>
                    </div>
                </section>
                
<?php elseif($current_page == 'orders'): ?>
<section id="orders" class="admin-section active">
    <h2><i class="fas fa-clipboard-list"></i> Все заказы</h2>
    <p>Полный список заказов в системе</p>
    
    <!-- Фильтры -->
    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <input type="date" class="form-control" placeholder="С даты" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            <input type="date" class="form-control" placeholder="По дату" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            <select style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                <option>Все статусы</option>
                                <option>Новые</option>
                                <option>В работе</option>
                                <option>Завершенные</option>
            </select>
            <select style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                <option>Все материалы</option>
                                <option>Макулатура</option>
                                <option>Пластик</option>
                                <option>Стекло</option>
            </select>
            <button class="btn">Применить</button>
            <button class="btn btn-secondary">Сбросить</button>
        </div>
    </div>
    
    <div class="table-container">
        <?php
        $all_orders = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                                    FROM orders o 
                                    LEFT JOIN materials m ON o.id_materials = m.id_material 
                                    ORDER BY o.data_Time DESC");
        
        // Определяем статусы для этой секции
        $order_status_badges = [
            1 => ['class' => 'badge-warning', 'text' => 'Новый'],
            2 => ['class' => 'badge-info', 'text' => 'В работе'],
            3 => ['class' => 'badge-success', 'text' => 'Завершен']
        ];
        ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Адрес</th>
                    <th>Материал</th>
                    <th>Объем</th>
                    <th>Статус</th>
                    <th>Водитель</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = $all_orders->fetch_assoc()): 
                    // Получаем водителя для заказа
                    $driver_query = $conn->query("SELECT u.First_Name, u.Last_Name 
                                                 FROM complete_orders co 
                                                 JOIN transport t ON co.id_transport = t.id_transport 
                                                 JOIN users u ON t.id_user = u.id_user 
                                                 WHERE co.id_order = {$order['id_order']} LIMIT 1");
                    $driver = $driver_query->num_rows > 0 ? $driver_query->fetch_assoc() : null;
                ?>
                <tr>
                    <td><strong>#<?php echo $order['id_order']; ?></strong></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></td>
                    <td title="<?php echo htmlspecialchars($order['addres']); ?>">
                        <?php echo substr($order['addres'], 0, 30) . (strlen($order['addres']) > 30 ? '...' : ''); ?>
                    </td>
                    <td><?php echo $order['material_name']; ?></td>
                    <td><?php echo $order['volume']; ?> кг</td>
                    <td>
                        <?php 
                        $status = $order['status'] ?? 1;
                        ?>
                        <span class="badge <?php echo $order_status_badges[$status]['class']; ?>">
                            <?php echo $order_status_badges[$status]['text']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if($driver): ?>
                            <?php echo $driver['First_Name'] . ' ' . $driver['Last_Name']; ?>
                        <?php else: ?>
                            <small style="color: #999;">Не назначен</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-small btn-icon" title="Просмотреть">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-small btn-icon" title="Назначить водителя">
                            <i class="fas fa-truck"></i>
                        </button>
                        <button class="btn btn-small btn-icon btn-secondary" title="Подробности">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>
                
                <?php elseif($current_page == 'reports'): ?>
                <section id="reports" class="admin-section active">
                    <h2><i class="fas fa-file-alt"></i> Отчеты</h2>
                    <p>Генерация и просмотр отчетов</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
                        <div style="background: white; border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center;">
                            <div style="font-size: 3rem; color: #4CAF50; margin: 10px 0;">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <h3>Финансовый отчет</h3>
                            <p>Выручка, расходы, прибыль</p>
                            <button class="btn" style="width: 100%;">Сформировать</button>
                        </div>
                        
                        <div style="background: white; border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center;">
                            <div style="font-size: 3rem; color: #2196F3; margin: 10px 0;">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h3>Анализ эффективности</h3>
                            <p>KPI, метрики, показатели</p>
                            <button class="btn" style="width: 100%;">Сформировать</button>
                        </div>
                        
                        <div style="background: white; border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center;">
                            <div style="font-size: 3rem; color: #FF9800; margin: 10px 0;">
                                <i class="fas fa-truck"></i>
                            </div>
                            <h3>Отчет по транспорту</h3>
                            <p>Пробег, расход, ремонты</p>
                            <button class="btn" style="width: 100%;">Сформировать</button>
                        </div>
                        
                        <div style="background: white; border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center;">
                            <div style="font-size: 3rem; color: #9C27B0; margin: 10px 0;">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>Отчет по персоналу</h3>
                            <p>Рабочее время, эффективность</p>
                            <button class="btn" style="width: 100%;">Сформировать</button>
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                        <h3>История отчетов</h3>
                        <div style="margin-top: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd;">
                                <div>
                                    <strong>Финансовый отчет за январь 2026</strong><br>
                                    <small>Сформирован: 01.02.2026 14:30</small>
                                </div>
                                <div>
                                    <button class="btn btn-small btn-icon">
                                        <i class="fas fa-download"></i> Скачать
                                    </button>
                                    <button class="btn btn-small btn-icon btn-secondary">
                                        <i class="fas fa-print"></i> Печать
                                    </button>
                                </div>
                            </div>
                            <!-- Добавьте больше отчетов по аналогии -->
                        </div>
                    </div>
                </section>
                
                <?php elseif($current_page == 'settings'): ?>
                <section id="settings" class="admin-section active">
                    <h2><i class="fas fa-cog"></i> Настройки системы</h2>
                    <p>Настройки параметров системы</p>
                    
                    <div style="display: grid; grid-template-columns: 1fr; gap: 20px; max-width: 800px;">
                        <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #eee;">
                            <h3><i class="fas fa-sliders-h"></i> Основные настройки</h3>
                            <div style="margin-top: 15px;">
                                <label style="display: block; margin-bottom: 10px;">
                                    <input type="checkbox" checked> Уведомления по email
                                </label>
                                <label style="display: block; margin-bottom: 10px;">
                                    <input type="checkbox" checked> Уведомления в Telegram
                                </label>
                                <label style="display: block; margin-bottom: 10px;">
                                    <input type="checkbox"> Автоматическое планирование маршрутов
                                </label>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #eee;">
                            <h3><i class="fas fa-map-marked-alt"></i> Настройки карт</h3>
                            <div style="margin-top: 15px;">
                                <div style="margin-bottom: 15px;">
                                    <label>API ключ Яндекс.Карт:</label>
                                    <input type="text" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;" placeholder="Введите API ключ">
                                </div>
                                <div>
                                    <label>Провайдер карт:</label>
                                    <select style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;">
                                        <option>Яндекс.Карты</option>
                                        <option>Google Maps</option>
                                        <option>OpenStreetMap</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid #eee;">
                            <h3><i class="fas fa-coins"></i> Тарифы и цены</h3>
                            <p style="color: #666; margin-top: 10px;">Настройка тарифов на вывоз материалов</p>
                            <button class="btn" style="margin-top: 10px;">
                                <i class="fas fa-edit"></i> Редактировать тарифы
                            </button>
                        </div>
                        
                        <div style="text-align: center; margin-top: 30px;">
                            <button class="btn" style="padding: 12px 40px;">
                                <i class="fas fa-save"></i> Сохранить все настройки
                            </button>
                        </div>
                    </div>
                </section>
                
                <?php endif; ?>
            </main>
        </div>
        
        <footer style="margin-top: 30px;">
            <p>&copy; 2026 Green Mile. Административная панель. 
               <span style="color: #666; float: right;">
                   <?php echo date('d.m.Y H:i'); ?>
               </span>
            </p>
        </footer>
    </div>
    
    <script>
        // Простая навигация между разделами
        document.addEventListener('DOMContentLoaded', function() {
            // Добавляем активный класс к текущему разделу
            const links = document.querySelectorAll('.admin-nav a');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    if(this.getAttribute('href').startsWith('?')) {
                        // Страница перезагрузится с параметром, ничего не делаем
                    } else {
                        e.preventDefault();
                        // Здесь можно добавить AJAX-загрузку контента
                    }
                });
            });
            
            // Имитация загрузки данных
            console.log('Админ-панель загружена. Текущий раздел: <?php echo $current_page; ?>');
        });
    </script>
</body>
</html>