<?php
// quick_test.php
echo "<h2>Быстрый тест работоспособности</h2>";

echo "<p>Нажмите на ссылку для автоматического входа:</p>";
echo "<ul>";
echo "<li><a href='login.php?suggest=gm_admin'>Войти как Директор (gm_admin)</a></li>";
echo "<li><a href='login.php?suggest=gm_dispatcher'>Войти как Диспетчер (gm_dispatcher)</a></li>";
echo "<li><a href='login.php?suggest=gm_driver'>Войти как Водитель (gm_driver)</a></li>";
echo "<li><a href='login.php?suggest=gm_test'>Войти как Тестовый (gm_test)</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p>Или используйте форму ниже:</p>";

echo '<form action="api/auth_api.php" method="POST" style="margin: 20px; padding: 20px; background: #f8f9fa; border-radius: 10px;">';
echo '<label>Логин: <input type="text" name="Login" value="gm_admin"></label><br><br>';
echo '<label>Пароль: <input type="password" name="password" value="admin123"></label><br><br>';
echo '<input type="submit" value="Тест входа">';
echo '</form>';
?>