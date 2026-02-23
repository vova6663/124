<?php
// accountant.php - адаптировано под предоставленную структуру БД
include_once 'api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user = $user_query->fetch_assoc();

// Проверяем, что пользователь действительно бухгалтер (роль 5)
if ($user['role'] != 5) {
    header("Location: dashboard.php");
    exit();
}

// Функция для извлечения числа из строки с ценой (например, "15р за кг" -> 15)
function extractPriceFromRate($rateString) {
    if (empty($rateString)) return 0;
    preg_match('/(\d+)/', $rateString, $matches);
    return isset($matches[1]) ? (float)$matches[1] : 0;
}

// Функция для определения единицы измерения (кг или шт)
function getRateUnit($rateString) {
    if (empty($rateString)) return 'kg';
    if (strpos($rateString, 'за шт') !== false) return 'piece';
    return 'kg';
}

// Получаем все заказы с связанными данными
$orders = [];
$orders_query = $conn->query("
    SELECT 
        o.*,
        c.con as client_name,
        c.INN as client_inn,
        c.addres as client_address,
        m.Name_Mat as material_name,
        m.rate as material_rate,
        m.comments as material_comments
    FROM orders o
    LEFT JOIN client c ON o.id_client = c.id_Client
    LEFT JOIN materials m ON o.id_materials = m.id_material
    ORDER BY o.data_Time DESC
");

while ($row = $orders_query->fetch_assoc()) {
    // Рассчитываем стоимость на PHP
    $volume = (float)($row['volume'] ?? 0);
    $rate_value = extractPriceFromRate($row['material_rate'] ?? '');
    $unit = getRateUnit($row['material_rate'] ?? '');
    
    // Для штучных товаров (опасные отходы) используем примерный вес
    if ($unit === 'piece') {
        // Примерно 0.5 кг за батарейку/лампу
        $calculated_cost = $volume * $rate_value * 0.5;
    } else {
        $calculated_cost = $volume * $rate_value;
    }
    
    $row['calculated_cost'] = $calculated_cost;
    $row['rate_value'] = $rate_value;
    $row['unit'] = $unit;
    $orders[] = $row;
}

// Финансовые показатели
$total_revenue = 0;
$today_revenue = 0;
$week_revenue = 0;
$month_revenue = 0;
$completed_orders = 0;
$pending_orders = 0;
$today = date('Y-m-d');
$current_week = date('W');
$current_month = date('m');
$current_year = date('Y');

foreach ($orders as $order) {
    if ($order['status'] == 5) { // Завершенные заказы
        $total_revenue += $order['calculated_cost'];
        
        $order_date = date('Y-m-d', strtotime($order['data_Time']));
        $order_week = date('W', strtotime($order['data_Time']));
        $order_month = date('m', strtotime($order['data_Time']));
        $order_year = date('Y', strtotime($order['data_Time']));
        
        if ($order_date == $today) {
            $today_revenue += $order['calculated_cost'];
        }
        
        if ($order_week == $current_week && $order_year == $current_year) {
            $week_revenue += $order['calculated_cost'];
        }
        
        if ($order_month == $current_month && $order_year == $current_year) {
            $month_revenue += $order['calculated_cost'];
        }
        
        $completed_orders++;
    } elseif ($order['status'] > 0 && $order['status'] < 5) {
        $pending_orders++;
    }
}

$total_orders = count($orders);
$avg_check = $completed_orders > 0 ? $total_revenue / $completed_orders : 0;

// Статистика по клиентам
$client_stats = [];
foreach ($orders as $order) {
    if ($order['status'] == 5 && !empty($order['client_name'])) {
        $client_id = $order['id_client'];
        if (!isset($client_stats[$client_id])) {
            $client_stats[$client_id] = [
                'name' => $order['client_name'],
                'inn' => $order['client_inn'],
                'orders_count' => 0,
                'total_spent' => 0,
                'total_volume' => 0
            ];
        }
        $client_stats[$client_id]['orders_count']++;
        $client_stats[$client_id]['total_spent'] += $order['calculated_cost'];
        $client_stats[$client_id]['total_volume'] += (float)$order['volume'];
    }
}

// Сортируем клиентов по сумме
usort($client_stats, function($a, $b) {
    return $b['total_spent'] <=> $a['total_spent'];
});
$top_clients = array_slice($client_stats, 0, 10);

// Статистика по материалам
$material_stats = [];
foreach ($orders as $order) {
    if ($order['status'] == 5 && !empty($order['material_name'])) {
        $material_id = $order['id_materials'];
        if (!isset($material_stats[$material_id])) {
            $material_stats[$material_id] = [
                'name' => $order['material_name'],
                'rate' => $order['material_rate'],
                'orders_count' => 0,
                'total_revenue' => 0,
                'total_volume' => 0
            ];
        }
        $material_stats[$material_id]['orders_count']++;
        $material_stats[$material_id]['total_revenue'] += $order['calculated_cost'];
        $material_stats[$material_id]['total_volume'] += (float)$order['volume'];
    }
}

// Сортируем материалы по выручке
usort($material_stats, function($a, $b) {
    return $b['total_revenue'] <=> $a['total_revenue'];
});

// Статистика по месяцам
$monthly_stats = [];
foreach ($orders as $order) {
    if ($order['status'] == 5) {
        $month_key = date('Y-m', strtotime($order['data_Time']));
        if (!isset($monthly_stats[$month_key])) {
            $monthly_stats[$month_key] = [
                'month' => $month_key,
                'orders_count' => 0,
                'revenue' => 0,
                'volume' => 0
            ];
        }
        $monthly_stats[$month_key]['orders_count']++;
        $monthly_stats[$month_key]['revenue'] += $order['calculated_cost'];
        $monthly_stats[$month_key]['volume'] += (float)$order['volume'];
    }
}

// Сортируем по месяцам
krsort($monthly_stats);
$last_12_months = array_slice($monthly_stats, 0, 12, true);

function getStatusText($status) {
    switch ($status) {
        case 1: return 'Новый';
        case 2: return 'Подтвержден';
        case 3: return 'В работе';
        case 4: return 'На месте';
        case 5: return 'Завершен';
        default: return 'Неизвестно';
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 1: return 'status-new';
        case 2: return 'status-processing';
        case 3: return 'status-progress';
        case 4: return 'status-assigned';
        case 5: return 'status-completed';
        default: return 'status-unknown';
    }
}
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .dashboard-header h1 {
            color: white;
            font-size: 1.8rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .user-info span {
            font-size: 1.1rem;
            color: white;
        }

        .user-info .role {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background: #45a049;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.3);
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #da190b;
        }

        .info-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .finance-header {
            background: linear-gradient(135deg, #2E7D32, #4CAF50);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.3);
        }

        .finance-header h2 {
            font-size: 1.5rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .total-revenue {
            font-size: 4rem;
            font-weight: bold;
            margin: 15px 0;
            line-height: 1.2;
        }

        .revenue-breakdown {
            display: flex;
            gap: 40px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            flex-wrap: wrap;
        }

        .revenue-item {
            text-align: center;
            min-width: 150px;
        }

        .revenue-item .label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .revenue-item .value {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: #4CAF50;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-trend {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #4CAF50;
        }

        .kpi-badge {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: auto;
            margin-bottom: 40px;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h3 {
            color: #333;
            font-size: 1.3rem;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-export {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-export.excel {
            background: #217346;
            color: white;
        }

        .btn-export.csv {
            background: #2196F3;
            color: white;
        }

        .btn-export:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .finance-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .finance-table th {
            background: #4CAF50;
            color: white;
            padding: 15px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
        }

        .finance-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #555;
        }

        .finance-table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }

        .status-new { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-progress { background: #d4edda; color: #155724; }
        .status-assigned { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-unknown { background: #e2e3e5; color: #383d41; }

        .amount-positive {
            color: #28a745;
            font-weight: bold;
        }

        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            padding-left: 10px;
            border-left: 4px solid #4CAF50;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .dashboard-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            transition: all 0.3s;
        }

        .dashboard-link:hover {
            background: rgba(255,255,255,0.3);
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                text-align: center;
            }

            .user-info {
                justify-content: center;
            }

            .total-revenue {
                font-size: 2.5rem;
            }

            .revenue-breakdown {
                flex-direction: column;
                gap: 15px;
                align-items: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1> </h1>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']); ?></span>
                <span class="role"><i class="fas fa-calculator"></i> Бухгалтер</span>
                <a href="api/logout.php" class="btn btn-danger btn-small">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </header>

        <div class="finance-header">
            <h2><i class="fas fa-ruble-sign"></i> Общая выручка</h2>
            <div class="total-revenue"><?php echo number_format($total_revenue, 2, '.', ' '); ?> ₽</div>
            <div class="revenue-breakdown">
                <div class="revenue-item">
                    <div class="label">За сегодня</div>
                    <div class="value"><?php echo number_format($today_revenue, 2, '.', ' '); ?> ₽</div>
                </div>
                <div class="revenue-item">
                    <div class="label">За неделю</div>
                    <div class="value"><?php echo number_format($week_revenue, 2, '.', ' '); ?> ₽</div>
                </div>
                <div class="revenue-item">
                    <div class="label">За месяц</div>
                    <div class="value"><?php echo number_format($month_revenue, 2, '.', ' '); ?> ₽</div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
                <div class="stat-label">Всего заказов</div>
                <div class="stat-trend">
                    <i class="fas fa-check-circle" style="color: #4CAF50;"></i> <?php echo $completed_orders; ?> завершено
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $completed_orders; ?></div>
                <div class="stat-label">Выполнено заказов</div>
                <div class="stat-trend">
                    <span class="kpi-badge"><?php echo $total_orders ? round(($completed_orders / $total_orders) * 100) : 0; ?>% выполнения</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $pending_orders; ?></div>
                <div class="stat-label">В работе</div>
                <div class="stat-trend">
                    <i class="fas fa-hourglass-half" style="color: #FF9800;"></i> ожидают выполнения
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="stat-value"><?php echo number_format($avg_check, 2, '.', ' '); ?> ₽</div>
                <div class="stat-label">Средний чек</div>
                <div class="stat-trend">
                    <i class="fas fa-calculator"></i> на один заказ
                </div>
            </div>
        </div>
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Детальная информация по заказам</h3>
                <div class="export-buttons">
                    <button class="btn-export excel" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="finance-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата</th>
                            <th>Клиент</th>
                            <th>ИНН</th>
                            <th>Адрес</th>
                            <th>Материал</th>
                            <th>Ставка</th>
                            <th>Объём</th>
                            <th>Расчёт</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id_order']; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['data_Time'])); ?></td>
                                <td><?php echo htmlspecialchars($order['client_name'] ?? 'Не указан'); ?></td>
                                <td><?php echo htmlspecialchars($order['client_inn'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($order['addres'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($order['material_name'] ?? 'Не указан'); ?></td>
                                <td><?php echo htmlspecialchars($order['material_rate'] ?? '-'); ?></td>
                                <td><?php echo number_format($order['volume'] ?? 0, 0, '.', ' '); ?> <?php echo $order['unit'] == 'piece' ? 'шт' : 'кг'; ?></td>
                                <td>
                                    <?php if ($order['unit'] == 'piece'): ?>
                                        <?php echo $order['volume']; ?> шт × <?php echo $order['rate_value']; ?> ₽ × 0.5 кг
                                    <?php else: ?>
                                        <?php echo $order['volume']; ?> кг × <?php echo $order['rate_value']; ?> ₽
                                    <?php endif; ?>
                                </td>
                                <td class="amount-positive"><?php echo number_format($order['calculated_cost'] ?? 0, 2, '.', ' '); ?> ₽</td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                                        <?php echo getStatusText($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; color: #ccc; margin-bottom: 10px; display: block;"></i>
                                    Нет данных о заказах
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function exportToExcel() {
        const table = document.getElementById('ordersTable');
        let html = '<html><head><meta charset="UTF-8"></head><body>';
        html += table.outerHTML;
        html += '</body></html>';
        
        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'finance_report_' + new Date().toISOString().slice(0,10) + '.xls';
        a.click();
        window.URL.revokeObjectURL(url);
    }

    function exportToCSV() {
        const table = document.getElementById('ordersTable');
        const rows = table.querySelectorAll('tr');
        let csv = [];
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = [];
            cols.forEach(col => {
                let text = col.innerText.replace(/,/g, ';').replace(/[\n\r]+/g, ' ');
                rowData.push('"' + text + '"');
            });
            csv.push(rowData.join(','));
        });
        
        const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'finance_report_' + new Date().toISOString().slice(0,10) + '.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>