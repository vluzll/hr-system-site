<?php
require_once 'config.php';

$pdo = connectDB();

$message = '';
$error = '';

// Получаем ID договора для редактирования
$contract_number = $_GET['id'] ?? null;

if (!$contract_number) {
    header('Location: contracts_management.php?error=' . urlencode('Не указан договор для редактирования'));
    exit;
}

// Получаем данные текущего договора
$contract = fetchOne($pdo, "
    SELECT ec.*, 
           e.last_name || ' ' || e.first_name || ' ' || COALESCE(e.middle_name, '') as employee_full_name,
           d.department_name,
           p.position_name
    FROM employment_contract ec
    JOIN employee e ON ec.employee_number = e.employee_number
    LEFT JOIN department d ON ec.department_code = d.department_code
    LEFT JOIN position p ON ec.position_code = p.position_code
    WHERE ec.contract_number = ?
", [$contract_number]);

if (!$contract) {
    header('Location: contracts_management.php?error=' . urlencode('Договор не найден'));
    exit;
}

// Получаем списки для выпадающих меню
$employees = fetchAll($pdo, "
    SELECT employee_number, last_name || ' ' || first_name as full_name 
    FROM employee 
    ORDER BY last_name, first_name
");

$departments = fetchAll($pdo, "SELECT department_code, department_name FROM department ORDER BY department_name");
$positions = fetchAll($pdo, "SELECT position_code, position_name FROM position ORDER BY position_name");

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $employee_number = intval($_POST['employee_number']);
        $department_code = !empty($_POST['department_code']) ? intval($_POST['department_code']) : null;
        $position_code = !empty($_POST['position_code']) ? intval($_POST['position_code']) : null;
        $salary = intval($_POST['salary']);
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $contract_status = $_POST['contract_status'];
        
        if (empty($employee_number)) {
            throw new Exception('Сотрудник обязателен');
        }
        
        if (empty($salary) || $salary <= 0) {
            throw new Exception('Зарплата должна быть положительным числом');
        }
        
        if (empty($start_date)) {
            throw new Exception('Дата начала обязательна');
        }
        
        if ($end_date && $end_date < $start_date) {
            throw new Exception('Дата окончания не может быть раньше даты начала');
        }
        
        // Обновляем данные
        $sql = "UPDATE employment_contract SET 
                employee_number = ?, 
                department_code = ?, 
                position_code = ?, 
                salary = ?, 
                start_date = ?, 
                end_date = ?, 
                contract_status = ?
                WHERE contract_number = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $employee_number, 
            $department_code, 
            $position_code, 
            $salary, 
            $start_date, 
            $end_date, 
            $contract_status,
            $contract_number
        ]);
        
        $message = "✅ Договор успешно обновлен!";
        
        // Обновляем данные для отображения
        $contract = fetchOne($pdo, "
            SELECT ec.*, 
                   e.last_name || ' ' || e.first_name || ' ' || COALESCE(e.middle_name, '') as employee_full_name,
                   d.department_name,
                   p.position_name
            FROM employment_contract ec
            JOIN employee e ON ec.employee_number = e.employee_number
            LEFT JOIN department d ON ec.department_code = d.department_code
            LEFT JOIN position p ON ec.position_code = p.position_code
            WHERE ec.contract_number = ?
        ", [$contract_number]);
        
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
    <title>Редактирование договора</title>
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
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-terminated {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Редактирование договора</h1>
            <p>№ договора: <?php echo htmlspecialchars($contract_number); ?></p>
        </div>
        
        <div class="card">
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="current-data">
                <h3>Текущие данные договора:</h3>
                <p><strong>Сотрудник:</strong> <?php echo htmlspecialchars($contract['employee_full_name']); ?></p>
                <p><strong>Отдел:</strong> <?php echo htmlspecialchars($contract['department_name'] ?: 'Не указан'); ?></p>
                <p><strong>Должность:</strong> <?php echo htmlspecialchars($contract['position_name'] ?: 'Не указана'); ?></p>
                <p><strong>Оклад:</strong> <?php echo number_format($contract['salary'], 0, ',', ' ') . ' ₽'; ?></p>
                <p><strong>Дата начала:</strong> <?php echo htmlspecialchars($contract['start_date']); ?></p>
                <p><strong>Дата окончания:</strong> <?php echo htmlspecialchars($contract['end_date'] ?: 'Бессрочный'); ?></p>
                <p><strong>Статус:</strong> 
                    <?php 
                    $status_class = '';
                    if ($contract['contract_status'] == 'Действующий') {
                        $status_class = 'status-active';
                    } elseif ($contract['contract_status'] == 'Расторгнут') {
                        $status_class = 'status-terminated';
                    } else {
                        $status_class = 'status-completed';
                    }
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars($contract['contract_status']); ?>
                    </span>
                </p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Сотрудник *</label>
                    <select name="employee_number" required>
                        <option value="">Выберите сотрудника</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_number']; ?>"
                                <?php echo ($contract['employee_number'] == $emp['employee_number']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Отдел</label>
                        <select name="department_code">
                            <option value="">Не указан</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_code']; ?>"
                                    <?php echo ($contract['department_code'] == $dept['department_code']) ? 'selected' : ''; ?>>
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
                                <option value="<?php echo $pos['position_code']; ?>"
                                    <?php echo ($contract['position_code'] == $pos['position_code']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pos['position_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Оклад (₽) *</label>
                    <input type="number" name="salary" required 
                           value="<?php echo htmlspecialchars($contract['salary']); ?>"
                           placeholder="50000" min="0" step="1000">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Дата начала *</label>
                        <input type="date" name="start_date" required 
                               value="<?php echo htmlspecialchars($contract['start_date']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Дата окончания</label>
                        <input type="date" name="end_date" 
                               value="<?php echo htmlspecialchars($contract['end_date'] ?? ''); ?>">
                        <small style="color: #666; font-size: 12px;">Оставьте пустым для бессрочного договора</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Статус договора *</label>
                    <select name="contract_status" required>
                        <option value="Действующий" <?php echo ($contract['contract_status'] == 'Действующий') ? 'selected' : ''; ?>>Действующий</option>
                        <option value="Расторгнут" <?php echo ($contract['contract_status'] == 'Расторгнут') ? 'selected' : ''; ?>>Расторгнут</option>
                        <option value="Завершен" <?php echo ($contract['contract_status'] == 'Завершен') ? 'selected' : ''; ?>>Завершен</option>
                    </select>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <button type="submit" class="btn btn-success">💾 Сохранить изменения</button>
                    <a href="contracts_management.php" class="btn btn-back">← Назад к списку</a>
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