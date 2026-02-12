<?php
// api/config.php - МАКСИМАЛЬНО ПРОСТОЙ
session_start();

// Простые настройки подключения
$host = 'localhost';
$dbname = 'green_mile';
$username = 'root';
$password = ''; // обычно пустой в XAMPP

// Подключаемся
$conn = new mysqli($host, $username, $password, $dbname);

// Проверяем соединение
if ($conn->connect_error) {
    // Простое сообщение об ошибке
    die("Ошибка подключения к базе данных. Проверьте XAMPP.");
}

// Устанавливаем кодировку
$conn->set_charset("utf8mb4");
?>