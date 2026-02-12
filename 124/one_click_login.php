<?php
// one_click_login.php - ОДИН КЛИК ДЛЯ ВХОДА
session_start();

$users = [
    'admin' => ['pass' => 'demo123', 'role' => 1, 'name' => 'Администратор'],
    'director' => ['pass' => 'demo123', 'role' => 1, 'name' => 'Директор'],
    'dispatcher' => ['pass' => 'demo123', 'role' => 2, 'name' => 'Диспетчер'],
    'driver1' => ['pass' => 'demo123', 'role' => 3, 'name' => 'Водитель 1'],
    'driver2' => ['pass' => 'demo123', 'role' => 3, 'name' => 'Водитель 2'],
];

echo "<h2>Вход одним кликом</h2>";
echo "<p>Выберите пользователя для автоматического входа:</p>";

foreach ($users as $login => $data) {
    $role_text = ['1' => '👑 Директор', '2' => '📞 Диспетчер', '3' => '🚚 Водитель'][$data['role']];
    
    echo "<div style='margin: 10px 0; padding: 15px; background: white; border-radius: 10px; border: 1px solid #ddd;'>";
    echo "<strong>$login</strong> - {$data['name']} ($role_text)";
    echo " <a href='auto_login.php?user=$login' style='float: right; padding: 5px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>ВОЙТИ</a>";
    echo "</div>";
}
?>