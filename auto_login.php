<?php
// auto_login.php - АВТОМАТИЧЕСКИЙ ВХОД
session_start();

$users = [
    'admin' => ['pass' => 'demo123', 'role' => 1, 'name' => 'Админ', 'lastname' => 'Системы'],
    'director' => ['pass' => 'demo123', 'role' => 1, 'name' => 'Александр', 'lastname' => 'Смирнов'],
    'dispatcher' => ['pass' => 'demo123', 'role' => 2, 'name' => 'Елена', 'lastname' => 'Иванова'],
    'manager' => ['pass' => 'demo123', 'role' => 2, 'name' => 'Сергей', 'lastname' => 'Козлов'],
    'driver1' => ['pass' => 'demo123', 'role' => 3, 'name' => 'Иван', 'lastname' => 'Петров'],
    'driver2' => ['pass' => 'demo123', 'role' => 3, 'name' => 'Алексей', 'lastname' => 'Волков'],
];

if(isset($_GET['user']) && isset($users[$_GET['user']])) {
    $login = $_GET['user'];
    $user = $users[$login];
    
    // Устанавливаем сессию
    $_SESSION['user_id'] = rand(100, 999);
    $_SESSION['login'] = $login;
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['name'];
    $_SESSION['last_name'] = $user['lastname'];
    $_SESSION['logout'] = true;
    
    // Редирект
    if($user['role'] == 1) {
        header("Location: admin/index.php?page=dashboard");
    } elseif($user['role'] == 2) {
        header("Location: dispatcher.php");
    } elseif($user['role'] == 3) {
        header("Location: driver.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>