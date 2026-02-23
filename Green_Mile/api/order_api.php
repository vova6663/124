<?php
require_once 'config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $material = intval($data['material']);
    $volume = $conn->real_escape_string($data['volume']);
    $date = $conn->real_escape_string($data['date'] . ' ' . $data['time']);
    $address = $conn->real_escape_string($data['address']);
    $phone = $conn->real_escape_string($data['phone']);
    $email = $conn->real_escape_string($data['email']);
    $comments = $conn->real_escape_string($data['comments']);
    $client_type = $data['client_type'];
    $client_id = $client_type == 'registered' ? intval($data['client_id']) : 'NULL';
    $sql = "INSERT INTO orders (id_client, id_materials, volume, data_Time, addres, comments, status) 
            VALUES ($client_id, $material, '$volume', '$date', '$address', '$comments', 1)";
    
    if ($conn->query($sql)) {
        $order_id = $conn->insert_id;
        if ($client_type == 'guest') {
            if (!isset($_SESSION['guest_orders'])) {
                $_SESSION['guest_orders'] = [];
            }
            $_SESSION['guest_orders'][] = [
                'order_id' => $order_id,
                'phone' => $phone,
                'email' => $email
            ];
        }
        
        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
            'tracking' => $order_id,
            'message' => 'Заказ успешно создан'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при создании заказа: ' . $conn->error
        ]);
    }
}
?>