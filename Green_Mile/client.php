<?php
require_once 'api/config.php';

$is_authorized = isset($_SESSION['user_id']) && $_SESSION['role'] == 4;
$client_name = $is_authorized ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : 'Гость';
$materials = $conn->query("SELECT * FROM materials ORDER BY Name_Mat");
$orders_history = [];
if ($is_authorized) {
    $user_id = $_SESSION['user_id'];
    $orders_result = $conn->query(" SELECT o.*, m.Name_Mat as material_name, m.rate as material_rate FROM orders o LEFT JOIN materials m ON o.id_materials = m.id_material WHERE o.id_client = $user_id AND o.id_client IS NOT NULL ORDER BY o.data_Time DESC
    ");
    
    while ($row = $orders_result->fetch_assoc()) {
        $orders_history[] = $row;
    }
}
function getOrderStatusInfo($status_id) {
    $statuses = [
        1 => ['text' => 'Новый', 'class' => 'badge-new', 'icon' => 'fa-clock'],
        2 => ['text' => 'Подтвержден', 'class' => 'badge-progress', 'icon' => 'fa-check-circle'],
        3 => ['text' => 'Назначен водитель', 'class' => 'badge-progress', 'icon' => 'fa-truck'],
        4 => ['text' => 'Выполняется', 'class' => 'badge-progress', 'icon' => 'fa-spinner'],
        5 => ['text' => 'Завершен', 'class' => 'badge-completed', 'icon' => 'fa-check-double']
    ];
    return $statuses[$status_id] ?? ['text' => 'Неизвестно', 'class' => 'badge-secondary', 'icon' => 'fa-question'];
}

function formatOrderDate($datetime) {
    if (!$datetime) return 'Дата не указана';
    $timestamp = strtotime($datetime);
    return date('d.m.Y H:i', $timestamp);
}

function calculateOrderCost($order) {
    if (empty($order['material_rate'])) return 0;
    
    preg_match('/(\d+)/', $order['material_rate'], $matches);
    $rate = isset($matches[1]) ? (float)$matches[1] : 0;
    $volume = (float)($order['volume'] ?? 0);
    
    if (strpos($order['material_rate'], 'за шт') !== false) {
        return $rate * $volume * 0.5;
    }
    
    return $rate * $volume;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Mile - Заказ вывоза вторсырья</title>
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

        .header {
            background: var(--gradient-mix);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .logo h1 {
            color: white;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 2rem;
        }

        .logo p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
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

        .btn-outline {
            background: transparent;
            border: 2px solid white;
            color: white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary-green);
        }

        .btn-primary {
            background: var(--secondary-green);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-green);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .order-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .order-form h2 {
            color: var(--primary-green);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--light-green);
            padding-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-group label i {
            color: var(--secondary-green);
            width: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--secondary-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        .form-control.error {
            border-color: #f44336;
            background-color: #ffebee;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .info-card h3 {
            color: var(--primary-green);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .price-list {
            list-style: none;
        }

        .price-list li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }

        .price-list li:last-child {
            border-bottom: none;
        }

        .material-name {
            color: #333;
            font-weight: 500;
        }

        .material-price {
            color: var(--secondary-green);
            font-weight: bold;
        }

        .history-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .history-section h2 {
            color: var(--primary-green);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--light-green);
            padding-bottom: 15px;
        }

        .history-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 20px;
            border: 2px solid var(--secondary-green);
            background: transparent;
            color: var(--secondary-green);
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .filter-btn.active {
            background: var(--secondary-green);
            color: white;
        }

        .filter-btn:hover {
            background: var(--secondary-green);
            color: white;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .order-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #e0e0e0;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .order-id {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-green);
        }

        .order-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-new { background: #fff3cd; color: #856404; }
        .badge-progress { background: #cce5ff; color: #004085; }
        .badge-completed { background: #d4edda; color: #155724; }

        .order-body {
            margin-bottom: 15px;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
            font-size: 0.95rem;
        }

        .info-item i {
            width: 20px;
            color: var(--secondary-green);
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }

        .order-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-green);
        }

        .order-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn.repeat {
            background: var(--secondary-green);
            color: white;
        }

        .action-btn.download {
            background: #17a2b8;
            color: white;
        }

        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .empty-history {
            text-align: center;
            padding: 50px;
            color: #999;
        }

        .empty-history i {
            font-size: 4rem;
            margin-bottom: 15px;
            color: #ccc;
        }

        .empty-history p {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .tracking-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .tracking-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tracking-form input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .order-status {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            display: none;
        }

        .order-status.show {
            display: block;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .status-timeline {
            margin-top: 20px;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .status-icon.completed {
            background: var(--light-green);
            color: var(--primary-green);
        }

        .status-icon.active {
            background: var(--secondary-green);
            color: white;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            display: none;
            z-index: 1000;
            animation: slideIn 0.3s;
        }

        .notification.show {
            display: block;
        }

        .notification.success { background: #4CAF50; }
        .notification.error { background: #f44336; }
        .notification.info { background: #2196F3; }

        .footer {
            text-align: center;
            padding: 30px;
            color: #666;
            border-top: 1px solid #ddd;
            margin-top: 50px;
        }

        .validation-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .validation-hint i {
            color: var(--secondary-green);
        }

        .validation-hint.error {
            color: #f44336;
        }

        .validation-hint.error i {
            color: #f44336;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .user-menu {
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .tracking-form {
                flex-direction: column;
            }

            .orders-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <h1><i class="fas fa-recycle"></i> Green Mile</h1>
                <p>Экологичный вывоз вторсырья</p>
            </div>
            <div class="user-menu">
                <div class="user-badge">
                    <i class="fas <?php echo $is_authorized ? 'fa-user-check' : 'fa-user'; ?>"></i>
                    <span><?php echo htmlspecialchars($client_name); ?></span>
                </div>
                <?php if($is_authorized): ?>
                    <a href="api/logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                <?php else: ?>
                    <a href="api/logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="main-content">
            <div class="order-form">
                <h2><i class="fas fa-truck"></i> Заказать вывоз</h2>
                <form id="orderForm" onsubmit="event.preventDefault(); submitOrder();">
                    <div class="form-group">
                        <label><i class="fas fa-box"></i> Тип сырья</label>
                        <select id="material" class="form-control" required>
                            <option value="">Выберите тип сырья</option>
                            <?php 
                            $materials->data_seek(0);
                            while($mat = $materials->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $mat['id_material']; ?>" 
                                    data-rate="<?php echo $mat['rate']; ?>">
                                <?php echo htmlspecialchars($mat['Name_Mat']); ?> - <?php echo $mat['rate']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-weight"></i> Объем (кг)</label>
                            <input type="number" id="volume" class="form-control" min="1" max="9999" maxlength="4" required>
                            <div class="validation-hint">
                                <i class="fas fa-info-circle"></i>
                                Максимум 4 цифры (до 9999 кг)
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-ruble-sign"></i> Примерная стоимость</label>
                            <input type="text" id="estimated_cost" class="form-control" readonly value="0 ₽">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Дата</label>
                            <input type="date" id="date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Время</label>
                            <input type="time" id="time" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Адрес</label>
                        <input type="text" id="address" class="form-control" placeholder="Введите адрес" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Контактный телефон</label>
                        <input type="tel" id="phone" class="form-control" placeholder="+7 (___) ___-__-__" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email для уведомлений</label>
                        <input type="email" id="email" class="form-control" placeholder="example@mail.ru" 
                               value="<?php echo $is_authorized ? htmlspecialchars($_SESSION['login']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Комментарий</label>
                        <textarea id="comments" class="form-control" rows="3" placeholder="Дополнительная информация..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem;">
                        <i class="fas fa-paper-plane"></i> Оформить заказ
                    </button>
                </form>
            </div>
            <div>
                <div class="info-card" style="margin-bottom: 30px;">
                    <h3><i class="fas fa-tags"></i> Наши цены</h3>
                    <ul class="price-list">
                        <?php 
                        $materials->data_seek(0);
                        while($mat = $materials->fetch_assoc()): 
                        ?>
                        <li>
                            <span class="material-name"><?php echo htmlspecialchars($mat['Name_Mat']); ?></span>
                            <span class="material-price"><?php echo $mat['rate']; ?></span>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <div class="info-card">
                    <h3><i class="fas fa-star"></i> Почему мы?</h3>
                    <ul style="list-style: none;">
                        <li style="margin: 15px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                            Быстрый вывоз в течение 24 часов
                        </li>
                        <li style="margin: 15px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                            Экологичная утилизация
                        </li>
                        <li style="margin: 15px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                            Прозрачные цены без скрытых платежей
                        </li>
                        <li style="margin: 15px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                            Электронные акты и документы
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php if($is_authorized): ?>
        <div class="history-section">
            <h2><i class="fas fa-history"></i> История моих заказов</h2>
            
            <?php if (!empty($orders_history)): ?>
                <div class="history-filters">
                    <button class="filter-btn active" onclick="filterOrders('all')">Все</button>
                    <button class="filter-btn" onclick="filterOrders('active')">Активные</button>
                    <button class="filter-btn" onclick="filterOrders('completed')">Завершенные</button>
                </div>

                <div class="orders-grid" id="ordersGrid">
                    <?php foreach ($orders_history as $order): 
                        $status = getOrderStatusInfo($order['status']);
                        $cost = calculateOrderCost($order);
                    ?>
                    <div class="order-card" data-status="<?php echo $order['status']; ?>">
                        <div class="order-header">
                            <span class="order-id">Заказ #<?php echo $order['id_order']; ?></span>
                            <span class="order-badge <?php echo $status['class']; ?>">
                                <i class="fas <?php echo $status['icon']; ?>"></i>
                                <?php echo $status['text']; ?>
                            </span>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-info">
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo formatOrderDate($order['data_Time']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-box"></i>
                                    <span><?php echo htmlspecialchars($order['material_name'] ?? 'Не указан'); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-weight"></i>
                                    <span><?php echo $order['volume']; ?> кг</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($order['addres'] ?? 'Адрес не указан'); ?></span>
                                </div>
                                <?php if (!empty($order['comments'])): ?>
                                <div class="info-item">
                                    <i class="fas fa-comment"></i>
                                    <span><?php echo htmlspecialchars($order['comments']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <span class="order-price">≈ <?php echo number_format($cost, 0, '.', ' '); ?> ₽</span>
                            <div class="order-actions">
                                <button class="action-btn repeat" onclick="repeatOrder(<?php echo $order['id_order']; ?>)">
                                    <i class="fas fa-redo"></i> Повторить
                                </button>
                                <?php if ($order['status'] == 5): ?>
                                <button class="action-btn download" onclick="downloadAct(<?php echo $order['id_order']; ?>)">
                                    <i class="fas fa-file-pdf"></i> Акт
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-history">
                    <i class="fas fa-box-open"></i>
                    <p>У вас пока нет заказов</p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="tracking-section">
            <h2><i class="fas fa-search"></i> Отследить заказ</h2>
            <div class="tracking-form">
                <input type="text" id="trackingNumber" placeholder="Введите номер заказа">
                <button class="btn btn-primary" onclick="trackOrder()">
                    <i class="fas fa-search"></i> Найти
                </button>
            </div>
            <div id="orderStatus" class="order-status">
                <h3 style="color: var(--primary-green);">Заказ #<span id="orderId"></span></h3>
                <div class="status-timeline" id="statusTimeline"></div>
            </div>
        </div>
        <div class="footer">
            <p>&copy; 2026 Green Mile. Система экологичного вывоза вторсырья</p>
            <p style="font-size: 0.85rem; margin-top: 10px;">
                <i class="fas fa-phone"></i> 8-800-123-45-67 | 
                <i class="fas fa-envelope"></i> info@greenmile.ru
            </p>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <script>
        document.getElementById('material').addEventListener('change', calculateCost);
        document.getElementById('volume').addEventListener('input', function(e) {
            if (this.value.length > 4) {
                this.value = this.value.slice(0, 4);
                showNotification('Максимум 4 цифры (до 9999 кг)', 'warning');
            }
            calculateCost();
        });

        function calculateCost() {
            const material = document.getElementById('material');
            const volume = document.getElementById('volume').value;
            const costField = document.getElementById('estimated_cost');

            if (material.value && volume) {
                const selectedOption = material.options[material.selectedIndex];
                const rateText = selectedOption.getAttribute('data-rate');
                const rate = parseInt(rateText) || 0;
                
                const cost = rate * volume;
                costField.value = cost.toLocaleString() + ' ₽';
            } else {
                costField.value = '0 ₽';
            }
        }
        function submitOrder() {
            const formData = {
                material: document.getElementById('material').value,
                volume: document.getElementById('volume').value,
                date: document.getElementById('date').value,
                time: document.getElementById('time').value,
                address: document.getElementById('address').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                comments: document.getElementById('comments').value
            };

            if (!formData.material || !formData.volume || !formData.date || !formData.time || !formData.address || !formData.phone) {
                showNotification('Заполните все обязательные поля', 'error');
                return;
            }

            // Проверка на количество цифр в объеме
            if (formData.volume.toString().length > 4) {
                showNotification('Объем не может быть больше 4 цифр (до 9999 кг)', 'error');
                return;
            }

            // Определяем, авторизован ли пользователь
            const isAuthorized = <?php echo $is_authorized ? 'true' : 'false'; ?>;
            const userId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
            
            // Создаем FormData для отправки
            const submitData = new FormData();
            submitData.append('material', formData.material);
            submitData.append('volume', formData.volume);
            submitData.append('date', formData.date);
            submitData.append('time', formData.time);
            submitData.append('address', formData.address);
            submitData.append('phone', formData.phone);
            submitData.append('email', formData.email);
            submitData.append('comments', formData.comments);
            
            // Если пользователь авторизован, добавляем client_id = user_id
            if (isAuthorized) {
                submitData.append('client_id', userId);
            }
            fetch('api/create_order.php', {
                method: 'POST',
                body: submitData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Заказ успешно создан! Номер заказа: ' + data.order_id, 'success');
                    document.getElementById('orderForm').reset();
                    document.getElementById('estimated_cost').value = '0 ₽';
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('date').value = today;
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification('Ошибка при создании заказа: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ошибка при создании заказа', 'error');
            });
        }

        function filterOrders(filter) {
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const cards = document.querySelectorAll('.order-card');
            
            cards.forEach(card => {
                const status = parseInt(card.dataset.status);
                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter === 'active') {
                    card.style.display = status < 5 ? 'block' : 'none';
                } else if (filter === 'completed') {
                    card.style.display = status === 5 ? 'block' : 'none';
                }
            });
        }
        function trackOrder() {
            const trackingNumber = document.getElementById('trackingNumber').value.trim();
            
            if (!trackingNumber) {
                showNotification('Введите номер заказа', 'error');
                return;
            }

            document.getElementById('orderId').textContent = trackingNumber;
            document.getElementById('orderStatus').classList.add('show');

            const timeline = document.getElementById('statusTimeline');
            const statuses = [
                { title: 'Заказ создан', date: 'Сегодня, 10:30', active: true },
                { title: 'Подтвержден диспетчером', date: 'Сегодня, 10:45', active: true },
                { title: 'Назначен водитель', date: 'Ожидание', active: false },
                { title: 'Выезд на место', date: 'Ожидание', active: false },
                { title: 'Выполнен', date: 'Ожидание', active: false }
            ];

            timeline.innerHTML = statuses.map(status => `
                <div class="status-item">
                    <div class="status-icon ${status.active ? 'active' : ''}">
                        <i class="fas ${status.active ? 'fa-check' : 'fa-clock'}"></i>
                    </div>
                    <div class="status-info">
                        <div class="status-title">${status.title}</div>
                        <div class="status-date">${status.date}</div>
                    </div>
                </div>
            `).join('');
        }
        function repeatOrder(orderId) {
            showNotification('Заказ #' + orderId + ' будет повторен', 'info');
        }
        function downloadAct(orderId) {
            showNotification('Акт для заказа #' + orderId + ' будет доступен для скачивания', 'info');
        }

        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type} show`;
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date').value = today;
    document.getElementById('date').min = today;
    
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('time').value = hours + ':' + minutes;

    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            const input = e.target;
            const cursorPos = input.selectionStart;
            const oldValue = input.value;
            let numbers = input.value.replace(/\D/g, '');
            if (numbers.length > 11) {
                numbers = numbers.slice(0, 11);
            }
            let formatted = numbers.replace(/^(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})$/, 
                '+7 ($2) $3-$4-$5')
                .replace(/^(\d{1})(\d{3})(\d{3})(\d{2})$/, '+7 ($2) $3-$4')
                .replace(/^(\d{1})(\d{3})(\d{3})$/, '+7 ($2) $3')
                .replace(/^(\d{1})(\d{3})$/, '+7 ($2')
                .replace(/^(\d{1})$/, '+7');
            
            input.value = formatted;
            if (cursorPos < oldValue.length) {
                if (oldValue.length > formatted.length) {
                    input.setSelectionRange(cursorPos, cursorPos);
                } else if (oldValue.length < formatted.length) {
                    input.setSelectionRange(cursorPos + (formatted.length - oldValue.length), 
                                           cursorPos + (formatted.length - oldValue.length));
                }
            }
        });
    }
});
    </script>
</body>
</html>