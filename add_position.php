<?php
require_once 'config.php';

// Подключаемся к базе данных
$pdo = connectDB();

$message = '';
$error = '';

// Получаем список отделов для выпадающего списка
$departments = fetchAll($pdo, "SELECT department_code, department_name FROM department ORDER BY department_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $position_name = trim($_POST['position_name'] ?? '');
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $salary_min = !empty($_POST['salary_min']) ? floatval($_POST['salary_min']) : null;
        $salary_max = !empty($_POST['salary_max']) ? floatval($_POST['salary_max']) : null;
        $description = trim($_POST['description'] ?? '');
        
        if (empty($position_name)) {
            throw new Exception('Название должности обязательно');
        }        
        
        // Проверяем, существует ли уже такая должность
        $existing = fetchOne($pdo, "SELECT position_code FROM position WHERE position_name = ?", [$position_name]);
        if ($existing) {
            throw new Exception('Должность с таким названием уже существует');
        }        
        
        // Проверяем мин/макс зарплату
        if ($salary_min && $salary_max && $salary_min > $salary_max) {
            throw new Exception('Минимальная зарплата не может быть больше максимальной');
        }
        
        // Получаем следующий код должности
        $max_code = fetchOne($pdo, "SELECT MAX(position_code) as max_code FROM position");
        $next_code = ($max_code['max_code'] ?? 0) + 1;
        
        $sql = "INSERT INTO position (position_code, position_name, department_id, salary_min, salary_max, description) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$next_code, $position_name, $department_id, $salary_min, $salary_max, $description]);
        
        $message = "✅ Должность успешно добавлена! Код: $next_code";
        
        // Очищаем форму
        $_POST = [];
        
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
    <title>Добавить должность</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 20px;
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        .btn-success:hover {
            background: #27ae60;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>➕ Добавить должность</h1>
        </div>
        
        <div class="card">
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Название должности *</label>
                    <input type="text" name="position_name" required 
                           value="<?php echo htmlspecialchars($_POST['position_name'] ?? ''); ?>"
                           placeholder="Менеджер по продажам">
                </div>
                
                <div class="form-group">
                    <label>Отдел</label>
                    <select name="department_id">
                        <option value="">Не указан</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_code']; ?>"
                                <?php echo (($_POST['department_id'] ?? '') == $dept['department_code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Минимальная зарплата (руб.)</label>
                        <input type="number" name="salary_min" min="0" step="0.01"
                               value="<?php echo htmlspecialchars($_POST['salary_min'] ?? ''); ?>"
                               placeholder="50000.00">
                    </div>
                    
                    <div class="form-group">
                        <label>Максимальная зарплата (руб.)</label>
                        <input type="number" name="salary_max" min="0" step="0.01"
                               value="<?php echo htmlspecialchars($_POST['salary_max'] ?? ''); ?>"
                               placeholder="80000.00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Описание должности</label>
                    <textarea name="description" 
                              placeholder="Обязанности, требования и описание должности"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-success">💾 Сохранить должность</button>
                    <a href="index.php" class="btn btn-back">← Назад</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>