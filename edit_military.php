<?php
require_once 'config.php';

$pdo = connectDB();

// Получаем ID записи
$military_id = $_GET['id'] ?? '';
if (empty($military_id)) {
    header('Location: index.php');
    exit;
}

// Получаем данные записи
$record = fetchOne($pdo, "SELECT * FROM military_record WHERE military_id_number = ?", [$military_id]);
if (!$record) {
    header('Location: index.php?error=' . urlencode("Запись не найдена"));
    exit;
}

// Получаем список сотрудников
$employees = fetchAll($pdo, "SELECT employee_number, last_name || ' ' || first_name as full_name FROM employee ORDER BY last_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Получаем данные формы
        $employee_number = intval($_POST['employee_number']);
        $issued_by = trim($_POST['issued_by']);
        $military_position = trim($_POST['military_position']);
        $reserve_category = trim($_POST['reserve_category']);
        $record_group = trim($_POST['record_group']);
        $military_composition = trim($_POST['military_composition']);
        
        // Обновляем данные
        $sql = "UPDATE military_record SET 
                employee_number = ?, 
                issued_by = ?, 
                military_position = ?, 
                reserve_category = ?, 
                record_group = ?, 
                military_composition = ?
                WHERE military_id_number = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employee_number, $issued_by, $military_position, $reserve_category, $record_group, $military_composition, $military_id]);
        
        header('Location: index.php?success=' . urlencode("✅ Запись воинского учета обновлена"));
        exit;
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Редактировать воинский учет</title>
    <style>
        /* Стили такие же как в add_military.php */
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
            <h1>✏️ Редактировать запись воинского учета</h1>
            <p>№ военного билета: <strong><?php echo htmlspecialchars($record['military_id_number']); ?></strong></p>
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
                        <label>Номер военного билета</label>
                        <input type="text" value="<?php echo htmlspecialchars($record['military_id_number']); ?>" disabled style="background: #f0f0f0;">
                        <small style="color: #6c757d;">Номер нельзя изменить</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Сотрудник *</label>
                        <select name="employee_number" required>
                            <option value="">Выберите сотрудника</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_number']; ?>"
                                <?php echo ($record['employee_number'] == $emp['employee_number']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Кем выдан *</label>
                        <input type="text" name="issued_by" required 
                               value="<?php echo htmlspecialchars($record['issued_by']); ?>"
                               placeholder="Военный комиссариат г. Москвы">
                    </div>
                    
                    <div class="form-group">
                        <label>Воинская должность *</label>
                        <input type="text" name="military_position" required 
                               value="<?php echo htmlspecialchars($record['military_position']); ?>"
                               placeholder="Командир отделения">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group military-field">
                        <label>Категория запаса *</label>
                        <select name="reserve_category" required>
                            <option value="">Выберите категорию</option>
                            <option value="А" <?php echo ($record['reserve_category'] == 'А') ? 'selected' : ''; ?>>А - годен к военной службе</option>
                            <option value="Б" <?php echo ($record['reserve_category'] == 'Б') ? 'selected' : ''; ?>>Б - годен с незначительными ограничениями</option>
                            <option value="В" <?php echo ($record['reserve_category'] == 'В') ? 'selected' : ''; ?>>В - ограниченно годен</option>
                            <option value="Г" <?php echo ($record['reserve_category'] == 'Г') ? 'selected' : ''; ?>>Г - временно не годен</option>
                            <option value="Д" <?php echo ($record['reserve_category'] == 'Д') ? 'selected' : ''; ?>>Д - не годен</option>
                        </select>
                    </div>
                    
                    <div class="form-group military-field">
                        <label>Группа учета *</label>
                        <select name="record_group" required>
                            <option value="">Выберите группу</option>
                            <option value="1" <?php echo ($record['record_group'] == '1') ? 'selected' : ''; ?>>1 - Первая</option>
                            <option value="2" <?php echo ($record['record_group'] == '2') ? 'selected' : ''; ?>>2 - Вторая</option>
                            <option value="3" <?php echo ($record['record_group'] == '3') ? 'selected' : ''; ?>>3 - Третья</option>
                            <option value="Специальная" <?php echo ($record['record_group'] == 'Специальная') ? 'selected' : ''; ?>>Специальная</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Состав *</label>
                    <select name="military_composition" required>
                        <option value="">Выберите состав</option>
                        <option value="Солдаты, матросы, сержанты, старшины" 
                            <?php echo ($record['military_composition'] == 'Солдаты, матросы, сержанты, старшины') ? 'selected' : ''; ?>>
                            Солдаты, матросы, сержанты, старшины
                        </option>
                        <option value="Прапорщики и мичманы" 
                            <?php echo ($record['military_composition'] == 'Прапорщики и мичманы') ? 'selected' : ''; ?>>
                            Прапорщики и мичманы
                        </option>
                        <option value="Офицерский состав" 
                            <?php echo ($record['military_composition'] == 'Офицерский состав') ? 'selected' : ''; ?>>
                            Офицерский состав
                        </option>
                        <option value="Высший офицерский состав" 
                            <?php echo ($record['military_composition'] == 'Высший офицерский состав') ? 'selected' : ''; ?>>
                            Высший офицерский состав
                        </option>
                    </select>
                </div>
                
                <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                    <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                        <span style="margin-right: 10px;">💾</span> Обновить запись
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