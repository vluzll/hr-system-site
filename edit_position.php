<?php
require_once 'config.php';

$pdo = connectDB();

$message = '';
$error = '';

// Получаем ID должности для редактирования
$position_code = $_GET['id'] ?? null;

if (!$position_code) {
    header('Location: positions_management.php?error=' . urlencode('Не указана должность для редактирования'));
    exit;
}

// Получаем данные текущей должности
$position = fetchOne($pdo, "
    SELECT p.*, d.department_name 
    FROM position p 
    LEFT JOIN department d ON p.department_id = d.department_code 
    WHERE p.position_code = ?
", [$position_code]);

if (!$position) {
    header('Location: positions_management.php?error=' . urlencode('Должность не найдена'));
    exit;
}

// Получаем список отделов для выпадающего списка
$departments = fetchAll($pdo, "SELECT department_code, department_name FROM department ORDER BY department_name");

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $position_name = trim($_POST['position_name'] ?? '');
        $salary_min = !empty($_POST['salary_min']) ? intval($_POST['salary_min']) : null;
        $salary_max = !empty($_POST['salary_max']) ? intval($_POST['salary_max']) : null;
        $description = trim($_POST['description'] ?? '');
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        
        if (empty($position_name)) {
            throw new Exception('Название должности обязательно');
        }
        
        // Проверяем диапазон зарплат
        if ($salary_min && $salary_max && $salary_min > $salary_max) {
            throw new Exception('Минимальная зарплата не может быть больше максимальной');
        }
        
        // Обновляем данные
        $sql = "UPDATE position SET 
                position_name = ?, 
                salary_min = ?, 
                salary_max = ?, 
                description = ?, 
                department_id = ?
                WHERE position_code = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $position_name, 
            $salary_min, 
            $salary_max, 
            $description, 
            $department_id,
            $position_code
        ]);
        
        $message = "✅ Должность успешно обновлена!";
        
        // Обновляем данные для отображения
        $position = fetchOne($pdo, "
            SELECT p.*, d.department_name 
            FROM position p 
            LEFT JOIN department d ON p.department_id = d.department_code 
            WHERE p.position_code = ?
        ", [$position_code]);
        
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
    <title>Редактирование должности</title>
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
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Редактирование должности</h1>
            <p>Код: <?php echo htmlspecialchars($position_code); ?></p>
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
                <p><strong>Название:</strong> <?php echo htmlspecialchars($position['position_name']); ?></p>
                <p><strong>Отдел:</strong> <?php echo htmlspecialchars($position['department_name'] ?: 'Не указан'); ?></p>
                <p><strong>Зарплата:</strong> 
                    <?php 
                    if ($position['salary_min'] && $position['salary_max']) {
                        echo number_format($position['salary_min'], 0, ',', ' ') . ' - ' . number_format($position['salary_max'], 0, ',', ' ') . ' ₽';
                    } elseif ($position['salary_min']) {
                        echo 'от ' . number_format($position['salary_min'], 0, ',', ' ') . ' ₽';
                    } elseif ($position['salary_max']) {
                        echo 'до ' . number_format($position['salary_max'], 0, ',', ' ') . ' ₽';
                    } else {
                        echo 'Не указана';
                    }
                    ?>
                </p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Название должности *</label>
                    <input type="text" name="position_name" required 
                           value="<?php echo htmlspecialchars($position['position_name']); ?>"
                           placeholder="Менеджер по продажам">
                </div>
                
                <div class="form-group">
                    <label>Отдел</label>
                    <select name="department_id">
                        <option value="">Не указан</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_code']; ?>"
                                <?php echo ($position['department_id'] == $dept['department_code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Минимальная зарплата (₽)</label>
                        <input type="number" name="salary_min" 
                               value="<?php echo htmlspecialchars($position['salary_min'] ?: ''); ?>"
                               placeholder="50000" min="0" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label>Максимальная зарплата (₽)</label>
                        <input type="number" name="salary_max" 
                               value="<?php echo htmlspecialchars($position['salary_max'] ?: ''); ?>"
                               placeholder="100000" min="0" step="1000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Описание должности</label>
                    <textarea name="description" 
                              placeholder="Обязанности, требования, условия работы"><?php echo htmlspecialchars($position['description'] ?? ''); ?></textarea>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <button type="submit" class="btn btn-success">💾 Сохранить изменения</button>
                    <a href="positions_management.php" class="btn btn-back">← Назад к списку</a>
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