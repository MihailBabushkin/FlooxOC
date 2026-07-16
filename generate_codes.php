<?php
// ============================================
// Файл: /var/www/html/api/generate_codes.php
// Назначение: Генерация новых кодов активации
// ============================================

// Настройки подключения к БД (ИЗМЕНИ ПОД СЕБЯ!)
$host = 'localhost';
$dbname = 'activation_system';
$username = 'app_user';
$password = 'YOUR_PASSWORD_HERE';  // ← СМЕНИ ПАРОЛЬ!

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

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

    $input = json_decode(file_get_contents('php://input'), true);
    $count = isset($input['count']) ? (int)$input['count'] : 1;
    $prefix = isset($input['prefix']) ? trim($input['prefix']) : 'KEY';

    if ($count < 1 || $count > 100) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Количество должно быть от 1 до 100'
        ]);
        exit();
    }

    $codes = [];
    $inserted = 0;

    for ($i = 0; $i < $count; $i++) {
        // Генерируем код: PREFIX-XXXX-XXXX-XXXX
        $code = $prefix . '-' . 
                strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)) . '-' .
                strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)) . '-' .
                strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));

        try {
            $stmt = $pdo->prepare("INSERT INTO activation_codes (code) VALUES (:code)");
            $stmt->execute(['code' => $code]);
            $codes[] = $code;
            $inserted++;
        } catch (PDOException $e) {
            // Если код уже существует, пропускаем
            continue;
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Сгенерировано {$inserted} кодов",
        'count' => $inserted,
        'codes' => $codes
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
