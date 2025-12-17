<?php
require_once 'config.php';

$message = '';
$error = '';

$award_code = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($award_code <= 0) {
    header('Location: awards_reference.php');
    exit;
}

// Получаем информацию о награде
$award = fetchOne($pdo, "
    SELECT a.*, e.last_name, e.first_name 
    FROM award a
    JOIN employee e ON a.employee_number = e.employee_number
    WHERE a.award_code = ?
", [$award_code]);

if (!$award) {
    die('<h2>Награда не найдена</h2>');
}

// Получаем все типы наград
$award_types = fetchAll($pdo, "
    SELECT * FROM award_types 
    ORDER BY award_type_code
");

// Обработка привязки типа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_award'])) {
    try {
        $award_type_code = !empty($_POST['award_type_code']) ? intval($_POST['award_type_code']) : null;
        
        if (empty($award_type_code)) {
            throw new Exception('Необходимо выбрать тип награды');
        }
        
        // Обновляем запись награды
        $sql = "UPDATE award SET award_type_code = ? WHERE award_code = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$award_type_code, $award_code]);
        
        $message = "✅ Тип награды успешно привязан! Код: $award_type_code";
        
        // Обновляем информацию о награде
        $award = fetchOne($pdo, "
            SELECT a.*, e.last_name, e.first_name, at.award_type_name
            FROM award a
            JOIN employee e ON a.employee_number = e.employee_number
            LEFT JOIN award_types at ON a.award_type_code = at.award_type_code
            WHERE a.award_code = ?
        ", [$award_code]);
        
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
    <title>Привязка типа награды</title>
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
            margin-bottom: 20px;
        }
        
        .award-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-label {
            font-weight: bold;
            color: #6c757d;
        }
        
        .info-value {
            font-size: 16px;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
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
        
        .type-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #fff3cd;
            color: #856404;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Привязка типа награды</h1>
        </div>
        
        <div class="card">
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="award-info">
                <h3>Информация о награде</h3>
                <div class="info-grid">
                    <div>
                        <div class="info-label">Код награды</div>
                        <div class="info-value">
                            <span class="code-badge"><?php echo htmlspecialchars($award['award_code']); ?></span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="info-label">Сотрудник</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($award['last_name'] . ' ' . $award['first_name']); ?>
                        </div>
                    </div>
                    
                    <div>
                        <div class="info-label">Текущее название</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($award['award_name']); ?>
                        </div>
                    </div>
                    
                    <div>
                        <div class="info-label">Дата награждения</div>
                        <div class="info-value"><?php echo htmlspecialchars($award['award_date']); ?></div>
                    </div>
                    
                    <?php if (!empty($award['award_type_code'])): ?>
                    <div>
                        <div class="info-label">Текущий тип</div>
                        <div class="info-value">
                            <span class="type-badge">
                                [<?php echo htmlspecialchars($award['award_type_code']); ?>] 
                                <?php echo htmlspecialchars($award['award_type_name'] ?? ''); ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3>Выберите тип награды</h3>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Тип награды из справочника *</label>
                    <select name="award_type_code" required>
                        <option value="">-- Выберите тип награды --</option>
                        <?php foreach ($award_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['award_type_code']); ?>"
                                <?php echo (isset($award['award_type_code']) && $award['award_type_code'] == $type['award_type_code']) ? 'selected' : ''; ?>>
                                [<?php echo htmlspecialchars($type['award_type_code']); ?>] 
                                <?php echo htmlspecialchars($type['award_type_name']); ?>
                                <?php if ($type['description']): ?>
                                    - <?php echo htmlspecialchars(substr($type['description'], 0, 50)) . '...'; ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" name="link_award" class="btn btn-success">💾 Привязать тип награды</button>
                    <a href="awards_reference.php" class="btn btn-back">← Назад к справочнику</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Автоподстановка похожих типов наград
        document.addEventListener('DOMContentLoaded', function() {
            const currentName = "<?php echo addslashes($award['award_name']); ?>";
            const select = document.querySelector('select[name="award_type_code"]');
            
            // Ищем похожие названия
            Array.from(select.options).forEach(option => {
                if (option.textContent.toLowerCase().includes(currentName.toLowerCase())) {
                    option.style.backgroundColor = '#fff3cd';
                    option.style.fontWeight = 'bold';
                }
            });
        });
    </script>
</body>
</html>

<?php 
// Закрываем соединение
closeDB($pdo);
?>