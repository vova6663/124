<?php
// test_users.php
$conn = new mysqli('localhost', 'root', '', 'green_mile');

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

echo "<h2>Тест пользователей в базе данных</h2>";

// Проверяем таблицу users
$result = $conn->query("SELECT * FROM users");
echo "<p>Всего пользователей: " . $result->num_rows . "</p>";

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Login</th><th>Имя</th><th>Роль</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id_user'] . "</td>";
    echo "<td>" . $row['Login'] . "</td>";
    echo "<td>" . $row['First_Name'] . " " . $row['Last_Name'] . "</td>";
    echo "<td>" . $row['role'] . " (";
    switch($row['role']) {
        case 1: echo "Директор"; break;
        case 2: echo "Диспетчер"; break;
        case 3: echo "Водитель"; break;
        case 4: echo "Клиент"; break;
        default: echo "Неизвестно";
    }
    echo ")</td>";
    echo "</tr>";
}
echo "</table>";

// Проверяем, есть ли поле Password
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'Password'");
if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✓ Поле Password существует</p>";
} else {
    echo "<p style='color:red;'>✗ Поле Password НЕ существует</p>";
    echo "<p>Нужно добавить поле:</p>";
    echo "<pre>ALTER TABLE users ADD COLUMN Password VARCHAR(255) DEFAULT 'demo123';</pre>";
}

$conn->close();
?>