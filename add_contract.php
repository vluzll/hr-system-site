<?php
require_once 'config.php';

$pdo = connectDB();

// Получаем списки для выпадающих меню
$employees = fetchAll($pdo, "SELECT employee_number, last_name || ' ' || first_name as full_name FROM employee ORDER BY last_name");
$departments = fetchAll($pdo, "SELECT department_code, department_name FROM department ORDER BY department_name");
$positions = fetchAll($pdo, "SELECT position_code, position_name FROM position ORDER BY position_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Получаем данные формы
        $contract_number = trim($_POST['contract_number']);
        $employee_number = intval($_POST['employee_number']);
        $department_code = !empty($_POST['department_code']) ? intval($_POST['department_code']) : null;
        $position_code = !empty($_POST['position_code']) ? intval($_POST['position_code']) : null;
        $salary = floatval($_POST['salary']);
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $contract_status = $_POST['contract_status'];
        
        // Вставляем данные
        $sql = "INSERT INTO employment_contract (contract_number, employee_number, department_code, position_code, salary, start_date, end_date, contract_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$contract_number, $employee_number, $department_code, $position_code, $salary, $start_date, $end_date, $contract_status]);
        
        header('Location: index.php?success=' . urlencode("✅ Договор успешно добавлен"));
        exit;
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Добавить договор</title>
    <style>
        /* Стили как в add_employee.php */
        body { font-family: Arial; padding: 20px; }
        .container { max-width: 600px; margin: auto; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; }
        .btn { padding: 12px 25px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn-success { background: #2ecc71; }
        .btn-back { background: #7f8c8d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>➕ Добавить трудовой договор</h1>
        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label>Номер договора *</label>
                    <input type="text" name="contract_number" required placeholder="ТД-2024-001">
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
                
                <div class="form-group">
                    <label>Отдел</label>
                    <select name="department_code">
                        <option value="">Не указан</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_code']; ?>">
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Должность</label>
                    <select name="position_code">
                        <option value="">Не указана</option>
                        <?php foreach ($positions as $pos): ?>
                        <option value="<?php echo $pos['position_code']; ?>">
                            <?php echo htmlspecialchars($pos['position_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Оклад *</label>
                    <input type="number" name="salary" required step="0.01" min="0" placeholder="50000.00">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Дата начала *</label>
                        <input type="date" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Дата окончания</label>
                        <input type="date" name="end_date">
                        <small style="color: #6c757d;">Оставьте пустым для бессрочного</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Статус *</label>
                    <select name="contract_status" required>
                        <option value="Действующий">Действующий</option>
                        <option value="Расторгнут">Расторгнут</option>
                        <option value="Завершен">Завершен</option>
                    </select>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-success">💾 Сохранить договор</button>
                    <a href="index.php" class="btn btn-back">← Назад</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>