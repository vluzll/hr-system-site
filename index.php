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
        
        <!-- Кнопка добавления сотрудника -->
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
            <button class="nav-tab active" onclick="showTab('employees')">👥 Сотрудники</button>
            <button class="nav-tab" onclick="showTab('contracts')">📝 Договоры</button>
            <button class="nav-tab" onclick="showTab('departments')">🏢 Отделы</button>
            <button class="nav-tab" onclick="showTab('education')">🎓 Образование</button>
            <button class="nav-tab" onclick="showTab('military')">🎖️ Воинский учет</button>
            <button class="nav-tab" onclick="showTab('awards')">🏆 Награды</button>
            <button class="nav-tab" onclick="showTab('awards-reference')">📖 Справочник наград</button>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">📖 Справочник типов наград</h2>
                <div>
                    <a href="awards_reference.php" class="btn btn-success">
                        <span style="margin-right: 5px;">⚙️</span> Управление справочником
                    </a>
                    <a href="create_awards_table.php" class="btn btn-info">
                        <span style="margin-right: 5px;">🛠️</span> Создать таблицу
                    </a>
                </div>
            </div>
            
            <div id="awards-reference-content" style="padding: 20px; text-align: center;">
                <p>Загрузка справочника наград...</p>
            </div>
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

    // ============================================
// ГЛАВНЫЙ СКРИПТ ДЛЯ УПРАВЛЕНИЯ ВКЛАДКАМИ
// ВЕРСИЯ С СОХРАНЕНИЕМ СОСТОЯНИЯ И ОТЛАДКОЙ
// ============================================

// ---------- ОТЛАДОЧНЫЙ КОД (можно потом удалить) ----------
console.log('🔧 [ОТЛАДКА] Скрипт вкладок загружен.');
console.log('🔧 [ОТЛАДКА] Функция showTab существует?', typeof showTab);
// ---------------------------------------------------------

// ---------- ФУНКЦИИ ДЛЯ СОХРАНЕНИЯ СОСТОЯНИЯ ----------
function saveActiveTab(tabId) {
    localStorage.setItem('activeTab', tabId);
    console.log('💾 [ОТЛАДКА] Сохранили активную вкладку:', tabId);
}

function loadActiveTab() {
    const savedTab = localStorage.getItem('activeTab');
    const defaultTab = 'employees';
    
    if (savedTab && document.getElementById(savedTab)) {
        console.log('📂 [ОТЛАДКА] Загружаем сохраненную вкладку:', savedTab);
        return savedTab;
    }
    console.log('📂 [ОТЛАДКА] Сохраненной вкладки нет, показываем:', defaultTab);
    return defaultTab;
}

// ---------- ОСНОВНАЯ ФУНКЦИЯ ПЕРЕКЛЮЧЕНИЯ ----------
function showTab(tabId) {
    console.log('🔄 [ОТЛАДКА] Пытаемся показать вкладку:', tabId);
    
    // 1. СОХРАНЯЕМ ВЫБОР
    saveActiveTab(tabId);
    
    // 2. Находим все элементы
    const allTabs = document.querySelectorAll('.tab-content');
    const allButtons = document.querySelectorAll('.nav-tab');
    const targetTab = document.getElementById(tabId);
    
    // 3. Проверяем, существует ли целевая вкладка
    if (!targetTab) {
        console.error('❌ [ОШИБКА] Вкладка с ID "' + tabId + '" не найдена!');
        return; // Прерываем выполнение, если вкладки нет
    }
    
    console.log('✅ [ОТЛАДКА] Целевая вкладка найдена, скрываем остальные...');
    
    // 4. Скрываем ВСЕ вкладки и деактивируем кнопки (силовым методом)
    allTabs.forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('active');
    });
    
    allButtons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    // 5. ПОКАЗЫВАЕМ нужную вкладку и активируем кнопку
    targetTab.style.display = 'block';
    targetTab.classList.add('active');
    
    // Ищем и активируем соответствующую кнопку
    const activeButton = document.querySelector(`.nav-tab[onclick*="${tabId}"]`);
    if (activeButton) {
        activeButton.classList.add('active');
        console.log('✅ [ОТЛАДКА] Активировали кнопку для вкладки:', tabId);
    } else {
        console.warn('⚠️ [ОТЛАДКА] Не найдена кнопка для вкладки:', tabId);
    }
    
    console.log('✅ [ОТЛАДКА] Вкладка успешно показана:', tabId);
}

// ---------- ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ СТРАНИЦЫ ----------
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [ОТЛАДКА] Страница загружена, инициализируем вкладки...');
    console.log('🔧 [ОТЛАДКА] Кнопок найдено:', document.querySelectorAll('.nav-tab').length);
    console.log('🔧 [ОТЛАДКА] Вкладок найдено:', document.querySelectorAll('.tab-content').length);
    
    // 1. Показываем вкладку (сохраненную или первую)
    const tabToShow = loadActiveTab();
    showTab(tabToShow);
    
    // 2. Убедимся, что все кнопки имеют правильный обработчик
    const allButtons = document.querySelectorAll('.nav-tab');
    allButtons.forEach(button => {
        // Проверяем, есть ли уже onclick
        if (!button.onclick) {
            // Если нет — назначаем, извлекая ID из атрибута data-tab или текста
            const tabId = button.getAttribute('data-tab') || 
                         (button.textContent.includes('Договоры') ? 'contracts' :
                          button.textContent.includes('Отделы') ? 'departments' :
                          button.textContent.includes('Образование') ? 'education' :
                          button.textContent.includes('Воинский') ? 'military' :
                          button.textContent.includes('Награды') ? 'awards' :
                          button.textContent.includes('Справочник') ? 'awards-reference' : 'employees');
            
            button.setAttribute('onclick', `showTab('${tabId}')`);
            console.log('🔗 [ОТЛАДКА] Назначили обработчик кнопке:', button.textContent.trim());
        }
    });
    
    console.log('✅ [ОТЛАДКА] Инициализация завершена.');
});

// ---------- ДОПОЛНИТЕЛЬНО: Быстрая проверка клика ----------
// Вешаем простой обработчик на первую кнопку для проверки
document.querySelector('.nav-tab')?.addEventListener('click', function() {
    console.log('👆 [ОТЛАДКА] Прямой клик зарегистрирован на:', this.textContent.trim());
});
</body>
</html>

<?php
// Закрываем соединение с БД
closeDB($pdo);
?>

