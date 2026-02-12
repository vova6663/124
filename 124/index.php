<<?php
// index.php - ГЛАВНАЯ СТРАНИЦА
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Green Mile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: #4CAF50;
            color: white;
            padding: 40px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        h1 {
            margin: 0;
            font-size: 2.5em;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        
        .btn:hover {
            background: #45a049;
        }
        
        .btn-secondary {
            background: #666;
        }
        
        .btn-secondary:hover {
            background: #555;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #666;
            border-top: 1px solid #ddd;
            margin-top: 30px;
        }
        
        .user-info {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Green Mile</h1>
            <p>Система автоматизации вывоза вторсырья</p>
        </header>
        
        <?php if(isset($_SESSION['login'])): ?>
            <div class="user-info">
                <p>Вы вошли как: <strong><?php echo $_SESSION['login']; ?></strong></p>
                <p>Роль: 
                    <?php 
                    switch($_SESSION['role']) {
                        case 1: echo "Директор"; break;
                        case 2: echo "Диспетчер"; break;
                        case 3: echo "Водитель"; break;
                        default: echo "Пользователь";
                    }
                    ?>
                </p>
                <a href="api/logout.php" class="btn btn-secondary">Выйти</a>
            </div>
        <?php endif; ?>
        
        <div class="main-content">
            <div class="card">
                <h3>Для клиентов</h3>
                <p>Заказать вывоз вторсырья онлайн</p>
                <a href="#" class="btn">Заказать вывоз</a>
            </div>
            
            <div class="card">
                <h3>Для сотрудников</h3>
                <p>Вход в систему управления</p>
                <?php if(isset($_SESSION['login'])): ?>
                    <a href="dashboard.php" class="btn">Личный кабинет</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Войти в систему</a>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>О системе</h3>
                <p>Автоматизация процессов вывоза вторсырья</p>
                <a href="#" class="btn">Подробнее</a>
            </div>
        </div>
        
        <footer>
            <p>&copy; 2026 Green Mile. Все права защищены.</p>
        </footer>
    </div>
</body>
</html>