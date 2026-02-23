<?php
include "config.php";
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    
    case 'get_users':
        $result = $conn->query("SELECT id_user, Login, First_Name, Last_Name, role FROM users ORDER BY id_user");
        $users = [];
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $users]);
        break;
    
    case 'add_user':
        $login = $conn->real_escape_string($_POST['login']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $role = intval($_POST['role']);
        $password = $conn->real_escape_string($_POST['password']);
        
        $check = $conn->query("SELECT id_user FROM users WHERE Login = '$login'");
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Логин уже существует']);
            break;
        }
        
        $sql = "INSERT INTO users (Login, First_Name, Last_Name, role, Password) 
                VALUES ('$login', '$first_name', '$last_name', $role, '$password')";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Пользователь добавлен', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;
    
    case 'edit_user':
        $id = intval($_POST['id']);
        $login = $conn->real_escape_string($_POST['login']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $role = intval($_POST['role']);
        
        $sql = "UPDATE users SET 
                Login = '$login',
                First_Name = '$first_name',
                Last_Name = '$last_name',
                role = $role
                WHERE id_user = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Данные обновлены']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;
    
    case 'delete_user':
        $id = intval($_POST['id']);
        
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Нельзя удалить самого себя']);
            break;
        }
        
        $sql = "DELETE FROM users WHERE id_user = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Пользователь удален']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;
    
    case 'export_users':
        $result = $conn->query("SELECT id_user, Login, First_Name, Last_Name, 
                                CASE role 
                                    WHEN 1 THEN 'Директор'
                                    WHEN 2 THEN 'Диспетчер'
                                    WHEN 3 THEN 'Водитель'
                                    WHEN 4 THEN 'Клиент'
                                    ELSE 'Неизвестно'
                                END as role_name
                                FROM users ORDER BY id_user");
        
        $csv = "ID,Логин,Имя,Фамилия,Роль\n";
        while($row = $result->fetch_assoc()) {
            $csv .= "{$row['id_user']},{$row['Login']},{$row['First_Name']},{$row['Last_Name']},{$row['role_name']}\n";
        }
        
        echo json_encode(['success' => true, 'data' => $csv]);
        break;
    
    
    case 'get_transport':
        $result = $conn->query("SELECT t.*, u.First_Name, u.Last_Name 
                               FROM transport t 
                               LEFT JOIN users u ON t.id_user = u.id_user 
                               ORDER BY t.id_transport");
        $transport = [];
        while($row = $result->fetch_assoc()) {
            $transport[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $transport]);
        break;
    
    case 'add_transport':
        $gos_n = $conn->real_escape_string($_POST['gos_n']);
        $model = $conn->real_escape_string($_POST['model']);
        $driver_id = $_POST['driver_id'] ? intval($_POST['driver_id']) : 'NULL';
        
        $sql = "INSERT INTO transport (Gos_N, Model_transport, id_user) 
                VALUES ('$gos_n', '$model', " . ($driver_id == 'NULL' ? 'NULL' : $driver_id) . ")";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Транспорт добавлен', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;
    
    case 'edit_transport':
        $id = intval($_POST['id']);
        $gos_n = $conn->real_escape_string($_POST['gos_n']);
        $model = $conn->real_escape_string($_POST['model']);
        $driver_id = $_POST['driver_id'] ? intval($_POST['driver_id']) : 'NULL';
        
        $sql = "UPDATE transport SET 
                Gos_N = '$gos_n',
                Model_transport = '$model',
                id_user = " . ($driver_id == 'NULL' ? 'NULL' : $driver_id) . "
                WHERE id_transport = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Данные обновлены']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;
    
    case 'delete_transport':
        $id = intval($_POST['id']);
        $sql = "DELETE FROM transport WHERE id_transport = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Транспорт удален']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;
    
    case 'assign_driver':
        $transport_id = intval($_POST['transport_id']);
        $driver_id = intval($_POST['driver_id']);
        
        $sql = "UPDATE transport SET id_user = $driver_id WHERE id_transport = $transport_id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Водитель назначен']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;
    
    case 'get_drivers':
        $result = $conn->query("SELECT id_user, First_Name, Last_Name FROM users WHERE role = 3");
        $drivers = [];
        while($row = $result->fetch_assoc()) {
            $drivers[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $drivers]);
        break;
    
    case 'get_transport_history':
        $transport_id = intval($_GET['transport_id']);
        $result = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                               FROM complete_orders co
                               JOIN orders o ON co.id_order = o.id_order
                               LEFT JOIN materials m ON o.id_materials = m.id_material
                               WHERE co.id_transport = $transport_id
                               ORDER BY o.data_Time DESC");
        $history = [];
        while($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $history]);
        break;
    
    case 'get_all_locations':
        $result = $conn->query("SELECT addres FROM orders WHERE addres IS NOT NULL AND addres != ''");
        $locations = [];
        while($row = $result->fetch_assoc()) {
            $locations[] = $row['addres'];
        }
        echo json_encode(['success' => true, 'data' => $locations]);
        break;
    
    case 'get_orders':
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        $date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
        
        $sql = "SELECT o.*, m.Name_Mat as material_name 
                FROM orders o 
                LEFT JOIN materials m ON o.id_materials = m.id_material 
                WHERE 1=1";
        
        if ($status > 0) {
            $sql .= " AND o.status = $status";
        }
        if ($date) {
            $sql .= " AND DATE(o.data_Time) = '$date'";
        }
        if ($search) {
            $sql .= " AND (o.addres LIKE '%$search%' OR o.comments LIKE '%$search%')";
        }
        
        $sql .= " ORDER BY o.data_Time DESC";
        
        $result = $conn->query($sql);
        $orders = [];
        while($row = $result->fetch_assoc()) {
            $driver = $conn->query("SELECT u.First_Name, u.Last_Name 
                                   FROM complete_orders co 
                                   JOIN transport t ON co.id_transport = t.id_transport 
                                   JOIN users u ON t.id_user = u.id_user 
                                   WHERE co.id_order = {$row['id_order']} LIMIT 1")->fetch_assoc();
            $row['driver_name'] = $driver ? $driver['First_Name'] . ' ' . $driver['Last_Name'] : null;
            $orders[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $orders]);
        break;
    
    case 'get_order':
        $id = intval($_GET['id']);
        $result = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                               FROM orders o 
                               LEFT JOIN materials m ON o.id_materials = m.id_material 
                               WHERE o.id_order = $id");
        if ($result->num_rows > 0) {
            echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
        }
        break;
    
    case 'update_order':
        $id = intval($_POST['id']);
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
                WHERE id_order = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Заказ обновлен']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;
    
    case 'delete_order':
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM complete_orders WHERE id_order = $id");
        $conn->query("DELETE FROM orders WHERE id_order = $id");
        echo json_encode(['success' => true, 'message' => 'Заказ удален']);
        break;
    
    case 'assign_order_driver':
        $order_id = intval($_POST['order_id']);
        $driver_id = intval($_POST['driver_id']);
        $transport = $conn->query("SELECT id_transport FROM transport WHERE id_user = $driver_id")->fetch_assoc();
        if (!$transport) {
            echo json_encode(['success' => false, 'message' => 'У водителя нет транспорта']);
            break;
        }
        $sql = "INSERT INTO complete_orders (id_order, id_transport, comments) 
                VALUES ($order_id, {$transport['id_transport']}, 'Назначено диспетчером')";
        
        if ($conn->query($sql)) {
            $conn->query("UPDATE orders SET status = 2 WHERE id_order = $order_id");
            echo json_encode(['success' => true, 'message' => 'Водитель назначен']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;
    
    case 'update_order_status':
        $order_id = intval($_POST['order_id']);
        $status = intval($_POST['status']);
        $comment = $conn->real_escape_string($_POST['comment'] ?? '');
        
        $conn->query("UPDATE orders SET status = $status WHERE id_order = $order_id");
        
        if ($comment) {
            $conn->query("UPDATE orders SET comments = CONCAT(IFNULL(comments,''), ' [$comment]') WHERE id_order = $order_id");
        }
        
        if ($status == 5) {
            $user_id = $_SESSION['user_id'];
            $transport = $conn->query("SELECT id_transport FROM transport WHERE id_user = $user_id")->fetch_assoc();
            if ($transport) {
                $conn->query("INSERT INTO complete_orders (id_order, id_transport, comments) 
                             VALUES ($order_id, {$transport['id_transport']}, 'Выполнено')");
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Статус обновлен']);
        break;
    
    case 'upload_photo':
        $order_id = intval($_POST['order_id']);
        
        if (isset($_FILES['photo'])) {
            $target_dir = "../uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $filename = time() . '_' . basename($_FILES["photo"]["name"]);
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                $conn->query("UPDATE complete_orders SET photo = '$filename' WHERE id_order = $order_id");
                echo json_encode(['success' => true, 'message' => 'Фото загружено']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ошибка загрузки']);
            }
        }
        break;
    
    // ==================== БУХГАЛТЕР ====================
    
    case 'get_finance_data':
        $period = $_GET['period'] ?? 'month';
        
        // Формируем запрос в зависимости от периода
        switch($period) {
            case 'day':
                $sql = "SELECT DATE(data_Time) as date, SUM(cost) as total 
                        FROM orders WHERE status = 5 AND DATE(data_Time) = CURDATE()
                        GROUP BY DATE(data_Time)";
                break;
            case 'week':
                $sql = "SELECT DATE(data_Time) as date, SUM(cost) as total 
                        FROM orders WHERE status = 5 
                        AND YEARWEEK(data_Time) = YEARWEEK(CURDATE())
                        GROUP BY DATE(data_Time)";
                break;
            case 'month':
                $sql = "SELECT DATE(data_Time) as date, SUM(cost) as total 
                        FROM orders WHERE status = 5 
                        AND MONTH(data_Time) = MONTH(CURDATE())
                        AND YEAR(data_Time) = YEAR(CURDATE())
                        GROUP BY DATE(data_Time)";
                break;
            case 'year':
                $sql = "SELECT MONTH(data_Time) as month, SUM(cost) as total 
                        FROM orders WHERE status = 5 AND YEAR(data_Time) = YEAR(CURDATE())
                        GROUP BY MONTH(data_Time)";
                break;
        }
        
        $result = $conn->query($sql);
        $data = [];
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        // Общая статистика
        $stats = [
            'total_revenue' => $conn->query("SELECT SUM(cost) as total FROM orders WHERE status = 5")->fetch_assoc()['total'] ?? 0,
            'total_orders' => $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'],
            'completed_orders' => $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 5")->fetch_assoc()['total'],
        ];
        
        echo json_encode(['success' => true, 'data' => $data, 'stats' => $stats]);
        break;
    
    case 'export_finance':
        $format = $_GET['format'] ?? 'csv';
        $period = $_GET['period'] ?? 'month';
        
        // Получаем данные
        $result = $conn->query("SELECT o.id_order, o.data_Time, c.con as client, 
                                m.Name_Mat as material, o.volume, o.cost, 
                                CASE o.status 
                                    WHEN 1 THEN 'Новый'
                                    WHEN 2 THEN 'В работе'
                                    WHEN 3 THEN 'В пути'
                                    WHEN 4 THEN 'Загружено'
                                    WHEN 5 THEN 'Завершен'
                                END as status
                                FROM orders o
                                LEFT JOIN client c ON o.id_client = c.id_Client
                                LEFT JOIN materials m ON o.id_materials = m.id_material
                                ORDER BY o.data_Time DESC");
        
        $csv = "ID;Дата;Клиент;Материал;Объем;Сумма;Статус\n";
        while($row = $result->fetch_assoc()) {
            $csv .= "{$row['id_order']};{$row['data_Time']};{$row['client']};{$row['material']};{$row['volume']};{$row['cost']};{$row['status']}\n";
        }
        
        echo json_encode(['success' => true, 'data' => $csv]);
        break;
    
    // ==================== МАТЕРИАЛЫ ====================
    
    case 'get_materials':
        $result = $conn->query("SELECT * FROM materials ORDER BY id_material");
        $materials = [];
        while($row = $result->fetch_assoc()) {
            $materials[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $materials]);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}
?>