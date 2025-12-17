<?php
require_once 'config.php';

// ПОДКЛЮЧАЕМСЯ К БАЗЕ ДАННЫХ
$pdo = connectDB();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $department_name = trim($_POST['department_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null;
        
        if (empty($department_name)) {
            throw new Exception('Название отдела обязательно');
        }
        
        // Проверяем, существует ли уже такой отдел
        // ИСПРАВЬТЕ НАЗВАНИЕ ТАБЛИЦЫ - должно быть department, а не departments
        $existing = fetchOne($pdo, "SELECT department_code FROM department WHERE department_name = ?", [$department_name]);
        if ($existing) {
            throw new Exception('Отдел с таким названием уже существует');
        }
        
        // Получаем следующий код отдела
        $max_code = fetchOne($pdo, "SELECT MAX(department_code) as max_code FROM department");
        $next_code = ($max_code['max_code'] ?? 0) + 1;
        
        $sql = "INSERT INTO department (department_code, department_name, description, manager_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$next_code, $department_name, $description, $manager_id]);
        
        $message = "✅ Отдел успешно добавлен! Код: " . $next_code;
        
        // Очищаем форму
        $_POST = [];
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}

// Получаем список руководителей
$managers = fetchAll($pdo, "
    SELECT employee_number, last_name || ' ' || first_name as full_name
    FROM employee 
    ORDER BY last_name, first_name
");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить отдел</title>
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
            <h1>➕ Добавить отдел</h1>
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
                    <label>Название отдела *</label>
                    <input type="text" name="department_name" required 
                           value="<?php echo htmlspecialchars($_POST['department_name'] ?? ''); ?>"
                           placeholder="Отдел продаж">
                </div>
                
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" 
                              placeholder="Описание отдела и его функций"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Руководитель отдела</label>
                    <select name="manager_number">
                        <option value="">Не указан</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?php echo $manager['employee_number']; ?>"
                                <?php echo (($_POST['manager_number'] ?? '') == $manager['employee_number']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($manager['full_name']); ?> (№<?php echo $manager['employee_number']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-success">💾 Сохранить отдел</button>
                    <a href="index.php" class="btn btn-back">← Назад</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>