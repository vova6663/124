<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'Нельзя удалить себя']);
        exit;
    }
    
    if ($conn->query("DELETE FROM users WHERE id_user = $id")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
?>