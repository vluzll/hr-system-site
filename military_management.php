<?php
require_once 'config.php';

$pdo = connectDB();

// Обработка удаления записи воинского учета
if (isset($_GET['delete'])) {
    $military_id = $_GET['delete'];
    
    try {
        $sql = "DELETE FROM military_record WHERE military_id_number = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$military_id]);
        
        $success = "✅ Запись воинского учета удалена";
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}

// Получаем все записи воинского учета
$military_records = fetchAll($pdo, "
    SELECT 
        mr.*,
        e.last_name || ' ' || e.first_name || ' ' || COALESCE(e.middle_name, '') as employee_full_name,
        d.department_name
    FROM military_record mr
    JOIN employee e ON mr.employee_number = e.employee_number
    LEFT JOIN employment_contract ec ON e.employee_number = ec.employee_number 
        AND ec.contract_status = 'Действующий'
    LEFT JOIN department d ON ec.department_code = d.department_code
    ORDER BY e.last_name, e.first_name
");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление воинским учетом</title>
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
        
        .military-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 4px;
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
        
        .composition-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .composition-soldiers {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .composition-officers {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .composition-senior {
            background: #fff3e0;
            color: #e65100;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎖️ Управление воинским учетом</h1>
            <p>Всего записей: <?php echo count($military_records); ?></p>
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
            $categories = array_count_values(array_column($military_records, 'reserve_category'));
            $compositions = array_count_values(array_column($military_records, 'military_composition'));
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($military_records); ?></div>
                    <div class="stat-label">📋 Всего записей</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $categories['А'] ?? 0; ?></div>
                    <div class="stat-label">🟢 Категория А</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $compositions['Солдаты, матросы, сержанты, старшины'] ?? 0; ?></div>
                    <div class="stat-label">👥 Солдаты</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $compositions['Офицерский состав'] ?? 0; ?></div>
                    <div class="stat-label">⭐ Офицеры</div>
                </div>
            </div>
            
            <!-- Таблица воинского учета -->
            <table>
                <thead>
                    <tr>
                        <th>№ военного билета</th>
                        <th>Сотрудник</th>
                        <th>Кем выдан</th>
                        <th>Воинская должность</th>
                        <th>Категория</th>
                        <th>Группа</th>
                        <th>Состав</th>
                        <th>Отдел</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($military_records as $record): 
                        // Определяем класс для состава
                        $composition_class = '';
                        if (strpos($record['military_composition'], 'Солдаты') !== false) {
                            $composition_class = 'composition-soldiers';
                        } elseif (strpos($record['military_composition'], 'Офицерский') !== false) {
                            $composition_class = 'composition-officers';
                        } elseif (strpos($record['military_composition'], 'Высший') !== false) {
                            $composition_class = 'composition-senior';
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($record['military_id_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($record['employee_full_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['issued_by']); ?></td>
                        <td><?php echo htmlspecialchars($record['military_position']); ?></td>
                        <td>
                            <span class="military-badge">
                                Кат. <?php echo htmlspecialchars($record['reserve_category']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="military-badge">
                                Гр. <?php echo htmlspecialchars($record['record_group']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="composition-badge <?php echo $composition_class; ?>">
                                <?php echo htmlspecialchars($record['military_composition']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($record['department_name'] ?: '—'); ?></td>
                        <td class="action-buttons">
                            <a href="edit_military.php?id=<?php echo htmlspecialchars($record['military_id_number']); ?>" 
                               class="btn btn-warning">
                                ✏️ Редактировать
                            </a>
                            <a href="?delete=<?php echo urlencode($record['military_id_number']); ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Удалить запись воинского учета?')">
                                🗑️ Удалить
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Кнопки управления -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                <a href="add_military.php" class="btn btn-success" style="padding: 12px 25px;">
                    <span style="margin-right: 10px;">➕</span> Добавить новую запись
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
                if (!confirm('Вы уверены, что хотите удалить эту запись воинского учета?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Фильтрация по нажатию на бейдж категории
        document.querySelectorAll('.military-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                const category = this.textContent.trim();
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const rowCategory = row.querySelector('.military-badge').textContent;
                    if (rowCategory.includes(category)) {
                        row.style.backgroundColor = '#e8f4fc';
                        setTimeout(() => {
                            row.style.backgroundColor = '';
                        }, 1000);
                    }
                });
            });
        });
    </script>
</body>
</html>