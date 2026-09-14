<?php
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php'; Auth::require();

header('Content-Type: application/json; charset=utf-8');

try {
    $distributor = $_GET['distributor'] ?? '';
    $from        = $_GET['from']        ?? '';
    $to          = $_GET['to']          ?? '';

    if (!$distributor || !$from || !$to) {
        throw new InvalidArgumentException('Missing distributor, from or to.');
    }

    $pdo = Database::connection();

    /*
     * Coordinate resolution (priority order):
     *   1. Stored customer GPS (DistCustUpdates000) — precise
     *   2. Nearest route point within ±15 min        — accurate
     *   3. NULL (visit shown in list only, no marker)
     *
     * The previous ±4-hour fallback was removed because:
     *   - Some visits have placeholder StartTime = 00:00:00
     *   - The 4-hour window then matched the first ping of the day
     *   - Result: markers appeared many km from the real customer
     *   - Better to show NO marker than a misleading one.
     */
    $stmt = $pdo->prepare("
        WITH latest_cust_gps AS (
            SELECT
                CustGuid,
                DistGuid,
                GPSX,
                GPSY,
                ROW_NUMBER() OVER (
                    PARTITION BY CustGuid, DistGuid
                    ORDER BY [Date] DESC
                ) AS rn
            FROM DistCustUpdates000
            WHERE GPSX IS NOT NULL AND GPSX <> 0
              AND GPSY IS NOT NULL AND GPSY <> 0
        )
        SELECT
            v.GUID          AS VisitGUID,
            v.TripGUID      AS TripGUID,
            v.CustomerGUID  AS CustomerGUID,
            v.StartTime     AS StartTime,
            v.FinishTime    AS FinishTime,
            v.State         AS State,
            c.CustomerName  AS CustomerName,

            COALESCE(g.GPSX, near15.Lon) AS GPSX,
            COALESCE(g.GPSY, near15.Lat) AS GPSY,

            CASE
                WHEN g.GPSX     IS NOT NULL THEN 'customer'
                WHEN near15.Lat IS NOT NULL THEN 'route_near'
                ELSE NULL
            END AS GPSSource

        FROM DistVi000 v
        INNER JOIN DistTr000 t
            ON t.GUID = v.TripGUID
        LEFT JOIN cu000 c
            ON c.GUID = v.CustomerGUID
        LEFT JOIN latest_cust_gps g
            ON g.CustGuid = v.CustomerGUID
           AND g.DistGuid = t.DistributorGUID
           AND g.rn = 1

        -- Only tier 2 remains: nearest route point within ±15 min
        OUTER APPLY (
            SELECT TOP 1 l.Lat, l.Lon
            FROM DistLocationLog000 l
            WHERE l.DistributorGUID = t.DistributorGUID
              AND ABS(DATEDIFF(second, l.[Date], v.StartTime)) <= 900
            ORDER BY ABS(DATEDIFF(second, l.[Date], v.StartTime)) ASC
        ) AS near15

        WHERE t.DistributorGUID = ?
          AND v.StartTime >= ?
          AND v.StartTime <  DATEADD(day, 1, ?)
        ORDER BY v.StartTime ASC
    ");
    $stmt->execute([$distributor, $from, $to]);
    $visits = $stmt->fetchAll();

    foreach ($visits as &$v) {
        $v['GPSX'] = $v['GPSX'] !== null ? (float) $v['GPSX'] : null;
        $v['GPSY'] = $v['GPSY'] !== null ? (float) $v['GPSY'] : null;
    }
    unset($v);

    echo json_encode([
        'success' => true,
        'count'   => count($visits),
        'visits'  => $visits,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}