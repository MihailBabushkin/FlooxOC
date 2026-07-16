<?php
// ============================================
// Файл: /var/www/html/api/activate.php
// Назначение: Проверка и активация кода
// ============================================

// Настройки подключения к БД (ИЗМЕНИ ПОД СЕБЯ!)
$host = 'localhost';
$dbname = 'activation_system';
$username = 'app_user';
$password = 'YOUR_PASSWORD_HERE';  // ← СМЕНИ ПАРОЛЬ!

// Заголовки для CORS и JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Обработка preflight запроса (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Принимаем только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не разрешён. Используйте POST.'
    ]);
    exit();
}

try {
    // Подключение к БД
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Получаем данные из запроса
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Проверяем, что код передан
    if (!isset($input['code']) || empty(trim($input['code']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Код активации не указан'
        ]);
        exit();
    }

    $code = trim($input['code']);
    $machineId = isset($input['machine_id']) ? trim($input['machine_id']) : 'unknown';

    // === ПРОВЕРЯЕМ КОД В БАЗЕ ===

    // Шаг 1: Проверяем, существует ли код
    $stmt = $pdo->prepare("SELECT * FROM activation_codes WHERE code = :code");
    $stmt->execute(['code' => $code]);
    $result = $stmt->fetch();

    if (!$result) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Код активации не найден!'
        ]);
        exit();
    }

    // Шаг 2: Проверяем, не использован ли код
    if ($result['is_used'] == 1) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Этот код уже был использован!',
            'used_at' => $result['used_at'],
            'used_by' => $result['used_by']
        ]);
        exit();
    }

    // Шаг 3: Активируем код (помечаем как использованный)
    $stmt = $pdo->prepare("UPDATE activation_codes SET 
        is_used = 1, 
        used_at = NOW(), 
        used_by = :machine_id 
        WHERE code = :code");

    $stmt->execute([
        'code' => $code,
        'machine_id' => $machineId
    ]);

    // Проверяем, что код успешно обновлён
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Активация успешна!',
            'code' => $code,
            'activated_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при активации кода. Попробуйте позже.'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сервера: ' . $e->getMessage()
    ]);
}
?>
