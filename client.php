<?php
// client.php
require_once 'api/config.php';

// Определяем тип клиента (роль 4 - это клиент)
$is_authorized = isset($_SESSION['user_id']) && $_SESSION['role'] == 4;
$client_name = $is_authorized ? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : 'Гость';

// Получаем список материалов для формы
$materials = $conn->query("SELECT * FROM materials ORDER BY Name_Mat");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Mile - Заказ вывоза вторсырья</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Все стили из предыдущей версии */
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Шапка */
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

        .user-badge i {
            font-size: 1rem;
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

        /* Основной контент */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        /* Форма заказа */
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Карточка информации */
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

        /* Отслеживание заказа */
        .tracking-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .tracking-section h2 {
            color: var(--primary-green);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .status-info {
            flex: 1;
        }

        .status-title {
            font-weight: bold;
            color: #333;
        }

        .status-date {
            font-size: 0.85rem;
            color: #999;
        }

        /* История заказов */
        .history-section {
            margin-top: 30px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .history-table th {
            background: var(--gradient-green);
            color: white;
            padding: 12px;
            text-align: left;
        }

        .history-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .history-table tr:hover {
            background: var(--light-blue);
        }

        .order-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .badge-new { background: #fff3cd; color: #856404; }
        .badge-progress { background: #cce5ff; color: #004085; }
        .badge-completed { background: #d4edda; color: #155724; }

        /* Уведомления */
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

        .notification.success {
            background: #4CAF50;
        }

        .notification.error {
            background: #f44336;
        }

        .notification.info {
            background: #2196F3;
        }

        /* Футер */
        .footer {
            text-align: center;
            padding: 30px;
            color: #666;
            border-top: 1px solid #ddd;
            margin-top: 50px;
        }

        /* Адаптивность */
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

            .history-table {
                font-size: 0.9rem;
            }
            
            .history-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Шапка -->
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
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-tachometer-alt"></i> Личный кабинет
                    </a>
                    <a href="api/logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </a>
                    <a href="register.php" class="btn btn-outline">
                        <i class="fas fa-user-plus"></i> Регистрация
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Основной контент -->
        <div class="main-content">
            <!-- Форма заказа -->
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
                                <?php echo $mat['Name_Mat']; ?> - <?php echo $mat['rate']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-weight"></i> Объем (кг)</label>
                            <input type="number" id="volume" class="form-control" min="1" required>
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

            <!-- Правая колонка с информацией -->
            <div>
                <!-- Карточка с ценами -->
                <div class="info-card" style="margin-bottom: 30px;">
                    <h3><i class="fas fa-tags"></i> Наши цены</h3>
                    <ul class="price-list">
                        <?php 
                        $materials->data_seek(0);
                        while($mat = $materials->fetch_assoc()): 
                        ?>
                        <li>
                            <span class="material-name"><?php echo $mat['Name_Mat']; ?></span>
                            <span class="material-price"><?php echo $mat['rate']; ?></span>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <!-- Преимущества -->
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
                        <li style="margin: 15px 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: #4CAF50;"></i>
                            Отслеживание заказа в реальном времени
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Отслеживание заказа -->
        <div class="tracking-section">
            <h2><i class="fas fa-search"></i> Отследить заказ</h2>
            <div class="tracking-form">
                <input type="text" id="trackingNumber" placeholder="Введите номер заказа">
                <button class="btn btn-primary" onclick="trackOrder()">
                    <i class="fas fa-search"></i> Найти
                </button>
            </div>

            <!-- Статус заказа -->
            <div id="orderStatus" class="order-status">
                <h3 style="color: var(--primary-green);">Заказ #<span id="orderId"></span></h3>
                <div class="status-timeline" id="statusTimeline"></div>
            </div>
        </div>

        <!-- История заказов для авторизованных -->
        <?php if($is_authorized): ?>
        <div class="history-section">
            <h2 style="color: var(--primary-green); margin-bottom: 20px;">
                <i class="fas fa-history"></i> История заказов
            </h2>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>№ заказа</th>
                        <th>Дата</th>
                        <th>Материал</th>
                        <th>Объем</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Получаем ID клиента
                    $client_id = $_SESSION['user_id'];
                    
                    // Проверяем, есть ли колонка id_client в таблице orders
                    $check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'id_client'");
                    
                    if ($check_column->num_rows > 0) {
                        // Если есть колонка id_client
                        $orders = $conn->query("SELECT o.*, m.Name_Mat as material 
                                               FROM orders o 
                                               LEFT JOIN materials m ON o.id_materials = m.id_material 
                                               WHERE o.id_client = $client_id 
                                               ORDER BY o.data_Time DESC 
                                               LIMIT 10");
                    } else {
                        // Если нет колонки id_client, показываем все заказы (для теста)
                        $orders = $conn->query("SELECT o.*, m.Name_Mat as material 
                                               FROM orders o 
                                               LEFT JOIN materials m ON o.id_materials = m.id_material 
                                               ORDER BY o.data_Time DESC 
                                               LIMIT 10");
                    }
                    
                    if ($orders && $orders->num_rows > 0):
                        while($order = $orders->fetch_assoc()):
                            $status_class = ['','badge-new','badge-progress','badge-progress','badge-progress','badge-completed'][$order['status']];
                            $status_text = ['','Новый','Подтвержден','В пути','Загружено','Завершен'][$order['status']];
                    ?>
                    <tr>
                        <td><strong>#<?php echo $order['id_order']; ?></strong></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></td>
                        <td><?php echo $order['material']; ?></td>
                        <td><?php echo $order['volume']; ?> кг</td>
                        <td><span class="order-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                        <td>
                            <button class="btn btn-primary" style="padding: 5px 10px;" onclick="repeatOrder(<?php echo $order['id_order']; ?>)">
                                <i class="fas fa-redo"></i> Повторить
                            </button>
                            <button class="btn btn-secondary" style="padding: 5px 10px;" onclick="downloadAct(<?php echo $order['id_order']; ?>)">
                                <i class="fas fa-file-pdf"></i> Акт
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px;">
                            <i class="fas fa-box-open" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                            <p>У вас пока нет заказов</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Футер -->
        <div class="footer">
            <p>&copy; 2026 Green Mile. Система экологичного вывоза вторсырья</p>
            <p style="font-size: 0.85rem; margin-top: 10px;">
                <i class="fas fa-phone"></i> 8-800-123-45-67 | 
                <i class="fas fa-envelope"></i> info@greenmile.ru
            </p>
        </div>
    </div>

    <!-- Уведомление -->
    <div id="notification" class="notification"></div>

    <script>
        // Расчет примерной стоимости
        document.getElementById('material').addEventListener('change', calculateCost);
        document.getElementById('volume').addEventListener('input', calculateCost);

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

        // Отправка заказа
        function submitOrder() {
            const formData = {
                material: document.getElementById('material').value,
                volume: document.getElementById('volume').value,
                date: document.getElementById('date').value,
                time: document.getElementById('time').value,
                address: document.getElementById('address').value,
                phone: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                comments: document.getElementById('comments').value,
                client_type: '<?php echo $is_authorized ? 'registered' : 'guest'; ?>',
                client_id: '<?php echo $_SESSION['user_id'] ?? 0; ?>'
            };

            // Валидация
            if (!formData.material || !formData.volume || !formData.date || !formData.time || !formData.address || !formData.phone) {
                showNotification('Заполните все обязательные поля', 'error');
                return;
            }

            showNotification('Заказ отправлен! Номер: ' + Math.floor(Math.random() * 1000), 'success');
            document.getElementById('orderForm').reset();
            document.getElementById('estimated_cost').value = '0 ₽';
            
            // Устанавливаем сегодняшнюю дату
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date').value = today;
        }

        // Отслеживание заказа
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

        // Повтор заказа
        function repeatOrder(orderId) {
            showNotification('Заказ #' + orderId + ' будет повторен', 'info');
        }

        // Скачать акт
        function downloadAct(orderId) {
            showNotification('Акт для заказа #' + orderId + ' будет доступен для скачивания', 'info');
        }

        // Уведомления
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type} show`;
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Автоматическое заполнение сегодняшней даты
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date').value = today;
            document.getElementById('date').min = today;
            
            // Устанавливаем время по умолчанию
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('time').value = hours + ':' + minutes;
        });

        // Маска для телефона
        document.getElementById('phone').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+7 (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
        });
    </script>
</body>
</html>