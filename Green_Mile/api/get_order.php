<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'ID заказа не указан']);
    exit;
}

$result = $conn->query("SELECT o.*, m.Name_Mat as material_name 
                       FROM orders o 
                       LEFT JOIN materials m ON o.id_materials = m.id_material 
                       WHERE o.id_order = $order_id");

if ($order = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'data' => $order
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Заказ не найден'
    ]);
}
?>