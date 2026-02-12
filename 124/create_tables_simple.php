<?php
// api/create_tables_simple.php - создание минимальных таблиц

// Таблица users
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    Login VARCHAR(255) NOT NULL,
    First_Name VARCHAR(255),
    Last_Name VARCHAR(255),
    role INT DEFAULT 3,
    Password VARCHAR(255) DEFAULT 'demo123'
)");

// Добавляем тестовых пользователей если таблица пуста
$check = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $check->fetch_assoc();
if ($row['count'] == 0) {
    $test_users = [
        "('smirnov_ad', 'Александр', 'Смирнов', 1, 'demo123')",
        "('ivanova_ed', 'Елена', 'Иванова', 2, 'demo123')",
        "('petrov_iv', 'Иван', 'Петров', 3, 'demo123')",
        "('kozlov_sa', 'Сергей', 'Козлов', 2, 'demo123')",
        "('volkov_ai', 'Алексей', 'Волков', 3, 'demo123')"
    ];
    
    $sql = "INSERT INTO users (Login, First_Name, Last_Name, role, Password) VALUES " . implode(',', $test_users);
    $conn->query($sql);
}
?>