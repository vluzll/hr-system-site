<?php
require_once 'config.php';

$pdo = connectDB();

// Получаем списки для выпадающих меню
$employees = fetchAll($pdo, "SELECT employee_number, last_name || ' ' || first_name as full_name FROM employee ORDER BY last_name");
$award_types = fetchAll($pdo, "SELECT award_type_code, award_type_name FROM award_types ORDER BY award_type_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Получаем данные формы
        $employee_number = intval($_POST['employee_number']);
        $award_type_code = intval($_POST['award_type_code']);
        $award_date = $_POST['award_date'];
        
        // Генерируем код награды
        $max_code = fetchOne($pdo, "SELECT MAX(award_code) as max_code FROM award");
        $next_code = ($max_code['max_code'] ?? 0) + 1;
        
        // Получаем название награды
        $award_name = '';
        foreach ($award_types as $type) {
            if ($type['award_type_code'] == $award_type_code) {
                $award_name = $type['award_type_name'];
                break;
            }
        }
        
        // Вставляем данные
        $sql = "INSERT INTO award (award_code, employee_number, award_type_code, award_name, award_date) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$next_code, $employee_number, $award_type_code, $award_name, $award_date]);
        
        header('Location: index.php?success=' . urlencode("✅ Награда успешно добавлена"));
        exit;
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Добавить награду</title>
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
            <h1>🏆 Добавить награду</h1>
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
                        <label>Тип награды *</label>
                        <select name="award_type_code" required>
                            <option value="">Выберите награду</option>
                            <?php foreach ($award_types as $type): ?>
                            <option value="<?php echo $type['award_type_code']; ?>">
                                <?php echo htmlspecialchars($type['award_type_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Дата награждения *</label>
                    <input type="date" name="award_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                    <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                        <span style="margin-right: 10px;">💾</span> Сохранить награду
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