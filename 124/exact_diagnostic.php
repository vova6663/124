<?php
// exact_diagnostic.php
echo "<h2>ТОЧНАЯ ДИАГНОСТИКА HEX ЗНАЧЕНИЙ</h2>";

$conn = new mysqli('localhost', 'root', '', 'green_mile');

// 1. Получаем ВСЕ символы каждого логина
echo "<h3>1. Детальный анализ каждого логина:</h3>";

$sql = "SELECT id_user, Login FROM users ORDER BY id_user";
$result = $conn->query($sql);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f2f2f2;'>";
echo "<th>ID</th><th>Login (визуально)</th><th>Длина</th><th>По символам</th><th>HEX</th><th>ASCII коды</th>";
echo "</tr>";

while($row = $result->fetch_assoc()) {
    $login = $row['Login'];
    $len = strlen($login);
    $hex = bin2hex($login);
    
    // Разбираем по символам
    $chars = [];
    $ascii_codes = [];
    for ($i = 0; $i < $len; $i++) {
        $char = $login[$i];
        $chars[] = htmlspecialchars($char);
        $ascii_codes[] = ord($char);
    }
    
    echo "<tr>";
    echo "<td>" . $row['id_user'] . "</td>";
    echo "<td><strong>" . htmlspecialchars($login) . "</strong></td>";
    echo "<td>" . $len . "</td>";
    echo "<td>" . implode(' ', $chars) . "</td>";
    echo "<td><code>" . chunk_split($hex, 2, ' ') . "</code></td>";
    echo "<td>" . implode(' ', $ascii_codes) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Проверяем поиск разными способами
echo "<h3>2. Поиск 'ivanova_ed' разными способами:</h3>";

$search_term = 'ivanova_ed';
echo "<p>Ищем: <strong>'$search_term'</strong></p>";

// Способ 1: Точное совпадение
$sql = "SELECT * FROM users WHERE Login = '$search_term'";
$result = $conn->query($sql);
echo "1. Точное совпадение: " . ($result->num_rows > 0 ? "✓ НАЙДЕНО" : "✗ НЕ НАЙДЕНО") . "<br>";

// Способ 2: Бинарное сравнение
$sql = "SELECT * FROM users WHERE BINARY Login = '$search_term'";
$result = $conn->query($sql);
echo "2. Бинарное сравнение: " . ($result->num_rows > 0 ? "✓ НАЙДЕНО" : "✗ НЕ НАЙДЕНО") . "<br>";

// Способ 3: LIKE
$sql = "SELECT * FROM users WHERE Login LIKE '%$search_term%'";
$result = $conn->query($sql);
echo "3. LIKE '%$search_term%': " . ($result->num_rows > 0 ? "✓ НАЙДЕНО" : "✗ НЕ НАЙДЕНО") . "<br>";

// Способ 4: Сравнение в PHP после выборки
$sql = "SELECT Login FROM users";
$result = $conn->query($sql);
$found_in_php = false;
$actual_login = '';
while($row = $result->fetch_assoc()) {
    if (trim($row['Login']) === $search_term) {
        $found_in_php = true;
        $actual_login = $row['Login'];
    }
}
echo "4. PHP сравнение после выборки: " . ($found_in_php ? "✓ НАЙДЕНО" : "✗ НЕ НАЙДЕНО") . "<br>";

// 3. Создаем новый корректный пользователь
echo "<h3>3. Создаем гарантированно рабочего пользователя:</h3>";

// Удаляем старую запись если есть
$conn->query("DELETE FROM users WHERE Login = 'ivanova_fixed'");

// Создаем новую запись с чистым логином
$clean_login = 'ivanova_fixed';
$clean_password = password_hash('demo123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (Login, First_Name, Last_Name, role, Password) 
        VALUES ('$clean_login', 'Елена', 'Иванова (исправленная)', 2, '$clean_password')";

if ($conn->query($sql)) {
    echo "<p style='color:green;'>✓ Создан пользователь 'ivanova_fixed' с паролем 'demo123'</p>";
} else {
    echo "<p style='color:red;'>✗ Ошибка: " . $conn->error . "</p>";
}

// 4. Показываем что искать
echo "<h3>4. Рекомендуемые логины для входа:</h3>";
echo "<ul>";
echo "<li><strong>ivanova_fixed</strong> / demo123 (Диспетчер - исправленный)</li>";
echo "<li><strong>admin</strong> / admin123 (если создавали ранее)</li>";
echo "<li><strong>test</strong> / test123 (если создавали ранее)</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='login.php'>Перейти к входу</a></p>";

$conn->close();
?>