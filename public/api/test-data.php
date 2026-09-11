<?php

require_once __DIR__ . '/../../app/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $pdo = Database::connection();

    /*
     * ============================================================
     * 1. DISTRIBUTORS
     * ============================================================
     */

    $stmt = $pdo->query("
        SELECT TOP 50
            GUID,
            Number,
            Code,
            Name,
            LatinName,
            PrimSalesmanGUID
        FROM Distributor000
        ORDER BY Name
    ");

    $distributors = $stmt->fetchAll();


    /*
     * ============================================================
     * 2. GPS SUMMARY
     * ============================================================
     */

    $stmt = $pdo->query("
        SELECT
            DistributorGUID,
            COUNT(*) AS GPSPoints,
            MIN(Date) AS FirstGPS,
            MAX(Date) AS LastGPS
        FROM DistLocationLog000
        GROUP BY DistributorGUID
        ORDER BY MAX(Date) DESC
    ");

    $gpsSummary = $stmt->fetchAll();


    /*
     * ============================================================
     * 3. RECENT TRIPS
     * ============================================================
     */

    $stmt = $pdo->query("
        SELECT TOP 50
            GUID,
            DistributorGUID,
            Number,
            Date,
            VanGUID,
            VisitReq,
            State
        FROM DistTr000
        ORDER BY Date DESC
    ");

    $trips = $stmt->fetchAll();


    /*
     * ============================================================
     * 4. RECENT VISITS
     *
     * DistVi000 does NOT contain DistributorGUID.
     *
     * Distributor is obtained through:
     *
     * DistVi000.TripGUID
     *       ↓
     * DistTr000.GUID
     *       ↓
     * DistTr000.DistributorGUID
     * ============================================================
     */

    $stmt = $pdo->query("
        SELECT TOP 100
            v.GUID,
            v.TripGUID,
            t.DistributorGUID,
            v.CustomerGUID,
            v.Number,
            v.StartTime,
            v.FinishTime,
            v.State,
            c.CustomerName
        FROM DistVi000 v

        LEFT JOIN DistTr000 t
            ON t.GUID = v.TripGUID

        LEFT JOIN cu000 c
            ON c.GUID = v.CustomerGUID

        ORDER BY v.StartTime DESC
    ");

    $visits = $stmt->fetchAll();


    /*
     * ============================================================
     * RESULT
     * ============================================================
     */

    echo json_encode([
        'success' => true,

        'distributors' => $distributors,

        'gps_summary' => $gpsSummary,

        'trips' => $trips,

        'visits' => $visits,

    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}