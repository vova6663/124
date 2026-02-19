<?php
// debug_role.php
include_once 'api/config.php';

echo "<h2>Отладка ролей</h2>";

if(isset($_SESSION['user_id'])) {
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Role: " . $_SESSION['role'] . "</p>";
    echo "<p>Login: " . $_SESSION['login'] . "</p>";
    
    // Проверяем пользователя в БД
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>Роль из БД: " . $user['role'] . "</p>";
        echo "<p>Имя: " . $user['First_Name'] . " " . $user['Last_Name'] . "</p>";
    }
} else {
    echo "<p style='color:red;'>Пользователь не авторизован</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>Войти</a> | <a href='api/logout.php'>Выйти</a></p>";
?>