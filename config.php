<?php
/**
 * Конфиг для подключения к PostgreSQL через PDO
 * Работает локально и на Render.com
 */

// Включаем отображение ошибок для отладки (только не в продакшене)
if (isset($_SERVER['RENDER']) || isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // На продакшене скрываем ошибки
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Настройки подключения для разных окружений
if (isset($_SERVER['RENDER'])) {
    // НА RENDER.COM (ПРОДАКШЕН)
    define('DB_HOST', 'dpg-d5049v56ubrc73a8f45g-a.oregon-postgres.render.com');
    define('DB_PORT', '5432');
    define('DB_NAME', 'hr_system_ayzz');
    define('DB_USER', 'hr_user');
    define('DB_PASS', 'nRfBQfsXlyQamhI8srOqOuWVZEMTvpA7');
    define('DB_SCHEMA', 'hr_schema');
} elseif (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') {
    // ЛОКАЛЬНО (OpenServer)
    define('DB_HOST', 'localhost');
    define('DB_PORT', '5432');
    define('DB_NAME', 'card_employee_db');
    define('DB_USER', 'postgres');
    define('DB_PASS', '123');  // Ваш пароль от PostgreSQL
    define('DB_SCHEMA', 'hr_schema');
} else {
    // ДЛЯ ТЕСТОВОГО РЕЖИМА (если нужно)
    define('DB_HOST', 'dpg-d5049v56ubrc73a8f45g-a.oregon-postgres.render.com');
    define('DB_PORT', '5432');
    define('DB_NAME', 'hr_system_ayzz');
    define('DB_USER', 'hr_user');
    define('DB_PASS', 'nRfBQfsXlyQamhI8srOqOuWVZEMTvpA7');
    define('DB_SCHEMA', 'hr_schema');
}

/**
 * Подключение к базе данных
 */
function connectDB() {
    try {
        // Формируем строку подключения DSN с указанием схемы
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        
        // Создаем подключение через PDO
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        
        // Настраиваем параметры PDO
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        // Устанавливаем схему через параметр connection
        $pdo->exec("SET search_path TO " . DB_SCHEMA);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Детализированная ошибка для отладки
        $error_message = "❌ Ошибка подключения к PostgreSQL:<br>";
        $error_message .= "<strong>Сообщение:</strong> " . $e->getMessage() . "<br>";
        
        // Добавляем информацию об окружении
        $error_message .= "<strong>Окружение:</strong> " . 
            (isset($_SERVER['RENDER']) ? 'Render' : 
             (isset($_SERVER['SERVER_NAME']) ? 'Локальное (' . $_SERVER['SERVER_NAME'] . ')' : 'Неизвестно')) . "<br>";
        
        $error_message .= "<strong>Хост:</strong> " . DB_HOST . "<br>";
        $error_message .= "<strong>База:</strong> " . DB_NAME . "<br>";
        $error_message .= "<strong>Пользователь:</strong> " . DB_USER . "<br>";
        
        // Проверяем доступность расширений только локально
        if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') {
            $error_message .= "<br><strong>Проверка расширений PHP:</strong><br>";
            $extensions = get_loaded_extensions();
            $pdo_ext = false;
            
            foreach ($extensions as $ext) {
                if (strtolower($ext) == 'pdo_pgsql') {
                    $pdo_ext = true;
                    $error_message .= "✅ " . $ext . "<br>";
                }
            }
            
            if (!$pdo_ext) {
                $error_message .= "<br>❌ Расширение <strong>pdo_pgsql</strong> не найдено!<br>";
                $error_message .= "Действия для Open Server:<br>";
                $error_message .= "1. Откройте Open Server → Настройки → PHP<br>";
                $error_message .= "2. Выберите вашу версию PHP<br>";
                $error_message .= "3. Нажмите 'Дополнительно' → 'php.ini'<br>";
                $error_message .= "4. Найдите строку: <code>;extension=pdo_pgsql</code><br>";
                $error_message .= "5. Уберите точку с запятой: <code>extension=pdo_pgsql</code><br>";
                $error_message .= "6. Также уберите точку с запятой у: <code>;extension=pgsql</code><br>";
                $error_message .= "7. Сохраните и перезапустите Open Server<br>";
            }
        }
        
        die($error_message);
    }
}

/**
 * Выполнение SQL запроса
 */
function queryDB($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // Более информативная ошибка
        $error_details = "❌ Ошибка SQL-запроса:<br>";
        $error_details .= "<strong>Сообщение:</strong> " . $e->getMessage() . "<br>";
        $error_details .= "<strong>Запрос:</strong> " . htmlspecialchars($sql) . "<br>";
        
        if (!empty($params)) {
            $error_details .= "<strong>Параметры:</strong> " . print_r($params, true) . "<br>";
        }
        
        // Добавляем информацию об окружении
        if (isset($_SERVER['RENDER'])) {
            $error_details .= "<br><em>⚠️ На Render могут быть задержки при первом подключении к БД</em><br>";
        }
        
        die($error_details);
    }
}

/**
 * Получение всех записей
 */
function fetchAll($pdo, $sql, $params = []) {
    $stmt = queryDB($pdo, $sql, $params);
    return $stmt->fetchAll();
}

/**
 * Получение одной записи
 */
function fetchOne($pdo, $sql, $params = []) {
    $stmt = queryDB($pdo, $sql, $params);
    return $stmt->fetch();
}

/**
 * Закрытие соединения
 */
function closeDB($pdo) {
    $pdo = null;
}

/**
 * Тестовая функция для проверки подключения
 */
function testConnection() {
    try {
        $pdo = connectDB();
        $result = fetchOne($pdo, "SELECT current_database() as db, current_schema() as schema");
        
        echo "<div style='background:#d4edda; padding:10px; border-radius:5px; margin:10px 0;'>";
        echo "✅ Подключение успешно!<br>";
        echo "<strong>База данных:</strong> " . $result['db'] . "<br>";
        echo "<strong>Схема:</strong> " . $result['schema'] . "<br>";
        echo "<strong>Хост:</strong> " . DB_HOST . "<br>";
        echo "<strong>Окружение:</strong> " . (isset($_SERVER['RENDER']) ? 'Render' : 'Локальное');
        echo "</div>";
        
        return $pdo;
    } catch (Exception $e) {
        echo "<div style='background:#f8d7da; padding:10px; border-radius:5px; margin:10px 0;'>";
        echo "❌ Ошибка подключения: " . $e->getMessage();
        echo "</div>";
        return false;
    }
}

// Автоматическое тестирование подключения при запросе test=1
if (isset($_GET['test']) && $_GET['test'] == '1') {
    testConnection();
}
?>