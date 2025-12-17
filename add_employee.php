<?php
require_once 'config.php';
$message = '';
$error = '';
// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Получаем данные из формы
        $last_name = trim($_POST['last_name'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $birth_date = $_POST['birth_date'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $inn = trim($_POST['inn'] ?? '');
        $snils = trim($_POST['snils'] ?? '');
        $work_experience = intval($_POST['work_experience'] ?? 0);
        $manager_number = !empty($_POST['manager_number']) ? intval($_POST['manager_number']) : null;        
        // Данные трудового договора
        $contract_number = trim($_POST['contract_number'] ?? '');
        $contract_date = $_POST['contract_date'] ?? '';
        $position = trim($_POST['position'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $salary = !empty($_POST['salary']) ? floatval($_POST['salary']) : null;
        $contract_type = trim($_POST['contract_type'] ?? 'permanent');
        $probation_period = intval($_POST['probation_period'] ?? 0);        
        // Данные образования (собираем в JSON)
        $educations = [];
        if (isset($_POST['education_level']) && is_array($_POST['education_level'])) {
            $education_count = count($_POST['education_level']);
            for ($i = 0; $i < $education_count; $i++) {
                $education_level = trim($_POST['education_level'][$i] ?? '');
                $institution = trim($_POST['institution'][$i] ?? '');
                $specialty = trim($_POST['specialty'][$i] ?? '');
                $graduation_year = !empty($_POST['graduation_year'][$i]) ? intval($_POST['graduation_year'][$i]) : null;
                $qualification = trim($_POST['qualification'][$i] ?? '');                
                // Добавляем только если есть хотя бы одно поле заполнено
                if (!empty($education_level) || !empty($institution) || !empty($specialty)) {
                    $educations[] = [
                        'level' => $education_level,
                        'institution' => $institution,
                        'specialty' => $specialty,
                        'graduation_year' => $graduation_year,
                        'qualification' => $qualification
                    ];
                }
            }
        }        
        // Данные воинского учета (собираем в JSON)
        $military_data = [];
        if (!empty($_POST['military_service_status']) || !empty($_POST['military_rank']) || 
            !empty($_POST['military_category']) || !empty($_POST['military_composition']) || 
            !empty($_POST['military_specialty']) || !empty($_POST['military_duty'])) {
            
            $military_data = [
                'status' => trim($_POST['military_service_status'] ?? ''),
                'rank' => trim($_POST['military_rank'] ?? ''),
                'category' => trim($_POST['military_category'] ?? ''),
                'composition' => trim($_POST['military_composition'] ?? ''),
                'specialty' => trim($_POST['military_specialty'] ?? ''),
                'duty' => trim($_POST['military_duty'] ?? '')
            ];
        }        
        // Данные наград (собираем в JSON)
        $awards = [];
        if (isset($_POST['award_type_code']) && is_array($_POST['award_type_code'])) {
            $award_count = count($_POST['award_type_code']);
            for ($i = 0; $i < $award_count; $i++) {
                $award_type_code = !empty($_POST['award_type_code'][$i]) ? intval($_POST['award_type_code'][$i]) : null;
                $award_date = trim($_POST['award_date'][$i] ?? '');
                
                // Добавляем только если выбран тип награды
                if (!empty($award_type_code)) {
                    // Получаем название награды по коду
                    $award_name = '';
                    foreach ($award_types as $type) {
                        if ($type['award_type_code'] == $award_type_code) {
                            $award_name = $type['award_type_name'];
                            break;
                        }
                    }
                    
                    $awards[] = [
                        'type_code' => $award_type_code,
                        'name' => $award_name,
                        'date' => $award_date
                    ];
                }
            }
        }
        // Валидация обязательных полей
        $errors = [];        
        if (empty($last_name)) $errors[] = 'Фамилия обязательна';
        if (empty($first_name)) $errors[] = 'Имя обязательно';
        if (empty($birth_date)) $errors[] = 'Дата рождения обязательна';
        if (empty($phone)) $errors[] = 'Телефон обязателен';
        if (empty($inn)) $errors[] = 'ИНН обязателен';
        if (empty($snils)) $errors[] = 'СНИЛС обязателен';
        
        if (!empty($errors)) {
            throw new Exception(implode('<br>', $errors));
        }        
        // Проверяем возраст (не менее 18 лет)
        $birth_date_obj = new DateTime($birth_date);
        $today = new DateTime();
        $age = $birth_date_obj->diff($today)->y;        
        if ($age < 18) {
            throw new Exception('Сотрудник должен быть старше 18 лет');
        }        
        // Проверяем, существует ли уже такой ИНН
        $existing_inn = fetchOne($pdo, "SELECT employee_number FROM employee WHERE inn = ?", [$inn]);
        if ($existing_inn) {
            throw new Exception('Сотрудник с таким ИНН уже существует (№' . $existing_inn['employee_number'] . ')');
        }        
        // Проверяем, существует ли уже такой СНИЛС
        $existing_snils = fetchOne($pdo, "SELECT employee_number FROM employee WHERE snils = ?", [$snils]);
        if ($existing_snils) {
            throw new Exception('Сотрудник с таким СНИЛС уже существует (№' . $existing_snils['employee_number'] . ')');
        }        
        // Получаем следующий табельный номер
        $max_number = fetchOne($pdo, "SELECT MAX(employee_number) as max FROM employee");
        $next_number = ($max_number['max'] ?? 0) + 1;        
        // Подключаемся к базе
        $pdo = connectDB();        
        // Конвертируем массивы в JSON для хранения в БД
        $education_json = !empty($educations) ? json_encode($educations, JSON_UNESCAPED_UNICODE) : null;
        $military_json = !empty($military_data) ? json_encode($military_data, JSON_UNESCAPED_UNICODE) : null;
        $awards_json = !empty($awards) ? json_encode($awards, JSON_UNESCAPED_UNICODE) : null;        
        // Добавляем сотрудника (все данные в одной таблице)
        $sql = "
            INSERT INTO employee (
                employee_number, last_name, first_name, middle_name,
                birth_date, phone, email, work_experience, inn, snils, manager_number,
                contract_number, contract_date, position, department, salary,
                contract_type, probation_period, education_data, military_data, awards_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $next_number, $last_name, $first_name, $middle_name,
            $birth_date, $phone, $email, $work_experience, $inn, $snils, $manager_number,
            $contract_number, $contract_date, $position, $department, $salary,
            $contract_type, $probation_period, $education_json, $military_json, $awards_json
        ]);        
        $message = "✅ Сотрудник успешно добавлен! Табельный номер: $next_number";        
        // Очищаем форму
        $_POST = [];        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
    }
}
// Подключаемся для получения списков
$pdo = connectDB();
// Получаем список руководителей для выпадающего списка
$managers = fetchAll($pdo, "
    SELECT employee_number, last_name || ' ' || first_name as full_name
    FROM employee 
    ORDER BY last_name, first_name
");
// Статусы воинской службы
$military_statuses = [
    '' => 'Не указано',
    'liable' => 'Военнообязанный',
    'non_liable' => 'Невоеннообязанный',
    'reserve' => 'Запас',
    'exempt' => 'Освобожден',
    'served' => 'Отслужил'
];
// Категории воинского учета
$military_categories = [
    '' => 'Не указано',
    'A' => 'А - годен к военной службе',
    'B' => 'Б - годен с незначительными ограничениями',
    'C' => 'В - ограниченно годен',
    'D' => 'Г - временно не годен',
    'E' => 'Д - не годен'
];

$award_types = fetchAll($pdo, "
    SELECT award_type_code, award_type_name 
    FROM award_types 
    ORDER BY award_type_name
");

// Создаем массив для выпадающего списка
$award_types_options = ['' => 'Не выбрано'];
foreach ($award_types as $type) {
    $award_types_options[$type['award_type_code']] = $type['award_type_name'];
}
// Уровни образования
$education_levels = [
    '' => 'Не указано',
    'secondary' => 'Среднее общее',
    'vocational' => 'Среднее профессиональное',
    'bachelor' => 'Бакалавриат',
    'specialist' => 'Специалитет',
    'master' => 'Магистратура',
    'phd' => 'Кандидат наук',
    'doctor' => 'Доктор наук'
];
// Типы трудового договора
$contract_types = [
    'permanent' => 'Бессрочный',
    'fixed_term' => 'Срочный',
    'seasonal' => 'Сезонный',
    'part_time' => 'По совместительству'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить нового сотрудника</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 25px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
        }        
        h1 {
            margin: 0;
            font-size: 28px;
        }        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
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
        /* Стили для вкладок */
        .tabs {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 2px solid #3498db;
            margin-bottom: 30px;
        }        
        .tab {
            padding: 12px 25px;
            background: #f8f9fa;
            border: none;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            font-size: 16px;
            margin-right: 5px;
            margin-bottom: -2px;
            transition: all 0.3s;
            border: 1px solid #ddd;
            border-bottom: none;
        }        
        .tab:hover {
            background: #e9ecef;
        }        
        .tab.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
            font-weight: bold;
        }        
        .tab-content {
            display: none;
            padding: 20px 0;
        }        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }        
        /* Общие стили формы */
        .form-group {
            margin-bottom: 20px;
        }        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }        
        input:focus, select:focus, textarea:focus {
            border-color: #3498db;
            outline: none;
        }        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }        
        .form-section {
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }        
        .form-section:last-child {
            border-bottom: none;
        }        
        .form-section h3 {
            color: #3498db;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }        
        .section-header h3 {
            margin: 0;
        }        
        .optional-badge {
            background: #f39c12;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: normal;
            margin-left: 10px;
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
            margin-right: 10px;
            transition: background 0.3s;
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
        .btn-back {
            background: #7f8c8d;
        }        
        .btn-back:hover {
            background: #666;
        }        
        .btn-danger {
            background: #e74c3c;
        }        
        .btn-danger:hover {
            background: #c0392b;
        }        
        .btn-add {
            background: #9b59b6;
        }        
        .btn-add:hover {
            background: #8e44ad;
        }        
        .btn-sm {
            padding: 8px 15px;
            font-size: 14px;
        }        
        .required::after {
            content: ' *';
            color: #e74c3c;
        }        
        .help-text {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }        
        /* Стили для карточек образования и наград */
        .education-card, .award-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            margin-bottom: 40px; /* Большой отступ между карточками */
            padding: 0;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: box-shadow 0.3s;
        }        
        .education-card:hover, .award-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }        
        .education-header, .award-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }        
        .education-header h4, .award-header h4 {
            margin: 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
        }        
        .education-fields, .award-fields {
            padding: 25px 20px;
        }        
        .education-footer, .award-footer {
            padding: 20px 25px; /* Увеличенный padding */
            border-top: 2px solid #e9ecef;
            background: #f8f9fa;
            text-align: right;
            margin-top: 20px; /* Отступ сверху */
        }        
        .education-footer .btn, .award-footer .btn {
            margin-right: 0;
            padding: 10px 20px;
            font-size: 15px;
        }        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }        
        select.form-control {
            height: 42px;
        }        
        /* Стиль для нумерации */
        .education-number, .award-number {
            color: #3498db;
            font-weight: bold;
        }        
        /* Кнопка добавления */
        .btn-add {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            transition: background 0.3s;
        }        
        .btn-add:hover {
            background: #219653;
        }        
        /* Отступы для вкладок */
        #education-container {
            margin-bottom: 25px;
        }        
        #awards-container {
            margin-bottom: 25px;
        }        
        /* Кнопка добавления награды с БОЛЬШИМ отступом */
        .add-award-container {
            margin-top: 50px !important;
            padding-top: 30px !important;
            border-top: 2px solid #dee2e6 !important;
            text-align: center;
        }        
        /* Убираем лишнее "Необязательно" из заголовка Образование */
        #education-tab .optional-badge {
            display: none !important;
        }        
        @media (max-width: 768px) {
            .form-row, .education-row, .award-row {
                grid-template-columns: 1fr;
            }            
            .tabs {
                flex-direction: column;
            }            
            .tab {
                margin-bottom: 5px;
                border-radius: 5px;
                border: 1px solid #ddd;
            }            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }            
            .section-header .btn {
                margin-top: 10px;
                margin-left: 0;
            }            
            .education-card, .award-card {
                margin-bottom: 30px;
            }            
            .education-fields, .award-fields {
                padding: 15px;
            }            
            .education-header, .award-header {
                padding: 12px 15px;
            }            
            .education-footer, .award-footer {
                padding: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>➕ Добавить нового сотрудника</h1>
            <p>Заполните информацию о новом сотруднике</p>
        </div>        
        <div class="card">
            <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>            
            <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>            
            <form method="POST" action="" id="employeeForm">
                <!-- Вкладки -->
                <div class="tabs">
                    <button type="button" class="tab active" data-tab="basic">Основная информация</button>
                    <button type="button" class="tab" data-tab="contract">Трудовой договор</button>
                    <button type="button" class="tab" data-tab="education">Образование</button>
                    <button type="button" class="tab" data-tab="military">Воинский учет <span class="optional-badge">Необязательно</span></button>
                    <button type="button" class="tab" data-tab="awards">Награды <span class="optional-badge">Необязательно</span></button>
                </div>                
                <!-- Вкладка 1: Основная информация -->
                <div id="basic-tab" class="tab-content active">
                    <div class="form-section">
                        <h3>Личные данные</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">Фамилия</label>
                                <input type="text" name="last_name" required 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                       placeholder="Иванов">
                            </div>                            
                            <div class="form-group">
                                <label class="required">Имя</label>
                                <input type="text" name="first_name" required 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                       placeholder="Иван">
                            </div>
                        </div>                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Отчество</label>
                                <input type="text" name="middle_name" 
                                       value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>"
                                       placeholder="Иванович">
                            </div>                            
                            <div class="form-group">
                                <label class="required">Дата рождения</label>
                                <input type="date" name="birth_date" required 
                                       value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>">
                                <div class="help-text">Сотрудник должен быть старше 18 лет</div>
                            </div>
                        </div>
                    </div>                    
                    <div class="form-section">
                        <h3>Контактная информация</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">Телефон</label>
                                <input type="tel" name="phone" required 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       placeholder="+79261234567">
                                <div class="help-text">Формат: +7XXXXXXXXXX</div>
                            </div>                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="ivanov@company.ru">
                            </div>
                        </div>
                    </div>                    
                    <div class="form-section">
                        <h3>Документы</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">ИНН</label>
                                <input type="text" name="inn" required 
                                       value="<?php echo htmlspecialchars($_POST['inn'] ?? ''); ?>"
                                       placeholder="770112345678">
                                <div class="help-text">12 цифр</div>
                            </div>                            
                            <div class="form-group">
                                <label class="required">СНИЛС</label>
                                <input type="text" name="snils" required 
                                       value="<?php echo htmlspecialchars($_POST['snils'] ?? ''); ?>"
                                       placeholder="123-456-789 01">
                                <div class="help-text">Формат: XXX-XXX-XXX XX</div>
                            </div>
                        </div>                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Стаж работы (лет)</label>
                                <input type="number" name="work_experience" min="0" max="70"
                                       value="<?php echo htmlspecialchars($_POST['work_experience'] ?? 0); ?>">
                            </div>                            
                            <div class="form-group">
                                <label>Руководитель</label>
                                <select name="manager_number">
                                    <option value="">Не указан</option>
                                    <?php foreach ($managers as $manager): ?>
                                    <option value="<?php echo $manager['employee_number']; ?>"
                                        <?php echo (($_POST['manager_number'] ?? '') == $manager['employee_number']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($manager['full_name']); ?>
                                        (№<?php echo $manager['employee_number']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>                
                <!-- Вкладка 2: Трудовой договор -->
                <div id="contract-tab" class="tab-content">
                    <div class="form-section">
                        <h3>Данные трудового договора</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Номер договора</label>
                                <input type="text" name="contract_number" 
                                       value="<?php echo htmlspecialchars($_POST['contract_number'] ?? ''); ?>"
                                       placeholder="ТД-2024-001">
                            </div>                            
                            <div class="form-group">
                                <label>Дата договора</label>
                                <input type="date" name="contract_date" 
                                       value="<?php echo htmlspecialchars($_POST['contract_date'] ?? ''); ?>">
                            </div>
                        </div>                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Должность</label>
                                <input type="text" name="position" 
                                       value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>"
                                       placeholder="Менеджер">
                            </div>                            
                            <div class="form-group">
                                <label>Подразделение</label>
                                <input type="text" name="department" 
                                       value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>"
                                       placeholder="Отдел продаж">
                            </div>
                        </div>                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Оклад (руб.)</label>
                                <input type="number" name="salary" min="0" step="0.01"
                                       value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>"
                                       placeholder="50000.00">
                            </div>                            
                            <div class="form-group">
                                <label>Тип договора</label>
                                <select name="contract_type">
                                    <?php foreach ($contract_types as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"
                                        <?php echo (($_POST['contract_type'] ?? 'permanent') == $value) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Испытательный срок (мес.)</label>
                                <input type="number" name="probation_period" min="0" max="12"
                                       value="<?php echo htmlspecialchars($_POST['probation_period'] ?? 0); ?>">
                            </div>
                            <div class="form-group"></div>
                        </div>
                    </div>
                </div>                
                <!-- Вкладка 3: Образование -->
            <div id="education-tab" class="tab-content">
                <div class="form-section">
                    <div class="section-header">
                        <h3>Образование</h3>
                        <button type="button" class="btn btn-add" onclick="addEducation()">
                            <span style="margin-right: 8px;">🎓</span> Добавить образование
                        </button>
                    </div>                    
                    <div class="help-text" style="margin-bottom: 25px;">
                        Можно добавить несколько образований. Заполните хотя бы одно поле в блоке.
                    </div>                    
                    <div id="education-container">
                        <!-- Образование будет добавляться сюда -->
                    </div>
                </div>
            </div>                
                <!-- Вкладка 4: Воинский учет -->
                <div id="military-tab" class="tab-content">
                    <div class="form-section">
                        <div class="section-header">
                            <h3>Воинский учет</h3>
                            <span class="optional-badge">Необязательно</span>
                        </div>                        
                        <div class="help-text" style="margin-bottom: 25px;">
                            Заполните только при наличии информации о воинском учете сотрудника.
                        </div>                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Статус воинской службы</label>
                                <select name="military_service_status">
                                    <?php foreach ($military_statuses as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"
                                        <?php echo (($_POST['military_service_status'] ?? '') == $value) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>                            
                            <div class="form-group">
                                <label>Воинское звание</label>
                                <input type="text" name="military_rank" 
                                       value="<?php echo htmlspecialchars($_POST['military_rank'] ?? ''); ?>"
                                       placeholder="Рядовой">
                            </div>
                        </div>                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Категория годности</label>
                                <select name="military_category">
                                    <?php foreach ($military_categories as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"
                                        <?php echo (($_POST['military_category'] ?? '') == $value) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>                            
                            <div class="form-group">
                                <label>Состав</label>
                                <input type="text" name="military_composition" 
                                       value="<?php echo htmlspecialchars($_POST['military_composition'] ?? ''); ?>"
                                       placeholder="Солдаты">
                            </div>
                        </div>                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Военно-учетная специальность</label>
                                <input type="text" name="military_specialty" 
                                       value="<?php echo htmlspecialchars($_POST['military_specialty'] ?? ''); ?>"
                                       placeholder="ВУС-100000">
                            </div>                            
                            <div class="form-group">
                                <label>Обязанность</label>
                                <select name="military_duty">
                                    <option value="">Не указано</option>
                                    <option value="general" <?php echo (($_POST['military_duty'] ?? '') == 'general') ? 'selected' : ''; ?>>Общая</option>
                                    <option value="special" <?php echo (($_POST['military_duty'] ?? '') == 'special') ? 'selected' : ''; ?>>Специальная</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>                
                <!-- Вкладка 5: Награды -->
                <div id="awards-tab" class="tab-content">
                <div class="form-section">
                    <div class="section-header">
                        <h3>Награды и поощрения</h3>
                        <span class="optional-badge">Необязательно</span>
                    </div>                    
                     <div class="help-text" style="margin-bottom: 25px;">
                        Можно добавить несколько наград. Выберите тип награды из справочника и укажите дату.
                    </div>                    
                    <div id="awards-container">
                        <!-- Награды будут добавляться сюда -->
                    </div>                    
                    <div class="add-award-container">
                        <button type="button" class="btn btn-add" onclick="addAward()">
                            <span style="margin-right: 8px;">🏆</span> Добавить награду
                        </button>
                    </div>
                </div>
            </div>            
            <!-- Кнопки формы -->
            <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
                <button type="submit" class="btn btn-success">💾 Сохранить сотрудника</button>
                <a href="index.php" class="btn btn-back">← Назад к списку</a>
                <button type="button" class="btn" onclick="resetForm()">🔄 Очистить форму</button>
            </div>
        </form>
    </div>    
    <!-- Шаблоны -->
    <template id="education-template">
        <div class="education-card">
            <div class="education-header">
                <h4>Образование #<span class="education-number">1</span></h4>
            </div>            
            <div class="education-fields">
                <div class="form-row">
                    <div class="form-group">
                        <label>Уровень образования</label>
                        <select name="education_level[]" class="form-control">
                            <?php foreach ($education_levels as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>                    
                    <div class="form-group">
                        <label>Учебное заведение</label>
                        <input type="text" name="institution[]" class="form-control" 
                               placeholder="Московский государственный университет">
                    </div>
                </div>                
                <div class="form-row">
                    <div class="form-group">
                        <label>Специальность</label>
                        <input type="text" name="specialty[]" class="form-control" placeholder="Экономика">
                    </div>                    
                    <div class="form-group">
                        <label>Год окончания</label>
                        <input type="number" name="graduation_year[]" class="form-control" 
                               min="1950" max="<?php echo date('Y'); ?>" placeholder="2020">
                    </div>
                </div>                
                <div class="form-row">
                    <div class="form-group">
                        <label>Квалификация</label>
                        <input type="text" name="qualification[]" class="form-control" placeholder="Экономист">
                    </div>
                    <div class="form-group"></div>
                </div>
            </div>            
            <div class="education-footer">
                <button type="button" class="btn btn-danger" onclick="removeEducation(this)">
                    <span style="margin-right: 8px;">🗑️</span> Удалить образование
                </button>
            </div>
        </div>
    </template>    
        <template id="award-template">
        <div class="award-card">
            <div class="award-header">
                <h4>Награда #<span class="award-number">1</span></h4>
            </div>
            
            <div class="award-fields">
                <div class="form-row">
                    <div class="form-group">
                        <label>Тип награды</label>
                        <select name="award_type_code[]" class="form-control">
                            <?php foreach ($award_types_options as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>">
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #6c757d; font-size: 12px;">Выберите из справочника</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Дата получения</label>
                        <input type="date" name="award_date[]" class="form-control" required>
                        <small style="color: #6c757d; font-size: 12px;">Обязательное поле</small>
                    </div>
                </div>
            </div>
            
            <div class="award-footer">
                <button type="button" class="btn btn-danger" onclick="removeAward(this)">
                    <span style="margin-right: 8px;">🗑️</span> Удалить награду
                </button>
            </div>
        </div>
    </template>
    <script>
    // Упрощенный и работающий JavaScript    
    // ====== УПРАВЛЕНИЕ ВКЛАДКАМИ ======
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Скрываем все вкладки
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });            
            // Убираем активный класс со всех табов
            document.querySelectorAll('.tab').forEach(t => {
                t.classList.remove('active');
            });            
            // Показываем выбранную вкладку
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId + '-tab').classList.add('active');
            this.classList.add('active');
        });
    });    
    // ====== ОБНОВЛЕНИЕ НУМЕРАЦИИ ======
    function updateNumbers() {
        // Образование
        const educationCards = document.querySelectorAll('.education-card');
        educationCards.forEach((card, index) => {
            const numberSpan = card.querySelector('.education-number');
            if (numberSpan) {
                numberSpan.textContent = index + 1;
            }
        });        
        // Награды
        const awardCards = document.querySelectorAll('.award-card');
        awardCards.forEach((card, index) => {
            const numberSpan = card.querySelector('.award-number');
            if (numberSpan) {
                numberSpan.textContent = index + 1;
            }
        });
    }    
    // ====== ОБРАЗОВАНИЕ ======
    function addEducation() {
        const container = document.getElementById('education-container');
        const template = document.getElementById('education-template');        
        if (!container || !template) {
            alert('Ошибка: не найден контейнер или шаблон образования');
            return;
        }        
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        updateNumbers();
    }    
    function removeEducation(button) {
        const educationCard = button.closest('.education-card');
        if (!educationCard) return;        
        const educationCards = document.querySelectorAll('.education-card');        
        if (educationCards.length > 1) {
            if (confirm('Вы уверены, что хотите удалить это образование?')) {
                educationCard.remove();
                updateNumbers();
            }
        } else {
            // Если это последнее образование, очищаем поля
            educationCard.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            educationCard.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
        }
    }    
    // ====== НАГРАДЫ ======
    function addAward() {
        const container = document.getElementById('awards-container');
        const template = document.getElementById('award-template');        
        if (!container || !template) {
            alert('Ошибка: не найден контейнер или шаблон наград');
            return;
        }        
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        updateNumbers();
    }    
    function removeAward(button) {
        const awardCard = button.closest('.award-card');
        if (!awardCard) return;        
        const awardCards = document.querySelectorAll('.award-card');        
        if (awardCards.length > 1) {
            if (confirm('Вы уверены, что хотите удалить эту награду?')) {
                awardCard.remove();
                updateNumbers();
            }
        } else {
            // Если это последняя награда, очищаем поля
            awardCard.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
        }
    }    
    // ====== СБРОС ФОРМЫ ======
    function resetForm() {
        if (confirm('Вы уверены, что хотите очистить все поля формы?')) {
            // Очищаем все поля
            document.querySelectorAll('input:not([type="button"]):not([type="submit"])').forEach(input => {
                input.value = '';
            });            
            document.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });            
            // Возвращаемся на первую вкладку
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(t => {
                t.classList.remove('active');
            });
            document.getElementById('basic-tab').classList.add('active');
            document.querySelector('.tab[data-tab="basic"]').classList.add('active');            
            // Очищаем и пересоздаем блоки
            const educationContainer = document.getElementById('education-container');
            const awardsContainer = document.getElementById('awards-container');            
            if (educationContainer) {
                educationContainer.innerHTML = '';
                addEducation();
            }            
            if (awardsContainer) {
                awardsContainer.innerHTML = '';
                addAward();
            }
        }
    }    
    // ====== МАСКИ ДЛЯ ПОЛЕЙ ======
    // Телефон
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (!value.startsWith('7') && !value.startsWith('8')) {
                value = '7' + value;
            }
            if (value.length > 11) value = value.substring(0, 11);
            e.target.value = '+7' + value.substring(1);
        });
    }    
    // СНИЛС
    const snilsInput = document.querySelector('input[name="snils"]');
    if (snilsInput) {
        snilsInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            
            let formatted = '';
            if (value.length > 0) formatted = value.substring(0, 3);
            if (value.length > 3) formatted += '-' + value.substring(3, 6);
            if (value.length > 6) formatted += '-' + value.substring(6, 9);
            if (value.length > 9) formatted += ' ' + value.substring(9, 11);
            
            e.target.value = formatted;
        });
    }    
    // ИНН
    const innInput = document.querySelector('input[name="inn"]');
    if (innInput) {
        innInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    }    
    // ====== ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ ======
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Форма добавления сотрудника загружена');        
        // Добавляем начальные блоки
        const educationContainer = document.getElementById('education-container');
        const awardsContainer = document.getElementById('awards-container');        
        if (educationContainer && educationContainer.children.length === 0) {
            addEducation();
        } else {
            updateNumbers();
        }        
        if (awardsContainer && awardsContainer.children.length === 0) {
            addAward();
        } else {
            updateNumbers();
        }
    });
    </script>
</body>
</html>
<?php 
// Закрываем соединение
if (isset($pdo)) {
    closeDB($pdo);
}
?>