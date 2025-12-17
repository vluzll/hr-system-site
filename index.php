<?php
require_once 'config.php';

// Подключаемся к базе
$pdo = connectDB();
// Обработка удаления записей
if (isset($_GET['delete'])) {
    $table = $_GET['table'] ?? '';
    $id = $_GET['id'] ?? 0;
    
    try {
        switch($table) {
            case 'contract':
                $sql = "DELETE FROM employment_contract WHERE contract_number = ?";
                break;
            case 'department':
                $sql = "DELETE FROM department WHERE department_code = ?";
                break;
            case 'education':
                $sql = "DELETE FROM education WHERE education_document_code = ?";
                break;
            case 'position':
                $sql = "DELETE FROM position WHERE position_code = ?";
                break;
            default:
                throw new Exception('Неизвестная таблица');
            case 'military':
                $sql = "DELETE FROM military_record WHERE military_id_number = ?";
                break;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        // Перенаправляем с сообщением об успехе
        header('Location: index.php?success=' . urlencode("✅ Запись успешно удалена"));
        exit;
        
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode("❌ Ошибка удаления: " . $e->getMessage()));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Полная система кадрового учета</title>
    <style>
        /* Основные стили */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
            color: white;
            padding: 25px;
            border-radius: 15px 15px 0 0;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        /* Навигация */
        .nav-tabs {
            display: flex;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        
        .nav-tab {
            padding: 15px 20px;
            cursor: pointer;
            background: white;
            border: none;
            font-size: 15px;
            flex: 1;
            min-width: 150px;
            text-align: center;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
        }
        
        .nav-tab:hover {
            background: #f8f9fa;
        }
        
        .nav-tab.active {
            background: #3498db;
            color: white;
            border-bottom: 3px solid #2980b9;
        }
        
        /* Контент вкладок */
        .tab-content {
            display: none;
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Статистика */
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
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
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
        
        /* Таблицы */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        thead {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
        }
        
        th {
            padding: 14px;
            text-align: left;
            font-weight: 600;
            border: none;
            font-size: 14px;
        }
        
        tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background 0.3s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        td {
            padding: 12px 14px;
            color: #2c3e50;
            font-size: 14px;
        }
        
        /* Статусы */
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-terminated {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background: #fff3cd;
            color: #856404;
        }
        
        /* Деньги */
        .salary {
            font-weight: bold;
            color: #27ae60;
        }
        
        /* Кнопки */
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
            transition: background 0.3s;
            margin: 2px;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-success {
            background: #2ecc71;
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .btn-info {
            background: #17a2b8;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        /* Поиск и фильтры */
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .filter-select,
        .filter-input {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-select:focus,
        .filter-input:focus {
            border-color: #3498db;
            outline: none;
        }
        
        /* Подвал */
        footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #7f8c8d;
            font-size: 14px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        /* Воинский учет */
        .military-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Награды */
        .award-badge {
            background: #fff3e0;
            color: #e65100;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
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

        .usage-high {
            background: #d4edda;
            color: #155724;
        }

        .usage-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Образование */
        .education-badge {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .nav-tabs {
                flex-direction: column;
            }
            
            .nav-tab {
                width: 100%;
                border-bottom: 1px solid #eee;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
            
            .tab-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🏢 Полная система кадрового учета</h1>
            <div class="subtitle">Управление всеми данными сотрудников предприятия</div>
        </header>
                <!-- Сообщения -->
        <?php if (isset($_GET['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; 
                    margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; 
                    margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>
        <!-- Добавьте этот код после заголовка на главной странице -->
<div style="text-align: center; margin-bottom: 25px;">
    <a href="add_employee.php" class="btn" style="
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        padding: 15px 30px;
        font-size: 18px;
        font-weight: bold;
        box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
    ">
        ➕ Добавить нового сотрудника
    </a>
</div>
        <?php
        try {
            // Получаем статистику по всем таблицам
            $stats = [
                'Сотрудники' => fetchOne($pdo, "SELECT COUNT(*) as count FROM employee"),
                'Договоры' => fetchOne($pdo, "SELECT COUNT(*) as count FROM employment_contract"),
                'Действующие договоры' => fetchOne($pdo, "SELECT COUNT(*) as count FROM employment_contract WHERE contract_status = 'Действующий'"),
                'Отделы' => fetchOne($pdo, "SELECT COUNT(*) as count FROM department"),
                'Должности' => fetchOne($pdo, "SELECT COUNT(*) as count FROM position"),
                'Образование' => fetchOne($pdo, "SELECT COUNT(*) as count FROM education"),
                'Воинский учет' => fetchOne($pdo, "SELECT COUNT(*) as count FROM military_record"),
                'Награды' => fetchOne($pdo, "SELECT COUNT(*) as count FROM award")
            ];
            
            // Получаем данные для фильтров
            $departments = fetchAll($pdo, "SELECT department_code, department_name FROM department ORDER BY department_name");
            $positions = fetchAll($pdo, "SELECT position_code, position_name FROM position ORDER BY position_name");
            $education_types = fetchAll($pdo, "SELECT education_type_code, education_type_name FROM education_type ORDER BY education_type_name");
            
            // Преобразуем в JavaScript-массив для фильтрации
            $departments_js = [];
            foreach ($departments as $dept) {
                $departments_js[$dept['department_code']] = $dept['department_name'];
            }
            
            $education_types_js = [];
            foreach ($education_types as $type) {
                $education_types_js[$type['education_type_code']] = $type['education_type_name'];
            }
            
        } catch (Exception $e) {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;">';
            echo '❌ Ошибка получения данных: ' . $e->getMessage();
            echo '</div>';
            $stats = [];
            $departments = [];
            $positions = [];
            $education_types = [];
            $departments_js = [];
            $education_types_js = [];
        }
        ?>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['Сотрудники']['count'] ?? 0; ?></div>
                <div class="stat-label">👥 Сотрудников</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['Договоры']['count'] ?? 0; ?></div>
                <div class="stat-label">📝 Договоров</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['Действующие договоры']['count'] ?? 0; ?></div>
                <div class="stat-label">✅ Действующих</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['Отделы']['count'] ?? 0; ?></div>
                <div class="stat-label">🏢 Отделов</div>
            </div>
        </div>
        
        <!-- Навигация -->
        <div class="nav-tabs">
            <button class="nav-tab active" data-tab="employees">👥 Сотрудники</button>
            <button class="nav-tab" data-tab="contracts">📝 Договоры</button>
            <button class="nav-tab" data-tab="departments">🏢 Отделы</button>
            <button class="nav-tab" data-tab="education">🎓 Образование</button>
            <button class="nav-tab" data-tab="military">🎖️ Воинский учет</button>
            <button class="nav-tab" data-tab="awards">🏆 Награды</button>
            <!-- Добавьте эту строку после вкладки "Награды" -->
            <button class="nav-tab" data-tab="awards-reference">📖 Справочник наград</button>
        </div>
        
        <!-- Вкладка 1: Сотрудники -->
        <div id="employees" class="tab-content active">
            <h2>👥 Список всех сотрудников</h2>
            
            <div class="filters">
                <div class="filter-group">
                    <label class="filter-label">Отдел:</label>
                    <select class="filter-select" id="deptFilter">
                        <option value="">Все отделы</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_code']; ?>">
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Поиск по ФИО:</label>
                    <input type="text" class="filter-input" placeholder="Введите фамилию..." id="nameFilter">
                </div>
                
                <div class="filter-group" style="display: flex; flex-direction: row; align-items: flex-end; gap: 10px;">
                    <button class="btn" onclick="filterEmployees()">🔍 Применить фильтр</button>
                    <button class="btn btn-warning" onclick="resetFilter('employees')">🔄 Сбросить</button>
                </div>
            </div>
            
            <div class="table-container">
                <table id="employeesTable">
                    <thead>
                        <tr>
                            <th>Таб. №</th>
                            <th>ФИО</th>
                            <th>Дата рождения</th>
                            <th>Телефон</th>
                            <th>Стаж</th>
                            <th>Отдел / Должность</th>
                            <th>Оклад</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $employees = fetchAll($pdo, "
                                SELECT 
                                    e.employee_number,
                                    e.last_name,
                                    e.first_name,
                                    e.middle_name,
                                    e.birth_date,
                                    e.phone,
                                    e.work_experience,
                                    d.department_name,
                                    p.position_name,
                                    ec.salary,
                                    ec.contract_status,
                                    d.department_code
                                FROM employee e
                                LEFT JOIN employment_contract ec ON e.employee_number = ec.employee_number 
                                    AND ec.contract_status = 'Действующий'
                                LEFT JOIN department d ON ec.department_code = d.department_code
                                LEFT JOIN position p ON ec.position_code = p.position_code
                                ORDER BY e.last_name, e.first_name
                            ");
                            
                            foreach ($employees as $emp):
                                // Рассчитываем возраст
                                $birth_date = new DateTime($emp['birth_date']);
                                $age = $birth_date->diff(new DateTime())->y;
                        ?>
                        <tr data-dept="<?php echo $emp['department_code'] ?? ''; ?>">
                            <td><?php echo htmlspecialchars($emp['employee_number']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($emp['last_name']); ?></strong><br>
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . ($emp['middle_name'] ?: '')); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($emp['birth_date']); ?><br>
                                <small style="color: #7f8c8d;">(<?php echo $age; ?> лет)</small>
                            </td>
                            <td><?php echo htmlspecialchars($emp['phone']); ?></td>
                            <td><?php echo htmlspecialchars($emp['work_experience']); ?> лет</td>
                            <td>
                                <?php if ($emp['department_name']): ?>
                                <strong><?php echo htmlspecialchars($emp['department_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($emp['position_name'] ?: ''); ?></small>
                                <?php else: ?>
                                <span style="color: #7f8c8d;">Не назначен</span>
                                <?php endif; ?>
                            </td>
                            <td class="salary">
                                <?php 
                                if ($emp['salary']) {
                                    echo number_format($emp['salary'], 0, ',', ' ') . ' ₽';
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="employee.php?id=<?php echo $emp['employee_number']; ?>" 
                                   class="btn btn-small btn-info">
                                    👁️ Просмотр
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px; color: #e74c3c;">
                                ❌ Ошибка загрузки данных: <?php echo $e->getMessage(); ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div id="employeesInfo" style="margin-top: 10px; color: #666; font-size: 14px;">
                Всего сотрудников: <?php echo count($employees); ?>
            </div>
        </div>
        
        <!-- Вкладка 2: Договоры -->
        <div id="contracts" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">📝 Трудовые договоры</h2>
                <div>
                    <a href="add_contract.php" class="btn btn-success">
                        <span style="margin-right: 5px;">➕</span> Добавить договор
                    </a>
                    <a href="contracts_management.php" class="btn btn-info">
                        <span style="margin-right: 5px;">⚙️</span> Управление
                    </a>
                </div>
            </div>
            
            <div class="filters">
                <div class="filter-group">
                    <label class="filter-label">Статус договора:</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="">Все статусы</option>
                        <option value="Действующий">Действующий</option>
                        <option value="Расторгнут">Расторгнут</option>
                        <option value="Завершен">Завершен</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Отдел:</label>
                    <select class="filter-select" id="contractDeptFilter">
                        <option value="">Все отделы</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_code']; ?>">
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group" style="display: flex; flex-direction: row; align-items: flex-end; gap: 10px;">
                    <button class="btn" onclick="filterContracts()">🔍 Применить фильтр</button>
                    <button class="btn btn-warning" onclick="resetFilter('contracts')">🔄 Сбросить</button>
                </div>
            </div>
            
            <div class="table-container">
                <table id="contractsTable">
                    <thead>
                        <tr>
                            <th>№ договора</th>
                            <th>Сотрудник</th>
                            <th>Отдел</th>
                            <th>Должность</th>
                            <th>Оклад</th>
                            <th>Дата начала</th>
                            <th>Дата окончания</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $contracts = fetchAll($pdo, "
                                SELECT 
                                    ec.contract_number,
                                    ec.salary,
                                    ec.start_date,
                                    ec.end_date,
                                    ec.contract_status,
                                    e.last_name,
                                    e.first_name,
                                    e.middle_name,
                                    d.department_name,
                                    p.position_name,
                                    d.department_code
                                FROM employment_contract ec
                                JOIN employee e ON ec.employee_number = e.employee_number
                                LEFT JOIN department d ON ec.department_code = d.department_code
                                LEFT JOIN position p ON ec.position_code = p.position_code
                                ORDER BY ec.start_date DESC
                            ");
                            
                            foreach ($contracts as $contract):
                                // Определяем класс статуса
                                $status_class = '';
                                if ($contract['contract_status'] == 'Действующий') {
                                    $status_class = 'status-active';
                                } elseif ($contract['contract_status'] == 'Расторгнут') {
                                    $status_class = 'status-terminated';
                                } else {
                                    $status_class = 'status-completed';
                                }
                        ?>
                        <tr data-status="<?php echo htmlspecialchars($contract['contract_status']); ?>"
                            data-dept="<?php echo $contract['department_code'] ?? ''; ?>">
                            <td><?php echo htmlspecialchars($contract['contract_number']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($contract['last_name']); ?></strong><br>
                                <?php echo htmlspecialchars($contract['first_name'] . ' ' . ($contract['middle_name'] ?: '')); ?>
                            </td>
                            <td><?php echo htmlspecialchars($contract['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($contract['position_name']); ?></td>
                            <td class="salary"><?php echo number_format($contract['salary'], 0, ',', ' ') . ' ₽'; ?></td>
                            <td><?php echo htmlspecialchars($contract['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($contract['end_date'] ?: 'Бессрочный'); ?></td>
                            <td>
                                <span class="status <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($contract['contract_status']); ?>
                                </span>
                            </td>
                            <td style="white-space: nowrap;">
                                <a href="edit_contract.php?id=<?php echo htmlspecialchars($contract['contract_number']); ?>" 
                                   class="btn btn-small btn-warning" style="padding: 4px 8px; margin: 2px;">
                                    ✏️
                                </a>
                                <a href="?table=contract&id=<?php echo htmlspecialchars($contract['contract_number']); ?>" 
                                   class="btn btn-small btn-danger" style="padding: 4px 8px; margin: 2px;"
                                   onclick="return confirm('Удалить договор №<?php echo addslashes($contract['contract_number']); ?>?')">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px; color: #e74c3c;">
                                ❌ Ошибка загрузки договоров: <?php echo $e->getMessage(); ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div id="contractsInfo" style="margin-top: 10px; color: #666; font-size: 14px;">
                Всего договоров: <?php echo count($contracts); ?>
            </div>
        </div>
        
        <!-- Вкладка 3: Отделы -->
        <div id="departments" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">🏢 Отделы компании</h2>
                <div>
                    <a href="add_department.php" class="btn btn-success">
                        <span style="margin-right: 5px;">➕</span> Добавить отдел
                    </a>
                    <a href="departments_management.php" class="btn btn-info">
                        <span style="margin-right: 5px;">⚙️</span> Управление
                    </a>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Код отдела</th>
                            <th>Название отдела</th>
                            <th>Кол-во сотрудников</th>
                            <th>Руководитель</th>
                            <th>Контактная информация</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $dept_stats = fetchAll($pdo, "
                                SELECT 
                                    d.department_code,
                                    d.department_name,
                                    COUNT(e.employee_number) as employee_count
                                FROM department d
                                LEFT JOIN employment_contract ec ON d.department_code = ec.department_code 
                                    AND ec.contract_status = 'Действующий'
                                LEFT JOIN employee e ON ec.employee_number = e.employee_number
                                GROUP BY d.department_code, d.department_name
                                ORDER BY d.department_name
                            ");
                            
                            foreach ($dept_stats as $dept):
                                // Находим руководителя отдела (должность с кодом 10 - руководитель)
                                $manager = fetchOne($pdo, "
                                    SELECT 
                                        e.employee_number,
                                        e.last_name || ' ' || e.first_name || ' ' || COALESCE(e.middle_name, '') as manager_name,
                                        e.phone,
                                        e.email
                                    FROM employment_contract ec
                                    JOIN employee e ON ec.employee_number = e.employee_number
                                    WHERE ec.department_code = ? 
                                        AND ec.contract_status = 'Действующий'
                                        AND ec.position_code = 10
                                    LIMIT 1
                                ", [$dept['department_code']]);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dept['department_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($dept['employee_count']); ?></td>
                            <td>
                                <?php if ($manager): ?>
                                <strong><?php echo htmlspecialchars($manager['manager_name']); ?></strong><br>
                                <small>Таб. №<?php echo htmlspecialchars($manager['employee_number']); ?></small>
                                <?php else: ?>
                                <span style="color: #7f8c8d;">Не назначен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($manager): ?>
                                📞 <?php echo htmlspecialchars($manager['phone']); ?><br>
                                <?php if ($manager['email']): ?>
                                📧 <?php echo htmlspecialchars($manager['email']); ?>
                                <?php endif; ?>
                                <?php else: ?>
                                <span style="color: #7f8c8d;">—</span>
                                <?php endif; ?>
                            </td>
                                                        <td style="white-space: nowrap;">
                                <a href="edit_department.php?id=<?php echo htmlspecialchars($dept['department_code']); ?>" 
                                   class="btn btn-small btn-warning" style="padding: 4px 8px; margin: 2px;">
                                    ✏️
                                </a>
                                <a href="?table=department&id=<?php echo htmlspecialchars($dept['department_code']); ?>" 
                                   class="btn btn-small btn-danger" style="padding: 4px 8px; margin: 2px;"
                                   onclick="return confirm('Удалить отдел «<?php echo addslashes($dept['department_name']); ?>»?')">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #e74c3c;">
                                ❌ Ошибка загрузки отделов: <?php echo $e->getMessage(); ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Вкладка 4: Образование -->
        <div id="education" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">🎓 Образование сотрудников</h2>
                <div>
                    <a href="add_education.php" class="btn btn-success">
                        <span style="margin-right: 5px;">➕</span> Добавить образование
                    </a>
                    <a href="education_management.php" class="btn btn-info">
                        <span style="margin-right: 5px;">⚙️</span> Управление
                    </a>
                </div>
            </div>
            
            <div class="filters">
                <div class="filter-group">
                    <label class="filter-label">Вид образования:</label>
                    <select class="filter-select" id="educationTypeFilter">
                        <option value="">Все виды</option>
                        <?php foreach ($education_types as $type): ?>
                        <option value="<?php echo $type['education_type_code']; ?>">
                            <?php echo htmlspecialchars($type['education_type_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Поиск по специальности:</label>
                    <input type="text" class="filter-input" placeholder="Специальность..." id="specialtyFilter">
                </div>
                
                <div class="filter-group" style="display: flex; flex-direction: row; align-items: flex-end; gap: 10px;">
                    <button class="btn" onclick="filterEducation()">🔍 Применить фильтр</button>
                    <button class="btn btn-warning" onclick="resetFilter('education')">🔄 Сбросить</button>
                </div>
            </div>
            
            <div class="table-container">
                <table id="educationTable">
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
                        <?php
                        try {
                            $education_records = fetchAll($pdo, "
                                SELECT 
                                    ed.education_document_code,
                                    ed.educational_institution,
                                    ed.specialty,
                                    ed.graduation_year,
                                    et.education_type_name,
                                    e.last_name,
                                    e.first_name,
                                    e.middle_name,
                                    e.employee_number,
                                    d.department_name,
                                    et.education_type_code
                                FROM education ed
                                JOIN employee e ON ed.employee_number = e.employee_number
                                JOIN education_type et ON ed.education_type_code = et.education_type_code
                                LEFT JOIN employment_contract ec ON e.employee_number = ec.employee_number 
                                    AND ec.contract_status = 'Действующий'
                                LEFT JOIN department d ON ec.department_code = d.department_code
                                ORDER BY ed.graduation_year DESC
                            ");
                            
                            foreach ($education_records as $edu):
                        ?>
                        <tr data-type="<?php echo $edu['education_type_code']; ?>">
                            <td><small><?php echo htmlspecialchars($edu['education_document_code']); ?></small></td>
                            <td>
                                <strong><?php echo htmlspecialchars($edu['last_name']); ?></strong><br>
                                <?php echo htmlspecialchars($edu['first_name'] . ' ' . ($edu['middle_name'] ?: '')); ?>
                            </td>
                            <td>
                                <span class="education-badge">
                                    <?php echo htmlspecialchars($edu['education_type_name']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($edu['educational_institution']); ?></td>
                            <td><?php echo htmlspecialchars($edu['specialty']); ?></td>
                            <td><?php echo htmlspecialchars($edu['graduation_year']); ?></td>
                            <td><?php echo htmlspecialchars($edu['department_name'] ?: '—'); ?></td>
                            <td style="white-space: nowrap;">
                                    <a href="edit_education.php?id=<?php echo htmlspecialchars($edu['education_document_code']); ?>" 
                                       class="btn btn-small btn-warning" style="padding: 4px 8px; margin: 2px;">
                                        ✏️
                                    </a>
                                    <a href="?table=education&id=<?php echo htmlspecialchars($edu['education_document_code']); ?>" 
                                       class="btn btn-small btn-danger" style="padding: 4px 8px; margin: 2px;"
                                       onclick="return confirm('Удалить запись об образовании?')">
                                        🗑️
                                    </a>
                                </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #e74c3c;">
                                ❌ Ошибка загрузки образования: <?php echo $e->getMessage(); ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Вкладка 5: Воинский учет -->
        <div id="military" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">🎖️ Воинский учет</h2>
                <div>
                    <a href="add_military.php" class="btn btn-success">
                        <span style="margin-right: 5px;">➕</span> Добавить запись
                    </a>
                    <a href="military_management.php" class="btn btn-info">
                        <span style="margin-right: 5px;">⚙️</span> Управление
                    </a>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>№ военного билета</th>
                            <th>Сотрудник</th>
                            <th>Кем выдан</th>
                            <th>Воинская должность</th>
                            <th>Категория запаса</th>
                            <th>Группа учета</th>
                            <th>Состав</th>
                            <th>Отдел</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $military_records = fetchAll($pdo, "
                                SELECT 
                                    mr.military_id_number,
                                    mr.issued_by,
                                    mr.military_position,
                                    mr.reserve_category,
                                    mr.record_group,
                                    mr.military_composition,
                                    e.last_name,
                                    e.first_name,
                                    e.middle_name,
                                    e.employee_number,
                                    d.department_name
                                FROM military_record mr
                                JOIN employee e ON mr.employee_number = e.employee_number
                                LEFT JOIN employment_contract ec ON e.employee_number = ec.employee_number 
                                    AND ec.contract_status = 'Действующий'
                                LEFT JOIN department d ON ec.department_code = d.department_code
                                ORDER BY e.last_name, e.first_name
                            ");
                            
                            foreach ($military_records as $record):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($record['military_id_number']); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['last_name']); ?></strong><br>
                                <?php echo htmlspecialchars($record['first_name'] . ' ' . ($record['middle_name'] ?: '')); ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['issued_by']); ?></td>
                            <td><?php echo htmlspecialchars($record['military_position']); ?></td>
                            <td>
                                <span class="military-badge">
                                    Кат. <?php echo htmlspecialchars($record['reserve_category']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="military-badge">
                                    Гр. <?php echo htmlspecialchars($record['record_group']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($record['military_composition']); ?></td>
                            <td><?php echo htmlspecialchars($record['department_name'] ?: '—'); ?></td>
                            <td style="white-space: nowrap;">
                                <a href="edit_military.php?id=<?php echo htmlspecialchars($record['military_id_number']); ?>" 
                                   class="btn btn-small btn-warning" style="padding: 4px 8px; margin: 2px;">
                                    ✏️
                                </a>
                                <a href="?table=military&id=<?php echo htmlspecialchars($record['military_id_number']); ?>" 
                                   class="btn btn-small btn-danger" style="padding: 4px 8px; margin: 2px;"
                                   onclick="return confirm('Удалить запись воинского учета?')">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px; color: #e74c3c;">
                                ❌ Ошибка загрузки воинского учета: <?php echo $e->getMessage(); ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Вкладка 6: Награды -->
        <div id="awards" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">🏆 Награды и поощрения</h2>
                <div>
                    <a href="add_award.php" class="btn btn-success">
                        <span style="margin-right: 5px;">➕</span> Добавить награду
                    </a>
                    <a href="awards_management.php" class="btn btn-info">
                        <span style="margin-right: 5px;">⚙️</span> Управление
                    </a>
                </div>
            </div>
            
            <div class="filters">
                <div class="filter-group">
                    <label class="filter-label">Поиск по названию:</label>
                    <input type="text" class="filter-input" placeholder="Название награды..." id="awardNameFilter">
                </div>
                
                <div class="filter-group" style="display: flex; flex-direction: row; align-items: flex-end; gap: 10px;">
                    <button class="btn" onclick="filterAwards()">🔍 Применить фильтр</button>
                    <button class="btn btn-warning" onclick="resetFilter('awards')">🔄 Сбросить</button>
                </div>
            </div>
            
            <div class="table-container">
                <table id="awardsTable">
                    <thead>
                        <tr>
                            <th>Код награды</th>
                            <th>Сотрудник</th>
                            <th>Название награды</th>
                            <th>Дата награждения</th>
                            <th>Отдел</th>
                            <th>Должность</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $awards = fetchAll($pdo, "
                                SELECT 
                                    a.award_code,
                                    a.award_name,
                                    a.award_date,
                                    e.last_name,
                                    e.first_name,
                                    e.middle_name,
                                    e.employee_number,
                                    d.department_name,
                                    p.position_name
                                FROM award a
                                JOIN employee e ON a.employee_number = e.employee_number
                                LEFT JOIN employment_contract ec ON e.employee_number = ec.employee_number 
                                    AND ec.contract_status = 'Действующий'
                                LEFT JOIN department d ON ec.department_code = d.department_code
                                LEFT JOIN position p ON ec.position_code = p.position_code
                                ORDER BY a.award_date DESC
                            ");
                            
                            foreach ($awards as $award):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($award['award_code']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($award['last_name']); ?></strong><br>
                                <?php echo htmlspecialchars($award['first_name'] . ' ' . ($award['middle_name'] ?: '')); ?>
                            </td>
                            <td>
                                <span class="award-badge">
                                    🏆 <?php echo htmlspecialchars($award['award_name']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($award['award_date']); ?></td>
                            <td><?php echo htmlspecialchars($award['department_name'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($award['position_name'] ?: '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } catch (Exception $e) { ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #e74c3c;">
                                ❌ Ошибка загрузки наград: <?php echo $e->getMessage(); ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>            
        </div>
        <!-- Вкладка 7: Справочник наград -->            
            <div id="awards-reference" class="tab-content">
                <h2>📖 Справочник типов наград</h2>                
                <?php
                try {
                    // Простая проверка - пытаемся получить данные напрямую
                    $award_types = fetchAll($pdo, "
                        SELECT 
                            award_type_code,
                            award_type_name,
                            description
                        FROM award_types 
                        ORDER BY award_type_code
                    ");
                    
                    if (empty($award_types)) {
                        echo '<div style="text-align: center; padding: 40px; color: #6c757d;">';
                        echo '<h3>🏆 Справочник типов наград пуст</h3>';
                        echo '<p>Таблица существует, но в ней нет данных.</p>';
                        echo '<p>Заполните таблицу данными:</p>';
                        echo '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: left;">';
                        echo '<strong>SQL для заполнения:</strong><br>';
                        echo '<code>INSERT INTO award_types (award_type_code, award_type_name, description) VALUES<br>';
                        echo "(1, 'Лучший руководитель', 'Награда лучшему руководителю отдела'),<br>";
                        echo "(2, 'За результаты', 'Награда за достижение высоких результатов'),<br>";
                        echo "(3, 'Сотрудник месяца', 'Ежемесячная награда лучшему сотруднику');</code>";
                        echo '</div>';
                        echo '<a href="awards_reference.php" class="btn btn-success" style="margin-top: 10px;">➕ Управление справочником</a>';
                        echo '</div>';
                    } else {
                        // Получаем статистику использования
                        $usage_stats = fetchAll($pdo, "
                            SELECT 
                                at.award_type_code,
                                COUNT(a.award_code) as award_count
                            FROM award_types at
                            LEFT JOIN award a ON at.award_type_code = a.award_type_code
                            GROUP BY at.award_type_code
                        ");
                        
                        // Создаем массив для быстрого поиска статистики
                        $usage_map = [];
                        foreach ($usage_stats as $stat) {
                            $usage_map[$stat['award_type_code']] = $stat['award_count'];
                        }
                        
                        echo '<div class="stats-grid" style="margin-bottom: 20px;">';
                        echo '<div class="stat-card">';
                        echo '<div class="stat-number">' . count($award_types) . '</div>';
                        echo '<div class="stat-label">📋 Типов наград</div>';
                        echo '</div>';
                        
                        $total_usage = array_sum($usage_map);
                        echo '<div class="stat-card">';
                        echo '<div class="stat-number">' . $total_usage . '</div>';
                        echo '<div class="stat-label">🏆 Использований</div>';
                        echo '</div>';
                        
                        echo '<div class="stat-card">';
                        echo '<div class="stat-number">' . max(array_keys($usage_map)) . '</div>';
                        echo '<div class="stat-label">🔢 Макс. код</div>';
                        echo '</div>';
                        echo '</div>';
                        
                        echo '<div style="text-align: center; margin-bottom: 20px;">';
                        echo '<a href="awards_reference.php" class="btn btn-success">🏆 Перейти к управлению</a>';
                        echo '</div>';
                        
                        echo '<div class="table-container">';
                        echo '<table>';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>Код</th>';
                        echo '<th>Название типа награды</th>';
                        echo '<th>Описание</th>';
                        echo '<th>Использовано раз</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        foreach ($award_types as $type) {
                            $count = $usage_map[$type['award_type_code']] ?? 0;
                            $usage_class = $count > 0 ? 'usage-high' : 'usage-low';
                            
                            echo '<tr>';
                            echo '<td>';
                            echo '<span class="code-badge">';
                            echo $type['award_type_code'];
                            echo '</span>';
                            echo '</td>';
                            echo '<td><strong>' . htmlspecialchars($type['award_type_name']) . '</strong></td>';
                            echo '<td>' . htmlspecialchars($type['description'] ?: '—') . '</td>';
                            echo '<td style="text-align: center;">';
                            echo '<span class="badge ' . $usage_class . '">';
                            echo $count;
                            echo '</span>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div style="text-align: center; padding: 30px; color: #e74c3c;">';
                    echo '<h3>❌ Ошибка загрузки справочника наград</h3>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p>Возможные причины:</p>';
                    echo '<ul style="text-align: left; max-width: 600px; margin: 15px auto;">';
                    echo '<li>1. Таблица award_types не создана</li>';
                    echo '<li>2. Не установлена схема hr_schema</li>';
                    echo '<li>3. Проблемы с правами доступа</li>';
                    echo '</ul>';
                    echo '<a href="create_awards_table.php" class="btn" style="margin-top: 10px;">🚀 Создать таблицу</a>';
                    echo '</div>';
                }
                
                ?>              
                
            </div>
        
        <footer>
            <p>Полная система кадрового учета &copy; 2024 | База данных: PostgreSQL | Схема: hr_schema</p>
            <p>Подключено таблиц: 8 | Всего записей в базе: 
                <?php 
                $total = 0;
                foreach ($stats as $stat) {
                    $total += $stat['count'] ?? 0;
                }
                echo $total;
                ?>
            </p>
            <p>Последнее обновление: <?php echo date('d.m.Y H:i:s'); ?></p>
        </footer>
    </div>

    <script>
        // Данные для фильтрации
        const departments = <?php echo json_encode($departments_js); ?>;
        const educationTypes = <?php echo json_encode($education_types_js); ?>;
        
        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            initTabs();
            initFilters();
            
            // Загружаем сохраненную вкладку
            const savedTab = localStorage.getItem('activeTab');
            if (savedTab) {
                showTab(savedTab);
            }
        });
        
        // Управление вкладками
        function initTabs() {
            const tabButtons = document.querySelectorAll('.nav-tab');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    showTab(tabId);
                });
            });
        }
        
        function showTab(tabId) {
            // Скрываем все вкладки
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Убираем активный класс у всех кнопок
            document.querySelectorAll('.nav-tab').forEach(button => {
                button.classList.remove('active');
            });
            
            // Показываем выбранную вкладку
            const tab = document.getElementById(tabId);
            if (tab) {
                tab.classList.add('active');
            }
            
            // Делаем кнопку активной
            const activeButton = document.querySelector(`.nav-tab[data-tab="${tabId}"]`);
            if (activeButton) {
                activeButton.classList.add('active');
            }
            
            // Сохраняем в localStorage
            localStorage.setItem('activeTab', tabId);
        }
        
        // Инициализация фильтров
        function initFilters() {
            // Добавляем обработчики на Enter для полей ввода
            document.getElementById('nameFilter')?.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') filterEmployees();
            });
            
            document.getElementById('specialtyFilter')?.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') filterEducation();
            });
            
            document.getElementById('awardNameFilter')?.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') filterAwards();
            });
        }
        
        // Фильтрация сотрудников
        function filterEmployees() {
            const deptFilter = document.getElementById('deptFilter')?.value || '';
            const nameFilter = document.getElementById('nameFilter')?.value.toLowerCase() || '';
            const rows = document.querySelectorAll('#employeesTable tbody tr');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const deptCode = row.getAttribute('data-dept') || '';
                const nameCell = row.cells[1].textContent.toLowerCase();
                
                const deptMatch = !deptFilter || deptCode == deptFilter;
                const nameMatch = !nameFilter || nameCell.includes(nameFilter);
                
                if (deptMatch && nameMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Обновляем информацию о количестве
            updateFilterInfo('employees', visibleCount);
        }
        
        // Фильтрация договоров
        function filterContracts() {
            const statusFilter = document.getElementById('statusFilter')?.value || '';
            const deptFilter = document.getElementById('contractDeptFilter')?.value || '';
            const rows = document.querySelectorAll('#contractsTable tbody tr');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status') || '';
                const deptCode = row.getAttribute('data-dept') || '';
                
                const statusMatch = !statusFilter || status === statusFilter;
                const deptMatch = !deptFilter || deptCode == deptFilter;
                
                if (statusMatch && deptMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Обновляем информацию о количестве
            updateFilterInfo('contracts', visibleCount);
        }
        
        // Фильтрация образования
        function filterEducation() {
            const typeFilter = document.getElementById('educationTypeFilter')?.value || '';
            const specialtyFilter = document.getElementById('specialtyFilter')?.value.toLowerCase() || '';
            const rows = document.querySelectorAll('#educationTable tbody tr');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const typeCode = row.getAttribute('data-type') || '';
                const specialtyCell = row.cells[4].textContent.toLowerCase();
                
                const typeMatch = !typeFilter || typeCode == typeFilter;
                const specialtyMatch = !specialtyFilter || specialtyCell.includes(specialtyFilter);
                
                if (typeMatch && specialtyMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Обновляем информацию о количестве
            updateFilterInfo('education', visibleCount);
        }
        
        // Фильтрация наград
        function filterAwards() {
            const nameFilter = document.getElementById('awardNameFilter')?.value.toLowerCase() || '';
            const rows = document.querySelectorAll('#awardsTable tbody tr');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const awardNameCell = row.cells[2].textContent.toLowerCase();
                
                if (!nameFilter || awardNameCell.includes(nameFilter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Обновляем информацию о количестве
            updateFilterInfo('awards', visibleCount);
        }
        
        // Обновление информации о количестве найденных записей
        function updateFilterInfo(tab, count) {
            const infoElement = document.getElementById(tab + 'Info');
            if (infoElement) {
                const totalRows = document.querySelectorAll(`#${tab}Table tbody tr`).length;
                if (count === totalRows) {
                    infoElement.textContent = `Всего записей: ${count}`;
                    infoElement.style.color = '#666';
                } else {
                    infoElement.textContent = `Найдено: ${count} из ${totalRows} записей`;
                    infoElement.style.color = '#3498db';
                    infoElement.style.fontWeight = 'bold';
                }
            }
        }
        
        // Сброс фильтров для конкретной вкладки
        function resetFilter(tab) {
            switch(tab) {
                case 'employees':
                    document.getElementById('deptFilter').value = '';
                    document.getElementById('nameFilter').value = '';
                    showAllRows('#employeesTable tbody tr');
                    updateFilterInfo('employees', document.querySelectorAll('#employeesTable tbody tr').length);
                    break;
                    
                case 'contracts':
                    document.getElementById('statusFilter').value = '';
                    document.getElementById('contractDeptFilter').value = '';
                    showAllRows('#contractsTable tbody tr');
                    updateFilterInfo('contracts', document.querySelectorAll('#contractsTable tbody tr').length);
                    break;
                    
                case 'education':
                    document.getElementById('educationTypeFilter').value = '';
                    document.getElementById('specialtyFilter').value = '';
                    showAllRows('#educationTable tbody tr');
                    updateFilterInfo('education', document.querySelectorAll('#educationTable tbody tr').length);
                    break;
                    
                case 'awards':
                    document.getElementById('awardNameFilter').value = '';
                    showAllRows('#awardsTable tbody tr');
                    updateFilterInfo('awards', document.querySelectorAll('#awardsTable tbody tr').length);
                    break;
            }
        }
        
        // Показать все строки в таблице
        function showAllRows(selector) {
            document.querySelectorAll(selector).forEach(row => {
                row.style.display = '';
            });
        }
        
        // Обновление страницы
        function refreshPage() {
            if (confirm('Обновить данные?')) {
                location.reload();
            }
        }
        
        // Автоматическое обновление каждые 10 минут
        setTimeout(() => {
            if (confirm('Прошло 10 минут. Обновить данные?')) {
                location.reload();
            }
        }, 600000);
    </script>
</body>
</html>

<?php
// Закрываем соединение с БД
closeDB($pdo);
?>