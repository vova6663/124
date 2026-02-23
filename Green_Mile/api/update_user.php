<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
?>