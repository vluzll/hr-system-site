<?php
require_once 'config.php';

$pdo = connectDB();

$message = '';
$error = '';

// Получаем ID образования для редактирования
$education_document_code = $_GET['id'] ?? null;

if (!$education_document_code) {
    header('Location: education_management.php?error=' . urlencode('Не указана запись об образовании для редактирования'));
    exit;
}

// Получаем данные текущей записи об образовании
$education = fetchOne($pdo, "
    SELECT e.*, 
           emp.last_name || ' ' || emp.first_name || ' ' || COALESCE(emp.middle_name, '') as employee_full_name,
           et.education_type_name
    FROM education e
    JOIN employee emp ON e.employee_number = emp.employee_number
    JOIN education_type et ON e.education_type_code = et.education_type_code
    WHERE e.education_document_code = ?
", [$education_document_code]);

if (!$education) {
    header('Location: education_management.php?error=' . urlencode('Запись об образовании не найдена'));
    exit;
}

// Получаем списки для выпадающих меню
$employees = fetchAll($pdo, "
    SELECT employee_number, last_name || ' ' || first_name as full_name 
    FROM employee 
    ORDER BY last_name, first_name
");

$education_types = fetchAll($pdo, "SELECT education_type_code, education_type_name FROM education_type ORDER BY education_type_name");

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $employee_number = intval($_POST['employee_number']);
        $education_type_code = intval($_POST['education_type_code']);
        $educational_institution = trim($_POST['educational_institution'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $graduation_year = intval($_POST['graduation_year']);
        
        if (empty($educational_institution)) {
            throw new Exception('Название учебного заведения обязательно');
        }
        
        if (empty($specialty)) {
            throw new Exception('Специальность обязательна');
        }
        
        if ($graduation_year < 1900 || $graduation_year > date('Y')) {
            throw new Exception('Год окончания должен быть от 1900 до ' . date('Y'));
        }
        
        // Обновляем данные
        $sql = "UPDATE education SET 
                employee_number = ?, 
                education_type_code = ?, 
                educational_institution = ?, 
                specialty = ?, 
                graduation_year = ?
                WHERE education_document_code = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $employee_number, 
            $education_type_code, 
            $educational_institution, 
            $specialty, 
            $graduation_year,
            $education_document_code
        ]);
        
        $message = "✅ Запись об образовании успешно обновлена!";
        
        // Обновляем данные для отображения
        $education = fetchOne($pdo, "
            SELECT e.*, 
                   emp.last_name || ' ' || emp.first_name || ' ' || COALESCE(emp.middle_name, '') as employee_full_name,
                   et.education_type_name
            FROM education e
            JOIN employee emp ON e.employee_number = emp.employee_number
            JOIN education_type et ON e.education_type_code = et.education_type_code
            WHERE e.education_document_code = ?
        ", [$education_document_code]);
        
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
    <title>Редактирование образования</title>
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
        
        .education-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Редактирование образования</h1>
            <p>Код документа: <?php echo htmlspecialchars($education_document_code); ?></p>
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
                <p><strong>Сотрудник:</strong> <?php echo htmlspecialchars($education['employee_full_name']); ?></p>
                <p><strong>Вид образования:</strong> 
                    <span class="education-badge">
                        <?php echo htmlspecialchars($education['education_type_name']); ?>
                    </span>
                </p>
                <p><strong>Учебное заведение:</strong> <?php echo htmlspecialchars($education['educational_institution']); ?></p>
                <p><strong>Специальность:</strong> <?php echo htmlspecialchars($education['specialty']); ?></p>
                <p><strong>Год окончания:</strong> <?php echo htmlspecialchars($education['graduation_year']); ?></p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Сотрудник *</label>
                    <select name="employee_number" required>
                        <option value="">Выберите сотрудника</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_number']; ?>"
                                <?php echo ($education['employee_number'] == $emp['employee_number']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Вид образования *</label>
                    <select name="education_type_code" required>
                        <option value="">Выберите вид образования</option>
                        <?php foreach ($education_types as $type): ?>
                            <option value="<?php echo $type['education_type_code']; ?>"
                                <?php echo ($education['education_type_code'] == $type['education_type_code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['education_type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Название учебного заведения *</label>
                    <input type="text" name="educational_institution" required 
                           value="<?php echo htmlspecialchars($education['educational_institution']); ?>"
                           placeholder="Московский государственный университет">
                </div>
                
                <div class="form-group">
                    <label>Специальность *</label>
                    <input type="text" name="specialty" required 
                           value="<?php echo htmlspecialchars($education['specialty']); ?>"
                           placeholder="Менеджмент">
                </div>
                
                <div class="form-group">
                    <label>Год окончания *</label>
                    <input type="number" name="graduation_year" required 
                           value="<?php echo htmlspecialchars($education['graduation_year']); ?>"
                           min="1900" max="<?php echo date('Y'); ?>" step="1"
                           placeholder="2020">
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <button type="submit" class="btn btn-success">💾 Сохранить изменения</button>
                    <a href="education_management.php" class="btn btn-back">← Назад к списку</a>
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