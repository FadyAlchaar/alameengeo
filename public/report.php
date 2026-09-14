<?php
require_once __DIR__ . '/../app/Auth.php'; Auth::require();

$distributor     = $_GET['d']    ?? '';
$distributorName = $_GET['name'] ?? '';
$from            = $_GET['f']    ?? '';
$to              = $_GET['t']    ?? '';
$lang            = $_GET['lang'] ?? 'ar';

if (!$distributor || !$from || !$to) {
    http_response_code(400);
    exit('Missing parameters: d, f, t');
}

$isAr = $lang === 'ar';
$t = function(string $en, string $ar) use ($isAr) { return $isAr ? $ar : $en; };
?>
<!DOCTYPE html>
<html lang="<?= $isAr ? 'ar' : 'en' ?>" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $t('Route Report', 'تقرير المسار') ?> — <?= htmlspecialchars($distributorName ?: 'Distributor') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  html, body {
    margin: 0;
    padding: 0;
    font-family: 'Cairo', system-ui, -apple-system, sans-serif;
    color: #1e293b;
    background: #f8fafc;
    font-size: 13px;
    line-height: 1.6;
  }

  .page {
    max-width: 900px;
    margin: 20px auto;
    background: #fff;
    padding: 40px 50px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    border-radius: 8px;
  }

  /* ─── Header ─── */
  .header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 16px;
    border-bottom: 3px solid #1e293b;
    margin-bottom: 24px;
    gap: 20px;
  }
  .header h1 {
    margin: 0 0 4px;
    font-size: 22px;
    color: #0f172a;
    font-weight: 700;
  }
  .header .subtitle {
    color: #64748b;
    font-size: 12px;
  }
  .header .meta {
    text-align: <?= $isAr ? 'left' : 'right' ?>;
    font-size: 12.5px;
    color: #475569;
    line-height: 1.8;
    white-space: nowrap;
  }
  .header .meta b { color: #0f172a; font-weight: 700; }

  /* ─── Stats ─── */
  .stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 26px;
  }
  .stat {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    background: #f8fafc;
  }
  .stat .label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 4px;
  }
  .stat .value {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    font-variant-numeric: tabular-nums;
  }

  /* ─── Sections ─── */
  h2 {
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #0f172a;
    margin: 28px 0 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 700;
  }

  /* ─── Tables ─── */
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
  }
  th {
    text-align: <?= $isAr ? 'right' : 'left' ?>;
    padding: 8px 10px;
    background: #f1f5f9;
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #475569;
    border-bottom: 1px solid #cbd5e1;
    font-weight: 700;
  }
  td {
    padding: 8px 10px;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
  }
  td.num {
    font-variant-numeric: tabular-nums;
    text-align: <?= $isAr ? 'left' : 'right' ?>;
  }
  tbody tr:nth-child(even) td { background: #fafbfc; }

  .badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    background: #dbeafe;
    color: #1d4ed8;
  }
  .badge.state-2 { background: #d1fae5; color: #047857; }
  .badge.state-0 { background: #f1f5f9; color: #64748b; }

  /* ─── Footer ─── */
  .footer {
    margin-top: 40px;
    padding-top: 14px;
    border-top: 1px solid #e2e8f0;
    font-size: 10.5px;
    color: #94a3b8;
    text-align: center;
  }

  /* ─── Toolbar (hidden on print) ─── */
  .toolbar {
    position: fixed;
    top: 20px;
    <?= $isAr ? 'left' : 'right' ?>: 20px;
    background: #0f172a;
    border-radius: 12px;
    padding: 10px;
    display: flex;
    gap: 8px;
    box-shadow: 0 6px 20px rgba(0,0,0,.25);
    z-index: 100;
  }
  .toolbar button {
    background: #3b82f6;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 16px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
  }
  .toolbar button:hover { background: #2563eb; }
  .toolbar button.ghost {
    background: transparent;
    border: 1px solid #334155;
  }
  .toolbar button.ghost:hover { background: #1e293b; border-color: #475569; }

  .loading {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
    font-style: italic;
  }
  .error-row {
    color: #dc2626;
    text-align: center;
    padding: 20px;
  }

  /* ─── Print ─── */
  @media print {
    html, body { background: #fff; }
    .page {
      max-width: none;
      margin: 0;
      padding: 12px;
      box-shadow: none;
      border-radius: 0;
    }
    .toolbar { display: none; }
    .header { margin-bottom: 14px; }
    h2 { font-size: 12px; margin: 16px 0 8px; }
    .stat .value { font-size: 15px; }
    th { padding: 5px 8px; font-size: 10px; }
    td { padding: 5px 8px; }
    tbody tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
  }

  @media (max-width: 700px) {
    .page { padding: 20px 16px; margin: 10px; }
    .stats { grid-template-columns: repeat(2, 1fr); }
    .header { flex-direction: column; }
    .header .meta { text-align: <?= $isAr ? 'right' : 'left' ?>; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()">🖨️ <?= $t('Print', 'طباعة') ?></button>
  <button class="ghost" onclick="window.close()">✕ <?= $t('Close', 'إغلاق') ?></button>
</div>

<div class="page">

  <div class="header">
    <div>
      <h1><?= $t('Route Report', 'تقرير المسار') ?></h1>
      <div class="subtitle"><?= $t('Alameen Distributor Tracker', ' للموزعين') ?></div>
    </div>
    <div class="meta">
      <div><b><?= $t('Distributor', 'الموزّع') ?>:</b> <?= htmlspecialchars($distributorName ?: '—') ?></div>
      <div><b><?= $t('Period', 'الفترة') ?>:</b> <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></div>
      <div><b><?= $t('Generated', 'تاريخ التصدير') ?>:</b> <span id="genTs">—</span></div>
    </div>
  </div>

  <div class="stats">
    <div class="stat"><div class="label"><?= $t('GPS Points', 'نقاط GPS') ?></div><div class="value" id="stPoints">—</div></div>
    <div class="stat"><div class="label"><?= $t('Distance', 'المسافة') ?></div><div class="value" id="stKm">—</div></div>
    <div class="stat"><div class="label"><?= $t('Days', 'الأيام') ?></div><div class="value" id="stDays">—</div></div>
    <div class="stat"><div class="label"><?= $t('Visits', 'الزيارات') ?></div><div class="value" id="stVisits">—</div></div>
  </div>

  <h2><?= $t('Per-Day Breakdown', 'التفصيل اليومي') ?></h2>
  <table>
    <thead>
      <tr>
        <th><?= $t('Date', 'التاريخ') ?></th>
        <th class="num"><?= $t('GPS Points', 'نقاط GPS') ?></th>
        <th class="num"><?= $t('Distance (km)', 'المسافة (كم)') ?></th>
        <th class="num"><?= $t('Visits', 'الزيارات') ?></th>
      </tr>
    </thead>
    <tbody id="daysBody">
      <tr><td colspan="4" class="loading"><?= $t('Loading…', 'جارٍ التحميل…') ?></td></tr>
    </tbody>
  </table>

  <h2><?= $t('Visit Log', 'سجل الزيارات') ?></h2>
  <table>
    <thead>
      <tr>
        <th><?= $t('Time', 'الوقت') ?></th>
        <th><?= $t('Customer', 'الزبون') ?></th>
        <th class="num"><?= $t('Trip', 'الرحلة') ?></th>
        <th><?= $t('State', 'الحالة') ?></th>
      </tr>
    </thead>
    <tbody id="visitsBody">
      <tr><td colspan="4" class="loading"><?= $t('Loading…', 'جارٍ التحميل…') ?></td></tr>
    </tbody>
  </table>

  <div class="footer">
    <?= $t('Generated by Alameen Distributor Tracker', 'تم إنشاؤه بواسطة  للموزعين') ?>
  </div>
</div>

<script>
const isAr = <?= $isAr ? 'true' : 'false' ?>;
const qs   = new URLSearchParams(window.location.search);

document.getElementById('genTs').textContent =
  new Date().toISOString().slice(0, 19).replace('T', ' ');

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  })[c]);
}

function stateClass(st) {
  if (st === 2 || st === '2') return 'state-2';
  if (st === 0 || st === '0') return 'state-0';
  return '';
}

(async function loadReport() {
  try {
    // Build query string for the existing APIs
    const apiQs = new URLSearchParams();
    apiQs.set('distributor', qs.get('d'));
    apiQs.set('from',        qs.get('f'));
    apiQs.set('to',          qs.get('t'));

    const [routeRes, visitsRes] = await Promise.all([
      fetch('api/route.php?'  + apiQs.toString()).then(r => r.json()),
      fetch('api/visits.php?' + apiQs.toString()).then(r => r.json()),
    ]);

    if (!routeRes.success) throw new Error(routeRes.error || 'Route API failed');
    if (!visitsRes.success) throw new Error(visitsRes.error || 'Visits API failed');

    // ── Stats ──
    document.getElementById('stPoints').textContent = (routeRes.count ?? 0).toLocaleString();
    document.getElementById('stKm').textContent     = (routeRes.distance_km ?? 0).toFixed(2) + ' km';
    document.getElementById('stDays').textContent   = routeRes.days ?? 0;
    document.getElementById('stVisits').textContent = (visitsRes.count ?? 0).toLocaleString();

    // ── Per-day breakdown ──
    const daysBody = document.getElementById('daysBody');
    const dayStats = routeRes.day_stats || [];
    const visitsPerDay = {};
    (visitsRes.visits || []).forEach(v => {
      const d = String(v.StartTime || '').slice(0, 10);
      if (d) visitsPerDay[d] = (visitsPerDay[d] || 0) + 1;
    });

    if (dayStats.length === 0) {
      daysBody.innerHTML = '<tr><td colspan="4" class="loading">—</td></tr>';
    } else {
      daysBody.innerHTML = dayStats.map(d => `
        <tr>
          <td>${escapeHtml(d.day)}</td>
          <td class="num">${Number(d.count || 0).toLocaleString()}</td>
          <td class="num">${Number(d.km || 0).toFixed(2)}</td>
          <td class="num">${visitsPerDay[d.day] || 0}</td>
        </tr>
      `).join('');
    }

    // ── Visit log ──
    const visitsBody = document.getElementById('visitsBody');
    const visits = visitsRes.visits || [];

    if (visits.length === 0) {
      visitsBody.innerHTML = '<tr><td colspan="4" class="loading">—</td></tr>';
    } else {
      visitsBody.innerHTML = visits.map(v => `
        <tr>
          <td>${escapeHtml((v.StartTime || '').slice(0, 16))}</td>
          <td>${escapeHtml(v.CustomerName || '—')}</td>
          <td class="num">${escapeHtml(v.TripNumber || '—')}</td>
          <td><span class="badge ${stateClass(v.State)}">${escapeHtml(v.State ?? '—')}</span></td>
        </tr>
      `).join('');
    }

  } catch (e) {
    console.error('[report] failed:', e);
    const msg = (isAr ? 'فشل التحميل: ' : 'Load failed: ') + e.message;
    document.getElementById('daysBody').innerHTML   = `<tr><td colspan="4" class="error-row">${escapeHtml(msg)}</td></tr>`;
    document.getElementById('visitsBody').innerHTML = `<tr><td colspan="4" class="error-row">${escapeHtml(msg)}</td></tr>`;
  }
})();
</script>
</body>
</html>