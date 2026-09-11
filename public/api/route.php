<?php
require_once __DIR__ . '/../../app/Database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $distributor = $_GET['distributor'] ?? '';
    $from        = $_GET['from']        ?? '';
    $to          = $_GET['to']          ?? '';
    $target      = isset($_GET['target']) ? max(100, (int) $_GET['target']) : 5000;

    if (!$distributor || !$from || !$to) {
        throw new InvalidArgumentException('Missing distributor, from or to.');
    }

    // ── Validate dates
    $dFrom = DateTime::createFromFormat('Y-m-d', $from);
    $dTo   = DateTime::createFromFormat('Y-m-d', $to);
    if (!$dFrom || !$dTo) {
        throw new InvalidArgumentException('Invalid date format. Use YYYY-MM-DD.');
    }
    $days = $dFrom->diff($dTo)->days + 1;
    if ($days > 366) {
        throw new InvalidArgumentException('Date range too large (max 366 days).');
    }

    $pdo = Database::connection();

    // ═══════════════════════════════════════════════════════════════
    //  QUERY 1 — Per-day stats + accurate distance (full dataset)
    //  Uses a single scan; LAG is partitioned per day so we never
    //  count overnight jumps between days.
    // ═══════════════════════════════════════════════════════════════
    $stmt = $pdo->prepare("
        WITH pts AS (
            SELECT
                CAST([Date] AS DATE) AS Day,
                Lat,
                Lon,
                LAG(Lat) OVER (PARTITION BY CAST([Date] AS DATE) ORDER BY [Date]) AS PrevLat,
                LAG(Lon) OVER (PARTITION BY CAST([Date] AS DATE) ORDER BY [Date]) AS PrevLon
            FROM DistLocationLog000
            WHERE DistributorGUID = ?
              AND [Date] >= ?
              AND [Date] <  DATEADD(day, 1, ?)
        )
        SELECT
            CONVERT(varchar(10), Day, 120) AS Day,
            COUNT(*)                       AS PointCount,
            SUM(
                CASE WHEN PrevLat IS NULL THEN 0
                ELSE 6371.0 * 2.0 * ASIN(SQRT(
                    POWER(SIN(RADIANS(Lat - PrevLat) / 2.0), 2) +
                    COS(RADIANS(PrevLat)) * COS(RADIANS(Lat)) *
                    POWER(SIN(RADIANS(Lon - PrevLon) / 2.0), 2)
                ))
                END
            ) AS Km
        FROM pts
        GROUP BY Day
        ORDER BY Day
    ");
    $stmt->execute([$distributor, $from, $to]);
    $dayRows = $stmt->fetchAll();

    $total    = 0;
    $totalKm  = 0.0;
    $dayStats = [];
    foreach ($dayRows as $r) {
        $cnt = (int)   $r['PointCount'];
        $km  = (float) $r['Km'];
        $total   += $cnt;
        $totalKm += $km;
        $dayStats[] = [
            'day'   => $r['Day'],
            'count' => $cnt,
            'km'    => round($km, 3),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  Compute decimation stride
    // ═══════════════════════════════════════════════════════════════
    $stride    = 1;
    $decimated = false;
    if ($total > $target) {
        $stride    = (int) ceil($total / $target);
        $decimated = true;
    }

    // ═══════════════════════════════════════════════════════════════
    //  QUERY 2 — Fetch points (full or decimated)
    //  Always keeps first + last of the range so start/end markers
    //  remain accurate.
    // ═══════════════════════════════════════════════════════════════
    if (!$decimated) {
        $stmt = $pdo->prepare("
            SELECT [Date] AS LogTime, Lat, Lon
            FROM DistLocationLog000
            WHERE DistributorGUID = ?
              AND [Date] >= ?
              AND [Date] <  DATEADD(day, 1, ?)
            ORDER BY [Date] ASC
        ");
        $stmt->execute([$distributor, $from, $to]);
    } else {
        $stmt = $pdo->prepare("
            SELECT LogTime, Lat, Lon
            FROM (
                SELECT
                    [Date] AS LogTime,
                    Lat,
                    Lon,
                    ROW_NUMBER() OVER (ORDER BY [Date] ASC) AS rn
                FROM DistLocationLog000
                WHERE DistributorGUID = ?
                  AND [Date] >= ?
                  AND [Date] <  DATEADD(day, 1, ?)
            ) t
            WHERE (rn - 1) % ? = 0
               OR rn = ?
            ORDER BY LogTime ASC
        ");
        $stmt->execute([$distributor, $from, $to, $stride, $total]);
    }
    $points = $stmt->fetchAll();

    foreach ($points as &$p) {
        $p['Lat'] = (float) $p['Lat'];
        $p['Lon'] = (float) $p['Lon'];
    }
    unset($p);

    // ═══════════════════════════════════════════════════════════════
    //  Response
    // ═══════════════════════════════════════════════════════════════
    echo json_encode([
        'success'     => true,
        'count'       => count($points),
        'total'       => $total,
        'stride'      => $stride,
        'decimated'   => $decimated,
        'days'        => count($dayStats),
        'distance_km' => round($totalKm, 2),
        'day_stats'   => $dayStats,
        'points'      => $points,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}