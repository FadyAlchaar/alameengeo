<?php
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php'; Auth::require();

header('Content-Type: application/json; charset=utf-8');

try {
    $distributor = $_GET['distributor'] ?? '';
    $from        = $_GET['from']        ?? '';
    $to          = $_GET['to']          ?? '';
    $refresh     = isset($_GET['refresh']);

    if (!$distributor || !$from || !$to) {
        throw new InvalidArgumentException('Missing distributor, from or to.');
    }

    // ── Tunable thresholds ──────────────────────────────────────
    $stopRadiusM     = (float) ($_GET['stop_radius'] ?? 150);
    $stopMinMin      = (float) ($_GET['stop_min']    ?? 20);
    $idleMinMin      = (float) ($_GET['idle_min']    ?? 60);
    $farStartKm      = (float) ($_GET['far_start']   ?? 15);
    $workStartHour   = (int)   ($_GET['work_start']  ?? 8);
    $workEndHour     = (int)   ($_GET['work_end']    ?? 18);
    $nearCustomerM   = (float) ($_GET['near_cust']   ?? 300);

    // ── Cache ───────────────────────────────────────────────────
    $cacheDir  = __DIR__ . '/../cache';
    $cacheKey  = 'anomalies-' . preg_replace('/[^A-Za-z0-9]/', '', $distributor)
               . '-' . preg_replace('/[^0-9]/', '', $from)
               . '-' . preg_replace('/[^0-9]/', '', $to)
               . '-' . md5(implode('|', [$stopRadiusM, $stopMinMin, $idleMinMin, $farStartKm, $workStartHour, $workEndHour, $nearCustomerM]))
               . '.json';
    $cacheFile = $cacheDir . '/' . $cacheKey;
    $cacheTtl  = 900; // 15 min

    if (!$refresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        readfile($cacheFile);
        exit;
    }

    $pdo = Database::connection();

    // ═══════════════════════════════════════════════════════════
    //  Load GPS points
    // ═══════════════════════════════════════════════════════════
    $stmt = $pdo->prepare("
        SELECT [Date] AS t, Lat, Lon
        FROM DistLocationLog000
        WHERE DistributorGUID = ?
          AND [Date] >= ?
          AND [Date] <  DATEADD(day, 1, ?)
        ORDER BY [Date] ASC
    ");
    $stmt->execute([$distributor, $from, $to]);
    $rows = $stmt->fetchAll();

    $points = [];
    foreach ($rows as $r) {
        $lat = (float) $r['Lat'];
        $lon = (float) $r['Lon'];
        if (!is_finite($lat) || !is_finite($lon)) continue;
        if ($lat === 0.0 && $lon === 0.0) continue;
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) continue;
        $points[] = ['lat' => $lat, 'lon' => $lon, 't' => $r['t']];
    }

    // ═══════════════════════════════════════════════════════════
    //  Load visits (for overlap check)
    // ═══════════════════════════════════════════════════════════
    $stmt = $pdo->prepare("
        SELECT v.StartTime, v.FinishTime, c.CustomerName, t.Number AS TripNumber
        FROM DistVi000 v
        INNER JOIN DistTr000 t ON t.GUID = v.TripGUID
        LEFT  JOIN cu000     c ON c.GUID = v.CustomerGUID
        WHERE t.DistributorGUID = ?
          AND v.StartTime >= ?
          AND v.StartTime <  DATEADD(day, 1, ?)
        ORDER BY v.StartTime ASC
    ");
    $stmt->execute([$distributor, $from, $to]);
    $visits = [];
    foreach ($stmt->fetchAll() as $v) {
        $visits[] = [
            'start_ts' => strtotime($v['StartTime']),
            'end_ts'   => strtotime($v['FinishTime'] ?: $v['StartTime']),
            'name'     => $v['CustomerName'] ?: '—',
            'trip'     => $v['TripNumber']   ?: '—',
        ];
    }

    // ═══════════════════════════════════════════════════════════
    //  Load known customer locations (for "near customer" check)
    // ═══════════════════════════════════════════════════════════
    $stmt = $pdo->prepare("
        WITH latest_cust_gps AS (
            SELECT CustGuid, DistGuid, GPSX, GPSY,
                   ROW_NUMBER() OVER (PARTITION BY CustGuid, DistGuid ORDER BY [Date] DESC) AS rn
            FROM DistCustUpdates000
            WHERE GPSX IS NOT NULL AND GPSX <> 0 AND GPSY IS NOT NULL AND GPSY <> 0
        )
        SELECT DISTINCT
            c.CustomerName  AS name,
            COALESCE(g.GPSX, near.Lon) AS lon,
            COALESCE(g.GPSY, near.Lat) AS lat
        FROM DistVi000 v
        INNER JOIN DistTr000 t ON t.GUID = v.TripGUID
        LEFT  JOIN cu000 c ON c.GUID = v.CustomerGUID
        LEFT  JOIN latest_cust_gps g ON g.CustGuid = v.CustomerGUID AND g.DistGuid = t.DistributorGUID AND g.rn = 1
        OUTER APPLY (
            SELECT TOP 1 l.Lat, l.Lon
            FROM DistLocationLog000 l
            WHERE l.DistributorGUID = t.DistributorGUID
              AND ABS(DATEDIFF(SECOND, l.[Date], v.StartTime)) <= 900
            ORDER BY ABS(DATEDIFF(SECOND, l.[Date], v.StartTime)) ASC
        ) AS near
        WHERE t.DistributorGUID = ?
          AND near.Lat IS NOT NULL
    ");
    $stmt->execute([$distributor]);
    $customers = [];
    foreach ($stmt->fetchAll() as $c) {
        $customers[] = [
            'name' => $c['name'] ?: '—',
            'lat'  => (float) $c['lat'],
            'lon'  => (float) $c['lon'],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    //  Helper: haversine in meters
    // ═══════════════════════════════════════════════════════════
    $hav = function($lat1, $lon1, $lat2, $lon2) {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    };

    // ═══════════════════════════════════════════════════════════
    //  Rule 1 & 2 — Detect stops
    // ═══════════════════════════════════════════════════════════
    $stops = [];
    $n = count($points);
    $i = 0;
    while ($i < $n) {
        $clusterStart = $i;
        $cLat = $points[$i]['lat'];
        $cLon = $points[$i]['lon'];
        $j = $i + 1;
        $cnt = 1;
        while ($j < $n) {
            $d = $hav($cLat, $cLon, $points[$j]['lat'], $points[$j]['lon']);
            if ($d > $stopRadiusM) break;
            $cLat = ($cLat * $cnt + $points[$j]['lat']) / ($cnt + 1);
            $cLon = ($cLon * $cnt + $points[$j]['lon']) / ($cnt + 1);
            $cnt++;
            $j++;
        }
        if ($j > $i + 1) {
            $dur = (strtotime($points[$j-1]['t']) - strtotime($points[$clusterStart]['t'])) / 60;
            if ($dur >= $stopMinMin) {
                $stops[] = [
                    'lat'      => round($cLat, 7),
                    'lon'      => round($cLon, 7),
                    'start'    => $points[$clusterStart]['t'],
                    'end'      => $points[$j-1]['t'],
                    'dur_min'  => round($dur, 1),
                    'points'   => $cnt,
                ];
            }
        }
        $i = max($i + 1, $j);
    }

    // ── Classify stops ──────────────────────────────────────────
    $anomalies = [];

    foreach ($stops as $s) {
        $sStart = strtotime($s['start']);
        $sEnd   = strtotime($s['end']);
        $sHour  = (int) date('G', $sStart);

        // Visit overlap?
        $overlaps = null;
        foreach ($visits as $v) {
            if ($sStart < $v['end_ts'] + 300 && $sEnd > $v['start_ts'] - 300) {
                $overlaps = $v;
                break;
            }
        }
        if ($overlaps) continue; // known activity

        // Near any known customer?
        $nearName = null;
        $nearDist = PHP_FLOAT_MAX;
        foreach ($customers as $c) {
            $d = $hav($s['lat'], $s['lon'], $c['lat'], $c['lon']);
            if ($d < $nearDist) { $nearDist = $d; $nearName = $c['name']; }
        }
        $isNearCustomer = $nearDist <= $nearCustomerM;

        // Rule 1 — Unknown stop
        if (!$isNearCustomer && $s['dur_min'] >= $stopMinMin) {
            $anomalies[] = [
                'type'        => 'unknown_stop',
                'lat'         => $s['lat'],
                'lon'         => $s['lon'],
                'start'       => $s['start'],
                'end'         => $s['end'],
                'dur_min'     => $s['dur_min'],
                'nearest'     => $nearName,
                'nearest_m'   => round($nearDist),
            ];
        }

        // Rule 2 — Long idle during work hours
        if ($s['dur_min'] >= $idleMinMin && $sHour >= $workStartHour && $sHour < $workEndHour) {
            $anomalies[] = [
                'type'      => 'long_idle',
                'lat'       => $s['lat'],
                'lon'       => $s['lon'],
                'start'     => $s['start'],
                'end'       => $s['end'],
                'dur_min'   => $s['dur_min'],
                'nearest'   => $nearName,
                'nearest_m' => round($nearDist),
            ];
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  Rule 3 — Far starts
    // ═══════════════════════════════════════════════════════════
    if (count($points) > 0) {
        $sumLat = 0; $sumLon = 0;
        foreach ($points as $p) { $sumLat += $p['lat']; $sumLon += $p['lon']; }
        $cLat = $sumLat / count($points);
        $cLon = $sumLon / count($points);

        $byDay = [];
        foreach ($points as $p) {
            $day = substr($p['t'], 0, 10);
            if (!isset($byDay[$day])) $byDay[$day] = $p;
        }

        foreach ($byDay as $day => $p) {
            $distKm = $hav($cLat, $cLon, $p['lat'], $p['lon']) / 1000;
            if ($distKm >= $farStartKm) {
                $anomalies[] = [
                    'type'        => 'far_start',
                    'lat'         => $p['lat'],
                    'lon'         => $p['lon'],
                    'start'       => $p['t'],
                    'end'         => $p['t'],
                    'dur_min'     => 0,
                    'dist_km'     => round($distKm, 1),
                ];
            }
        }
    }

    // ── Sort by time
    usort($anomalies, function($a, $b) { return strtotime($a['start']) - strtotime($b['start']); });

    // ── Counts
    $counts = ['unknown_stop' => 0, 'long_idle' => 0, 'far_start' => 0];
    foreach ($anomalies as $a) $counts[$a['type']]++;

    $payload = json_encode([
        'success'   => true,
        'count'     => count($anomalies),
        'counts'    => $counts,
        'thresholds' => [
            'stop_radius_m' => $stopRadiusM,
            'stop_min_min'  => $stopMinMin,
            'idle_min_min'  => $idleMinMin,
            'far_start_km'  => $farStartKm,
            'work_hours'    => "$workStartHour-$workEndHour",
        ],
        'anomalies' => $anomalies,
    ], JSON_UNESCAPED_UNICODE);

    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
    @file_put_contents($cacheFile, $payload);
    echo $payload;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}