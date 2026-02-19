<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['order_id']);
    $status = intval($_POST['status']);
    $material = intval($_POST['material']);
    $volume = floatval($_POST['volume']);
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
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
?>