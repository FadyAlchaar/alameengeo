<?php
require_once __DIR__ . '/../app/Database.php';

$id = $_GET['id'] ?? '';
if (!$id || !preg_match('/^[0-9A-Fa-f\-]{36}$/', $id)) {
    http_response_code(400);
    exit('Invalid customer id');
}

$pdo = Database::connection();

function hasColumn(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = "$table.$column";
    if (array_key_exists($key, $cache)) return $cache[$key];
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    $cache[$key] = ((int) $stmt->fetchColumn()) > 0;
    return $cache[$key];
}

$wanted = [
    'Number', 'CustomerName', 'Phone1', 'Phone2', 'FAX', 'Notes',
    'EMail', 'Mobile', 'Debit', 'Credit', 'MaxDebit', 'Warn',
];

$selects = [];
foreach ($wanted as $f) {
    $selects[] = hasColumn($pdo, 'cu000', $f) ? "c.[$f] AS [$f]" : "NULL AS [$f]";
}

$stmt = $pdo->prepare("
    SELECT " . implode(', ', $selects) . "
    FROM cu000 c
    WHERE c.GUID = ?
");
$stmt->execute([$id]);
$cust = $stmt->fetch() ?: null;

// ── Barcode flags ────────────────────────────────────────────
$barcodeStart = null;
$barcodeEnd   = null;

if ($cust) {
    foreach (['cu000', 'DistDeviceCu000', 'DistCustUpdates000'] as $tbl) {
        if (hasColumn($pdo, $tbl, 'DistCustForceVisitStartWithBarcode') &&
            hasColumn($pdo, $tbl, 'DistCustForceVisitEndWithBarcode')) {

            $linkCol = null;
            foreach (['GUID', 'CustomerGUID', 'CustomerID', 'CustGuid'] as $c) {
                if (hasColumn($pdo, $tbl, $c)) { $linkCol = $c; break; }
            }
            if (!$linkCol) break;

            try {
                $stmt = $pdo->prepare("
                    SELECT TOP 1
                        [DistCustForceVisitStartWithBarcode] AS StartBc,
                        [DistCustForceVisitEndWithBarcode]   AS EndBc
                    FROM [$tbl]
                    WHERE [$linkCol] = ?
                ");
                $stmt->execute([$id]);
                $bc = $stmt->fetch();
                if ($bc) {
                    $barcodeStart = $bc['StartBc'];
                    $barcodeEnd   = $bc['EndBc'];
                }
            } catch (Throwable $e) { /* silent */ }
            break;
        }
    }
}

// ── Visit history ────────────────────────────────────────────
$visits = [];
if ($cust) {
    try {
        $stmt = $pdo->prepare("
            SELECT TOP 50
                v.GUID        AS VisitGUID,
                v.StartTime   AS StartTime,
                v.FinishTime  AS FinishTime,
                v.State       AS State,
                t.Number      AS TripNumber,
                t.[Date]      AS TripDate,
                d.Name        AS DistributorName
            FROM DistVi000 v
            INNER JOIN DistTr000 t ON t.GUID = v.TripGUID
            LEFT  JOIN Distributor000 d ON d.GUID = t.DistributorGUID
            WHERE v.CustomerGUID = ?
            ORDER BY v.StartTime DESC
        ");
        $stmt->execute([$id]);
        $visits = $stmt->fetchAll();
    } catch (Throwable $e) {
        $visits = [];
    }
}

$debit   = (float) ($cust['Debit']  ?? 0);
$credit  = (float) ($cust['Credit'] ?? 0);
$balance = $debit - $credit;

function h($v)      { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function money($v)  { return number_format((float) $v, 2, '.', ','); }
function isTrue($v) { return $v === true || $v === 1 || $v === '1' || $v === 'true'; }

function fmtDate(?string $dt): string {
    if (!$dt) return '';
    $ts = strtotime($dt);
    return $ts ? date('Y-m-d', $ts) : '';
}
function fmtTime(?string $dt): string {
    if (!$dt) return '';
    $ts = strtotime($dt);
    return $ts ? date('H:i', $ts) : '';
}
function visitDuration(?string $start, ?string $finish): string {
    if (!$start || !$finish) return '—';
    $s = strtotime($start);
    $f = strtotime($finish);
    if (!$s || !$f || $f < $s) return '—';
    $sec = $f - $s;
    if ($sec < 60)   return $sec . 's';
    if ($sec < 3600) return floor($sec / 60) . 'm';
    $h = floor($sec / 3600);
    $m = floor(($sec % 3600) / 60);
    return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customer — Alameen Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:          #f5f7fa;
    --bg-card:     #ffffff;
    --bg-soft:     #f8fafc;
    --bg-softer:   #f1f5f9;
    --border:      #e2e8f0;
    --border-soft: #eef2f7;

    --text:        #1e293b;
    --text-mid:    #475569;
    --text-dim:    #64748b;
    --text-faint:  #94a3b8;

    --accent:      #3b82f6;
    --accent-soft: #eff6ff;
    --accent-ink:  #1d4ed8;

    --danger:      #dc2626;
    --danger-soft: #fef2f2;
    --success:     #059669;
    --success-soft:#ecfdf5;
    --warning:     #d97706;
    --warning-soft:#fffbeb;
    --purple:      #7c3aed;
    --purple-soft: #f5f3ff;

    --shadow-sm:   0 1px 2px rgba(15,23,42,.04);
    --radius:      12px;
  }

  * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

  html, body {
    margin: 0;
    background: var(--bg);
    color: var(--text);
    font-family: 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    font-size: 14px;
    line-height: 1.55;
  }

  header.topbar {
    position: sticky; top: 0; z-index: 100;
    background: #ffffff;
    border-bottom: 1px solid var(--border);
    padding: 12px 18px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: var(--shadow-sm);
  }
  .back-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 38px; height: 38px; border-radius: 10px;
    background: var(--bg-soft); border: 1px solid var(--border);
    color: var(--text); font-size: 17px; text-decoration: none; flex-shrink: 0;
    transition: background .15s, border-color .15s;
  }
  .back-btn:hover { background: var(--accent-soft); border-color: var(--accent); color: var(--accent-ink); }

  .title-group { flex: 1; min-width: 0; line-height: 1.3; }
  .cust-name {
    font-size: 17px; font-weight: 700; color: var(--text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .cust-number { font-size: 12px; color: var(--text-dim); font-variant-numeric: tabular-nums; }
  .cust-number b { color: var(--text-mid); font-weight: 600; }

  .lang-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 54px; height: 38px; padding: 0 12px; border-radius: 10px;
    background: var(--bg-soft); border: 1px solid var(--border);
    color: var(--text); font-family: inherit; font-size: 13px; font-weight: 700;
    cursor: pointer; flex-shrink: 0; transition: background .15s, border-color .15s;
  }
  .lang-btn:hover { background: var(--accent-soft); border-color: var(--accent); color: var(--accent-ink); }

  main {
    max-width: 1000px; margin: 0 auto;
    padding: 20px 18px 60px;
    display: flex; flex-direction: column; gap: 16px;
  }

  .card {
    background: var(--bg-card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 16px 18px;
    box-shadow: var(--shadow-sm);
  }
  .card-title {
    font-size: 11.5px; text-transform: uppercase; letter-spacing: .8px;
    color: var(--text-dim); font-weight: 700; margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
  }
  .card-title::before {
    content: ''; width: 3px; height: 14px;
    background: var(--accent); border-radius: 2px;
  }

  .financial-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(135px, 1fr));
    gap: 10px;
  }
  .fin-item {
    background: var(--bg-soft); border: 1px solid var(--border-soft);
    border-radius: 10px; padding: 12px 14px;
  }
  .fin-item.balance-primary {
    background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
    border-color: #bfdbfe;
  }
  .fin-item .label {
    font-size: 10.5px; text-transform: uppercase; letter-spacing: .7px;
    color: var(--text-dim); font-weight: 700; margin-bottom: 6px;
  }
  .fin-item .value {
    font-size: 16px; font-weight: 700; color: var(--text);
    font-variant-numeric: tabular-nums; letter-spacing: .2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .value.pos  { color: var(--danger);  }
  .value.neg  { color: var(--success); }
  .value.zero { color: var(--text-dim); }
  .value.muted{ color: var(--text-faint); font-weight: 500; }

  .info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .info-row {
    background: var(--bg-soft); border: 1px solid var(--border-soft);
    border-radius: 10px; padding: 10px 14px;
    display: flex; flex-direction: column; gap: 4px; min-width: 0;
  }
  .info-row.full { grid-column: 1 / -1; }
  .info-row .label {
    font-size: 10.5px; text-transform: uppercase; letter-spacing: .7px;
    color: var(--text-dim); font-weight: 700;
  }
  .info-row .value {
    font-size: 14px; color: var(--text); font-weight: 500; word-break: break-word;
  }
  .info-row .value.empty { color: var(--text-faint); font-weight: 400; font-style: italic; }
  .info-row .value a {
    color: var(--accent-ink); text-decoration: none;
    direction: ltr; unicode-bidi: embed; display: inline-block; font-weight: 600;
  }
  .info-row .value a:hover { text-decoration: underline; }

  .settings-list { display: flex; flex-direction: column; gap: 8px; }
  .setting {
    background: var(--bg-soft); border: 1px solid var(--border-soft);
    border-radius: 10px; padding: 12px 14px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
  }
  .setting .label { font-size: 13.5px; color: var(--text); font-weight: 500; flex: 1; min-width: 0; }
  .badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11.5px; font-weight: 700; padding: 4px 10px;
    border-radius: 20px; letter-spacing: .3px; flex-shrink: 0;
  }
  .badge.on  { background: var(--success-soft); color: var(--success); border: 1px solid #a7f3d0; }
  .badge.off { background: var(--bg-softer);    color: var(--text-dim); border: 1px solid var(--border); }
  .badge.na  { background: var(--bg-softer);    color: var(--text-faint); border: 1px solid var(--border-soft); }
  .badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

  .notes-body {
    background: var(--bg-soft); border: 1px solid var(--border-soft);
    border-radius: 10px; padding: 12px 14px;
    font-size: 13.5px; line-height: 1.7; color: var(--text-mid);
    white-space: pre-wrap; word-break: break-word;
    max-height: 280px; overflow-y: auto;
  }
  .notes-body.empty { color: var(--text-faint); font-style: italic; }

  /* Visit history */
  .visit-summary {
    display: flex; align-items: center; gap: 10px;
    font-size: 12px; color: var(--text-dim);
    margin-inline-start: auto; font-weight: 500;
    letter-spacing: 0; text-transform: none;
  }
  .visit-summary .pill {
    background: var(--accent-soft); color: var(--accent-ink);
    padding: 2px 9px; border-radius: 20px; font-weight: 700; font-size: 11.5px;
  }

  .visit-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .visit-table thead th {
    background: var(--bg-softer); color: var(--text-dim);
    font-size: 10.5px; text-transform: uppercase; letter-spacing: .7px;
    font-weight: 700; padding: 9px 12px; text-align: start;
    border-bottom: 1px solid var(--border); white-space: nowrap;
  }
  .visit-table tbody tr {
    border-bottom: 1px solid var(--border-soft); transition: background .12s;
  }
  .visit-table tbody tr:last-child { border-bottom: none; }
  .visit-table tbody tr:hover { background: var(--bg-soft); }
  .visit-table td { padding: 10px 12px; color: var(--text-mid); vertical-align: middle; }
  .visit-table td.trip { font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums; }
  .visit-table td.date, .visit-table td.time { white-space: nowrap; font-variant-numeric: tabular-nums; }
  .visit-table td.time { color: var(--text-dim); }
  .visit-table td.duration { white-space: nowrap; font-weight: 600; color: var(--text); }
  .visit-table td.distributor {
    max-width: 200px; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap;
  }

  .state-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700; padding: 3px 9px;
    border-radius: 20px; letter-spacing: .3px;
  }
  .state-0  { background: var(--bg-softer);    color: var(--text-dim);   border: 1px solid var(--border); }
  .state-2  { background: var(--success-soft); color: var(--success);    border: 1px solid #a7f3d0; }
  .state-na { background: var(--bg-softer);    color: var(--text-faint); border: 1px solid var(--border-soft); }
  .state-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

  .visit-empty {
    text-align: center; padding: 28px 12px;
    color: var(--text-faint); font-style: italic;
    background: var(--bg-soft); border: 1px dashed var(--border);
    border-radius: 10px;
  }

  .not-found { text-align: center; padding: 80px 20px; color: var(--text-dim); }
  .not-found .icon { font-size: 52px; margin-bottom: 14px; }
  .not-found h2 { color: var(--text); font-size: 18px; margin: 0 0 8px; }

  html[dir="rtl"] .back-btn { transform: scaleX(-1); }

  @media (max-width: 720px) {
    main { padding: 14px 10px 40px; gap: 12px; }
    .card { padding: 14px 14px; }
    .financial-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .fin-item.balance-primary { grid-column: 1 / -1; }
    .info-grid { grid-template-columns: 1fr; }
    .visit-table { font-size: 12.5px; }
    .visit-table thead th { padding: 8px 10px; font-size: 10px; }
    .visit-table td { padding: 9px 10px; }
    .visit-table td.distributor { max-width: 130px; }
  }

  @media (max-width: 560px) {
    header.topbar { padding: 10px 12px; gap: 8px; }
    .back-btn { width: 34px; height: 34px; font-size: 15px; }
    .lang-btn { height: 34px; font-size: 12px; min-width: 46px; padding: 0 9px; }
    .cust-name { font-size: 15px; }

    .visit-table, .visit-table thead, .visit-table tbody,
    .visit-table tr, .visit-table td { display: block; }
    .visit-table thead { display: none; }
    .visit-table tbody tr {
      background: var(--bg-soft); border: 1px solid var(--border-soft);
      border-radius: 10px; padding: 10px 12px; margin-bottom: 8px;
    }
    .visit-table tbody tr:last-child { margin-bottom: 0; }
    .visit-table td {
      padding: 4px 0; display: flex; justify-content: space-between;
      align-items: center; gap: 12px; border: none;
    }
    .visit-table td::before {
      content: attr(data-label);
      font-size: 10.5px; text-transform: uppercase; letter-spacing: .7px;
      color: var(--text-dim); font-weight: 700; flex-shrink: 0;
    }
    .visit-table td.distributor { max-width: none; white-space: normal; text-align: end; }
  }

  @media (max-width: 380px) {
    .financial-grid { grid-template-columns: 1fr; }
    .fin-item.balance-primary { grid-column: auto; }
  }

  @media print {
    html, body { background: #fff; }
    header.topbar { position: static; box-shadow: none; }
    .back-btn, .lang-btn { display: none; }
    .card { box-shadow: none; }
  }
</style>
</head>
<body>

<header class="topbar">
  <a class="back-btn" href="index.php" title="Back">←</a>
  <div class="title-group">
    <div class="cust-name"><?= h($cust['CustomerName'] ?? '—') ?></div>
    <div class="cust-number">
      <span data-i18n="numberLabel">Number</span>:
      <b><?= h($cust['Number'] ?? '—') ?></b>
    </div>
  </div>
  <button class="lang-btn" id="langBtn" title="Language">
    <span id="langLabel">ع</span>
  </button>
</header>

<main>
<?php if (!$cust): ?>
  <div class="not-found">
    <div class="icon">🔍</div>
    <h2 data-i18n="notFound">Customer not found</h2>
  </div>
<?php else: ?>

  <section class="card">
    <div class="card-title" data-i18n="financial">Financial Summary</div>
    <div class="financial-grid">
      <div class="fin-item">
        <div class="label" data-i18n="debit">Debit</div>
        <div class="value"><?= money($debit) ?></div>
      </div>
      <div class="fin-item">
        <div class="label" data-i18n="credit">Credit</div>
        <div class="value"><?= money($credit) ?></div>
      </div>
      <div class="fin-item balance-primary">
        <div class="label" data-i18n="balance">Balance</div>
        <div class="value <?= $balance > 0 ? 'pos' : ($balance < 0 ? 'neg' : 'zero') ?>">
          <?= money($balance) ?>
        </div>
      </div>
      <div class="fin-item">
        <div class="label" data-i18n="maxDebit">Max Debit</div>
        <div class="value <?= ($cust['MaxDebit'] ?? null) === null ? 'muted' : '' ?>">
          <?= ($cust['MaxDebit'] ?? null) === null ? '—' : money($cust['MaxDebit']) ?>
        </div>
      </div>
      <div class="fin-item">
        <div class="label" data-i18n="warn">Warn Threshold</div>
        <div class="value <?= ($cust['Warn'] ?? null) === null ? 'muted' : '' ?>">
          <?= ($cust['Warn'] ?? null) === null ? '—' : money($cust['Warn']) ?>
        </div>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-title" data-i18n="contact">Contact Information</div>
    <div class="info-grid">
      <?php
        $contactRows = [
          ['key' => 'Phone1', 'label' => 'phone1'],
          ['key' => 'Phone2', 'label' => 'phone2'],
          ['key' => 'Mobile', 'label' => 'mobile'],
          ['key' => 'FAX',    'label' => 'fax'],
        ];
        foreach ($contactRows as $row):
          $val = $cust[$row['key']] ?? null;
          $hasVal = $val !== null && trim((string)$val) !== '';
      ?>
        <div class="info-row">
          <div class="label" data-i18n="<?= $row['label'] ?>"><?= h($row['label']) ?></div>
          <div class="value <?= $hasVal ? '' : 'empty' ?>">
            <?php if ($hasVal): ?>
              <a href="tel:<?= h($val) ?>"><?= h($val) ?></a>
            <?php else: ?>
              <span data-i18n="empty">—</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <?php
        $email = $cust['EMail'] ?? null;
        $hasEmail = $email !== null && trim((string)$email) !== '';
      ?>
      <div class="info-row full">
        <div class="label" data-i18n="email">Email</div>
        <div class="value <?= $hasEmail ? '' : 'empty' ?>">
          <?php if ($hasEmail): ?>
            <a href="mailto:<?= h($email) ?>"><?= h($email) ?></a>
          <?php else: ?>
            <span data-i18n="empty">—</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-title" data-i18n="settings">Distribution Settings</div>
    <div class="settings-list">
      <div class="setting">
        <div class="label" data-i18n="forceStart">Force visit start with barcode</div>
        <?php if ($barcodeStart === null): ?>
          <span class="badge na"><span class="dot"></span><span data-i18n="na">N/A</span></span>
        <?php elseif (isTrue($barcodeStart)): ?>
          <span class="badge on"><span class="dot"></span><span data-i18n="enabled">Enabled</span></span>
        <?php else: ?>
          <span class="badge off"><span class="dot"></span><span data-i18n="disabled">Disabled</span></span>
        <?php endif; ?>
      </div>
      <div class="setting">
        <div class="label" data-i18n="forceEnd">Force visit end with barcode</div>
        <?php if ($barcodeEnd === null): ?>
          <span class="badge na"><span class="dot"></span><span data-i18n="na">N/A</span></span>
        <?php elseif (isTrue($barcodeEnd)): ?>
          <span class="badge on"><span class="dot"></span><span data-i18n="enabled">Enabled</span></span>
        <?php else: ?>
          <span class="badge off"><span class="dot"></span><span data-i18n="disabled">Disabled</span></span>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-title">
      <span data-i18n="visitHistory">Visit History</span>
      <div class="visit-summary">
        <span class="pill"><?= count($visits) ?></span>
        <span data-i18n="totalVisits">total visits</span>
      </div>
    </div>

    <?php if (count($visits) === 0): ?>
      <div class="visit-empty" data-i18n="noVisits">No visits recorded for this customer.</div>
    <?php else: ?>
      <div style="overflow-x:auto">
        <table class="visit-table">
          <thead>
            <tr>
              <th data-i18n="trip">Trip</th>
              <th data-i18n="date">Date</th>
              <th data-i18n="start">Start</th>
              <th data-i18n="finish">Finish</th>
              <th data-i18n="duration">Duration</th>
              <th data-i18n="distributorCol">Distributor</th>
              <th data-i18n="state">State</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($visits as $v): ?>
              <?php
                $stateRaw = $v['State'];
                $stateCls = 'state-na';
                $stateTxt = $stateRaw === null ? '—' : (string) $stateRaw;
                if ($stateRaw === 0 || $stateRaw === '0') $stateCls = 'state-0';
                if ($stateRaw === 2 || $stateRaw === '2') $stateCls = 'state-2';
              ?>
              <tr>
                <td class="trip" data-label="Trip"><?= h($v['TripNumber'] ?? '—') ?></td>
                <td class="date" data-label="Date"><?= h(fmtDate($v['StartTime'])) ?></td>
                <td class="time" data-label="Start"><?= h(fmtTime($v['StartTime'])) ?></td>
                <td class="time" data-label="Finish"><?= h(fmtTime($v['FinishTime'])) ?></td>
                <td class="duration" data-label="Duration"><?= h(visitDuration($v['StartTime'], $v['FinishTime'])) ?></td>
                <td class="distributor" data-label="Distributor" title="<?= h($v['DistributorName'] ?? '') ?>">
                  <?= h($v['DistributorName'] ?? '—') ?>
                </td>
                <td data-label="State">
                  <span class="state-badge <?= $stateCls ?>">
                    <span class="dot"></span>
                    <span><?= h($stateTxt) ?></span>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <?php
    $notes = $cust['Notes'] ?? '';
    $hasNotes = trim((string)$notes) !== '';
  ?>
  <section class="card">
    <div class="card-title" data-i18n="notes">Notes</div>
    <div class="notes-body <?= $hasNotes ? '' : 'empty' ?>">
      <?= $hasNotes ? h($notes) : '<span data-i18n="noNotes">No notes</span>' ?>
    </div>
  </section>

<?php endif; ?>
</main>

<script>
const I18N = {
  en: {
    numberLabel: 'Number', financial: 'Financial Summary', contact: 'Contact Information',
    settings: 'Distribution Settings', notes: 'Notes', visitHistory: 'Visit History',
    totalVisits: 'total visits', debit: 'Debit', credit: 'Credit', balance: 'Balance',
    maxDebit: 'Max Debit', warn: 'Warn Threshold', phone1: 'Phone 1', phone2: 'Phone 2',
    mobile: 'Mobile', fax: 'FAX', email: 'Email',
    forceStart: 'Force visit start with barcode', forceEnd: 'Force visit end with barcode',
    enabled: 'Enabled', disabled: 'Disabled', na: 'N/A', notFound: 'Customer not found',
    empty: '—', noNotes: 'No notes', noVisits: 'No visits recorded for this customer.',
    trip: 'Trip', date: 'Date', start: 'Start', finish: 'Finish',
    duration: 'Duration', distributorCol: 'Distributor', state: 'State',
  },
  ar: {
    numberLabel: 'الرقم', financial: 'الملخص المالي', contact: 'معلومات الاتصال',
    settings: 'إعدادات التوزيع', notes: 'ملاحظات', visitHistory: 'سجل الزيارات',
    totalVisits: 'إجمالي الزيارات', debit: 'الرصيد المدين', credit: 'الرصيد الدائن',
    balance: 'الرصيد الحالي', maxDebit: 'أعلى رصيد مدين', warn: 'حد التنبيه',
    phone1: 'رقم هاتف 1', phone2: 'رقم هاتف 2', mobile: 'الهاتف المحمول',
    fax: 'رقم الفاكس', email: 'البريد الإلكتروني',
    forceStart: 'فرض بداية الزيارة باستخدام الباركود',
    forceEnd: 'فرض نهاية الزيارة باستخدام الباركود',
    enabled: 'مُفعّل', disabled: 'معطّل', na: 'غير متوفر', notFound: 'الزبون غير موجود',
    empty: '—', noNotes: 'لا توجد ملاحظات', noVisits: 'لا توجد زيارات مسجّلة لهذا الزبون.',
    trip: 'الرحلة', date: 'التاريخ', start: 'البداية', finish: 'النهاية',
    duration: 'المدة', distributorCol: 'الموزّع', state: 'الحالة',
  },
};

let LANG = localStorage.getItem('alameen_lang') || 'ar';
function t(k) { return (I18N[LANG] && I18N[LANG][k]) || I18N.en[k] || k; }

function applyLanguage() {
  document.documentElement.lang = LANG;
  document.documentElement.dir  = LANG === 'ar' ? 'rtl' : 'ltr';

  document.querySelectorAll('[data-i18n]').forEach(el => {
    el.textContent = t(el.getAttribute('data-i18n'));
  });

  const colKeys = ['trip', 'date', 'start', 'finish', 'duration', 'distributorCol', 'state'];
  document.querySelectorAll('.visit-table tbody tr').forEach(tr => {
    [...tr.children].forEach((td, i) => {
      if (colKeys[i]) td.setAttribute('data-label', t(colKeys[i]));
    });
  });

  document.getElementById('langLabel').textContent = LANG === 'ar' ? 'EN' : 'ع';
}

document.getElementById('langBtn').addEventListener('click', () => {
  LANG = LANG === 'ar' ? 'en' : 'ar';
  localStorage.setItem('alameen_lang', LANG);
  applyLanguage();
});

applyLanguage();
</script>
</body>
</html>