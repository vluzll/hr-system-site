<?php
require_once 'config.php';

$pdo = connectDB();

// Обработка удаления записи об образовании
if (isset($_GET['delete'])) {
    $education_document_code = $_GET['delete'];
    
    try {
        $sql = "DELETE FROM education WHERE education_document_code = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$education_document_code]);
        
        $success = "✅ Запись об образовании удалена";
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}

// Получаем все записи об образовании
$education_records = fetchAll($pdo, "
    SELECT 
        ed.*,
        e.last_name || ' ' || e.first_name || ' ' || COALESCE(e.middle_name, '') as employee_full_name,
        et.education_type_name,
        d.department_name
    FROM education ed
    JOIN employee e ON ed.employee_number = e.employee_number
    JOIN education_type et ON ed.education_type_code = et.education_type_code
    LEFT JOIN employment_contract ec ON e.employee_number = ec.employee_number 
        AND ec.contract_status = 'Действующий'
    LEFT JOIN department d ON ec.department_code = d.department_code
    ORDER BY ed.graduation_year DESC
");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление образованием</title>
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
        
        .education-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #e8f5e9;
            color: #2e7d32;
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
        
        .year-badge {
            background: #e3f2fd;
            color: #1565c0;
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
            <h1>🎓 Управление образованием сотрудников</h1>
            <p>Всего записей: <?php echo count($education_records); ?></p>
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
            // Группируем по видам образования
            $education_stats = fetchAll($pdo, "
                SELECT et.education_type_name, COUNT(*) as count
                FROM education e
                JOIN education_type et ON e.education_type_code = et.education_type_code
                GROUP BY et.education_type_name
                ORDER BY count DESC
            ");
            
            // Получаем самый частый год окончания
            $common_year = fetchOne($pdo, "
                SELECT graduation_year, COUNT(*) as count
                FROM education
                GROUP BY graduation_year
                ORDER BY count DESC
                LIMIT 1
            ");
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($education_records); ?></div>
                    <div class="stat-label">📋 Записей</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($education_stats); ?></div>
                    <div class="stat-label">🎓 Видов образования</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $common_year['graduation_year'] ?? '—'; ?></div>
                    <div class="stat-label">📅 Частый год окончания</div>
                </div>
            </div>
            
            <!-- Таблица образования -->
            <table>
                <thead>
                    <tr>
                        <th>Код документа</th>
                        <th>Сотрудник</th>
                        <th>Вид образования</th>
                        <th>Учебное заведение</th>
                        <th>Специальность</th>
                        <th>Год окончания</th>
                        <th>Отдел</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($education_records as $edu): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($edu['education_document_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($edu['employee_full_name']); ?></td>
                        <td>
                            <span class="education-badge">
                                <?php echo htmlspecialchars($edu['education_type_name']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($edu['educational_institution']); ?></td>
                        <td><?php echo htmlspecialchars($edu['specialty']); ?></td>
                        <td>
                            <span class="year-badge">
                                <?php echo htmlspecialchars($edu['graduation_year']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($edu['department_name'] ?: '—'); ?></td>
                        <td class="action-buttons">
                            <a href="edit_education.php?id=<?php echo htmlspecialchars($edu['education_document_code']); ?>" 
                               class="btn btn-warning">
                                ✏️ Редактировать
                            </a>
                            <a href="?delete=<?php echo urlencode($edu['education_document_code']); ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Удалить запись об образовании?')">
                                🗑️ Удалить
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Кнопки управления -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                <a href="add_education.php" class="btn btn-success" style="padding: 12px 25px;">
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
                if (!confirm('Вы уверены, что хотите удалить эту запись об образовании?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>