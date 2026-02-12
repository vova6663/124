<?php
// fix_password.php
echo "<h2>Исправление базы данных - добавление поля Password</h2>";

$conn = new mysqli('localhost', 'root', '', 'green_mile');

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// 1. Проверяем структуру таблицы users
echo "<h3>1. Текущая структура таблицы users:</h3>";
$result = $conn->query("DESCRIBE users");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Добавляем поле Password если нет
echo "<h3>2. Добавляем поле Password...</h3>";
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'Password'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN Password VARCHAR(255) NOT NULL DEFAULT 'demo123'";
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>✓ Поле Password успешно добавлено</p>";
    } else {
        echo "<p style='color:red;'>✗ Ошибка: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green;'>✓ Поле Password уже существует</p>";
}

// 3. Обновляем пароли для всех пользователей
echo "<h3>3. Обновляем пароли...</h3>";
$sql = "UPDATE users SET Password = 'demo123'";
if ($conn->query($sql)) {
    $affected = $conn->affected_rows;
    echo "<p style='color:green;'>✓ Пароли обновлены для $affected пользователей</p>";
} else {
    echo "<p style='color:red;'>✗ Ошибка: " . $conn->error . "</p>";
}

// 4. Проверяем результат
echo "<h3>4. Проверяем пользователей с паролями:</h3>";
$result = $conn->query("SELECT id_user, Login, Password, role FROM users");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Login</th><th>Password</th><th>Role</th><th>Статус</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id_user'] . "</td>";
    echo "<td>" . $row['Login'] . "</td>";
    echo "<td>" . htmlspecialchars($row['Password']) . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td>";
    if (!empty($row['Password'])) {
        echo "<span style='color:green;'>✓ Есть пароль</span>";
    } else {
        echo "<span style='color:red;'>✗ Нет пароля</span>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Что делать дальше:</h3>";
echo "<ol>";
echo "<li><a href='login.php'>Попробовать войти</a></li>";
echo "<li><a href='test_auth_simple.php'>Протестировать авторизацию</a></li>";
echo "</ol>";

$conn->close();
?>