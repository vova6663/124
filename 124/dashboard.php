<?php
// dashboard.php
session_start();

if(!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Green Mile</title>
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
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .user-info {
            text-align: right;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        
        .btn:hover {
            background: #45a049;
        }
        
        .btn-secondary {
            background: #666;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #666;
            border-top: 1px solid #ddd;
            margin-top: 30px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 15px;
            font-size: 0.9em;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Green Mile - Личный кабинет</h1>
            <div class="user-info">
                <p>
                    <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
                    <span class="role-badge">
                        <?php 
                        switch($_SESSION['role']) {
                            case 1: echo "Директор"; break;
                            case 2: echo "Диспетчер"; break;
                            case 3: echo "Водитель"; break;
                            default: echo "Пользователь";
                        }
                        ?>
                    </span>
                </p>
                <a href="api/logout.php" class="btn btn-secondary">Выйти</a>
            </div>
        </header>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Мой профиль</h3>
                <p>Логин: <?php echo $_SESSION['login']; ?></p>
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
            </div>
            
            <?php if($_SESSION['role'] == 1): // Директор ?>
            <div class="dashboard-card">
                <h3>Администратор</h3>
                <p>Панель управления системой</p>
                <a href="admin/index.php?page=dashboard" class="btn">Админ-панель</a>
            </div>
            <?php endif; ?>
            
            <?php if($_SESSION['role'] == 2): // Диспетчер ?>
            <div class="dashboard-card">
                <h3>Диспетчер</h3>
                <p>Управление заказами и маршрутами</p>
                <a href="dispatcher.php" class="btn">Панель диспетчера</a>
            </div>
            <?php endif; ?>
            
            <?php if($_SESSION['role'] == 3): // Водитель ?>
            <div class="dashboard-card">
                <h3>Водитель</h3>
                <p>Мои заказы и маршруты</p>
                <a href="driver.php" class="btn">Панель водителя</a>
            </div>
            <?php endif; ?>
            
            <div class="dashboard-card">
                <h3>На главную</h3>
                <p>Вернуться на главную страницу</p>
                <a href="index.php" class="btn">На главную</a>
            </div>
        </div>
        
        <footer>
            <p>&copy; 2026 Green Mile. Личный кабинет пользователя.</p>
        </footer>
    </div>
</body>
</html>