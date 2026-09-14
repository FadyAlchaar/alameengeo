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
<title><?= $t('Anomaly Report', 'تقرير الحالات الشاذة') ?> — <?= htmlspecialchars($distributorName ?: 'Distributor') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  html, body {
    margin: 0; padding: 0;
    font-family: 'Cairo', system-ui, -apple-system, sans-serif;
    color: #1e293b;
    background: #f8fafc;
    font-size: 13px;
    line-height: 1.6;
  }

  .page {
    max-width: 950px;
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

  /* ─── Warning banner ─── */
  .warn-banner {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 13px;
    color: #78350f;
    margin-bottom: 22px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }
  .warn-banner .icon { font-size: 18px; flex-shrink: 0; }
  .warn-banner b { color: #78350f; }

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
    border-top: 3px solid #94a3b8;
  }
  .stat.red    { border-top-color: #ef4444; background: #fef2f2; }
  .stat.amber  { border-top-color: #f59e0b; background: #fffbeb; }
  .stat.purple { border-top-color: #8b5cf6; background: #f5f3ff; }
  .stat .label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 4px;
  }
  .stat .value {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
  }
  .stat .sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
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
    display: flex;
    align-items: center;
    gap: 8px;
  }
  h2 .count-pill {
    background: #f1f5f9;
    color: #475569;
    padding: 2px 9px;
    border-radius: 12px;
    font-size: 11px;
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
    vertical-align: top;
  }
  td.num {
    font-variant-numeric: tabular-nums;
    text-align: <?= $isAr ? 'left' : 'right' ?>;
    white-space: nowrap;
  }
  td.time {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
  }
  tbody tr:nth-child(even) td { background: #fafbfc; }

  .badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: .3px;
    white-space: nowrap;
  }
  .badge.unknown_stop { background: #fee2e2; color: #b91c1c; }
  .badge.long_idle    { background: #fef3c7; color: #b45309; }
  .badge.far_start    { background: #ede9fe; color: #6d28d9; }

  .coord {
    font-family: 'Courier New', monospace;
    font-size: 11px;
    color: #64748b;
    direction: ltr;
    display: inline-block;
  }

  /* ─── Footer ─── */
  .footer {
    margin-top: 40px;
    padding-top: 14px;
    border-top: 1px solid #e2e8f0;
    font-size: 10.5px;
    color: #94a3b8;
    text-align: center;
  }

  /* ─── Toolbar ─── */
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
    padding: 40px 20px;
    color: #94a3b8;
    font-style: italic;
  }
  .error-row { color: #dc2626; text-align: center; padding: 20px; }
  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #16a34a;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    margin: 20px 0;
  }
  .empty-state .icon { font-size: 32px; display: block; margin-bottom: 8px; }

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
    h2 { font-size: 12px; margin: 16px 0 8px; page-break-after: avoid; }
    .stat .value { font-size: 17px; }
    th { padding: 5px 8px; font-size: 10px; }
    td { padding: 6px 8px; font-size: 11px; }
    tbody tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
    .stats { page-break-inside: avoid; }
  }

  @media (max-width: 700px) {
    .page { padding: 20px 16px; margin: 10px; }
    .stats { grid-template-columns: repeat(2, 1fr); }
    .header { flex-direction: column; }
    .header .meta { text-align: <?= $isAr ? 'right' : 'left' ?>; }

    /* Stack table on mobile */
    table, thead, tbody, tr, td { display: block; }
    thead { display: none; }
    tbody tr {
      background: #f8fafc !important;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 10px 12px;
      margin-bottom: 8px;
    }
    tbody td {
      padding: 3px 0;
      border: none;
      display: flex;
      justify-content: space-between;
      gap: 12px;
    }
    tbody td::before {
      content: attr(data-label);
      font-size: 10.5px;
      text-transform: uppercase;
      color: #64748b;
      font-weight: 700;
      flex-shrink: 0;
    }
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
      <h1>⚠️ <?= $t('Anomaly Report', 'تقرير الحالات الشاذة') ?></h1>
      <div class="subtitle"><?= $t('Alameen Distributor Tracker', ' للموزعين') ?></div>
    </div>
    <div class="meta">
      <div><b><?= $t('Distributor', 'الموزّع') ?>:</b> <?= htmlspecialchars($distributorName ?: '—') ?></div>
      <div><b><?= $t('Period', 'الفترة') ?>:</b> <?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></div>
      <div><b><?= $t('Generated', 'تاريخ التصدير') ?>:</b> <span id="genTs">—</span></div>
    </div>
  </div>

  <div id="content">
    <div class="loading"><?= $t('Loading…', 'جارٍ التحميل…') ?></div>
  </div>

  <div class="footer">
    <?= $t('Generated by Alameen Distributor Tracker', 'تم إنشاؤه بواسطة  للموزعين') ?>
  </div>
</div>

<script>
const isAr  = <?= $isAr ? 'true' : 'false' ?>;
const qs    = new URLSearchParams(window.location.search);

document.getElementById('genTs').textContent =
  new Date().toISOString().slice(0, 19).replace('T', ' ');

const T = {
  en: {
    loading:      'Loading…',
    errorPrefix:  'Load failed: ',
    unknownStop:  'Unknown stop',
    longIdle:     'Long idle',
    farStart:     'Far start',
    statsTotal:   'Total anomalies',
    statsUnknown: 'Unknown stops',
    statsIdle:    'Long idles',
    statsFar:     'Far starts',
    sectionUnk:   'Unknown Stops',
    sectionIdle:  'Long Idles',
    sectionFar:   'Far Starts',
    bannerTitle:  'Attention required',
    bannerText:   'This report lists stops and route behaviors that could not be matched to any registered customer visit during the selected period. Some may be legitimate — this is a starting point for investigation, not a verdict.',
    colDate:      'Date',
    colTime:      'Time',
    colDur:       'Duration',
    colNearest:   'Nearest customer',
    colDist:      'Distance',
    colCoords:    'Coordinates',
    colFromCenter:'Distance from usual area',
    min:          'min',
    emptyState:   'No anomalies detected in this period.',
    emptySub:     'The route behavior looks consistent with the registered visits.',
  },
  ar: {
    loading:      'جارٍ التحميل…',
    errorPrefix:  'فشل التحميل: ',
    unknownStop:  'توقف غير معروف',
    longIdle:     'توقف طويل',
    farStart:     'بداية بعيدة',
    statsTotal:   'إجمالي الحالات',
    statsUnknown: 'توقفات غير معروفة',
    statsIdle:    'توقفات طويلة',
    statsFar:     'بدايات بعيدة',
    sectionUnk:   'التوقفات غير المعروفة',
    sectionIdle:  'التوقفات الطويلة',
    sectionFar:   'البدايات البعيدة',
    bannerTitle:  'يتطلب انتباهًا',
    bannerText:   'يُدرج هذا التقرير التوقفات والأنماط التي لم يتم مطابقتها مع أي زيارة زبون مسجّلة خلال الفترة المحدّدة. بعضها قد يكون مشروعًا — هذا نقطة بداية للتحقق، وليس حكمًا.',
    colDate:      'التاريخ',
    colTime:      'الوقت',
    colDur:       'المدة',
    colNearest:   'أقرب زبون',
    colDist:      'المسافة',
    colCoords:    'الإحداثيات',
    colFromCenter:'المسافة عن المنطقة المعتادة',
    min:          'دقيقة',
    emptyState:   'لم يتم اكتشاف أي حالات شاذة في هذه الفترة.',
    emptySub:     'سلوك المسار يبدو متوافقًا مع الزيارات المسجّلة.',
  }
};
const t = k => (T[isAr ? 'ar' : 'en'][k]) || k;

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  })[c]);
}

function fmtDate(str) {
  if (!str) return '—';
  return String(str).slice(0, 10);
}
function fmtTime(str) {
  if (!str) return '—';
  const m = String(str).match(/\d{2}:\d{2}/);
  return m ? m[0] : '—';
}
function fmtCoord(lat, lon) {
  if (!isFinite(lat) || !isFinite(lon)) return '—';
  return `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
}

(async function loadReport() {
  const content = document.getElementById('content');

  try {
    const apiQs = new URLSearchParams();
    apiQs.set('distributor', qs.get('d'));
    apiQs.set('from',        qs.get('f'));
    apiQs.set('to',          qs.get('t'));

    const res = await fetch('api/anomalies.php?' + apiQs.toString()).then(r => r.json());

    if (!res.success) throw new Error(res.error || 'Anomalies API failed');

    const anomalies = res.anomalies || [];
    const counts    = res.counts    || {};

    // ── Stats ──
    let html = `
      <div class="stats">
        <div class="stat">
          <div class="label">${t('statsTotal')}</div>
          <div class="value">${anomalies.length}</div>
        </div>
        <div class="stat red">
          <div class="label">${t('statsUnknown')}</div>
          <div class="value">${counts.unknown_stop || 0}</div>
        </div>
        <div class="stat amber">
          <div class="label">${t('statsIdle')}</div>
          <div class="value">${counts.long_idle || 0}</div>
        </div>
        <div class="stat purple">
          <div class="label">${t('statsFar')}</div>
          <div class="value">${counts.far_start || 0}</div>
        </div>
      </div>
    `;

    // ── Empty state ──
    if (anomalies.length === 0) {
      html += `
        <div class="empty-state">
          <span class="icon">✅</span>
          ${t('emptyState')}
          <div style="font-weight:400;font-size:12px;color:#16a34a;margin-top:6px;opacity:.85">
            ${t('emptySub')}
          </div>
        </div>
      `;
      content.innerHTML = html;
      return;
    }

    // ── Warning banner ──
    html += `
      <div class="warn-banner">
        <span class="icon">⚠️</span>
        <div>
          <b>${t('bannerTitle')}</b><br>
          ${t('bannerText')}
        </div>
      </div>
    `;

    // ── Bucket by type ──
    const byType = { unknown_stop: [], long_idle: [], far_start: [] };
    anomalies.forEach(a => { if (byType[a.type]) byType[a.type].push(a); });

    // ── Section: Unknown stops ──
    if (byType.unknown_stop.length > 0) {
      html += renderStopsSection(t('sectionUnk'), byType.unknown_stop, 'unknown_stop');
    }

    // ── Section: Long idles ──
    if (byType.long_idle.length > 0) {
      html += renderStopsSection(t('sectionIdle'), byType.long_idle, 'long_idle');
    }

    // ── Section: Far starts ──
    if (byType.far_start.length > 0) {
      html += renderFarSection(byType.far_start);
    }

    content.innerHTML = html;

  } catch (e) {
    console.error('[report-anomalies] failed:', e);
    content.innerHTML = `
      <div class="error-row">${escapeHtml(t('errorPrefix'))}${escapeHtml(e.message)}</div>
    `;
  }
})();

function renderStopsSection(title, items, type) {
  let h = `<h2>${escapeHtml(title)} <span class="count-pill">${items.length}</span></h2>`;
  h += `<table><thead><tr>
    <th>${t('colDate')}</th>
    <th>${t('colTime')}</th>
    <th>${t('colDur')}</th>
    <th>${t('colNearest')}</th>
    <th>${t('colDist')}</th>
    <th>${t('colCoords')}</th>
  </tr></thead><tbody>`;

  items.forEach(a => {
    const d  = fmtDate(a.start);
    const t1 = fmtTime(a.start);
    const t2 = fmtTime(a.end);
    const timeRange = (t1 && t2 && t1 !== t2) ? `${t1} → ${t2}` : t1;

    h += `<tr>
      <td data-label="${t('colDate')}">${escapeHtml(d)}</td>
      <td class="time" data-label="${t('colTime')}">${escapeHtml(timeRange)}</td>
      <td class="num" data-label="${t('colDur')}">${a.dur_min} ${escapeHtml(t('min'))}</td>
      <td data-label="${t('colNearest')}">${escapeHtml(a.nearest || '—')}</td>
      <td class="num" data-label="${t('colDist')}">${a.nearest_m ? a.nearest_m + ' m' : '—'}</td>
      <td data-label="${t('colCoords')}"><span class="coord">${escapeHtml(fmtCoord(a.lat, a.lon))}</span></td>
    </tr>`;
  });

  h += `</tbody></table>`;
  return h;
}

function renderFarSection(items) {
  let h = `<h2>${escapeHtml(t('sectionFar'))} <span class="count-pill">${items.length}</span></h2>`;
  h += `<table><thead><tr>
    <th>${t('colDate')}</th>
    <th>${t('colTime')}</th>
    <th>${t('colFromCenter')}</th>
    <th>${t('colCoords')}</th>
  </tr></thead><tbody>`;

  items.forEach(a => {
    h += `<tr>
      <td data-label="${t('colDate')}">${escapeHtml(fmtDate(a.start))}</td>
      <td class="time" data-label="${t('colTime')}">${escapeHtml(fmtTime(a.start))}</td>
      <td class="num" data-label="${t('colFromCenter')}">${a.dist_km} km</td>
      <td data-label="${t('colCoords')}"><span class="coord">${escapeHtml(fmtCoord(a.lat, a.lon))}</span></td>
    </tr>`;
  });

  h += `</tbody></table>`;
  return h;
}
</script>
</body>
</html>