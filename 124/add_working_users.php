<?php
// add_working_users.php - ДОБАВЛЯЕТ ВСЕХ РАБОЧИХ ПОЛЬЗОВАТЕЛЕЙ
$conn = new mysqli('localhost', 'root', '', 'green_mile');

echo "<h2>Добавление ВСЕХ рабочих пользователей</h2>";
echo "<p>Все пароли: <strong>demo123</strong></p>";

// УДАЛЯЕМ старых пользователей и добавляем новых с ЧИСТЫМИ логинами
$conn->query("DELETE FROM users");

// ДОБАВЛЯЕМ ВСЕХ пользователей с ГАРАНТИРОВАННО РАБОЧИМИ логинами
$users = [
    // Директора
    ['admin', 'Администратор', 'Системы', 1, 'demo123'],
    ['director', 'Александр', 'Смирнов', 1, 'demo123'],
    
    // Диспетчеры
    ['dispatcher', 'Елена', 'Иванова', 2, 'demo123'],
    ['manager', 'Сергей', 'Козлов', 2, 'demo123'],
    ['operator', 'Наталья', 'Зайцева', 2, 'demo123'],
    
    // Водители
    ['driver1', 'Иван', 'Петров', 3, 'demo123'],
    ['driver2', 'Алексей', 'Волков', 3, 'demo123'],
    ['driver3', 'Татьяна', 'Николаева', 3, 'demo123'],
    ['driver4', 'Ольга', 'Морозова', 3, 'demo123'],
    ['driver5', 'Михаил', 'Фролов', 3, 'demo123'],
    
    // Клиенты
    ['client1', 'ООО "Ромашка"', 'Клиент', 4, 'demo123'],
    ['client2', 'ТЦ "Мега"', 'Клиент', 4, 'demo123'],
];

$added = 0;

foreach ($users as $user) {
    list($login, $first_name, $last_name, $role, $password) = $user;
    
    // Простой текстовый пароль (НЕ хэшируем для простоты)
    $sql = "INSERT INTO users (Login, First_Name, Last_Name, role, Password) 
            VALUES ('$login', '$first_name', '$last_name', $role, '$password')";
    
    if ($conn->query($sql)) {
        $added++;
        echo "<p style='color:green;'>✓ $login / $password (роль: $role)</p>";
    }
}

echo "<hr>";
echo "<h3>✅ ГОТОВО! Добавлено $added пользователей</h3>";

// Показываем таблицу
$result = $conn->query("SELECT * FROM users ORDER BY role, Login");
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #4CAF50; color: white;'>";
echo "<th>Логин</th><th>Пароль</th><th>Роль</th><th>Имя</th>";
echo "</tr>";

while($row = $result->fetch_assoc()) {
    $role_text = ['1' => '👑 Директор', '2' => '📞 Диспетчер', '3' => '🚚 Водитель', '4' => '👤 Клиент'][$row['role']];
    
    echo "<tr>";
    echo "<td><strong>" . $row['Login'] . "</strong></td>";
    echo "<td>" . $row['Password'] . "</td>";
    echo "<td>" . $role_text . "</td>";
    echo "<td>" . $row['First_Name'] . " " . $row['Last_Name'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>🚀 БЫСТРЫЙ ДОСТУП:</h3>";
echo "<p><a href='login.php?login=admin&password=demo123' style='font-size: 1.2em; color: green;'>→ Войти как Админ (admin/demo123)</a></p>";
echo "<p><a href='login.php?login=dispatcher&password=demo123' style='font-size: 1.2em; color: blue;'>→ Войти как Диспетчер (dispatcher/demo123)</a></p>";
echo "<p><a href='login.php?login=driver1&password=demo123' style='font-size: 1.2em; color: orange;'>→ Войти как Водитель (driver1/demo123)</a></p>";
echo "</div>";

echo "<script>
// Автоматический переход через 5 секунд
setTimeout(function() {
    window.location.href = 'login.php?login=admin&password=demo123';
}, 5000);
</script>";

$conn->close();
?>