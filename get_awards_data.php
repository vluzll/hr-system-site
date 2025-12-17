<?php
require_once 'config.php';

$pdo = connectDB();

try {
    // Упрощенный запрос - сначала проверяем таблицу
    $table_check = fetchOne($pdo, "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'hr_schema' AND table_name = 'award_types') as exists");
    
    if (!$table_check['exists']) {
        echo '<div style="text-align: center; padding: 40px; color: #e74c3c;">';
        echo '<h3>❌ Таблица award_types не найдена</h3>';
        echo '<p>Создайте таблицу с помощью:</p>';
        echo '<pre style="text-align: left; background: #f0f0f0; padding: 10px; margin: 10px auto; max-width: 600px;">';
        echo "CREATE TABLE hr_schema.award_types (\n";
        echo "    award_type_code INTEGER PRIMARY KEY,\n";
        echo "    award_type_name VARCHAR(200) NOT NULL,\n";
        echo "    description TEXT,\n";
        echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n";
        echo ");";
        echo '</pre>';
        echo '<a href="create_awards_table.php" class="btn" style="margin-top: 10px;">🚀 Создать таблицу</a>';
        echo '</div>';
        exit;
    }
    
    // Получаем данные
    $award_types = fetchAll($pdo, "
        SELECT 
            at.award_type_code,
            at.award_type_name,
            at.description,
            COUNT(a.award_code) as award_count
        FROM award_types at
        LEFT JOIN award a ON at.award_type_code = a.award_type_code
        GROUP BY at.award_type_code, at.award_type_name, at.description
        ORDER BY at.award_type_code
    ");
    
    if (empty($award_types)) {
        echo '<div style="text-align: center; padding: 40px; color: #6c757d;">';
        echo '<h3>🏆 Справочник пуст</h3>';
        echo '<p>Добавьте типы наград в справочник.</p>';
        echo '<a href="awards_reference.php" class="btn btn-success">➕ Добавить первую награду</a>';
        echo '</div>';
    } else {
        echo '<div class="table-container">';
        echo '<table>';
        echo '<thead>';
        echo '<tr><th>Код</th><th>Название</th><th>Описание</th><th>Использовано</th></tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($award_types as $type) {
            echo '<tr>';
            echo '<td><span style="display: inline-block; padding: 4px 10px; background: #e3f2fd; color: #1565c0; border-radius: 4px; font-family: monospace; font-weight: bold;">' . htmlspecialchars($type['award_type_code']) . '</span></td>';
            echo '<td><strong>' . htmlspecialchars($type['award_type_name']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($type['description'] ?: '—') . '</td>';
            echo '<td style="text-align: center;">';
            echo '<span style="display: inline-block; padding: 4px 8px; border-radius: 10px; font-size: 12px; font-weight: bold; background: ' . ($type['award_count'] > 0 ? '#d4edda' : '#f8d7da') . '; color: ' . ($type['award_count'] > 0 ? '#155724' : '#721c24') . ';">';
            echo $type['award_count'];
            echo '</span>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        echo '<div style="margin-top: 20px; text-align: center; color: #666; font-size: 14px;">';
        echo 'Всего типов наград: ' . count($award_types);
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div style="text-align: center; padding: 30px; color: #e74c3c; background: #f8d7da; border-radius: 8px;">';
    echo '<h3>❌ Ошибка загрузки данных</h3>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<button onclick="loadAwardsData()" class="btn">🔄 Попробовать снова</button>';
    echo '</div>';
}

closeDB($pdo);
?>