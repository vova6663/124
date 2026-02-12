<?php
// test_auth_simple.php
echo "<h2>Простой тест авторизации</h2>";

$conn = new mysqli('localhost', 'root', '', 'green_mile');

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Форма для теста
echo '<form method="POST">';
echo 'Логин: <input type="text" name="Login" value="smirnov_ad"><br>';
echo 'Пароль: <input type="password" name="password" value="demo123"><br>';
echo '<input type="submit" value="Тест входа">';
echo '</form>';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST['Login'];
    $pass = $_POST['password'];
    
    echo "<hr><h3>Результат проверки для '$login':</h3>";
    
    // Экранируем для безопасности
    $login = $conn->real_escape_string($login);
    
    // Ищем пользователя
    $sql = "SELECT * FROM users WHERE Login = '$login'";
    echo "SQL запрос: <code>$sql</code><br><br>";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        echo "Ошибка SQL: " . $conn->error . "<br>";
    } elseif ($result->num_rows == 0) {
        echo "✗ Пользователь '$login' не найден в базе данных<br>";
        
        // Показываем всех пользователей для отладки
        echo "<br><strong>Все пользователи в базе:</strong><br>";
        $all_users = $conn->query("SELECT Login FROM users");
        while($user = $all_users->fetch_assoc()) {
            echo "- " . $user['Login'] . "<br>";
        }
    } else {
        $row = $result->fetch_assoc();
        echo "✓ Пользователь найден!<br>";
        echo "ID: " . $row['id_user'] . "<br>";
        echo "Имя: " . $row['First_Name'] . " " . $row['Last_Name'] . "<br>";
        echo "Роль: " . $row['role'] . "<br>";
        
        // Проверяем пароль
        $db_password = $row['Password'] ?? '';
        echo "Пароль в БД: '$db_password'<br>";
        echo "Введенный пароль: '$pass'<br>";
        
        if ($pass === $db_password) {
            echo "<h3 style='color:green;'>✓ Пароль совпадает!</h3>";
            
            // Тестируем сессию
            session_start();
            $_SESSION['user_id'] = $row['id_user'];
            $_SESSION['login'] = $row['Login'];
            $_SESSION['role'] = $row['role'];
            
            echo "Сессия установлена:<br>";
            echo "- user_id: " . $_SESSION['user_id'] . "<br>";
            echo "- login: " . $_SESSION['login'] . "<br>";
            echo "- role: " . $_SESSION['role'] . "<br>";
            
            echo "<br><a href='dashboard.php' style='color:green; font-weight:bold;'>→ Перейти в кабинет</a>";
        } else {
            echo "<h3 style='color:red;'>✗ Пароль НЕ совпадает!</h3>";
        }
    }
}

$conn->close();
?>