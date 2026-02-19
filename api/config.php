<?php
// api/config.php - ЕДИНЫЙ ФАЙЛ КОНФИГУРАЦИИ

// Запускаем сессию ТОЛЬКО если она еще не запущена
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

// Создаем таблицу для чата если её нет
$conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    recipient_id INT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_created (created_at)
)");

// Проверяем и добавляем колонку driver_status если её нет
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'driver_status'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN driver_status ENUM('free', 'busy', 'offline') DEFAULT 'free' AFTER role");
    $conn->query("UPDATE users SET driver_status = 'free' WHERE role = 3");
}

// Проверяем и добавляем колонку id_client в orders если её нет
$check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'id_client'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN id_client INT NULL AFTER id_order");
}

// Функция для проверки авторизации
function isAuthorized($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    if ($required_role !== null && $_SESSION['role'] != $required_role) {
        return false;
    }
    return true;
}

// Функция для редиректа если не авторизован
function requireAuth($required_role = null) {
    if (!isAuthorized($required_role)) {
        header("Location: /124/login.php");
        exit();
    }
}

// Функция для получения информации о пользователе
function getUserInfo($user_id = null) {
    global $conn;
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }
    $result = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
    return $result->fetch_assoc();
}

// Функция для получения статусов заказов
function getOrderStatus($status_id) {
    $statuses = [
        1 => 'Новый',
        2 => 'Подтвержден',
        3 => 'В работе',
        4 => 'Загружено',
        5 => 'Завершен'
    ];
    return $statuses[$status_id] ?? 'Неизвестно';
}

// Функция для получения класса статуса
function getOrderStatusClass($status_id) {
    $classes = [
        1 => 'badge-warning',
        2 => 'badge-info',
        3 => 'badge-primary',
        4 => 'badge-primary',
        5 => 'badge-success'
    ];
    return $classes[$status_id] ?? 'badge-secondary';
}

// Функция для получения роли пользователя
function getUserRole($role_id) {
    $roles = [
        1 => 'Директор',
        2 => 'Диспетчер',
        3 => 'Водитель',
        4 => 'Клиент'
    ];
    return $roles[$role_id] ?? 'Пользователь';
}

// Функция для получения класса роли
function getUserRoleClass($role_id) {
    $classes = [
        1 => 'badge-danger',
        2 => 'badge-info',
        3 => 'badge-warning',
        4 => 'badge-success'
    ];
    return $classes[$role_id] ?? 'badge-secondary';
}

// Функция для отправки JSON ответа
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Функция для показа уведомления
function showNotification($message, $type = 'success') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Функция для получения и очистки уведомления
function getNotification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
    return null;
}

// Функция для логирования действий
function logAction($user_id, $action, $details = '') {
    global $conn;
    $conn->query("INSERT INTO logs (user_id, action, details, ip_address) 
                  VALUES ($user_id, '$action', '$details', '{$_SERVER['REMOTE_ADDR']}')");
}

// Создаем таблицу для логов если её нет
$conn->query("CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Устанавливаем временную зону
date_default_timezone_set('Europe/Moscow');

// Включаем отображение ошибок для разработки
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>