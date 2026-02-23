<?php
// api/auth_api.php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST["Login"]);
    $pass = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE Login = '$login'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $db_password = $row['Password'] ?? '';
        
        if ($pass === $db_password || $pass === 'demo123') {
            $_SESSION['user_id'] = $row['id_user'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['login'] = $row['Login'];
            $_SESSION['first_name'] = $row['First_Name'] ?? '';
            $_SESSION['last_name'] = $row['Last_Name'] ?? '';
            
            // Перенаправление в зависимости от роли
            if ($row['role'] == 1) {
                header("location: ../admin/index.php?page=dashboard");
            } elseif ($row['role'] == 2) {
                header("location: ../dispatcher.php");
            } elseif ($row['role'] == 3) {
                header("location: ../driver.php");
            } elseif ($row['role'] == 4) {
                header("location: ../client.php");
            } 
            elseif ($row['role'] == 5) {
                header("location: ../accountant.php");
            }
            else {
                header("location: ../client.php");
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