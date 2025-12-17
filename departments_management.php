<?php
require_once 'config.php';

$pdo = connectDB();

// Обработка удаления отдела
if (isset($_GET['delete'])) {
    $department_code = $_GET['delete'];
    
    try {
        // Проверяем, используется ли отдел
        $used = fetchOne($pdo, "SELECT department_code FROM employment_contract WHERE department_code = ?", [$department_code]);
        if ($used) {
            throw new Exception('Невозможно удалить отдел: он используется в договорах');
        }
        
        $sql = "DELETE FROM department WHERE department_code = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$department_code]);
        
        $success = "✅ Отдел успешно удален";
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}

// Получаем все отделы со статистикой (исправленный запрос)
$departments = fetchAll($pdo, "
    SELECT 
        d.*,
        COUNT(e.employee_number) as employee_count,
        m.last_name || ' ' || m.first_name as manager_name
    FROM department d
    LEFT JOIN employment_contract ec ON d.department_code = ec.department_code 
        AND ec.contract_status = 'Действующий'
    LEFT JOIN employee e ON ec.employee_number = e.employee_number
    LEFT JOIN employee m ON d.manager_number = m.employee_number
    GROUP BY d.department_code, d.department_name, d.manager_number,  -- УБРАЛ d.description
             m.last_name, m.first_name
    ORDER BY d.department_name
");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление отделами</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
            color: white;
            padding: 25px;
            border-radius: 15px 15px 0 0;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
        }
        
        th {
            padding: 14px;
            text-align: left;
            font-weight: 600;
        }
        
        tbody tr {
            border-bottom: 1px solid #e9ecef;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        td {
            padding: 12px 14px;
        }
        
        .code-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
        }
        
        .employee-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            margin: 2px;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-success {
            background: #2ecc71;
        }
        
        .btn-back {
            background: #7f8c8d;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .action-buttons {
            white-space: nowrap;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .description {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 Управление отделами</h1>
            <p>Всего отделов: <?php echo count($departments); ?></p>
        </div>
        
        <div class="card">
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <?php
            $total_employees = array_sum(array_column($departments, 'employee_count'));
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($departments); ?></div>
                    <div class="stat-label">📋 Всего отделов</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_employees; ?></div>
                    <div class="stat-label">👥 Всего сотрудников</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo round($total_employees / max(count($departments), 1)); ?></div>
                    <div class="stat-label">📊 В среднем на отдел</div>
                </div>
            </div>
            
            <!-- Таблица отделов -->
            <table>
                <thead>
                    <tr>
                        <th>Код отдела</th>
                        <th>Название отдела</th>
                        <th>Описание</th>
                        <th>Кол-во сотрудников</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td>
                            <span class="code-badge"><?php echo htmlspecialchars($dept['department_code']); ?></span>
                        </td>
                        <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                        <td class="description"><?php echo htmlspecialchars($dept['description'] ?: '—'); ?></td>
                        <td style="text-align: center;">
                            <span class="employee-badge" style="background: <?php echo $dept['employee_count'] > 0 ? '#d4edda' : '#f8f9fa'; ?>; 
                                         color: <?php echo $dept['employee_count'] > 0 ? '#155724' : '#6c757d'; ?>;">
                                <?php echo $dept['employee_count']; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="edit_department.php?id=<?php echo htmlspecialchars($dept['department_code']); ?>" 
                               class="btn btn-warning">
                                ✏️ Редактировать
                            </a>
                            <?php if ($dept['employee_count'] == 0): ?>
                            <a href="?delete=<?php echo urlencode($dept['department_code']); ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Удалить отдел «<?php echo addslashes($dept['department_name']); ?>»?')">
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
            
            <!-- Кнопки управления -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                <a href="add_department.php" class="btn btn-success" style="padding: 12px 25px;">
                    <span style="margin-right: 10px;">➕</span> Добавить новый отдел
                </a>
                <a href="index.php" class="btn btn-back" style="padding: 12px 25px; margin-left: 10px;">
                    <span style="margin-right: 10px;">←</span> Назад на главную
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Подтверждение удаления
        document.querySelectorAll('.btn-danger').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Вы уверены, что хотите удалить этот отдел?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>