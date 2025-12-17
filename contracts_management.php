<?php
require_once 'config.php';

$pdo = connectDB();

// Обработка удаления договора
if (isset($_GET['delete'])) {
    $contract_number = $_GET['delete'];
    
    try {
        $sql = "DELETE FROM employment_contract WHERE contract_number = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$contract_number]);
        
        $success = "✅ Договор успешно удален";
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}

// Получаем все договоры с дополнительной информацией
$contracts = fetchAll($pdo, "
    SELECT 
        ec.*,
        e.last_name || ' ' || e.first_name || ' ' || COALESCE(e.middle_name, '') as employee_full_name,
        d.department_name,
        p.position_name
    FROM employment_contract ec
    JOIN employee e ON ec.employee_number = e.employee_number
    LEFT JOIN department d ON ec.department_code = d.department_code
    LEFT JOIN position p ON ec.position_code = p.position_code
    ORDER BY ec.start_date DESC
");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление договорами</title>
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
        
        .status {
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
        
        .salary {
            font-weight: bold;
            color: #27ae60;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Управление трудовыми договорами</h1>
            <p>Всего договоров: <?php echo count($contracts); ?></p>
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
            $active_contracts = array_filter($contracts, function($c) {
                return $c['contract_status'] == 'Действующий';
            });
            
            $total_salary = array_sum(array_column($active_contracts, 'salary'));
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($contracts); ?></div>
                    <div class="stat-label">📋 Всего договоров</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($active_contracts); ?></div>
                    <div class="stat-label">✅ Действующих</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($total_salary, 0, ',', ' '); ?> ₽</div>
                    <div class="stat-label">💰 Общий ФОТ</div>
                </div>
            </div>
            
            <!-- Таблица договоров -->
            <table>
                <thead>
                    <tr>
                        <th>№ договора</th>
                        <th>Сотрудник</th>
                        <th>Отдел / Должность</th>
                        <th>Оклад</th>
                        <th>Дата начала</th>
                        <th>Дата окончания</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contracts as $contract): 
                        $status_class = '';
                        if ($contract['contract_status'] == 'Действующий') {
                            $status_class = 'status-active';
                        } elseif ($contract['contract_status'] == 'Расторгнут') {
                            $status_class = 'status-terminated';
                        } else {
                            $status_class = 'status-completed';
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($contract['contract_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($contract['employee_full_name']); ?></td>
                        <td>
                            <?php if ($contract['department_name']): ?>
                            <strong><?php echo htmlspecialchars($contract['department_name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($contract['position_name'] ?: ''); ?></small>
                            <?php else: ?>
                            <span style="color: #7f8c8d;">Не указано</span>
                            <?php endif; ?>
                        </td>
                        <td class="salary"><?php echo number_format($contract['salary'], 0, ',', ' ') . ' ₽'; ?></td>
                        <td><?php echo htmlspecialchars($contract['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($contract['end_date'] ?: 'Бессрочный'); ?></td>
                        <td>
                            <span class="status <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($contract['contract_status']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="edit_contract.php?id=<?php echo htmlspecialchars($contract['contract_number']); ?>" 
                               class="btn btn-warning">
                                ✏️ Редактировать
                            </a>
                            <a href="?delete=<?php echo urlencode($contract['contract_number']); ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Удалить договор №<?php echo addslashes($contract['contract_number']); ?>?')">
                                🗑️ Удалить
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Кнопки управления -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                <a href="add_contract.php" class="btn btn-success" style="padding: 12px 25px;">
                    <span style="margin-right: 10px;">➕</span> Добавить новый договор
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
                if (!confirm('Вы уверены, что хотите удалить этот договор?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>