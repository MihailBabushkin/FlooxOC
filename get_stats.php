<?php
// ============================================
// Файл: /var/www/html/api/get_stats.php
// Назначение: Получение статистики по кодам
// ============================================

// Настройки подключения к БД (ИЗМЕНИ ПОД СЕБЯ!)
$host = 'localhost';
$dbname = 'activation_system';
$username = 'app_user';
$password = 'YOUR_PASSWORD_HERE';  // ← СМЕНИ ПАРОЛЬ!

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не разрешён. Используйте GET.'
    ]);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Всего кодов
    $total = $pdo->query("SELECT COUNT(*) FROM activation_codes")->fetchColumn();
    
    // Использовано
    $used = $pdo->query("SELECT COUNT(*) FROM activation_codes WHERE is_used = 1")->fetchColumn();
    
    // Свободно
    $free = $total - $used;

    // Последние 10 использованных
    $recent = $pdo->query("SELECT * FROM activation_codes WHERE is_used = 1 ORDER BY used_at DESC LIMIT 10")->fetchAll();

    // Все коды (для админа)
    $all = $pdo->query("SELECT * FROM activation_codes ORDER BY created_at DESC LIMIT 50")->fetchAll();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'statistics' => [
            'total' => (int)$total,
            'used' => (int)$used,
            'free' => (int)$free
        ],
        'recent' => $recent,
        'all_codes' => $all
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
