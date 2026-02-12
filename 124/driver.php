<?php
// driver.php
include_once 'api/config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) {
    header("Location: login.php");
    exit();
}

// Получаем информацию о водителе
$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user = $user_query->fetch_assoc();

// Получаем транспорт водителя
$transport_query = $conn->query("SELECT * FROM transport WHERE id_user = $user_id");
$transport = $transport_query->num_rows > 0 ? $transport_query->fetch_assoc() : null;

// Получаем заказы на сегодня
$today = date('Y-m-d');
$orders_query = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                              FROM orders o 
                              LEFT JOIN materials m ON o.id_materials = m.id_material 
                              LEFT JOIN complete_orders co ON o.id_order = co.id_order 
                              LEFT JOIN transport t ON co.id_transport = t.id_transport 
                              WHERE t.id_user = $user_id 
                              AND DATE(o.data_Time) = '$today' 
                              AND o.status IN (1,2) 
                              ORDER BY o.data_Time");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель водителя - Green Mile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .driver-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .driver-header {
            background: linear-gradient(135deg, #FF9800, #FF5722);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .driver-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .driver-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            color: #FF9800;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .transport-info {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #FF9800;
        }
        
        .orders-list {
            margin: 20px 0;
        }
        
        .order-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .order-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .order-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .status-planned { background: #fff3cd; color: #856404; }
        .status-in-progress { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: bold;
            color: #333;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-driver {
            flex: 1;
            min-width: 120px;
            padding: 12px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-start { background: #4CAF50; }
        .btn-arrived { background: #2196F3; }
        .btn-loaded { background: #FF9800; }
        .btn-completed { background: #9C27B0; }
        .btn-problem { background: #f44336; }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            color: #666;
        }
        
        .no-orders i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="driver-container">
            <!-- Шапка водителя -->
            <div class="driver-header">
                <div class="driver-info">
                    <div class="driver-avatar">
                        <?php 
                        $initials = substr($user['First_Name'], 0, 1) . substr($user['Last_Name'], 0, 1);
                        echo $initials;
                        ?>
                    </div>
                    <div>
                        <h2 style="margin: 0;"><?php echo $user['First_Name'] . ' ' . $user['Last_Name']; ?></h2>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">Водитель</p>
                    </div>
                </div>
                <div>
                    <a href="dashboard.php" class="btn" style="background: white; color: #FF9800;">Общий кабинет</a>
                    <a href="api/logout.php" class="btn btn-secondary">Выйти</a>
                </div>
            </div>
            
            <!-- Информация о транспорте -->
            <?php if($transport): ?>
            <div class="transport-info">
                <h3><i class="fas fa-truck"></i> Ваш транспорт</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
                    <div>
                        <div class="detail-label">Гос. номер</div>
                        <div class="detail-value"><?php echo $transport['Gos_N']; ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Модель</div>
                        <div class="detail-value"><?php echo $transport['Model_transport']; ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Статус</div>
                        <div class="detail-value" style="color: #4CAF50;">Готов к работе</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Заказы на сегодня -->
            <div class="orders-list">
                <h2><i class="fas fa-clipboard-list"></i> Заказы на сегодня</h2>
                <p style="color: #666; margin-bottom: 20px;"><?php echo date('d.m.Y'); ?></p>
                
                <?php if($orders_query->num_rows > 0): ?>
                    <?php while($order = $orders_query->fetch_assoc()): 
                        // Определяем текущий статус для отображения кнопок
                        $current_status = $order['status'];
                    ?>
                    <div class="order-item">
                        <div class="order-header">
                            <h3 style="margin: 0;">Заказ #<?php echo $order['id_order']; ?></h3>
                            <span class="order-status 
                                <?php echo $current_status == 1 ? 'status-planned' : 
                                       ($current_status == 2 ? 'status-in-progress' : 'status-completed'); ?>">
                                <?php echo $current_status == 1 ? 'Запланирован' : 
                                       ($current_status == 2 ? 'В пути' : 'Завершен'); ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-item">
                                <div class="detail-label">Материал</div>
                                <div class="detail-value"><?php echo $order['material_name']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Объем</div>
                                <div class="detail-value"><?php echo $order['volume']; ?> кг</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Адрес</div>
                                <div class="detail-value"><?php echo $order['addres']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Время</div>
                                <div class="detail-value"><?php echo date('H:i', strtotime($order['data_Time'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <?php if($current_status == 1): ?>
                                <button class="btn btn-driver btn-start">
                                    <i class="fas fa-play"></i> Начать маршрут
                                </button>
                            <?php endif; ?>
                            
                            <?php if($current_status == 2): ?>
                                <button class="btn btn-driver btn-arrived">
                                    <i class="fas fa-map-marker-alt"></i> Прибыл на место
                                </button>
                                <button class="btn btn-driver btn-loaded">
                                    <i class="fas fa-box"></i> Загружено
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-driver btn-completed">
                                <i class="fas fa-check-circle"></i> Завершить
                            </button>
                            
                            <button class="btn btn-driver btn-problem">
                                <i class="fas fa-exclamation-triangle"></i> Проблема
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>На сегодня заказов нет</h3>
                        <p>Ожидайте назначения от диспетчера</p>
                        <p style="margin-top: 20px;">
                            <button class="btn" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Обновить
                            </button>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Быстрые действия -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-top: 30px;">
                <h3><i class="fas fa-bolt"></i> Быстрые действия</h3>
                <div style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                    <button class="btn" style="flex: 1;">
                        <i class="fas fa-map-marked-alt"></i> Маршрут на карте
                    </button>
                    <button class="btn btn-secondary" style="flex: 1;">
                        <i class="fas fa-camera"></i> Фотоотчет
                    </button>
                    <button class="btn" style="flex: 1; background: #2196F3;">
                        <i class="fas fa-comment"></i> Чат с диспетчером
                    </button>
                </div>
            </div>
        </div>
        
        <footer style="margin-top: 30px; text-align: center;">
            <p>&copy; 2026 Green Mile. Панель водителя</p>
            <p style="color: #666; font-size: 0.9rem;">
                Сессия: <?php echo $_SESSION['login']; ?> | 
                Время: <?php echo date('H:i:s'); ?>
            </p>
        </footer>
    </div>
    
    <script>
        // Простые действия для кнопок водителя
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn-driver');
            
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    const buttonType = this.classList[1]; // btn-start, btn-arrived и т.д.
                    const orderId = this.closest('.order-item').querySelector('h3').textContent.replace('Заказ #', '');
                    
                    // В реальном приложении здесь был бы AJAX-запрос
                    alert('Действие: ' + this.textContent.trim() + '\nЗаказ #' + orderId);
                    
                    // Визуальная обратная связь
                    this.style.opacity = '0.7';
                    setTimeout(() => {
                        this.style.opacity = '1';
                    }, 300);
                });
            });
        });
    </script>
</body>
</html>