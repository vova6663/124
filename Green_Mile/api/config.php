<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Настройки подключения к БД
$host = 'localhost';
$dbname = 'green_mile';
$username = 'root';
$password = '';

// Подключение к базе данных
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения к БД: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function isAuthorized($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    if ($required_role !== null && $_SESSION['role'] != $required_role) {
        return false;
    }
    return true;
}

function requireAuth($required_role = null) {
    if (!isAuthorized($required_role)) {
        header("Location: ../login.php");
        exit();
    }
}

function getUserInfo($user_id = null) {
    global $conn;
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }
    $result = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
    return $result->fetch_assoc();
}

// Устанавливаем временную зону
date_default_timezone_set('Europe/Moscow');

// Включаем отображение ошибок для разработки
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>