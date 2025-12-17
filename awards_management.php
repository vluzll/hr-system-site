<?php
require_once 'config.php';

$pdo = connectDB();

// Обработка удаления награды
if (isset($_GET['delete'])) {
    $award_code = $_GET['delete'];
    
    try {
        $sql = "DELETE FROM award WHERE award_code = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$award_code]);
        
        $success = "✅ Награда удалена";
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}

// Получаем все награды
$awards = fetchAll($pdo, "
    SELECT 
        a.*,
        e.last_name || ' ' || e.first_name || ' ' || COALESCE(e.middle_name, '') as employee_full_name,
        at.award_type_name,
        d.department_name,
        p.position_name
    FROM award a
    JOIN employee e ON a.employee_number = e.employee_number
    LEFT JOIN award_types at ON a.award_type_code = at.award_type_code
    LEFT JOIN employment_contract ec ON e.employee_number = ec.employee_number 
        AND ec.contract_status = 'Действующий'
    LEFT JOIN department d ON ec.department_code = d.department_code
    LEFT JOIN position p ON ec.position_code = p.position_code
    ORDER BY a.award_date DESC
");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление наградами</title>
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
        
        .award-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #fff3e0;
            color: #e65100;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
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
        
        .date-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .type-badge {
            background: #f3e5f5;
            color: #7b1fa2;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏆 Управление наградами</h1>
            <p>Всего наград: <?php echo count($awards); ?></p>
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
            // Получаем статистику по наградам
            $awards_by_year = fetchOne($pdo, "
                SELECT COUNT(*) as count, 
                       EXTRACT(YEAR FROM award_date) as year
                FROM award
                GROUP BY EXTRACT(YEAR FROM award_date)
                ORDER BY year DESC
                LIMIT 1
            ");
            
            $awards_with_type = fetchOne($pdo, "
                SELECT COUNT(*) as count
                FROM award
                WHERE award_type_code IS NOT NULL
            ");
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($awards); ?></div>
                    <div class="stat-label">📋 Всего наград</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $awards_by_year['count'] ?? 0; ?></div>
                    <div class="stat-label">🏆 В <?php echo $awards_by_year['year'] ?? date('Y'); ?> году</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $awards_with_type['count']; ?></div>
                    <div class="stat-label">📖 Со справочником</div>
                </div>
            </div>
            
            <!-- Таблица наград -->
            <table>
                <thead>
                    <tr>
                        <th>Код награды</th>
                        <th>Сотрудник</th>
                        <th>Награда</th>
                        <th>Тип награды</th>
                        <th>Дата награждения</th>
                        <th>Отдел</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($awards as $award): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($award['award_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($award['employee_full_name']); ?></td>
                        <td>
                            <span class="award-badge">
                                🏆 <?php echo htmlspecialchars($award['award_name']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($award['award_type_name']): ?>
                                <span class="type-badge">
                                    <?php echo htmlspecialchars($award['award_type_name']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #7f8c8d;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="date-badge">
                                <?php echo htmlspecialchars($award['award_date']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($award['department_name'] ?: '—'); ?></td>
                        <td class="action-buttons">
                            <a href="edit_award.php?id=<?php echo htmlspecialchars($award['award_code']); ?>" 
                               class="btn btn-warning">
                                ✏️ Редактировать
                            </a>
                            <a href="?delete=<?php echo urlencode($award['award_code']); ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Удалить награду?')">
                                🗑️ Удалить
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Кнопки управления -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                <a href="add_award.php" class="btn btn-success" style="padding: 12px 25px;">
                    <span style="margin-right: 10px;">➕</span> Добавить новую награду
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
                if (!confirm('Вы уверены, что хотите удалить эту награду?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>