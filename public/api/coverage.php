<?php
require_once __DIR__ . '/../../app/Database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Database::connection();

    // System-wide GPS coverage
    $overall = $pdo->query("
        SELECT
            COUNT(*)     AS TotalPoints,
            MIN([Date])  AS FirstGPS,
            MAX([Date])  AS LastGPS
        FROM DistLocationLog000
    ")->fetch();

    // Per-distributor summary (includes those with 0)
    $per = $pdo->query("
        SELECT
            d.Code                          AS Code,
            d.Name                          AS Name,
            d.GUID                          AS GUID,
            ISNULL(COUNT(l.[Date]), 0)      AS GPSPoints,
            MIN(l.[Date])                   AS FirstGPS,
            MAX(l.[Date])                   AS LastGPS
        FROM Distributor000 d
        LEFT JOIN DistLocationLog000 l
               ON l.DistributorGUID = d.GUID
        GROUP BY d.Code, d.Name, d.GUID
        ORDER BY GPSPoints DESC
    ")->fetchAll();

    $withData = 0;
    foreach ($per as $p) {
        if ((int) $p['GPSPoints'] > 0) $withData++;
    }

    echo json_encode([
        'success'                => true,
        'total_points'           => (int) $overall['TotalPoints'],
        'first_gps'              => $overall['FirstGPS'],
        'last_gps'               => $overall['LastGPS'],
        'distributors_with_data' => $withData,
        'distributors_total'     => count($per),
        'per_distributor'        => $per,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}