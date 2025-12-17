<?php
require_once 'config.php';
$pdo = connectDB();

$message = '';
$error = '';

// Получаем все типы наград для отображения
$award_types = fetchAll($pdo, "
    SELECT * FROM award_types 
    ORDER BY award_type_code
");

// Добавление нового типа награды
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_award_type'])) {
    try {
        $award_type_code = !empty($_POST['award_type_code']) ? intval($_POST['award_type_code']) : null;
        $award_type_name = trim($_POST['award_type_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($award_type_code) || $award_type_code < 1 || $award_type_code > 9999) {
            throw new Exception('Код типа награды должен быть числом от 1 до 9999');
        }
        
        if (empty($award_type_name)) {
            throw new Exception('Название типа награды обязательно');
        }
        
        // Проверяем, существует ли уже такой код типа награды
        $existing = fetchOne($pdo, "SELECT award_type_code FROM award_types WHERE award_type_code = ?", [$award_type_code]);
        if ($existing) {
            throw new Exception('Тип награды с таким кодом уже существует');
        }
        
        $sql = "INSERT INTO award_types (award_type_code, award_type_name, description) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$award_type_code, $award_type_name, $description]);
        
        $message = "✅ Тип награды успешно добавлен! Код: $award_type_code";
        
        // Обновляем список типов наград
        $award_types = fetchAll($pdo, "
            SELECT * FROM award_types 
            ORDER BY award_type_code
        ");
        
        // Очищаем форму
        $_POST['award_type_code'] = '';
        $_POST['award_type_name'] = '';
        $_POST['description'] = '';
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}

// Удаление типа награды
if (isset($_GET['delete'])) {
    try {
        $award_type_code = intval($_GET['delete']);
        
        // Проверяем, используется ли тип награды
        $used = fetchOne($pdo, "SELECT award_type_code FROM award WHERE award_type_code = ?", [$award_type_code]);
        if ($used) {
            throw new Exception('Невозможно удалить тип награды: он используется в наградах сотрудников');
        }
        
        $sql = "DELETE FROM award_types WHERE award_type_code = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$award_type_code]);
        
        $message = "✅ Тип награды удален!";
        
        // Обновляем список типов наград
        $award_types = fetchAll($pdo, "
            SELECT * FROM award_types 
            ORDER BY award_type_code
        ");
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}

// Получаем статистику использования типов наград
$usage_stats = fetchAll($pdo, "
    SELECT 
        at.award_type_code,
        at.award_type_name,
        COUNT(a.award_code) as usage_count
    FROM award_types at
    LEFT JOIN award a ON at.award_type_code = a.award_type_code
    GROUP BY at.award_type_code, at.award_type_name
    ORDER BY at.award_type_code
");

// Получаем следующий свободный код для подсказки
$max_code = fetchOne($pdo, "SELECT MAX(award_type_code) as max_code FROM award_types");
$next_code = ($max_code['max_code'] ?? 0) + 1;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Справочник типов наград</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
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
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 150px 2fr 1fr;
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
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .btn-back {
            background: #7f8c8d;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
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
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .empty-row {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .actions-cell {
            white-space: nowrap;
        }
        
        .award-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #fff3cd;
            color: #856404;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .usage-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .usage-low {
            background: #d4edda;
            color: #155724;
        }
        
        .usage-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .usage-high {
            background: #f8d7da;
            color: #721c24;
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
        
        .code-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏆 Справочник типов наград</h1>
            <p>Управление типами наград, доступных для присвоения сотрудникам</p>
        </div>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($award_types); ?></div>
                <div class="stat-label">📋 Типов наград</div>
            </div>
            
            <?php
            $total_awards = fetchOne($pdo, "SELECT COUNT(*) as cnt FROM award")['cnt'];
            $awards_with_type = fetchOne($pdo, "SELECT COUNT(*) as cnt FROM award WHERE award_type_code IS NOT NULL")['cnt'];
            $usage_percentage = $total_awards > 0 ? round(($awards_with_type / $total_awards) * 100) : 0;
            ?>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_awards; ?></div>
                <div class="stat-label">🏆 Всего награждений</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $usage_percentage; ?>%</div>
                <div class="stat-label">📊 Связано со справочником</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $next_code; ?></div>
                <div class="stat-label">🔢 Следующий свободный код</div>
            </div>
        </div>
        
                <div class="card">
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <h2 style="margin-bottom: 25px;">➕ Добавить новый тип награды</h2>
            
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px; margin-bottom: 25px;">
                    <!-- Левая колонка -->
                    <div>
                        <div class="form-group">
                            <label class="required" style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <span style="color: #e74c3c;">*</span>
                                Код типа награды
                            </label>
                            <div style="position: relative;">
                                <input type="number" name="award_type_code" required 
                                       value="<?php echo htmlspecialchars($_POST['award_type_code'] ?? $next_code); ?>"
                                       min="1" max="9999" step="1"
                                       placeholder="11"
                                       style="width: 100%; padding: 12px 15px; font-size: 16px; 
                                              border: 2px solid #ddd; border-radius: 6px;
                                              background: #f8f9fa;">
                                <div style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); 
                                            color: #6c757d; font-size: 14px; pointer-events: none;">
                                    🔢
                                </div>
                            </div>
                            <div style="margin-top: 8px; font-size: 13px; color: #6c757d;">
                                <span style="display: inline-block; padding: 2px 8px; background: #e8f4fc; 
                                             border-radius: 3px; margin-right: 5px;">1-9999</span>
                                Уникальный числовой идентификатор
                            </div>
                        </div>
                    </div>
                    
                    <!-- Правая колонка -->
                    <div>
                        <div class="form-group">
                            <label class="required" style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <span style="color: #e74c3c;">*</span>
                                Название награды
                            </label>
                            <div style="position: relative;">
                                <input type="text" name="award_type_name" required 
                                       value="<?php echo htmlspecialchars($_POST['award_type_name'] ?? ''); ?>"
                                       placeholder="За отличную работу"
                                       maxlength="200"
                                       style="width: 100%; padding: 12px 15px; font-size: 16px; 
                                              border: 2px solid #ddd; border-radius: 6px;
                                              background: #f8f9fa;">
                                <div style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); 
                                            color: #6c757d; font-size: 14px; pointer-events: none;">
                                    🏆
                                </div>
                            </div>
                            <div style="margin-top: 8px; font-size: 13px; color: #6c757d;">
                                Будет отображаться в карточках сотрудников
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Описание (полная ширина) -->
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <span style="color: #f39c12;">ℹ️</span>
                        Описание награды (необязательно)
                    </label>
                    <textarea name="description" 
                              placeholder="Опишите условия получения награды, критерии, историю..."
                              maxlength="500"
                              style="width: 100%; padding: 15px; font-size: 15px; 
                                     border: 2px solid #ddd; border-radius: 6px;
                                     min-height: 100px; resize: vertical;
                                     background: #f8f9fa;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <div style="margin-top: 8px; display: flex; justify-content: space-between; font-size: 13px; color: #6c757d;">
                        <span>Максимум 500 символов</span>
                        <span>Для подробного описания условий</span>
                    </div>
                </div>
                
                <!-- Кнопки -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; 
                            display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 13px; color: #6c757d;">
                            <span style="color: #e74c3c;">*</span> — обязательные поля
                        </span>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <button type="submit" name="add_award_type" 
                                style="padding: 12px 30px; background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
                                       color: white; border: none; border-radius: 6px; cursor: pointer;
                                       font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                            <span>💾</span>
                            Сохранить тип награды
                        </button>
                        
                        <a href="index.php" 
                           style="padding: 12px 25px; background: #7f8c8d; color: white; 
                                  border-radius: 6px; text-decoration: none;
                                  font-size: 16px; display: flex; align-items: center; gap: 10px;">
                            <span>←</span>
                            На главную
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>📋 Список всех типов наград</h2>
            
            <?php if (empty($award_types)): ?>
                <div class="empty-row">
                    🏆 Справочник типов наград пуст. Добавьте первый тип награды.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Код типа</th>
                                <th>Название типа награды</th>
                                <th>Описание</th>
                                <th>Использование</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usage_stats as $type): 
                                $usage_class = 'usage-low';
                                if ($type['usage_count'] > 5) {
                                    $usage_class = 'usage-high';
                                } elseif ($type['usage_count'] > 0) {
                                    $usage_class = 'usage-medium';
                                }
                            ?>
                            <tr>
                                <td>
                                    <span class="code-badge"><?php echo htmlspecialchars($type['award_type_code']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($type['award_type_name']); ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    $description = fetchOne($pdo, "SELECT description FROM award_types WHERE award_type_code = ?", [$type['award_type_code']])['description'];
                                    echo htmlspecialchars($description ?? '—'); 
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="usage-badge <?php echo $usage_class; ?>">
                                        <?php echo $type['usage_count']; ?> раз
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <?php if ($type['usage_count'] == 0): ?>
                                        <a href="?delete=<?php echo urlencode($type['award_type_code']); ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Удалить тип награды «<?php echo addslashes($type['award_type_name']); ?>»?')">
                                            🗑️ Удалить
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #6c757d; font-size: 12px;">
                                            ⚠️ Используется
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>       
        
    </div>
    
    <script>
        // Подтверждение удаления
        document.querySelectorAll('.btn-danger').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Вы уверены, что хотите удалить этот тип награды?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Автоматическая фокусировка на поле кода
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.querySelector('input[name="award_type_code"]');
            if (codeInput) {
                codeInput.focus();
                codeInput.select();
            }
        });
    </script>
</body>
</html>

<?php 
// Закрываем соединение
closeDB($pdo);
?>