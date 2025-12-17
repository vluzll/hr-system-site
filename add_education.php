<?php
require_once 'config.php';

$pdo = connectDB();

// Получаем списки для выпадающих меню
$employees = fetchAll($pdo, "SELECT employee_number, last_name || ' ' || first_name as full_name FROM employee ORDER BY last_name");
$education_types = fetchAll($pdo, "SELECT education_type_code, education_type_name FROM education_type ORDER BY education_type_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Получаем данные формы
        $employee_number = intval($_POST['employee_number']);
        $education_type_code = intval($_POST['education_type_code']);
        $educational_institution = trim($_POST['educational_institution']);
        $specialty = trim($_POST['specialty']);
        $graduation_year = intval($_POST['graduation_year']);
        
        // Генерируем код документа
        $max_code = fetchOne($pdo, "SELECT MAX(education_document_code) as max_code FROM education");
        $next_code = ($max_code['max_code'] ?? 0) + 1;
        
        // Вставляем данные
        $sql = "INSERT INTO education (education_document_code, employee_number, education_type_code, educational_institution, specialty, graduation_year) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$next_code, $employee_number, $education_type_code, $educational_institution, $specialty, $graduation_year]);
        
        header('Location: index.php?success=' . urlencode("✅ Образование успешно добавлено"));
        exit;
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Добавить образование</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: auto; }
        .header { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); color: white; padding: 25px; border-radius: 10px 10px 0 0; }
        .card { background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50; }
        input, select { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn { padding: 12px 25px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn-success { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); }
        .btn-back { background: #7f8c8d; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Добавить образование</h1>
        </div>
        
        <div class="card">
            <?php if (isset($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Сотрудник *</label>
                        <select name="employee_number" required>
                            <option value="">Выберите сотрудника</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_number']; ?>">
                                <?php echo htmlspecialchars($emp['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Вид образования *</label>
                        <select name="education_type_code" required>
                            <option value="">Выберите вид</option>
                            <?php foreach ($education_types as $type): ?>
                            <option value="<?php echo $type['education_type_code']; ?>">
                                <?php echo htmlspecialchars($type['education_type_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Учебное заведение *</label>
                    <input type="text" name="educational_institution" required 
                           placeholder="Московский государственный университет">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Специальность *</label>
                        <input type="text" name="specialty" required placeholder="Экономика">
                    </div>
                    
                    <div class="form-group">
                        <label>Год окончания *</label>
                        <input type="number" name="graduation_year" required 
                               min="1950" max="<?php echo date('Y'); ?>" 
                               value="<?php echo date('Y'); ?>">
                    </div>
                </div>
                
                <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                    <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                        <span style="margin-right: 10px;">💾</span> Сохранить
                    </button>
                    <a href="index.php" class="btn btn-back" style="padding: 12px 25px; margin-left: 10px;">
                        <span style="margin-right: 10px;">←</span> Назад
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>