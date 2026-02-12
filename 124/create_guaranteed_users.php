<?php
// create_guaranteed_users.php
echo "<h2>Создание гарантированно рабочих пользователей</h2>";

$conn = new mysqli('localhost', 'root', '', 'green_mile');

// Удаляем старые гарантированные пользователи
$conn->query("DELETE FROM users WHERE Login IN ('gm_admin', 'gm_dispatcher', 'gm_driver', 'gm_test')");

// Создаем новых с ЧИСТЫМИ логинами
$users = [
    [
        'login' => 'gm_admin',
        'name' => 'Администратор',
        'lastname' => 'Гарантированный',
        'role' => 1,
        'pass' => 'admin123'
    ],
    [
        'login' => 'gm_dispatcher', 
        'name' => 'Диспетчер',
        'lastname' => 'Гарантированный',
        'role' => 2,
        'pass' => 'dispatcher123'
    ],
    [
        'login' => 'gm_driver',
        'name' => 'Водитель',
        'lastname' => 'Гарантированный', 
        'role' => 3,
        'pass' => 'driver123'
    ],
    [
        'login' => 'gm_test',
        'name' => 'Тестовый',
        'lastname' => 'Пользователь',
        'role' => 1,
        'pass' => 'test123'
    ]
];

foreach ($users as $user_data) {
    $hashed_password = password_hash($user_data['pass'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (Login, First_Name, Last_Name, role, Password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", 
        $user_data['login'],
        $user_data['name'],
        $user_data['lastname'],
        $user_data['role'],
        $hashed_password
    );
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>✓ Создан: {$user_data['login']} / {$user_data['pass']} (роль: {$user_data['role']})</p>";
    } else {
        echo "<p style='color:red;'>✗ Ошибка: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

// Также создаем копии оригинальных пользователей с чистыми логинами
echo "<h3>Копии оригинальных пользователей:</h3>";

$original_users = [
    ['ivanova_ed_clean', 'Елена', 'Иванова', 2, 'demo123'],
    ['smirnov_ad_clean', 'Александр', 'Смирнов', 1, 'demo123'],
    ['petrov_iv_clean', 'Иван', 'Петров', 3, 'demo123']
];

foreach ($original_users as $user) {
    $hashed_password = password_hash($user[4], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (Login, First_Name, Last_Name, role, Password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $user[0], $user[1], $user[2], $user[3], $hashed_password);
    
    if ($stmt->execute()) {
        echo "<p>✓ {$user[0]} / {$user[4]}</p>";
    }
    
    $stmt->close();
}

echo "<hr>";
echo "<h3>ГАРАНТИРОВАННО РАБОЧИЕ ЛОГИНЫ:</h3>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px;'>";
echo "<h4>Основные тестовые аккаунты:</h4>";
echo "<ul>";
echo "<li><strong>gm_admin</strong> / admin123 (Директор)</li>";
echo "<li><strong>gm_dispatcher</strong> / dispatcher123 (Диспетчер)</li>";
echo "<li><strong>gm_driver</strong> / driver123 (Водитель)</li>";
echo "<li><strong>gm_test</strong> / test123 (Тестовый)</li>";
echo "</ul>";

echo "<h4>Копии оригиналов:</h4>";
echo "<ul>";
echo "<li><strong>ivanova_ed_clean</strong> / demo123</li>";
echo "<li><strong>smirnov_ad_clean</strong> / demo123</li>";
echo "<li><strong>petrov_iv_clean</strong> / demo123</li>";
echo "</ul>";
echo "</div>";

echo "<p style='margin-top: 20px;'><a href='login.php' style='font-size: 1.2em; font-weight: bold; color: green;'>→ ПЕРЕЙТИ К ВХОДУ</a></p>";

$conn->close();
?>