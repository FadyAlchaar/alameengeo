<?php
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php'; Auth::require();

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo  = Database::connection();
    $rows = $pdo->query("
        SELECT GUID, Number, Code, Name, LatinName
        FROM Distributor000
        ORDER BY Number
    ")->fetchAll();

    echo json_encode(['success' => true, 'distributors' => $rows],
        JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}