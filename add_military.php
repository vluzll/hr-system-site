<?php
require_once 'config.php';

$pdo = connectDB();

// Получаем список сотрудников
$employees = fetchAll($pdo, "SELECT employee_number, last_name || ' ' || first_name as full_name FROM employee ORDER BY last_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Получаем данные формы
        $military_id_number = trim($_POST['military_id_number']);
        $employee_number = intval($_POST['employee_number']);
        $issued_by = trim($_POST['issued_by']);
        $military_position = trim($_POST['military_position']);
        $reserve_category = trim($_POST['reserve_category']);
        $record_group = trim($_POST['record_group']);
        $military_composition = trim($_POST['military_composition']);
        
        // Вставляем данные
        $sql = "INSERT INTO military_record (military_id_number, employee_number, issued_by, military_position, reserve_category, record_group, military_composition) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$military_id_number, $employee_number, $issued_by, $military_position, $reserve_category, $record_group, $military_composition]);
        
        header('Location: index.php?success=' . urlencode("✅ Запись воинского учета добавлена"));
        exit;
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Добавить воинский учет</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: auto; }
        .header { background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%); color: white; padding: 25px; border-radius: 10px 10px 0 0; }
        .card { background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50; }
        input, select, textarea { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 15px; }
        input:focus, select:focus { border-color: #3498db; outline: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn { padding: 12px 25px; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn-success { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); }
        .btn-back { background: #7f8c8d; }
        .btn:hover { opacity: 0.9; }
        .military-field { border-left: 4px solid #3498db; padding-left: 15px; background: #f8f9fa; padding: 15px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎖️ Добавить запись воинского учета</h1>
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
                        <label>Номер военного билета *</label>
                        <input type="text" name="military_id_number" required placeholder="АБ 1234567">
                    </div>
                    
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
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Кем выдан *</label>
                        <input type="text" name="issued_by" required placeholder="Военный комиссариат г. Москвы">
                    </div>
                    
                    <div class="form-group">
                        <label>Воинская должность *</label>
                        <input type="text" name="military_position" required placeholder="Командир отделения">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group military-field">
                        <label>Категория запаса *</label>
                        <select name="reserve_category" required>
                            <option value="">Выберите категорию</option>
                            <option value="А">А - годен к военной службе</option>
                            <option value="Б">Б - годен с незначительными ограничениями</option>
                            <option value="В">В - ограниченно годен</option>
                            <option value="Г">Г - временно не годен</option>
                            <option value="Д">Д - не годен</option>
                        </select>
                    </div>
                    
                    <div class="form-group military-field">
                        <label>Группа учета *</label>
                        <select name="record_group" required>
                            <option value="">Выберите группу</option>
                            <option value="1">1 - Первая</option>
                            <option value="2">2 - Вторая</option>
                            <option value="3">3 - Третья</option>
                            <option value="Специальная">Специальная</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Состав *</label>
                    <select name="military_composition" required>
                        <option value="">Выберите состав</option>
                        <option value="Солдаты, матросы, сержанты, старшины">Солдаты, матросы, сержанты, старшины</option>
                        <option value="Прапорщики и мичманы">Прапорщики и мичманы</option>
                        <option value="Офицерский состав">Офицерский состав</option>
                        <option value="Высший офицерский состав">Высший офицерский состав</option>
                    </select>
                </div>
                
                <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                    <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                        <span style="margin-right: 10px;">💾</span> Сохранить запись
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