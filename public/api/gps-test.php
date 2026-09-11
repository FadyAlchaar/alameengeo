<?php

require_once __DIR__ . '/../../app/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $pdo = Database::connection();

    $distributor = '45196F08-E66C-47D4-9E44-EDDE2EA5E5D4';

    /*
     * 1. GPS points for September
     */
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS GPSPoints,
            MIN(Date) AS FirstGPS,
            MAX(Date) AS LastGPS
        FROM DistLocationLog000
        WHERE DistributorGUID = ?
          AND Date >= '2026-09-01'
          AND Date <  '2026-10-01'
    ");

    $stmt->execute([$distributor]);

    $septemberGps = $stmt->fetch();


    /*
     * 2. Recent GPS points regardless of date
     */
    $stmt = $pdo->prepare("
        SELECT TOP 20
            Date,
            Lat,
            Lon
        FROM DistLocationLog000
        WHERE DistributorGUID = ?
        ORDER BY Date DESC
    ");

    $stmt->execute([$distributor]);

    $recentGps = $stmt->fetchAll();


    /*
     * 3. Visits for September
     */
    $stmt = $pdo->prepare("
        SELECT TOP 50
            v.GUID AS VisitGUID,
            v.TripGUID,
            v.CustomerGUID,
            c.CustomerName,
            v.StartTime,
            v.FinishTime,
            v.State
        FROM DistVi000 v

        INNER JOIN DistTr000 t
            ON t.GUID = v.TripGUID

        LEFT JOIN cu000 c
            ON c.GUID = v.CustomerGUID

        WHERE t.DistributorGUID = ?
          AND v.StartTime >= '2026-09-01'
          AND v.StartTime <  '2026-10-01'

        ORDER BY v.StartTime DESC
    ");

    $stmt->execute([$distributor]);

    $septemberVisits = $stmt->fetchAll();


    /*
     * 4. Customer GPS from distributor-specific address table
     */
    $stmt = $pdo->prepare("
        SELECT TOP 100
            dca.CustomerGUID,
            dca.DistributorGUID,
            dca.AddressGUID,
            dca.GPSX,
            dca.GPSY
        FROM DistDeviceCustAddress000 dca

        INNER JOIN DistVi000 v
            ON v.CustomerGUID = dca.CustomerGUID

        INNER JOIN DistTr000 t
            ON t.GUID = v.TripGUID

        WHERE t.DistributorGUID = ?
          AND v.StartTime >= '2026-09-01'
          AND v.StartTime <  '2026-10-01'

        ORDER BY v.StartTime DESC
    ");

    $stmt->execute([$distributor]);

    $customerGps = $stmt->fetchAll();


    echo json_encode([
        'success' => true,

        'distributor' => $distributor,

        'september_gps' => $septemberGps,

        'recent_gps' => $recentGps,

        'september_visits' => $septemberVisits,

        'customer_gps' => $customerGps

    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}