<?php
session_start();

$host = 'localhost';
$dbname = 'green_mile';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    // Простое сообщение об ошибке
    die("Ошибка подключения к базе данных. Проверьте XAMPP.");
}
?>