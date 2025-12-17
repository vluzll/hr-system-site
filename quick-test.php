<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? 'postgres';
    $password = $_POST['password'] ?? '';
    
    try {
        // Пробуем подключиться с введенными данными
        $dsn = "pgsql:host=localhost;port=5432;dbname=card_employee_db";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Проверяем схему
        $pdo->exec("SET search_path TO hr_schema");
        
        echo json_encode([
            'success' => true,
            'message' => '✅ Подключение успешно!',
            'user' => $username
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Ошибка: ' . $e->getMessage()
        ]);
    }
    exit;
}
?>
