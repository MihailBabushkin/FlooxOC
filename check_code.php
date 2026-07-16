<?php
// ============================================
// Файл: /var/www/html/api/check_code.php
// Назначение: Проверка валидности кода (без активации)
// ============================================

// Настройки подключения к БД (ИЗМЕНИ ПОД СЕБЯ!)
$host = 'localhost';
$dbname = 'activation_system';
$username = 'app_user';
$password = 'YOUR_PASSWORD_HERE';  // ← СМЕНИ ПАРОЛЬ!

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не разрешён. Используйте POST.'
    ]);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['code']) || empty(trim($input['code']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Код активации не указан'
        ]);
        exit();
    }

    $code = trim($input['code']);

    // Проверяем код
    $stmt = $pdo->prepare("SELECT * FROM activation_codes WHERE code = :code");
    $stmt->execute(['code' => $code]);
    $result = $stmt->fetch();

    if (!$result) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Код не найден'
        ]);
        exit();
    }

    if ($result['is_used'] == 1) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Код уже использован',
            'used_at' => $result['used_at'],
            'used_by' => $result['used_by']
        ]);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Код действителен',
        'code' => $code
    ]);

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
