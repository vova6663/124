<?php
// accountant.php
include_once 'api/config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Бухгалтер - это роль 2 (та же что и диспетчер)
$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user = $user_query->fetch_assoc();

// Получаем финансовые данные
$total_revenue = $conn->query("SELECT SUM(o.cost) as total FROM orders o WHERE o.status = 5")->fetch_assoc()['total'] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$completed_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 5")->fetch_assoc()['total'];
$pending_orders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status < 5")->fetch_assoc()['total'];

// Получаем данные по клиентам
$top_clients = $conn->query("SELECT c.con as client_name, COUNT(o.id_order) as orders_count, SUM(o.cost) as total_spent 
                             FROM orders o 
                             JOIN client c ON o.id_client = c.id_Client 
                             GROUP BY o.id_client 
                             ORDER BY total_spent DESC 
                             LIMIT 5");

// Получаем данные по материалам
$materials_stats = $conn->query("SELECT m.Name_Mat, COUNT(o.id_order) as count, SUM(o.volume) as total_volume 
                                 FROM orders o 
                                 JOIN materials m ON o.id_materials = m.id_material 
                                 GROUP BY o.id_materials");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Финансовая панель - Green Mile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .accountant-container {
            padding: 20px;
        }
        
        .finance-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .total-revenue {
            font-size: 3rem;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #4CAF50;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .finance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .finance-table th {
            background: #4CAF50;
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        .finance-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .finance-table tr:hover {
            background: #f5f5f5;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn-export {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
        }
        
        .btn-export.excel { background: #217346; }
        .btn-export.pdf { background: #f44336; }
        .btn-export.csv { background: #2196F3; }
        
        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .period-selector button {
            padding: 10px 20px;
            border: 2px solid #4CAF50;
            background: white;
            color: #4CAF50;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .period-selector button.active {
            background: #4CAF50;
            color: white;
        }
        
        .period-selector button:hover {
            background: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1>Финансовая панель</h1>
            <div class="user-info">
                <span><?php echo $user['First_Name'] . ' ' . $user['Last_Name']; ?></span>
                <span class="role">(Бухгалтер)</span>
                <a href="dashboard.php" class="btn btn-small">В кабинет</a>
                <a href="api/logout.php" class="btn btn-small btn-secondary">Выйти</a>
            </div>
        </header>
        
        <div class="finance-header">
            <h2>Общая выручка</h2>
            <div class="total-revenue"><?php echo number_format($total_revenue, 2, '.', ' '); ?> ₽</div>
            <p>За все время работы системы</p>
        </div>
        
        <div class="period-selector">
            <button class="active" onclick="changePeriod('day')">День</button>
            <button onclick="changePeriod('week')">Неделя</button>
            <button onclick="changePeriod('month')">Месяц</button>
            <button onclick="changePeriod('quarter')">Квартал</button>
            <button onclick="changePeriod('year')">Год</button>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-shopping-cart" style="font-size: 2rem; color: #4CAF50;"></i>
                <div class="stat-value"><?php echo $total_orders; ?></div>
                <div class="stat-label">Всего заказов</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-check-circle" style="font-size: 2rem; color: #4CAF50;"></i>
                <div class="stat-value"><?php echo $completed_orders; ?></div>
                <div class="stat-label">Выполнено заказов</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock" style="font-size: 2rem; color: #FF9800;"></i>
                <div class="stat-value"><?php echo $pending_orders; ?></div>
                <div class="stat-label">В работе</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-percent" style="font-size: 2rem; color: #2196F3;"></i>
                <div class="stat-value"><?php echo $total_orders ? round(($completed_orders / $total_orders) * 100) : 0; ?>%</div>
                <div class="stat-label">Выполнение</div>
            </div>
        </div>
        
        <div class="export-buttons">
            <button class="btn-export excel" onclick="exportReport('excel')">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button class="btn-export pdf" onclick="exportReport('pdf')">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
            <button class="btn-export csv" onclick="exportReport('csv')">
                <i class="fas fa-file-csv"></i> CSV
            </button>
            <button class="btn-export" onclick="exportReport('1c')" style="background: #9C27B0;">
                <i class="fas fa-database"></i> 1С
            </button>
        </div>
        
        <div class="chart-container">
            <h3>Динамика заказов</h3>
            <canvas id="ordersChart" style="width: 100%; height: 300px;"></canvas>
        </div>
        
        <div class="stats-grid">
            <!-- Топ клиенты -->
            <div class="stat-card" style="grid-column: span 2;">
                <h3>Топ клиенты</h3>
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>Клиент</th>
                            <th>Заказов</th>
                            <th>Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($client = $top_clients->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $client['client_name']; ?></td>
                            <td><?php echo $client['orders_count']; ?></td>
                            <td><?php echo number_format($client['total_spent'], 2, '.', ' '); ?> ₽</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Статистика по материалам -->
            <div class="stat-card">
                <h3>По материалам</h3>
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>Материал</th>
                            <th>Кол-во</th>
                            <th>Объем</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($mat = $materials_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $mat['Name_Mat']; ?></td>
                            <td><?php echo $mat['count']; ?></td>
                            <td><?php echo $mat['total_volume']; ?> кг</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Детальная таблица заказов -->
        <div style="margin-top: 30px;">
            <h3>Детальная информация по заказам</h3>
            <table class="finance-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Клиент</th>
                        <th>Материал</th>
                        <th>Объем</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody id="orders-table-body">
                    <!-- Загружается через AJAX -->
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let ordersChart;
        
        function loadOrdersData(period = 'month') {
            fetch(`api/get_finance_data.php?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    updateChart(data.labels, data.values);
                    updateOrdersTable(data.orders);
                });
        }
        
        function updateChart(labels, values) {
            const ctx = document.getElementById('ordersChart').getContext('2d');
            
            if (ordersChart) {
                ordersChart.destroy();
            }
            
            ordersChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Выручка',
                        data: values,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' ₽';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function updateOrdersTable(orders) {
            const tbody = document.getElementById('orders-table-body');
            tbody.innerHTML = '';
            
            orders.forEach(order => {
                const row = tbody.insertRow();
                row.innerHTML = `
                    <td>#${order.id}</td>
                    <td>${order.date}</td>
                    <td>${order.client}</td>
                    <td>${order.material}</td>
                    <td>${order.volume} кг</td>
                    <td>${order.amount} ₽</td>
                    <td><span class="status-${order.status}">${order.status_text}</span></td>
                `;
            });
        }
        
        function changePeriod(period) {
            document.querySelectorAll('.period-selector button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            loadOrdersData(period);
        }
        
        function exportReport(format) {
            const period = document.querySelector('.period-selector button.active').textContent;
            const data = {
                format: format,
                period: period,
                start_date: document.getElementById('start-date')?.value || '',
                end_date: document.getElementById('end-date')?.value || ''
            };
            
            // Для Excel/CSV/PDF
            if (format === 'excel') {
                window.location.href = `api/export_excel.php?period=${period}`;
            } else if (format === 'csv') {
                window.location.href = `api/export_csv.php?period=${period}`;
            } else if (format === 'pdf') {
                window.location.href = `api/export_pdf.php?period=${period}`;
            } else if (format === '1c') {
                // Экспорт в формат 1С
                fetch('api/export_1c.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'export_1c.xml';
                    a.click();
                });
            }
            
            alert(`Экспорт в ${format.toUpperCase()} будет доступен в следующей версии`);
        }
        
        // Загружаем данные при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadOrdersData('month');
            
            // Пример данных для демонстрации
            updateChart(
                ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                [12500, 15000, 8900, 16700, 21000, 18900, 23000]
            );
        });
    </script>
</body>
</html>