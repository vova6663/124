<?php
// reset_password.php
echo "<h2>Утилита сброса и установки паролей</h2>";

$conn = new mysqli('localhost', 'root', '', 'green_mile');

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Форма для сброса пароля
echo '<form method="POST" style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 10px;">';
echo '<h3>Сбросить пароль пользователю:</h3>';
echo '<label>Логин пользователя: </label>';
echo '<input type="text" name="login" value="smirnov_ad" required><br><br>';
echo '<label>Новый пароль: </label>';
echo '<input type="password" name="new_password" value="demo123" required><br><br>';
echo '<input type="submit" name="reset" value="Сбросить пароль" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">';
echo '</form>';

if (isset($_POST['reset'])) {
    $login = $_POST['login'];
    $new_password = $_POST['new_password'];
    
    // Ищем пользователя
    $stmt = $conn->prepare("SELECT id_user FROM users WHERE Login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id_user'];
        
        // Хэшируем новый пароль
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Обновляем пароль
        $update_stmt = $conn->prepare("UPDATE users SET Password = ? WHERE id_user = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            echo "<div style='color:green; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0;'>";
            echo "✓ Пароль для пользователя '$login' успешно сброшен!";
            echo "</div>";
            
            echo "<p>Новые данные для входа:</p>";
            echo "<ul>";
            echo "<li><strong>Логин:</strong> $login</li>";
            echo "<li><strong>Пароль:</strong> $new_password</li>";
            echo "</ul>";
        } else {
            echo "<div style='color:red; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0;'>";
            echo "✗ Ошибка при сбросе пароля: " . $update_stmt->error;
            echo "</div>";
        }
        
        $update_stmt->close();
    } else {
        echo "<div style='color:red; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0;'>";
        echo "✗ Пользователь '$login' не найден!";
        echo "</div>";
    }
    
    $stmt->close();
}

// Показать текущих пользователей
echo "<h3>Текущие пользователи:</h3>";
$result = $conn->query("SELECT id_user, Login, role, LENGTH(Password) as pass_length FROM users");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f2f2f2;'>";
echo "<th>ID</th><th>Login</th><th>Роль</th><th>Длина пароля</th><th>Статус</th>";
echo "</tr>";

while($row = $result->fetch_assoc()) {
    $status = $row['pass_length'] > 20 ? 
        "<span style='color:green'>✓ Хэширован</span>" : 
        "<span style='color:red'>✗ Требует хэширования</span>";
    
    echo "<tr>";
    echo "<td>" . $row['id_user'] . "</td>";
    echo "<td>" . $row['Login'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td>" . $row['pass_length'] . "</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='migrate_passwords.php'>Хэшировать все пароли</a> | ";
echo "<a href='login.php'>Перейти к входу</a></p>";

$conn->close();
?>