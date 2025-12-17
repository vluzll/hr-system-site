<?php
require_once 'config.php';

$pdo = connectDB();

// Обработка удаления должности
if (isset($_GET['delete'])) {
    $position_code = $_GET['delete'];
    
    try {
        // Проверяем, используется ли должность
        $used = fetchOne($pdo, "SELECT position_code FROM employment_contract WHERE position_code = ?", [$position_code]);
        if ($used) {
            $error = "❌ Невозможно удалить: должность используется в договорах";
        } else {
            $sql = "DELETE FROM position WHERE position_code = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$position_code]);
            
            $success = "✅ Должность успешно удалена";
        }
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}

// Получаем все должности
$positions = fetchAll($pdo, "
    SELECT 
        p.*,
        d.department_name,
        COUNT(ec.employee_number) as employee_count
    FROM position p
    LEFT JOIN department d ON p.department_id = d.department_code
    LEFT JOIN employment_contract ec ON p.position_code = ec.position_code 
        AND ec.contract_status = 'Действующий'
    GROUP BY p.position_code, p.position_name, p.salary_min, p.salary_max, 
             p.description, d.department_name
    ORDER BY p.position_name
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Управление должностями</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: auto; }
        .header { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); color: white; padding: 25px; border-radius: 10px 10px 0 0; }
        .card { background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; }
        tr:hover { background: #f8f9fa; }
        .btn { padding: 8px 16px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 2px; }
        .btn-success { background: #2ecc71; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #e74c3c; }
        .btn-back { background: #7f8c8d; }
        .salary { color: #27ae60; font-weight: bold; }
        .code-badge { padding: 4px 10px; background: #e3f2fd; color: #1565c0; border-radius: 4px; font-family: monospace; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💼 Управление должностями</h1>
            <p>Всего должностей: <?php echo count($positions); ?></p>
        </div>
        
        <div class="card">
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Код</th>
                        <th>Должность</th>
                        <th>Отдел</th>
                        <th>Зарплата</th>
                        <th>Описание</th>
                        <th>Сотрудники</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($positions as $position): 
                        $salary_range = '';
                        if ($position['salary_min'] && $position['salary_max']) {
                            $salary_range = number_format($position['salary_min'], 0, ',', ' ') . ' - ' . 
                                           number_format($position['salary_max'], 0, ',', ' ') . ' ₽';
                        } elseif ($position['salary_min']) {
                            $salary_range = 'от ' . number_format($position['salary_min'], 0, ',', ' ') . ' ₽';
                        } elseif ($position['salary_max']) {
                            $salary_range = 'до ' . number_format($position['salary_max'], 0, ',', ' ') . ' ₽';
                        }
                    ?>
                    <tr>
                        <td><span class="code-badge"><?php echo $position['position_code']; ?></span></td>
                        <td><strong><?php echo htmlspecialchars($position['position_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($position['department_name'] ?: '—'); ?></td>
                        <td class="salary"><?php echo $salary_range ?: '—'; ?></td>
                        <td>
                            <?php 
                            if ($position['description']) {
                                echo htmlspecialchars(substr($position['description'], 0, 30));
                                if (strlen($position['description']) > 30) echo '...';
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td style="text-align: center;">
                            <span style="padding: 4px 8px; border-radius: 10px; font-size: 12px; font-weight: bold;
                                         background: <?php echo $position['employee_count'] > 0 ? '#d4edda' : '#f8f9fa'; ?>;
                                         color: <?php echo $position['employee_count'] > 0 ? '#155724' : '#6c757d'; ?>;">
                                <?php echo $position['employee_count']; ?>
                            </span>
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="edit_position.php?id=<?php echo $position['position_code']; ?>" class="btn btn-warning">
                                ✏️ Редактировать
                            </a>
                            <?php if ($position['employee_count'] == 0): ?>
                            <a href="?delete=<?php echo $position['position_code']; ?>" class="btn btn-danger"
                               onclick="return confirm('Удалить должность «<?php echo addslashes($position['position_name']); ?>»?')">
                                🗑️ Удалить
                            </a>
                            <?php else: ?>
                            <span style="color: #6c757d; font-size: 12px;">Используется</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                <a href="add_position.php" class="btn btn-success" style="padding: 12px 25px;">
                    <span style="margin-right: 10px;">➕</span> Добавить новую должность
                </a>
                <a href="index.php" class="btn btn-back" style="padding: 12px 25px; margin-left: 10px;">
                    <span style="margin-right: 10px;">←</span> Назад на главную
                </a>
            </div>
        </div>
    </div>
</body>
</html>