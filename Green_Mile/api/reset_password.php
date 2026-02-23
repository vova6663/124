<?php
include 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id']) && isset($data['password'])) {
    $id = intval($data['id']);
    $password = $conn->real_escape_string($data['password']);
    
    $sql = "UPDATE users SET Password = '$password' WHERE id_user = $id";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
?>