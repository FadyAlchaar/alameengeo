<?php
$distributor     = $_GET['distributor'] ?? '';
$distributorName = $_GET['name']        ?? '';
$from            = $_GET['from']        ?? '';
$to              = $_GET['to']          ?? '';
$lang            = $_GET['lang']        ?? 'en';

if (!$distributor || !$from || !$to) {
    http_response_code(400);
    exit('Missing parameters');
}

$isAr = $lang === 'ar';
$t = function(string $en, string $ar) use ($isAr) { return $isAr ? $ar : $en; };
?>
<!DOCTYPE html>
<html lang="<?= $isAr ? 'ar' : 'en' ?>" dir="<?= $isAr ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<title><?= $t('Route Report', 'تقرير المسار') ?> — <?= htmlspecialchars($distributorName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 30px 40px;
    font-family: 'Cairo', system-ui, sans-serif;
    color: #1e293b;
    background: #fff;
    font-size: 13px;
    line-height: 1.6;
  }
  .header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 16px;
    border-bottom: 2px solid #1e293b;
    margin-bottom: 24px;
  }
  .header h1 {
    margin: 0 0 4px;
    font-size: 20px;
    color: #0f172a;
  }
  .header .subtitle {
    color: #64748b;
    font-size: 12px;
  }
  .header .meta {
    text-align: <?= $isAr ? 'left' : 'right' ?>;
    font-size: 12px;
    color: #475569;
    line-height: 1.8;
  }
  .header .meta b { color: #0f172a; }

  .stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 24px;
  }
  .stat {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
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

  h2 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #0f172a;
    margin: 26px 0 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
  }

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
  }
  td {
    padding: 8px 10px;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
  }
  td.num { font-variant-numeric: tabular-nums; text-align: <?= $isAr ? 'left' : 'right' ?>; }
  tr:nth-child(even) td { background: #fafbfc; }

  .badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    background: #dbeafe;
    color: #1d4ed8;
  }

  .footer {
    margin-top: 40px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    font-size: 11px;
    color: #94a3b8;
    text-align: center;
  }

  .toolbar {
    position: fixed;
    top: 20px;
    <?= $isAr ? 'left' : 'right' ?>: 20px;
    background: #0f172a;
    border-radius: 10px;
    padding: 10px;
    display: flex;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(0,0,0,.2);
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
  }
  .toolbar button:hover { background: #2563eb; }
  .toolbar button.ghost { background: transparent; border: 1px solid #334155; }
  .toolbar button.ghost:hover { background: #1e293b; }

  @media print {
    body { padding: 12px; font-size: 11px; }
    .toolbar { display: none; }
    .header { margin-bottom: 14px; }
    h2 { font-size: 12px; margin: 14px 0 6px; }
    .stat .value { font-size: 15px; }
    th { padding: 5px 8px; }
    td { padding: 5px 8px; }
    tr { page-break-inside: avoid; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()">🖨️ <?= $t('Print', 'طباعة') ?></button>
  <button class="ghost" onclick="window.close()">✕ <?= $t('Close', 'إغلاق') ?></button>
</div>

<div class="header">
  <div>
    <h1><?= $t('Route Report', 'تقرير المسار') ?></h1>
    <div class="subtitle"><?= $t('Alameen Distributor Tracker', 'متتبع العلمين للموزعين') ?></div>
  </div>
  <div class="meta">
    <div><b><?= $t('Distributor', 'الموزّع') ?>:</b> <?= htmlspecialchars($distributorName) ?></div>
    <div><b><?= $t('Period', 'الفترة') ?>:</b> <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></div>
    <div><b><?= $t('Generated', 'تاريخ التصدير') ?>:</b> <span id="genTs">—</span></div>
  </div>
</div>

<div class="stats" id="statGrid">
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
      <th><?= $t('GPS Points', 'نقاط GPS') ?></th>
      <th><?= $t('Distance (km)', 'المسافة (كم)') ?></th>
      <th><?= $t('Visits', 'الزيارات') ?></th>
    </tr>
  </thead>
  <tbody id="daysBody">
    <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;"><?= $t('Loading…', 'جارٍ التحميل…') ?></td></tr>
  </tbody>
</table>

<h2><?= $t('Visit Log', 'سجل الزيارات') ?></h2>
<table>
  <thead>
    <tr>
      <th><?= $t('Time', 'الوقت') ?></th>
      <th><?= $t('Customer', 'الزبون') ?></th>
      <th><?= $t('Trip', 'الرحلة') ?></th>
      <th><?= $t('State', 'الحالة') ?></th>
    </tr>
  </thead>
  <tbody id="visitsBody">
    <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;"><?= $t('Loading…', 'جارٍ التحميل…') ?></td></tr>
  </tbody>
</table>

<div class="footer">
  <?= $t('Generated by Alameen Distributor Tracker', 'تم إنشاؤه بواسطة متتبع العلمين للموزعين') ?>
</div>

<script>
const qs = new URLSearchParams(window.location.search);
const isAr = <?= $isAr ? 'true' : 'false' ?>;

document.getElementById('genTs').textContent =
  new Date().toISOString().slice(0, 19).replace('T', ' ');

(async () => {
  try {
    const [routeRes, visitsRes] = await Promise.all([
      fetch('api/route.php?' + qs.toString()).then(r => r.json()),
      fetch('api/visits.php?' + qs.toString()).then(r => r.json()),
    ]);

    // Stats
    document.getElementById('stPoints').textContent = routeRes.count ?? 0;
    document.getElementById('stKm').textContent     = (routeRes.distance_km ?? 0).toFixed(2);
    document.getElementById('stDays').textContent   = routeRes.days ?? 0;
    document.getElementById('stVisits').textContent = visitsRes.count ?? 0;

    // Per-day
    const daysBody = document.getElementById('daysBody');
    const dayStats = routeRes.day_stats || [];
    const visitsPerDay = {};
    (visitsRes.visits || []).forEach(v => {
      const d = String(v.StartTime || '').slice(0, 10);
      if (d) visitsPerDay[d] = (visitsPerDay[d] || 0) + 1;
    });

    if (dayStats.length === 0) {
      daysBody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">—</td></tr>';
    } else {
      daysBody.innerHTML = dayStats.map(d => `
        <tr>
          <td>${d.day}</td>
          <td class="num">${d.count}</td>
          <td class="num">${(d.km || 0).toFixed(2)}</td>
          <td class="num">${visitsPerDay[d.day] || 0}</td>
        </tr>
      `).join('');
    }

    // Visits log
    const visitsBody = document.getElementById('visitsBody');
    const visits = visitsRes.visits || [];
    if (visits.length === 0) {
      visitsBody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">—</td></tr>';
    } else {
      visitsBody.innerHTML = visits.map(v => `
        <tr>
          <td>${(v.StartTime || '').slice(0, 16)}</td>
          <td>${escapeHtml(v.CustomerName || '')}</td>
          <td class="num">${v.TripNumber || '—'}</td>
          <td><span class="badge">${v.State ?? '—'}</span></td>
        </tr>
      `).join('');
    }

  } catch (e) {
    console.error(e);
    document.getElementById('daysBody').innerHTML =
      '<tr><td colspan="4" style="text-align:center;color:#ef4444;padding:20px;">Error: ' + e.message + '</td></tr>';
  }
})();

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  })[c]);
}
</script>
</body>
</html>