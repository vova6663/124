<?php
include 'config.php';

$locations = [];

// Получаем адреса из заказов
$orders = $conn->query("SELECT addres FROM orders WHERE addres IS NOT NULL AND addres != ''");
while($row = $orders->fetch_assoc()) {
    $locations[] = $row['addres'];
}

echo json_encode(['locations' => $locations]);
?>