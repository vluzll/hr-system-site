<?php
require_once 'config.php';

$pdo = connectDB();

$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Создаем таблицу award_types
        $sql1 = "CREATE TABLE IF NOT EXISTS hr_schema.award_types (
            award_type_code INTEGER PRIMARY KEY
                CHECK (award_type_code BETWEEN 1 AND 9999),
            award_type_name VARCHAR(200) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql1);
        $messages[] = "✅ Таблица award_types создана";
        
        // 2. Заполняем данными
        $sql2 = "INSERT INTO hr_schema.award_types (award_type_code, award_type_name, description) VALUES
            (1, 'Лучший руководитель', 'Награда лучшему руководителю отдела'),
            (2, 'За результаты', 'Награда за достижение высоких результатов'),
            (3, 'Сотрудник месяца', 'Ежемесячная награда лучшему сотруднику'),
            (4, 'За инновации', 'Награда за внедрение инновационных решений'),
            (5, 'За маркетинг', 'Награда за достижения в области маркетинга'),
            (6, 'За работу', 'Награда за качественную работу'),
            (7, 'За культуру', 'Награда за вклад в корпоративную культуру'),
            (8, 'За тестирование', 'Награда за качественное тестирование'),
            (9, 'Ветеран труда', 'За многолетний добросовестный труд'),
            (10, 'За профессионализм', 'Награда за высокий профессионализм')
        ON CONFLICT (award_type_code) DO NOTHING";
        
        $pdo->exec($sql2);
        $messages[] = "✅ Данные добавлены в award_types";
        
        // 3. Добавляем столбец в таблицу award
        $sql3 = "ALTER TABLE hr_schema.award 
                ADD COLUMN IF NOT EXISTS award_type_code INTEGER";
        
        $pdo->exec($sql3);
        $messages[] = "✅ Столбец award_type_code добавлен в таблицу award";
        
        // 4. Создаем внешний ключ
        try {
            $sql4 = "ALTER TABLE hr_schema.award 
                    ADD CONSTRAINT fk_award_type 
                    FOREIGN KEY (award_type_code) 
                    REFERENCES hr_schema.award_types(award_type_code)
                    ON DELETE SET NULL";
            
            $pdo->exec($sql4);
            $messages[] = "✅ Внешний ключ создан";
        } catch (Exception $e) {
            $messages[] = "⚠️ Внешний ключ уже существует или ошибка: " . $e->getMessage();
        }
        
        // 5. Обновляем существующие записи
        $sql5 = "UPDATE hr_schema.award a
                SET award_type_code = CASE 
                    WHEN award_name ILIKE '%руководитель%' THEN 1
                    WHEN award_name ILIKE '%результат%' THEN 2
                    WHEN award_name ILIKE '%месяц%' THEN 3
                    WHEN award_name ILIKE '%инновац%' THEN 4
                    WHEN award_name ILIKE '%маркетинг%' THEN 5
                    WHEN award_name ILIKE '%работ%' THEN 6
                    WHEN award_name ILIKE '%культур%' THEN 7
                    WHEN award_name ILIKE '%тестирован%' THEN 8
                    ELSE NULL
                END";
        
        $affected = $pdo->exec($sql5);
        $messages[] = "✅ Обновлено записей: $affected";
        
    } catch (Exception $e) {
        $messages[] = "❌ Ошибка: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание справочника наград</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .message {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #3498db;
            background: #f8f9fa;
        }
        
        .success {
            border-left-color: #2ecc71;
            background: #d4edda;
            color: #155724;
        }
        
        .error {
            border-left-color: #e74c3c;
            background: #f8d7da;
            color: #721c24;
        }
        
        .warning {
            border-left-color: #ffc107;
            background: #fff3cd;
            color: #856404;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin-top: 20px;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #2ecc71;
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .code-block {
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin: 20px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ Создание справочника типов наград</h1>
        
        <p>Этот скрипт создаст необходимые таблицы и связи для работы справочника наград.</p>
        
        <?php if (!empty($messages)): ?>
            <h3>Результат выполнения:</h3>
            <?php foreach ($messages as $msg): ?>
                <?php if (strpos($msg, '✅') !== false): ?>
                    <div class="message success"><?php echo $msg; ?></div>
                <?php elseif (strpos($msg, '❌') !== false): ?>
                    <div class="message error"><?php echo $msg; ?></div>
                <?php elseif (strpos($msg, '⚠️') !== false): ?>
                    <div class="message warning"><?php echo $msg; ?></div>
                <?php else: ?>
                    <div class="message"><?php echo $msg; ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="code-block">
-- Что будет создано:
1. Таблица award_types (типы наград)
2. 10 предустановленных типов наград
3. Столбец award_type_code в таблице award
4. Внешний ключ между таблицами
5. Привязка существующих наград к типам
        </div>
        
        <form method="POST" action="">
            <button type="submit" class="btn btn-success">🚀 Запустить создание справочника</button>
        </form>
        
        <div style="margin-top: 30px;">
            <a href="check_awards_table.php" class="btn">🔍 Проверить состояние таблиц</a>
            <a href="index.php" class="btn" style="background: #7f8c8d;">← На главную</a>
            <a href="awards_reference.php" class="btn">🏆 Перейти к справочнику</a>
        </div>
        
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3>Если что-то пошло не так:</h3>
            <p>Выполните эти SQL запросы вручную в PostgreSQL:</p>
            <div class="code-block">
-- 1. Создание таблицы типов наград
CREATE TABLE IF NOT EXISTS hr_schema.award_types (
    award_type_code INTEGER PRIMARY KEY
        CHECK (award_type_code BETWEEN 1 AND 9999),
    award_type_name VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Заполнение данными
INSERT INTO hr_schema.award_types (award_type_code, award_type_name, description) VALUES
    (1, 'Лучший руководитель', 'Награда лучшему руководителю отдела'),
    (2, 'За результаты', 'Награда за достижение высоких результатов'),
    (3, 'Сотрудник месяца', 'Ежемесячная награда лучшему сотруднику'),
    (4, 'За инновации', 'Награда за внедрение инновационных решений'),
    (5, 'За маркетинг', 'Награда за достижения в области маркетинга'),
    (6, 'За работу', 'Награда за качественную работу'),
    (7, 'За культуру', 'Награда за вклад в корпоративную культуру'),
    (8, 'За тестирование', 'Награда за качественное тестирование'),
    (9, 'Ветеран труда', 'За многолетний добросовестный труд'),
    (10, 'За профессионализм', 'Награда за высокий профессионализм')
ON CONFLICT (award_type_code) DO NOTHING;

-- 3. Добавление столбца в существующую таблицу award
ALTER TABLE hr_schema.award 
ADD COLUMN IF NOT EXISTS award_type_code INTEGER;

-- 4. Создание внешнего ключа
ALTER TABLE hr_schema.award 
ADD CONSTRAINT fk_award_type 
FOREIGN KEY (award_type_code) 
REFERENCES hr_schema.award_types(award_type_code)
ON DELETE SET NULL;

-- 5. Обновление существующих записей (опционально)
UPDATE hr_schema.award a
SET award_type_code = CASE 
    WHEN award_name ILIKE '%руководитель%' THEN 1
    WHEN award_name ILIKE '%результат%' THEN 2
    WHEN award_name ILIKE '%месяц%' THEN 3
    WHEN award_name ILIKE '%инновац%' THEN 4
    WHEN award_name ILIKE '%маркетинг%' THEN 5
    WHEN award_name ILIKE '%работ%' THEN 6
    WHEN award_name ILIKE '%культур%' THEN 7
    WHEN award_name ILIKE '%тестирован%' THEN 8
    ELSE NULL
END;
            </div>
        </div>
    </div>
</body>
</html>

<?php closeDB($pdo); ?>