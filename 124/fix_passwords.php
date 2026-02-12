<?php
// fix_passwords.php - ДОБАВЛЯЕМ ПАРОЛИ СУЩЕСТВУЮЩИМ ПОЛЬЗОВАТЕЛЯМ
$conn = new mysqli('localhost', 'root', '', 'green_mile');

echo "<h2>Добавление паролей существующим пользователям</h2>";

// Добавляем поле Password если его нет
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'Password'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN Password VARCHAR(255) NOT NULL DEFAULT ''");
    echo "<p>✓ Добавлено поле Password</p>";
}

// Задаем пароли для каждого пользователя
$users_passwords = [
    'smirnov_ad' => 'Smirnov123',
    'ivanova_ed' => 'Ivanova123',
    'petrov_iv' => 'Petrov123',
    'sidorova_ma' => 'Sidorova123',
    'kozlov_sa' => 'Kozlov123',
    'volkov_ai' => 'Volkov123',
    'nikolaeva_t' => 'Nikolaeva123',
    'morozova_o' => 'Morozova123',
    'frolov_mp' => 'Frolov123',
    'zaitseva_n' => 'Zaitseva123'
];

foreach ($users_passwords as $login => $password) {
    $sql = "UPDATE users SET Password = '$password' WHERE Login = '$login'";
    if ($conn->query($sql)) {
        echo "<p>✓ $login : $password</p>";
    }
}

// Показываем результат
echo "<h3>Все пользователи с паролями:</h3>";
$result = $conn->query("SELECT Login, First_Name, Last_Name, role, Password FROM users");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Логин</th><th>Имя</th><th>Пароль</th><th>Роль</th></tr>";

while($row = $result->fetch_assoc()) {
    $role = match($row['role']) {
        1 => 'Директор',
        2 => 'Диспетчер',
        3 => 'Водитель',
        4 => 'Клиент',
        default => 'Неизвестно'
    };
    
    echo "<tr>";
    echo "<td><strong>{$row['Login']}</strong></td>";
    echo "<td>{$row['First_Name']} {$row['Last_Name']}</td>";
    echo "<td><code>{$row['Password']}</code></td>";
    echo "<td>$role</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Для входа используйте:</h3>";
echo "<ul>";
echo "<li><strong>Директор:</strong> smirnov_ad / Smirnov123</li>";
echo "<li><strong>Диспетчер:</strong> ivanova_ed / Ivanova123</li>";
echo "<li><strong>Водитель:</strong> petrov_iv / Petrov123</li>";
echo "</ul>";

$conn->close();
?>