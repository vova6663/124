<?php
// api/chat_api.php
include "config.php";

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Создаем таблицу для чата если её нет
$conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    recipient_id INT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE
)");

switch($action) {
    case 'get_messages':
        $user_id = intval($_GET['user_id']);
        $last_id = intval($_GET['last_id'] ?? 0);
        
        $sql = "SELECT c.*, u.First_Name, u.Last_Name, u.role 
                FROM chat_messages c 
                JOIN users u ON c.user_id = u.id_user 
                WHERE (c.recipient_id IS NULL OR c.recipient_id = 0 OR c.recipient_id = $user_id OR c.user_id = $user_id)
                AND c.id > $last_id
                ORDER BY c.created_at DESC 
                LIMIT 50";
        
        $result = $conn->query($sql);
        $messages = [];
        
        while($row = $result->fetch_assoc()) {
            $row['user_name'] = $row['First_Name'] . ' ' . $row['Last_Name'];
            $messages[] = $row;
        }
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;
        
    case 'send_message':
        $user_id = intval($_POST['user_id']);
        $message = $conn->real_escape_string($_POST['message']);
        $recipient_id = isset($_POST['recipient_id']) ? intval($_POST['recipient_id']) : 'NULL';
        
        $sql = "INSERT INTO chat_messages (user_id, message, recipient_id) 
                VALUES ($user_id, '$message', " . ($recipient_id ?: 'NULL') . ")";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
        
    case 'mark_read':
        $user_id = intval($_POST['user_id']);
        $conn->query("UPDATE chat_messages SET is_read = 1 WHERE recipient_id = $user_id OR (recipient_id IS NULL AND user_id != $user_id)");
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
}
?>