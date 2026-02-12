<?php
// migrate_passwords.php
echo "<h2>Миграция паролей на хэшированные</h2>";

$conn = new mysqli('localhost', 'root', '', 'green_mile');

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// 1. Проверяем текущие пароли
echo "<h3>1. Текущие пароли:</h3>";
$result = $conn->query("SELECT id_user, Login, Password FROM users");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Login</th><th>Password</th><th>Тип</th></tr>";

while($row = $result->fetch_assoc()) {
    $password = $row['Password'];
    $type = 'текст';
    
    // Проверяем, хэширован ли уже пароль
    if (password_verify('demo123', $password) || 
        password_get_info($password)['algo'] !== 0) {
        $type = '<span style="color:green">хэш</span>';
    }
    
    echo "<tr>";
    echo "<td>" . $row['id_user'] . "</td>";
    echo "<td>" . $row['Login'] . "</td>";
    echo "<td>" . htmlspecialchars(substr($password, 0, 20)) . "...</td>";
    echo "<td>" . $type . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Хэшируем пароли
echo "<h3>2. Хэширование паролей...</h3>";

$result = $conn->query("SELECT id_user, Password FROM users");
$updated = 0;

while($row = $result->fetch_assoc()) {
    $id = $row['id_user'];
    $current_password = $row['Password'];
    
    // Если пароль не хэширован или равен demo123
    if ($current_password === 'demo123' || 
        password_get_info($current_password)['algo'] === 0) {
        
        // Хэшируем пароль
        $hashed_password = password_hash('demo123', PASSWORD_DEFAULT);
        
        // Обновляем в базе
        $stmt = $conn->prepare("UPDATE users SET Password = ? WHERE id_user = ?");
        $stmt->bind_param("si", $hashed_password, $id);
        
        if ($stmt->execute()) {
            $updated++;
            echo "ID $id: пароль хэширован<br>";
        } else {
            echo "ID $id: ошибка - " . $stmt->error . "<br>";
        }
        
        $stmt->close();
    }
}

echo "<p style='color:green;'>✓ Обновлено $updated пользователей</p>";

// 3. Проверяем результат
echo "<h3>3. Результат:</h3>";
$result = $conn->query("SELECT id_user, Login, Password FROM users LIMIT 3");
echo "<pre>";
while($row = $result->fetch_assoc()) {
    echo $row['Login'] . ": " . substr($row['Password'], 0, 30) . "...\n";
    echo "Длина: " . strlen($row['Password']) . " символов\n";
    echo "Является хэшем: " . (password_get_info($row['Password'])['algo'] !== 0 ? 'Да' : 'Нет') . "\n\n";
}
echo "</pre>";

echo "<hr>";
echo "<h3>Тестовые данные для входа:</h3>";
echo "<ul>";
echo "<li><strong>Логин:</strong> smirnov_ad</li>";
echo "<li><strong>Пароль:</strong> demo123</li>";
echo "</ul>";
echo "<p><a href='login.php'>Перейти к входу</a></p>";

$conn->close();
?>