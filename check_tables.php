<?php
require_once 'config.php';

$pdo = connectDB();

echo '<h1>Проверка всех таблиц базы данных</h1>';

$tables = [
    'employee' => 'Сотрудники',
    'employment_contract' => 'Трудовые договоры',
    'department' => 'Отделы',
    'position' => 'Должности',
    'education' => 'Образование',
    'education_type' => 'Типы образования',
    'military_record' => 'Воинский учет',
    'award' => 'Награды'
];

echo '<table border="1" cellpadding="10">';
echo '<tr><th>Таблица</th><th>Название</th><th>Кол-во записей</th><th>Столбцы</th><th>Статус</th></tr>';

foreach ($tables as $table => $name) {
    try {
        // Получаем количество записей
        $count_result = fetchOne($pdo, "SELECT COUNT(*) as cnt FROM $table");
        $count = $count_result['cnt'];
        
        // Получаем информацию о столбцах
        $columns_result = fetchAll($pdo, "
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns 
            WHERE table_schema = 'hr_schema' 
                AND table_name = '$table'
            ORDER BY ordinal_position
        ");
        
        $columns = [];
        foreach ($columns_result as $col) {
            $columns[] = $col['column_name'] . ' (' . $col['data_type'] . ')';
        }
        
        echo '<tr>';
        echo '<td>' . $table . '</td>';
        echo '<td><strong>' . $name . '</strong></td>';
        echo '<td>' . $count . '</td>';
        echo '<td><small>' . implode('<br>', $columns) . '</small></td>';
        echo '<td style="color: green;">✅ Доступна</td>';
        echo '</tr>';
        
    } catch (Exception $e) {
        echo '<tr>';
        echo '<td>' . $table . '</td>';
        echo '<td>' . $name . '</td>';
        echo '<td>—</td>';
        echo '<td>—</td>';
        echo '<td style="color: red;">❌ Ошибка: ' . $e->getMessage() . '</td>';
        echo '</tr>';
    }
}

echo '</table>';

echo '<h2>Сводная информация:</h2>';

try {
    $total_employees = fetchOne($pdo, "SELECT COUNT(*) as cnt FROM employee")['cnt'];
    $total_contracts = fetchOne($pdo, "SELECT COUNT(*) as cnt FROM employment_contract")['cnt'];
    $active_contracts = fetchOne($pdo, "SELECT COUNT(*) as cnt FROM employment_contract WHERE contract_status = 'Действующий'")['cnt'];
    $total_salary = fetchOne($pdo, "SELECT SUM(salary) as sum FROM employment_contract WHERE contract_status = 'Действующий'")['sum'];
    
    echo '<ul>';
    echo '<li><strong>Всего сотрудников:</strong> ' . $total_employees . '</li>';
    echo '<li><strong>Всего договоров:</strong> ' . $total_contracts . '</li>';
    echo '<li><strong>Действующих договоров:</strong> ' . $active_contracts . '</li>';
    echo '<li><strong>Общий ФОТ:</strong> ' . number_format($total_salary, 0, ',', ' ') . ' ₽</li>';
    echo '</ul>';
    
} catch (Exception $e) {
    echo '<p style="color: red;">Ошибка получения сводной информации: ' . $e->getMessage() . '</p>';
}

echo '<p><a href="index.php">← Вернуться на главную</a></p>';

closeDB($pdo);
?>