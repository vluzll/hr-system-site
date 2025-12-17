<?php
echo '<h1>Проверка расширений PHP</h1>';
echo '<h2>Текущая версия PHP: ' . phpversion() . '</h2>';

// Получаем все расширения
$extensions = get_loaded_extensions();
sort($extensions);

echo '<h3>Все расширения PHP (' . count($extensions) . '):</h3>';
echo '<table border="1" cellpadding="5">';
echo '<tr><th>Расширение</th><th>Версия</th><th>Статус</th></tr>';

$pdo_found = false;
$pgsql_found = false;

foreach ($extensions as $ext) {
    $version = phpversion($ext) ?: 'N/A';
    $status = '';
    
    if (strtolower($ext) == 'pdo') {
        $pdo_found = true;
        $status = '✅ PDO доступен';
    } elseif (strtolower($ext) == 'pdo_pgsql') {
        $pdo_found = true;
        $status = '✅ PDO_PGSQL доступен!';
    } elseif (strtolower($ext) == 'pgsql') {
        $pgsql_found = true;
        $status = '✅ PGSQL доступен';
    }
    
    echo '<tr>';
    echo '<td>' . $ext . '</td>';
    echo '<td>' . $version . '</td>';
    echo '<td>' . $status . '</td>';
    echo '</tr>';
}

echo '</table>';

// Проверка специфически для PostgreSQL
echo '<h3>Проверка PostgreSQL:</h3>';

if (extension_loaded('pdo_pgsql')) {
    echo '✅ Расширение pdo_pgsql загружено<br>';
    
    // Проверяем доступные драйверы PDO
    echo '<h4>Доступные драйверы PDO:</h4>';
    $drivers = PDO::getAvailableDrivers();
    echo '<ul>';
    foreach ($drivers as $driver) {
        echo '<li>' . $driver . '</li>';
    }
    echo '</ul>';
    
} else {
    echo '❌ Расширение pdo_pgsql НЕ загружено<br>';
    
    echo '<h4>Как исправить в Open Server:</h4>';
    echo '<ol>';
    echo '<li>Откройте панель Open Server</li>';
    echo '<li>Настройки → PHP</li>';
    echo '<li>Выберите вашу версию PHP (например, PHP 8.1)</li>';
    echo '<li>Нажмите кнопку "Дополнительно" справа</li>';
    echo '<li>Выберите "php.ini"</li>';
    echo '<li>Найдите строки:</li>';
    echo '<li><code>;extension=pdo_pgsql</code> → замените на <code>extension=pdo_pgsql</code></li>';
    echo '<li><code>;extension=pgsql</code> → замените на <code>extension=pgsql</code></li>';
    echo '<li>Сохраните файл</li>';
    echo '<li>Перезапустите Open Server</li>';
    echo '</ol>';
}

// Тест подключения
echo '<h3>Тест подключения к PostgreSQL:</h3>';

if (extension_loaded('pdo_pgsql')) {
    try {
        $test_dsn = "pgsql:host=localhost;port=5432;dbname=postgres;user=postgres;password=123";
        $test_pdo = new PDO($test_dsn);
        $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $result = $test_pdo->query("SELECT version()");
        $version = $result->fetchColumn();
        
        echo '✅ Подключение успешно!<br>';
        echo '<strong>Версия PostgreSQL:</strong> ' . $version . '<br>';
        
        // Проверяем наличие нашей базы
        $dbs = $test_pdo->query("SELECT datname FROM pg_database WHERE datname = 'card_employee_db'");
        if ($dbs->rowCount() > 0) {
            echo '✅ База данных card_employee_db существует<br>';
        } else {
            echo '❌ База данных card_employee_db не найдена<br>';
        }
        
    } catch (PDOException $e) {
        echo '❌ Ошибка подключения: ' . $e->getMessage() . '<br>';
        echo '<strong>Проверьте:</strong><br>';
        echo '1. Запущен ли PostgreSQL в Open Server<br>';
        echo '2. Правильный ли пароль (по умолчанию пустой или "123")<br>';
        echo '3. Открыт ли порт 5432<br>';
    }
} else {
    echo 'Невозможно проверить подключение: расширение pdo_pgsql не загружено';
}

echo '<hr>';
echo '<a href="index.php">Вернуться к главной</a>';
?>