<?php
// api/auth_api.php - БЕЗ ФИКСИРОВАННЫХ ПАРОЛЕЙ
include "config.php";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputLogin = trim($_POST["Login"]);
    $inputPass = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE Login = '$inputLogin'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $db_password = $row['Password'] ?? '';
        
        // ПРОВЕРЯЕМ ТОЛЬКО С ПАРОЛЕМ ИЗ БАЗЫ ДАННЫХ
        if ($inputPass === $db_password) {
            $_SESSION['user_id'] = $row['id_user'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['login'] = $row['Login'];
            $_SESSION['first_name'] = $row['First_Name'] ?? '';
            $_SESSION['last_name'] = $row['Last_Name'] ?? '';
            
            if ($row['role'] == 1) {
                header("location: ../admin/index.php?page=dashboard");
            } elseif ($row['role'] == 2) {
                header("location: ../dispatcher.php");
            } elseif ($row['role'] == 3) {
                header("location: ../driver.php");
            } else {
                header("location: ../dashboard.php");
            }
            exit;
        } else {
            header("location: ../login.php?error=" . urlencode("Неверный пароль!"));
            exit;
        }
    } else {
        header("location: ../login.php?error=" . urlencode("Пользователь не найден!"));
        exit;
    }
} else {
    header("location: ../login.php");
    exit;
}
?>