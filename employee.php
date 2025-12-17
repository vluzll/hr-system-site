<?php
// Подключаем config.php
require_once 'config.php';

// Получаем ID сотрудника
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($employee_id <= 0) {
    header('Location: index.php');
    exit;
}

// ПОДКЛЮЧАЕМСЯ К БАЗЕ ДАННЫХ
$pdo = connectDB();

// Получаем данные сотрудника
$employee = fetchOne($pdo, "
    SELECT * FROM employee 
    WHERE employee_number = ?
", [$employee_id]);

if (!$employee) {
    die('<h2>Сотрудник не найден</h2>');
}

// Получаем текущий договор
$current_contract = fetchOne($pdo, "
    SELECT 
        ec.*,
        d.department_name,
        p.position_name
    FROM employment_contract ec
    LEFT JOIN department d ON ec.department_code = d.department_code
    LEFT JOIN position p ON ec.position_code = p.position_code
    WHERE ec.employee_number = ? 
        AND ec.contract_status = 'Действующий'
    LIMIT 1
", [$employee_id]);

// Рассчитываем возраст
$birth_date = new DateTime($employee['birth_date']);
$age = $birth_date->diff(new DateTime())->y;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Карточка сотрудника #<?php echo $employee_id; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 20px;
        }
        
        .card {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 30px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .header .info {
            margin-top: 10px;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .content {
            padding: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .label {
            font-weight: bold;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .value {
            font-size: 16px;
            color: #2c3e50;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-back {
            background: #7f8c8d;
        }
        
        .btn-back:hover {
            background: #666;
        }
        
        .salary {
            color: #27ae60;
            font-weight: bold;
            font-size: 18px;
        }
        
        .contact-info {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1><?php echo htmlspecialchars($employee['last_name'] . ' ' . $employee['first_name'] . ' ' . ($employee['middle_name'] ?: '')); ?></h1>
            <div class="info">
                Табельный номер: <?php echo $employee_id; ?> | 
                Возраст: <?php echo $age; ?> лет | 
                Стаж: <?php echo htmlspecialchars($employee['work_experience']); ?> лет
            </div>
        </div>
        
        <div class="content">
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Дата рождения</div>
                    <div class="value"><?php echo htmlspecialchars($employee['birth_date']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="label">ИНН</div>
                    <div class="value"><?php echo htmlspecialchars($employee['inn']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="label">СНИЛС</div>
                    <div class="value"><?php echo htmlspecialchars($employee['snils']); ?></div>
                </div>
            </div>
            
            <div class="contact-info">
                <div class="label">Контактная информация</div>
                <div class="value">
                    📞 Телефон: <?php echo htmlspecialchars($employee['phone']); ?><br>
                    📧 Email: <?php echo htmlspecialchars($employee['email'] ?: 'Не указан'); ?>
                </div>
            </div>
            
            <?php if ($current_contract): ?>
            <div style="margin-top: 30px; padding: 20px; background: #e8f5e9; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #2e7d32;">💼 Текущее место работы</h3>
                <div class="info-grid">
                    <div class="info-item" style="border-left-color: #2ecc71;">
                        <div class="label">Отдел</div>
                        <div class="value"><?php echo htmlspecialchars($current_contract['department_name']); ?></div>
                    </div>
                    
                    <div class="info-item" style="border-left-color: #2ecc71;">
                        <div class="label">Должность</div>
                        <div class="value"><?php echo htmlspecialchars($current_contract['position_name']); ?></div>
                    </div>
                    
                    <div class="info-item" style="border-left-color: #2ecc71;">
                        <div class="label">Оклад</div>
                        <div class="value salary"><?php echo number_format($current_contract['salary'], 0, ',', ' ') . ' ₽'; ?></div>
                    </div>
                    
                    <div class="info-item" style="border-left-color: #2ecc71;">
                        <div class="label">Дата приема</div>
                        <div class="value"><?php echo htmlspecialchars($current_contract['start_date']); ?></div>
                    </div>
                    
                    <div class="info-item" style="border-left-color: #2ecc71;">
                        <div class="label">Статус договора</div>
                        <div class="value" style="color: #27ae60; font-weight: bold;">
                            ✅ <?php echo htmlspecialchars($current_contract['contract_status']); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #856404;">⚠️ Нет действующего трудового договора</h3>
            </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <button class="btn btn-back" onclick="window.history.back()">← Назад</button>
                <a href="index.php" class="btn">🏠 На главную</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php 
// Закрываем соединение
closeDB($pdo);
?>