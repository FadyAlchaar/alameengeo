<?php
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php'; Auth::require();

header('Content-Type: application/json; charset=utf-8');

try {
    $distributor = $_GET['distributor'] ?? '';
    $from        = $_GET['from']        ?? '';
    $to          = $_GET['to']          ?? '';
    $cellSizeM   = (float) ($_GET['cell'] ?? 200);   // meters per cell
    $refresh     = isset($_GET['refresh']);

    if (!$distributor || !$from || !$to) {
        throw new InvalidArgumentException('Missing distributor, from or to.');
    }
    if ($cellSizeM < 50 || $cellSizeM > 2000) $cellSizeM = 200;

    // ── Cache ───────────────────────────────────────────────────
    $cacheDir = __DIR__ . '/../cache';
    $cacheKey = 'coverage-' . preg_replace('/[^A-Za-z0-9]/', '', $distributor)
              . '-' . preg_replace('/[^0-9]/', '', $from)
              . '-' . preg_replace('/[^0-9]/', '', $to)
              . '-' . (int) $cellSizeM . '.json';
    $cacheFile = $cacheDir . '/' . $cacheKey;
    $cacheTtl  = 3600;

    if (!$refresh && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        readfile($cacheFile);
        exit;
    }

    $pdo = Database::connection();

    $stmt = $pdo->prepare("
        SELECT Lat, Lon
        FROM DistLocationLog000
        WHERE DistributorGUID = ?
          AND [Date] >= ?
          AND [Date] <  DATEADD(day, 1, ?)
    ");
    $stmt->execute([$distributor, $from, $to]);
    $rows = $stmt->fetchAll();

    // ── Filter valid coordinates ────────────────────────────────
    $points = [];
    foreach ($rows as $r) {
        $lat = (float) $r['Lat'];
        $lon = (float) $r['Lon'];
        if (!is_finite($lat) || !is_finite($lon)) continue;
        if ($lat === 0.0 && $lon === 0.0) continue;
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) continue;
        $points[] = [$lat, $lon];
    }

    if (count($points) === 0) {
        $empty = json_encode([
            'success' => true,
            'cells'   => [],
            'max'     => 0,
            'count'   => 0,
            'points'  => 0,
        ], JSON_UNESCAPED_UNICODE);
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
        @file_put_contents($cacheFile, $empty);
        echo $empty;
        exit;
    }

    // ── Bounding box ────────────────────────────────────────────
    $minLat = $maxLat = $points[0][0];
    $minLon = $maxLon = $points[0][1];
    foreach ($points as [$lat, $lon]) {
        if ($lat < $minLat) $minLat = $lat;
        if ($lat > $maxLat) $maxLat = $lat;
        if ($lon < $minLon) $minLon = $lon;
        if ($lon > $maxLon) $maxLon = $lon;
    }

    // ── Grid step in degrees ────────────────────────────────────
    $latStep = $cellSizeM / 111320.0;
    $centerLat = ($minLat + $maxLat) / 2.0;
    $cosLat = max(0.1, cos(deg2rad($centerLat)));
    $lonStep = $cellSizeM / (111320.0 * $cosLat);

    // ── Bucket points into cells ────────────────────────────────
    $cells = [];
    foreach ($points as [$lat, $lon]) {
        $gy = (int) floor($lat / $latStep);
        $gx = (int) floor($lon / $lonStep);
        $key = $gy . ':' . $gx;
        if (!isset($cells[$key])) {
            $cells[$key] = ['count' => 0, 'gy' => $gy, 'gx' => $gx];
        }
        $cells[$key]['count']++;
    }

    // ── Build output: [lat, lon, count] with cell centers ───────
    $out = [];
    $max = 0;
    foreach ($cells as $info) {
        $cellLat = ($info['gy'] + 0.5) * $latStep;
        $cellLon = ($info['gx'] + 0.5) * $lonStep;
        if ($info['count'] > $max) $max = $info['count'];
        $out[] = [round($cellLat, 6), round($cellLon, 6), $info['count']];
    }

    $payload = json_encode([
        'success'   => true,
        'cells'     => $out,
        'max'       => $max,
        'count'     => count($out),
        'points'    => count($points),
        'latStep'   => $latStep,
        'lonStep'   => $lonStep,
        'cellSizeM' => $cellSizeM,
        'bounds'    => [[$minLat, $minLon], [$maxLat, $maxLon]],
    ], JSON_UNESCAPED_UNICODE);

    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
    @file_put_contents($cacheFile, $payload);
    echo $payload;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}