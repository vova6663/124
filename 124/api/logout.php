<?php
// api/logout.php
session_start();

// Очищаем все данные сессии
$_SESSION = array();

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную
header("location: ../index.php");
exit;
?>