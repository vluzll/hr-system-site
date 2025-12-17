<?php
require_once 'config.php';

$pdo = connectDB();

$message = '';
$error = '';

// Получаем ID отдела для редактирования
$department_code = $_GET['id'] ?? null;

if (!$department_code) {
    header('Location: departments_management.php?error=' . urlencode('Не указан отдел для редактирования'));
    exit;
}

// Получаем данные текущего отдела
$department = fetchOne($pdo, "SELECT * FROM department WHERE department_code = ?", [$department_code]);

if (!$department) {
    header('Location: departments_management.php?error=' . urlencode('Отдел не найден'));
    exit;
}

// Получаем список руководителей
$managers = fetchAll($pdo, "
    SELECT employee_number, last_name || ' ' || first_name as full_name
    FROM employee 
    ORDER BY last_name, first_name
");

// Получаем статистику по сотрудникам в отделе
$stats = fetchOne($pdo, "
    SELECT COUNT(e.employee_number) as employee_count
    FROM employment_contract ec
    JOIN employee e ON ec.employee_number = e.employee_number
    WHERE ec.department_code = ? AND ec.contract_status = 'Действующий'
", [$department_code]);

// Получаем имя руководителя (если есть)
$manager_name = '';
if (!empty($department['manager_number'])) {
    $manager = fetchOne($pdo, "
        SELECT last_name || ' ' || first_name as full_name 
        FROM employee WHERE employee_number = ?
    ", [$department['manager_number']]);
    $manager_name = $manager['full_name'] ?? '';
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $department_name = trim($_POST['department_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $manager_number = !empty($_POST['manager_number']) ? intval($_POST['manager_number']) : null;
        
        if (empty($department_name)) {
            throw new Exception('Название отдела обязательно');
        }
        
        // Проверяем, не используется ли это название другим отделом
        $existing = fetchOne($pdo, 
            "SELECT department_code FROM department WHERE department_name = ? AND department_code != ?", 
            [$department_name, $department_code]
        );
        
        if ($existing) {
            throw new Exception('Отдел с таким названием уже существует');
        }
        
        // Обновляем данные
        $sql = "UPDATE department SET 
                department_name = ?, 
                description = ?, 
                manager_number = ?
                WHERE department_code = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$department_name, $description, $manager_number, $department_code]);
        
        $message = "✅ Отдел успешно обновлен!";
        
        // Обновляем данные для отображения
        $department = fetchOne($pdo, "SELECT * FROM department WHERE department_code = ?", [$department_code]);
        
        // Обновляем имя руководителя
        if (!empty($department['manager_number'])) {
            $manager = fetchOne($pdo, "
                SELECT last_name || ' ' || first_name as full_name 
                FROM employee WHERE employee_number = ?
            ", [$department['manager_number']]);
            $manager_name = $manager['full_name'] ?? '';
        } else {
            $manager_name = '';
        }
        
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
    <title>Редактирование отдела</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1565c0;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Редактирование отдела</h1>
            <p>Код отдела: <?php echo htmlspecialchars($department_code); ?></p>
        </div>
        
        <div class="card">
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['employee_count'] ?? 0; ?></div>
                    <div class="stat-label">👥 Сотрудников</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $department_code; ?></div>
                    <div class="stat-label">🔢 Код отдела</div>
                </div>
            </div>
            
            <div class="current-data">
                <h3>Текущие данные:</h3>
                <p><strong>Название:</strong> <?php echo htmlspecialchars($department['department_name']); ?></p>
                <p><strong>Описание:</strong> <?php echo htmlspecialchars($department['description'] ?? 'Не указано'); ?></p>
                <?php if (!empty($department['manager_number'])): ?>
                    <p><strong>Руководитель:</strong> 
                        <?php echo htmlspecialchars($manager_name ?: 'Данные не найдены'); ?> 
                        (Таб.№<?php echo $department['manager_number']; ?>)
                    </p>
                <?php else: ?>
                    <p><strong>Руководитель:</strong> Не назначен</p>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Название отдела *</label>
                    <input type="text" name="department_name" required 
                           value="<?php echo htmlspecialchars($department['department_name']); ?>"
                           placeholder="Отдел продаж">
                </div>
                
                <div class="form-group">
                    <label>Описание отдела</label>
                    <textarea name="description" 
                              placeholder="Описание отдела и его функций"><?php echo htmlspecialchars($department['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Руководитель отдела</label>
                    <select name="manager_number">
                        <option value="">Не назначен</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?php echo $manager['employee_number']; ?>"
                                <?php echo (isset($department['manager_number']) && $department['manager_number'] == $manager['employee_number']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($manager['full_name']); ?> (№<?php echo $manager['employee_number']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($manager_name)): ?>
                        <div style="margin-top: 5px; font-size: 13px; color: #27ae60;">
                            Текущий руководитель: <strong><?php echo htmlspecialchars($manager_name); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <button type="submit" class="btn btn-success">💾 Сохранить изменения</button>
                    <a href="departments_management.php" class="btn btn-back">← Назад к списку</a>
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