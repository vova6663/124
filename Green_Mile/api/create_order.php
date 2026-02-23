<?php
// api/create_order.php
require_once 'config.php';

header('Content-Type: application/json');

// Временно отключаем проверку внешних ключей
$conn->query("SET FOREIGN_KEY_CHECKS=0");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

// Получаем данные из формы
$material_id = intval($_POST['material'] ?? 0);
$volume = floatval($_POST['volume'] ?? 0);
$date = $conn->real_escape_string($_POST['date'] ?? '');
$time = $conn->real_escape_string($_POST['time'] ?? '');
$address = $conn->real_escape_string($_POST['address'] ?? '');
$phone = $conn->real_escape_string($_POST['phone'] ?? '');
$email = $conn->real_escape_string($_POST['email'] ?? '');
$comments = $conn->real_escape_string($_POST['comments'] ?? '');
$client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 'NULL';

// Формируем дату и время
$datetime = $date . ' ' . $time . ':00';

// Валидация
if (!$material_id || !$volume || !$date || !$time || !$address || !$phone) {
    echo json_encode(['success' => false, 'message' => 'Заполните все обязательные поля']);
    exit;
}

// Проверяем объем (не больше 4 цифр)
if ($volume > 9999) {
    echo json_encode(['success' => false, 'message' => 'Объем не может быть больше 9999 кг']);
    exit;
}

// Вставляем заказ в БД
if ($client_id !== 'NULL') {
    // Для авторизованного пользователя - id_client = user_id
    $sql = "INSERT INTO orders (id_client, id_materials, volume, data_Time, addres, comments, status) 
            VALUES ($client_id, $material_id, '$volume', '$datetime', '$address', '$comments', 1)";
} else {
    // Для гостя - id_client = NULL
    $sql = "INSERT INTO orders (id_materials, volume, data_Time, addres, comments, status) 
            VALUES ($material_id, '$volume', '$datetime', '$address', '$comments', 1)";
}

if ($conn->query($sql)) {
    $order_id = $conn->insert_id;
    
    // Включаем обратно проверку внешних ключей
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Заказ успешно создан',
        'order_id' => $order_id
    ]);
} else {
    // Включаем обратно проверку внешних ключей
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при создании заказа: ' . $conn->error
    ]);
}
?>