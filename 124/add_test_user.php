<?php
// add_test_user.php
$conn = new mysqli('localhost', 'root', '', 'green_mile');

echo "<h2>Добавление тестового пользователя</h2>";

// Создаем простого пользователя с текстовым паролем
$login = 'testuser';
$password = 'test123'; // Текстовый пароль - просто для теста
$role = 2; // Диспетчер

// Проверяем, есть ли уже такой пользователь
$check = $conn->query("SELECT * FROM users WHERE Login = '$login'");
if ($check->num_rows > 0) {
    echo "<p style='color:orange;'>Пользователь '$login' уже существует</p>";
} else {
    // Добавляем нового пользователя
    $sql = "INSERT INTO users (Login, First_Name, Last_Name, role, Password) 
            VALUES ('$login', 'Тестовый', 'Пользователь', $role, '$password')";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>✓ Пользователь '$login' добавлен с паролем '$password'</p>";
    } else {
        echo "<p style='color:red;'>✗ Ошибка: " . $conn->error . "</p>";
    }
}

// Показываем всех пользователей
echo "<h3>Все пользователи в системе:</h3>";
$result = $conn->query("SELECT id_user, Login, Password, role FROM users");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Login</th><th>Password</th><th>Role</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id_user'] . "</td>";
    echo "<td>" . $row['Login'] . "</td>";
    echo "<td>" . htmlspecialchars($row['Password']) . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><strong>Для входа используйте:</strong></p>";
echo "<ul>";
echo "<li>Логин: <strong>testuser</strong></li>";
echo "<li>Пароль: <strong>test123</strong></li>";
echo "</ul>";
echo "<p><a href='login.php'>Перейти к входу</a></p>";

$conn->close();
?>