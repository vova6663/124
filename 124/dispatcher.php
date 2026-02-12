<?php
// dispatcher.php
include_once 'api/config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: login.php");
    exit();
}

// Получаем информацию о диспетчере
$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user = $user_query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель диспетчера - Green Mile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dispatcher-container {
            display: flex;
            min-height: calc(100vh - 100px);
            margin-top: 20px;
        }
        
        .dispatcher-sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            border-radius: 10px;
            padding: 20px 0;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .dispatcher-content {
            flex: 1;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            color: #ecf0f1;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .dispatcher-nav a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #2196F3;
            color: white;
        }
        
        .dispatcher-nav a.active {
            background: rgba(33, 150, 243, 0.2);
            border-left-color: #2196F3;
            color: #2196F3;
            font-weight: bold;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .order-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .order-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .status-new { background: #fff3cd; color: #856404; }
        .status-in-progress { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        
        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1>Панель диспетчера</h1>
            <div class="user-info">
                <span><?php echo $user['First_Name'] . ' ' . $user['Last_Name']; ?></span>
                <span class="role">(Диспетчер)</span>
                <a href="dashboard.php" class="btn btn-small">Общий кабинет</a>
                <a href="api/logout.php" class="btn btn-small btn-secondary">Выйти</a>
            </div>
        </header>
        
        <div class="dispatcher-container">
            <!-- Боковая панель -->
            <nav class="dispatcher-sidebar">
                <div class="dispatcher-nav">
                    <ul>
                        <li>
                            <a href="#" class="active">
                                <i class="fas fa-tasks"></i> Управление заказами
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="fas fa-route"></i> Планирование маршрутов
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="fas fa-truck"></i> Назначение водителям
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="fas fa-map-marked-alt"></i> Карта и мониторинг
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="fas fa-comments"></i> Чат с водителями
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="fas fa-chart-bar"></i> Отчеты
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Основной контент -->
            <main class="dispatcher-content">
                <h2><i class="fas fa-tasks"></i> Управление заказами</h2>
                <p>Просмотр, подтверждение и назначение заказов</p>
                
                <!-- Фильтры -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 20px 0;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <span>Фильтры:</span>
                        <select style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                            <option>Все статусы</option>
                            <option>Новые</option>
                            <option>Подтвержденные</option>
                            <option>В работе</option>
                        </select>
                        <input type="date" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                        <button class="btn btn-small">Применить</button>
                        <button class="btn btn-small btn-secondary">Сбросить</button>
                    </div>
                </div>
                
                <!-- Статистика -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
                    <?php
                    // Статистика для диспетчера
                    $new_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 1")->fetch_assoc()['total'];
                    $confirmed_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 2")->fetch_assoc()['total'];
                    $today_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE DATE(data_Time) = CURDATE()")->fetch_assoc()['total'];
                    $active_drivers = $conn->query("SELECT COUNT(DISTINCT id_user) as total FROM transport WHERE id_user IS NOT NULL")->fetch_assoc()['total'];
                    ?>
                    
                    <div style="background: white; padding: 15px; border-radius: 10px; text-align: center; border-top: 4px solid #FF9800;">
                        <div style="font-size: 2rem; font-weight: bold; color: #FF9800;"><?php echo $new_orders; ?></div>
                        <div style="color: #666; font-size: 0.9rem;">Новых заказов</div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 10px; text-align: center; border-top: 4px solid #2196F3;">
                        <div style="font-size: 2rem; font-weight: bold; color: #2196F3;"><?php echo $confirmed_orders; ?></div>
                        <div style="color: #666; font-size: 0.9rem;">Подтверждено</div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 10px; text-align: center; border-top: 4px solid #4CAF50;">
                        <div style="font-size: 2rem; font-weight: bold; color: #4CAF50;"><?php echo $today_orders; ?></div>
                        <div style="color: #666; font-size: 0.9rem;">Сегодня</div>
                    </div>
                    
                    <div style="background: white; padding: 15px; border-radius: 10px; text-align: center; border-top: 4px solid #9C27B0;">
                        <div style="font-size: 2rem; font-weight: bold; color: #9C27B0;"><?php echo $active_drivers; ?></div>
                        <div style="color: #666; font-size: 0.9rem;">Активных водителей</div>
                    </div>
                </div>
                
                <!-- Список заказов -->
                <h3>Последние заказы</h3>
                <div class="orders-grid">
                    <?php
                    $orders = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                                            FROM orders o 
                                            LEFT JOIN materials m ON o.id_materials = m.id_material 
                                            ORDER BY o.data_Time DESC 
                                            LIMIT 6");
                    
                    while($order = $orders->fetch_assoc()):
                        // Определяем статус
                        $status_class = '';
                        $status_text = '';
                        switch($order['status']) {
                            case 1: 
                                $status_class = 'status-new';
                                $status_text = 'Новый';
                                break;
                            case 2: 
                                $status_class = 'status-in-progress';
                                $status_text = 'В работе';
                                break;
                            case 3: 
                                $status_class = 'status-completed';
                                $status_text = 'Завершен';
                                break;
                        }
                    ?>
                    <div class="order-card">
                        <div class="order-status <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </div>
                        <h4>Заказ #<?php echo $order['id_order']; ?></h4>
                        <p><strong>Материал:</strong> <?php echo $order['material_name']; ?></p>
                        <p><strong>Объем:</strong> <?php echo $order['volume']; ?> кг</p>
                        <p><strong>Адрес:</strong> <?php echo substr($order['addres'], 0, 40) . '...'; ?></p>
                        <p><strong>Дата:</strong> <?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></p>
                        
                        <div class="order-actions">
                            <?php if($order['status'] == 1): ?>
                                <button class="btn btn-small" style="background: #4CAF50;">
                                    <i class="fas fa-check"></i> Подтвердить
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-small btn-secondary">
                                <i class="fas fa-eye"></i> Просмотреть
                            </button>
                            
                            <button class="btn btn-small">
                                <i class="fas fa-truck"></i> Назначить
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Кнопка для просмотра всех заказов -->
                <div style="text-align: center; margin-top: 30px;">
                    <a href="orders.php?role=dispatcher" class="btn">
                        <i class="fas fa-list"></i> Просмотреть все заказы
                    </a>
                </div>
            </main>
        </div>
        
        <footer style="margin-top: 30px;">
            <p>&copy; 2026 Green Mile. Панель диспетчера. 
               <span style="color: #666; float: right;">
                   Логин: <?php echo $_SESSION['login']; ?>
               </span>
            </p>
        </footer>
    </div>
</body>
</html>