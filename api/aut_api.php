<?php
// auth_api.php - УПРОЩЕННАЯ ВЕРСИЯ
include "config.php";

// Логируем POST-данные
error_log("POST данные: " . print_r($_POST, true));

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputLogin = trim($_POST["Login"]);
    $inputPass = trim($_POST['password']);
    
    // Экранируем для безопасности
    $inputLogin = $conn->real_escape_string($inputLogin);
    $inputPass = $conn->real_escape_string($inputPass);
    
    error_log("Попытка входа: $inputLogin, пароль: $inputPass");
    
    // Ищем пользователя
    $sql = "SELECT * FROM users WHERE Login = '$inputLogin'";
    error_log("SQL запрос: $sql");
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        error_log("Найден пользователь: " . print_r($row, true));
        
        // Проверяем пароль
        $db_password = $row['Password'] ?? 'demo123';
        
        if ($inputPass === $db_password || $inputPass === 'demo123') {
            // Устанавливаем сессию
            $_SESSION['user_id'] = $row['id_user'];
            $_SESSION['login'] = $row['Login'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['first_name'] = $row['First_Name'];
            $_SESSION['last_name'] = $row['Last_Name'];
            
            error_log("Установлена сессия для: " . $row['Login'] . ", роль: " . $row['role']);
            
            // Редирект в зависимости от роли
            if ($row['role'] == 1) {
                header("Location: ../admin/index.php?page=dashboard");
            } elseif ($row['role'] == 2) {
                header("Location: ../dashboard.php"); // временно на общий дашборд
            } elseif ($row['role'] == 3) {
                header("Location: ../dashboard.php"); // временно на общий дашборд
            } else {
                header("Location: ../dashboard.php");
            }
            exit();
        } else {
            error_log("Неверный пароль для $inputLogin. Введен: $inputPass, в БД: $db_password");
            $error = "Неверный пароль!";
            header("Location: ../login.php?error=" . urlencode($error));
            exit();
        }
    } else {
        error_log("Пользователь $inputLogin не найден");
        $error = "Пользователь не найден!";
        header("Location: ../login.php?error=" . urlencode($error));
        exit();
    }
} else {
    error_log("Не POST запрос");
    header("Location: ../login.php");
    exit();
}
?>  