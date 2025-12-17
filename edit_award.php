<?php
require_once 'config.php';

$pdo = connectDB();

$message = '';
$error = '';

// Получаем ID награды для редактирования
$award_code = $_GET['id'] ?? null;

if (!$award_code) {
    header('Location: awards_management.php?error=' . urlencode('Не указана награда для редактирования'));
    exit;
}

// Получаем данные текущей награды
$award = fetchOne($pdo, "
    SELECT a.*, 
           emp.last_name || ' ' || emp.first_name || ' ' || COALESCE(emp.middle_name, '') as employee_full_name,
           at.award_type_name
    FROM award a
    JOIN employee emp ON a.employee_number = emp.employee_number
    LEFT JOIN award_types at ON a.award_type_code = at.award_type_code
    WHERE a.award_code = ?
", [$award_code]);

if (!$award) {
    header('Location: awards_management.php?error=' . urlencode('Награда не найдена'));
    exit;
}

// Получаем списки для выпадающих меню
$employees = fetchAll($pdo, "
    SELECT employee_number, last_name || ' ' || first_name as full_name 
    FROM employee 
    ORDER BY last_name, first_name
");

$award_types = fetchAll($pdo, "SELECT award_type_code, award_type_name FROM award_types ORDER BY award_type_name");

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $employee_number = intval($_POST['employee_number']);
        $award_name = trim($_POST['award_name'] ?? '');
        $award_date = $_POST['award_date'];
        $award_type_code = !empty($_POST['award_type_code']) ? intval($_POST['award_type_code']) : null;
        $description = trim($_POST['description'] ?? '');
        
        if (empty($award_name)) {
            throw new Exception('Название награды обязательно');
        }
        
        if (empty($award_date)) {
            throw new Exception('Дата награждения обязательна');
        }
        
        if ($award_date > date('Y-m-d')) {
            throw new Exception('Дата награждения не может быть в будущем');
        }
        
        // Обновляем данные
        $sql = "UPDATE award SET 
                employee_number = ?, 
                award_name = ?, 
                award_date = ?, 
                award_type_code = ?, 
                description = ?
                WHERE award_code = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $employee_number, 
            $award_name, 
            $award_date, 
            $award_type_code, 
            $description,
            $award_code
        ]);
        
        $message = "✅ Награда успешно обновлена!";
        
        // Обновляем данные для отображения
        $award = fetchOne($pdo, "
            SELECT a.*, 
                   emp.last_name || ' ' || emp.first_name || ' ' || COALESCE(emp.middle_name, '') as employee_full_name,
                   at.award_type_name
            FROM award a
            JOIN employee emp ON a.employee_number = emp.employee_number
            LEFT JOIN award_types at ON a.award_type_code = at.award_type_code
            WHERE a.award_code = ?
        ", [$award_code]);
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование награды</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 25px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 25px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-success {
            background: #2ecc71;
        }
        
        .btn-back {
            background: #7f8c8d;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .current-data {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .award-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #fff3e0;
            color: #e65100;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Редактирование награды</h1>
            <p>Код награды: <?php echo htmlspecialchars($award_code); ?></p>
        </div>
        
        <div class="card">
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="current-data">
                <h3>Текущие данные:</h3>
                <p><strong>Сотрудник:</strong> <?php echo htmlspecialchars($award['employee_full_name']); ?></p>
                <p><strong>Название награды:</strong> 
                    <span class="award-badge">
                        🏆 <?php echo htmlspecialchars($award['award_name']); ?>
                    </span>
                </p>
                <p><strong>Тип награды:</strong> <?php echo htmlspecialchars($award['award_type_name'] ?: 'Не указан'); ?></p>
                <p><strong>Дата награждения:</strong> <?php echo htmlspecialchars($award['award_date']); ?></p>
                <p><strong>Описание:</strong> <?php echo htmlspecialchars($award['description'] ?: 'Не указано'); ?></p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Сотрудник *</label>
                    <select name="employee_number" required>
                        <option value="">Выберите сотрудника</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_number']; ?>"
                                <?php echo ($award['employee_number'] == $emp['employee_number']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Название награды *</label>
                    <input type="text" name="award_name" required 
                           value="<?php echo htmlspecialchars($award['award_name']); ?>"
                           placeholder="Лучший сотрудник месяца">
                </div>
                
                <div class="form-group">
                    <label>Тип награды</label>
                    <select name="award_type_code">
                        <option value="">Не указан</option>
                        <?php foreach ($award_types as $type): ?>
                            <option value="<?php echo $type['award_type_code']; ?>"
                                <?php echo ($award['award_type_code'] == $type['award_type_code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['award_type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Дата награждения *</label>
                    <input type="date" name="award_date" required 
                           value="<?php echo htmlspecialchars($award['award_date']); ?>"
                           max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Описание награды</label>
                    <textarea name="description" 
                              placeholder="Дополнительная информация о награде, причина награждения"><?php echo htmlspecialchars($award['description'] ?? ''); ?></textarea>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <button type="submit" class="btn btn-success">💾 Сохранить изменения</button>
                    <a href="awards_management.php" class="btn btn-back">← Назад к списку</a>
                    <a href="index.php" class="btn">🏠 На главную</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php 
closeDB($pdo);
?>