<?php
// login.php - ПРОСТАЯ ВЕРСИЯ
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Green Mile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 300px;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        
        button {
            width: 100%;
            padding: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        button:hover {
            background: #45a049;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info {
            margin-top: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #666;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Green Mile</h1>
        <p style="text-align:center; color:#666; margin-bottom:20px;">Вход в систему</p>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <form action="api/auth_api.php" method="POST">
            <div class="form-group">
                <label for="Login">Логин:</label>
                <input type="text" id="Login" name="Login" required 
                       placeholder="Введите логин" value="testuser">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Введите пароль" value="test123">
            </div>
            
            <button type="submit">Войти</button>
        </form>
        
        <div class="info">
            <p><strong>Тестовый доступ:</strong></p>
            <p>Логин: <strong>testuser</strong></p>
            <p>Пароль: <strong>test123</strong></p>
            <p style="margin-top: 10px; font-size: 12px; color: #666;">
                <a href="add_test_user.php" style="color: #2196F3;">Создать тестового пользователя</a>
            </p>
        </div>
        
        <div class="back-link">
            <a href="index.php">← На главную</a>
        </div>
    </div>
</body>
</html>