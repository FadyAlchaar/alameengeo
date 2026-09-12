<?php
require_once __DIR__ . '/../../app/Database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $distributor = $_GET['distributor'] ?? '';
    $refresh     = isset($_GET['refresh']);

    // ── Cache setup ──────────────────────────────────────────────
    $cacheDir  = __DIR__ . '/../cache';
    $cacheFile = $cacheDir . '/customers-map'
               . ($distributor ? '-' . preg_replace('/[^A-Za-z0-9]/', '', $distributor) : '')
               . '.json';
    $cacheTtl  = 3600;   // 1 hour

    if (!$refresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        readfile($cacheFile);
        exit;
    }

    $pdo = Database::connection();

    // ── Build the query ──────────────────────────────────────────
    //  Strategy: for each customer, look at their MOST RECENT visit,
    //  then find the GPS ping within ±15 min of that visit's StartTime
    //  → that is our best estimate of the customer's location.
    $sql = "
        SELECT
            c.GUID                                   AS CustomerGUID,
            c.Number                                 AS Number,
            c.CustomerName                           AS CustomerName,
            c.Phone1                                 AS Phone1,
            c.Mobile                                 AS Mobile,
            v.StartTime                              AS LastVisitTime,
            t.DistributorGUID                        AS DistributorGUID,
            d.Name                                   AS DistributorName,
            near.Lat                                 AS Lat,
            near.Lon                                 AS Lon,
            ABS(DATEDIFF(SECOND, near.[Date], v.StartTime)) AS TimeDiff,
            (SELECT COUNT(*) FROM DistVi000 WHERE CustomerGUID = c.GUID) AS TotalVisits
        FROM cu000 c
        INNER JOIN DistVi000 v
            ON v.GUID = (
                SELECT TOP 1 v2.GUID
                FROM DistVi000 v2
                WHERE v2.CustomerGUID = c.GUID
                ORDER BY v2.StartTime DESC
            )
        INNER JOIN DistTr000 t ON t.GUID = v.TripGUID
        LEFT  JOIN Distributor000 d ON d.GUID = t.DistributorGUID
        CROSS APPLY (
            SELECT TOP 1 l.Lat, l.Lon, l.[Date]
            FROM DistLocationLog000 l
            WHERE l.DistributorGUID = t.DistributorGUID
              AND ABS(DATEDIFF(SECOND, l.[Date], v.StartTime)) <= 900
            ORDER BY ABS(DATEDIFF(SECOND, l.[Date], v.StartTime)) ASC
        ) AS near
        WHERE 1=1
    ";

    $params = [];
    if ($distributor) {
        $sql .= " AND t.DistributorGUID = ?";
        $params[] = $distributor;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Sanitize numeric values
    foreach ($rows as &$r) {
        $r['Lat']         = (float) $r['Lat'];
        $r['Lon']         = (float) $r['Lon'];
        $r['TimeDiff']    = (int)   $r['TimeDiff'];
        $r['TotalVisits'] = (int)   $r['TotalVisits'];
    }
    unset($r);

    $payload = json_encode([
        'success'    => true,
        'count'      => count($rows),
        'cached_at'  => date('c'),
        'customers'  => $rows,
    ], JSON_UNESCAPED_UNICODE);

    // Write cache
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    @file_put_contents($cacheFile, $payload);

    echo $payload;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}