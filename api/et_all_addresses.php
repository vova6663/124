<?php
include 'config.php';

$addresses = [];

$orders = $conn->query("SELECT addres FROM orders WHERE addres IS NOT NULL AND addres != ''");
while($row = $orders->fetch_assoc()) {
    $addresses[] = $row['addres'];
}

echo json_encode(['addresses' => $addresses]);
?>