<?php
$conn = new mysqli('localhost', 'root', 'root', 'kindergarten_attendance');
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>