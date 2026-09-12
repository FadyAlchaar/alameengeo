<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title>Customers Map — Alameen Tracker</title>

<link rel="stylesheet" href="vendor/leaflet.css">
<link rel="stylesheet" href="vendor/MarkerCluster.css">
<link rel="stylesheet" href="vendor/MarkerCluster.Default.css">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
  :root {
    --bg-dark:      #0f172a;
    --bg-panel:     #1e293b;
    --bg-input:     #0f172a;
    --border:       #334155;
    --text:         #e2e8f0;
    --text-dim:     #94a3b8;
    --accent:       #3b82f6;
    --accent-hover: #2563eb;
    --danger:       #ef4444;
    --warning:      #f59e0b;
    --success:      #10b981;
    --purple:       #8b5cf6;
    --amber:        #fbbf24;

    --sidebar-w:    360px;
    --header-h:     48px;
  }

  * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

  html, body {
    margin: 0;
    height: 100%;
    overflow: hidden;
    font-family: 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--bg-dark);
    color: var(--text);
  }

  /* Topbar */
  #topbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    padding-top: env(safe-area-inset-top, 0px);
    padding-left: max(12px, env(safe-area-inset-left, 0px));
    padding-right: max(12px, env(safe-area-inset-right, 0px));
    background: var(--bg-panel);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
    z-index: 2000;
  }
  #topbar .bar-title {
    flex: 1; display: flex; align-items: center; gap: 10px;
    font-weight: 700; font-size: 14px; color: #fff; min-width: 0;
  }
  #topbar .bar-title .logo {
    width: 30px; height: 30px; border-radius: 8px;
    background: linear-gradient(135deg, var(--accent), var(--purple));
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
  }
  #topbar .bar-title .text {
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }

  .tb-btn {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 8px;
    padding: 6px 10px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    text-decoration: none;
    transition: background .15s, border-color .15s;
  }
  .tb-btn:hover { background: var(--bg-dark); border-color: var(--accent); }
  .tb-btn.lang { min-width: 52px; justify-content: center; }
  .tb-btn .icon { font-size: 16px; line-height: 1; }

  #app {
    position: fixed;
    top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    left: 0; right: 0; bottom: 0;
    display: flex;
  }

  /* Sidebar */
  #side {
    width: var(--sidebar-w);
    flex-shrink: 0;
    background: var(--bg-panel);
    display: flex; flex-direction: column; gap: 12px;
    padding: 14px;
    overflow-y: auto;
    border-inline-end: 1px solid var(--border);
    transition: transform .25s ease, margin .25s ease;
    z-index: 1500;
  }
  #side::-webkit-scrollbar { width: 8px; }
  #side::-webkit-scrollbar-track { background: var(--bg-panel); }
  #side::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

  body.side-collapsed #side { margin-inline-start: calc(-1 * var(--sidebar-w)); }

  .field { display: flex; flex-direction: column; gap: 6px; }
  .field label {
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px;
    color: var(--text-dim); font-weight: 600;
  }
  input[type="text"], select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text);
    font-family: inherit;
    font-size: 14px;
    outline: none;
    transition: border-color .15s;
  }
  input[type="text"]:focus, select:focus { border-color: var(--accent); }

  .stat-bar {
    display: flex; justify-content: space-between; gap: 10px;
    background: var(--bg-dark); border-radius: 8px;
    padding: 10px 12px; font-size: 12px;
  }
  .stat-bar b { color: #60a5fa; font-weight: 700; }

  .customer-list {
    display: flex; flex-direction: column; gap: 6px;
    overflow-y: auto;
    padding-inline-end: 4px;
    flex: 1;
  }
  .customer-item {
    background: var(--bg-dark);
    border-radius: 8px;
    padding: 8px 11px;
    font-size: 12.5px;
    cursor: pointer;
    border-inline-start: 3px solid var(--accent);
    transition: background .15s;
  }
  .customer-item:hover { background: #1a2235; }
  .customer-item.selected { background: #1e3a8a; border-inline-start-color: var(--amber); }
  .customer-item .name { font-weight: 600; color: var(--text); display: block; margin-bottom: 2px; }
  .customer-item .meta { font-size: 11px; color: var(--text-dim); display: flex; justify-content: space-between; gap: 6px; }
  .customer-item .badge {
    font-size: 10px; padding: 1px 6px; border-radius: 8px;
    background: rgba(59,130,246,.2); color: #93c5fd; font-weight: 600;
  }

  /* Map */
  #map { flex: 1; position: relative; min-width: 0; }

  #sidebarToggle {
    position: absolute;
    top: 12px; right: 12px;
    width: 40px; height: 40px;
    border-radius: 8px;
    background: var(--bg-panel);
    border: 1px solid var(--border);
    color: var(--text);
    cursor: pointer;
    z-index: 1000;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,.4);
    transition: background .15s, border-color .15s;
  }
  #sidebarToggle:hover { background: var(--bg-dark); border-color: var(--accent); }

  #sideBackdrop {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 1400;
    display: none;
    backdrop-filter: blur(2px);
  }
  body.side-open-mobile #sideBackdrop { display: block; }

  /* Leaflet popup */
  .leaflet-popup-content {
    font-family: 'Cairo', sans-serif;
    margin: 12px 14px; font-size: 13px; min-width: 200px;
  }
  .leaflet-popup-content b { color: #0f172a; font-size: 14px; }
  .popup-meta { color: #64748b; font-size: 11.5px; margin-top: 4px; }
  .popup-link {
    display: inline-block; margin-top: 8px;
    color: #3b82f6; text-decoration: none;
    font-weight: 700; font-size: 12.5px;
  }
  .popup-link:hover { text-decoration: underline; }

  /* Selected crosshair (reuses index.php's look) */
  .selection-crosshair {
    background: transparent !important; border: none !important; pointer-events: none;
  }
  .selection-crosshair .ring, .selection-crosshair .ring-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid #ef4444;
  }
  .selection-crosshair .ring {
    box-shadow: 0 0 14px rgba(239,68,68,.75), inset 0 0 10px rgba(239,68,68,.4);
  }
  .selection-crosshair .ring-pulse { animation: xhPulse 1.6s ease-out infinite; }
  .selection-crosshair .arm {
    position: absolute; background: #ef4444;
    box-shadow: 0 0 4px rgba(0,0,0,.6); border-radius: 1px;
  }
  .selection-crosshair .arm.n { top: -14px;    left: 50%; transform: translateX(-50%); width: 2px; height: 14px; }
  .selection-crosshair .arm.s { bottom: -14px; left: 50%; transform: translateX(-50%); width: 2px; height: 14px; }
  .selection-crosshair .arm.e { right: -14px;  top: 50%; transform: translateY(-50%); width: 14px; height: 2px; }
  .selection-crosshair .arm.w { left: -14px;   top: 50%; transform: translateY(-50%); width: 14px; height: 2px; }
  @keyframes xhPulse {
    0%   { transform: scale(0.65); opacity: 1; }
    100% { transform: scale(1.85); opacity: 0; }
  }

  /* Loading */
  .loading-overlay {
    position: absolute; inset: 0;
    background: rgba(15,23,42,.6);
    display: none; align-items: center; justify-content: center;
    z-index: 1000;
  }
  .loading-overlay.active { display: flex; }
  .spinner {
    width: 44px; height: 44px;
    border: 4px solid rgba(255,255,255,.2);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin .8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* Responsive */
  @media (max-width: 900px) { :root { --sidebar-w: 320px; } }

  @media (max-width: 700px) {
    :root { --sidebar-w: min(88vw, 340px); }
    #side {
      position: fixed;
      top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
      bottom: 0;
      inset-inline-start: 0;
      box-shadow: 4px 0 18px rgba(0,0,0,.5);
    }
    html[dir="rtl"] #side { box-shadow: -4px 0 18px rgba(0,0,0,.5); }

    body:not(.side-open-mobile) #side { transform: translateX(-105%); }
    html[dir="rtl"] body:not(.side-open-mobile) #side { transform: translateX(105%); }

    body.side-collapsed #side { margin-inline-start: 0; }
  }
</style>
</head>
<body>

<div id="topbar">
  <div class="bar-title">
    <div class="logo">📍</div>
    <div class="text" data-i18n="pageTitle">Customers Map</div>
  </div>

  <a class="tb-btn" href="index.php" title="Back to routes">
    <span class="icon">🚚</span>
  </a>

  <button class="tb-btn" id="refreshBtn" title="Refresh cache" data-i18n-title="refresh">
    <span class="icon">🔄</span>
  </button>

  <button class="tb-btn lang" id="langBtn" title="Language">
    <span class="icon">🌐</span>
    <span id="langLabel">ع</span>
  </button>
</div>

<div id="app">

  <aside id="side">
    <div class="field">
      <label for="distributor" data-i18n="distributor">Distributor</label>
      <select id="distributor"></select>
    </div>

    <div class="field">
      <label for="search" data-i18n="search">Search</label>
      <input type="text" id="search" placeholder="—" autocomplete="off">
    </div>

    <div class="stat-bar">
      <span><span data-i18n="showing">Showing</span> <b id="visibleCount">0</b></span>
      <span><span data-i18n="total">Total</span> <b id="totalCount">0</b></span>
    </div>

    <div class="customer-list" id="customerList"></div>
  </aside>

  <div id="map">
    <button id="sidebarToggle" title="Toggle sidebar">☰</button>
    <div class="loading-overlay" id="loading">
      <div class="spinner"></div>
    </div>
  </div>

  <div id="sideBackdrop"></div>
</div>

<script src="vendor/leaflet.js"></script>
<script src="vendor/leaflet.markercluster.js"></script>

<script>
// ═══════════════════════════════════════════════════════════════
//  CONFIG
// ═══════════════════════════════════════════════════════════════
const FALLBACK_CENTER = [35.52, 35.78];
const FALLBACK_ZOOM   = 11;
const STORAGE_KEY     = 'alameen_custmap_state_v1';

function stalenessColor(lastVisitTime) {
  if (!lastVisitTime) return '#94a3b8';   // grey — never visited
  const ts = new Date(String(lastVisitTime).replace(' ', 'T')).getTime();
  if (!Number.isFinite(ts)) return '#94a3b8';
  const days = (Date.now() - ts) / 86400000;
  if (days <= 30)  return '#10b981';      // green
  if (days <= 90)  return '#f59e0b';      // amber
  return '#ef4444';                       // red
}

// ═══════════════════════════════════════════════════════════════
//  i18n
// ═══════════════════════════════════════════════════════════════
const I18N = {
  en: {
    pageTitle:   'Customers Map',
    distributor: 'Distributor',
    search:      'Search',
    showing:     'Showing',
    total:       'Total',
    refresh:     'Refresh cache',
    allDist:     'All distributors',
    noResults:   'No customers with coordinates.',
    noMatch:     'No matches.',
    viewDetails: 'View details →',
    lastVisit:   'Last visit',
    visits:      'visits',
    loading:     'Loading…',
    error:       'Error',
    approx:      'approx',
    loadingCache:'Loading from cache…',
  },
  ar: {
    pageTitle:   'خريطة الزبائن',
    distributor: 'الموزّع',
    search:      'بحث',
    showing:     'يُعرض',
    total:       'الإجمالي',
    refresh:     'تحديث الذاكرة',
    allDist:     'كل الموزعين',
    noResults:   'لا يوجد زبائن بإحداثيات.',
    noMatch:     'لا نتائج مطابقة.',
    viewDetails: 'عرض التفاصيل ←',
    lastVisit:   'آخر زيارة',
    visits:      'زيارة',
    loading:     'جارٍ التحميل…',
    error:       'خطأ',
    approx:      'تقريبي',
    loadingCache:'جارٍ التحميل من الذاكرة…',
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
  document.querySelectorAll('[data-i18n-title]').forEach(el => {
    el.title = t(el.getAttribute('data-i18n-title'));
  });
  document.getElementById('langLabel').textContent = LANG === 'ar' ? 'EN' : 'ع';
}

// ═══════════════════════════════════════════════════════════════
//  MAP
// ═══════════════════════════════════════════════════════════════
const map = L.map('map', { zoomControl: true }).setView(FALLBACK_CENTER, FALLBACK_ZOOM);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

const markerCluster = L.markerClusterGroup({
  maxClusterRadius: 55,
  showCoverageOnHover: false,
  iconCreateFunction: function(cluster) {
    const count = cluster.getChildCount();
    const size = count > 100 ? 48 : count > 20 ? 42 : 36;
    const bg = count > 100 ? 'rgba(239,68,68,.92)' :
               count > 20  ? 'rgba(245,158,11,.92)' :
                             'rgba(59,130,246,.92)';
    return L.divIcon({
      html: `<div style="
        width:${size}px;height:${size}px;border-radius:50%;
        background:${bg};border:3px solid #fff;
        color:#fff;font-weight:700;font-size:13px;
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 2px 6px rgba(0,0,0,.4);
      ">${count}</div>`,
      className: 'customer-cluster',
      iconSize: [size, size],
    });
  },
}).addTo(map);

let selectionMarker = null;
let allCustomers    = [];
let markerByGuid    = {};

// ═══════════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════════
const $ = id => document.getElementById(id);

function escapeHtml(s) {
  if (s === null || s === undefined) return '';
  return String(s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

async function jsonFetch(url) {
  const r    = await fetch(url);
  const text = await r.text();
  let data;
  try { data = JSON.parse(text); }
  catch (e) { throw new Error('Invalid JSON: ' + text.slice(0, 200)); }
  if (!r.ok || data.success === false) throw new Error(data.error || ('HTTP ' + r.status));
  return data;
}

function fmtDate(str) {
  if (!str) return '';
  const s = String(str);
  return s.slice(0, 16);
}

function showCrosshair(lat, lon) {
  if (selectionMarker) { map.removeLayer(selectionMarker); selectionMarker = null; }
  selectionMarker = L.marker([lat, lon], {
    icon: L.divIcon({
      className: 'selection-crosshair',
      html: `
        <div class="ring-pulse"></div>
        <div class="ring"></div>
        <span class="arm n"></span>
        <span class="arm s"></span>
        <span class="arm e"></span>
        <span class="arm w"></span>
      `,
      iconSize: [52, 52],
      iconAnchor: [26, 26],
    }),
    interactive: false,
    zIndexOffset: 3000,
  }).addTo(map);
}

function clearCrosshair() {
  if (selectionMarker) { map.removeLayer(selectionMarker); selectionMarker = null; }
}

function setLoading(on) { $('loading').classList.toggle('active', on); }

// ═══════════════════════════════════════════════════════════════
//  LOAD DATA
// ═══════════════════════════════════════════════════════════════
async function loadCustomers(distributorGuid, forceRefresh) {
  setLoading(true);
  markerCluster.clearLayers();
  clearCrosshair();
  markerByGuid = {};

  try {
    const qs = new URLSearchParams();
    if (distributorGuid) qs.set('distributor', distributorGuid);
    if (forceRefresh)    qs.set('refresh', '1');

    const data = await jsonFetch('api/customers-map.php?' + qs.toString());
    allCustomers = data.customers || [];

    // Build markers
    for (const c of allCustomers) {
      const marker = L.marker([c.Lat, c.Lon], {
        icon: L.divIcon({
          className: 'customer-marker',
          html: `<div style="
            width:14px;height:14px;border-radius:50%;
            background:${stalenessColor(c.LastVisitTime)};
            border:3px solid #fff;
            box-shadow:0 1px 3px rgba(0,0,0,.5);
          "></div>`,
          iconSize: [14, 14],
          iconAnchor: [7, 7],
        }),
      }).bindPopup(buildPopup(c));

      marker.__customer = c;
      markerCluster.addLayer(marker);
      markerByGuid[c.CustomerGUID] = marker;
    }

    $('totalCount').textContent = allCustomers.length;
    renderList(allCustomers, '');

    if (allCustomers.length > 0) {
      const bounds = L.latLngBounds(allCustomers.map(c => [c.Lat, c.Lon]));
      map.fitBounds(bounds, { padding: [60, 60] });
    } else {
      map.setView(FALLBACK_CENTER, FALLBACK_ZOOM);
    }

  } catch (e) {
    console.error('[loadCustomers] failed:', e);
    $('customerList').innerHTML =
      `<div style="color:#ef4444;font-size:12px;padding:8px">
        <b>${t('error')}:</b> ${escapeHtml(e.message)}
       </div>`;
  } finally {
    setLoading(false);
  }
}

function buildPopup(c) {
  const name    = escapeHtml(c.CustomerName || '—');
  const last    = escapeHtml(fmtDate(c.LastVisitTime));
  const visits  = c.TotalVisits || 0;
  const id      = encodeURIComponent(c.CustomerGUID || '');
  const dist    = escapeHtml(c.DistributorName || '');

  return `
    <b>${name}</b>
    <div class="popup-meta">🕒 ${t('lastVisit')}: ${last}</div>
    <div class="popup-meta">📊 ${visits} ${t('visits')}${dist ? ' · ' + dist : ''}</div>
    <a class="popup-link" href="customer.php?id=${id}" target="_blank">
      ${t('viewDetails')}
    </a>
  `;
}

// ═══════════════════════════════════════════════════════════════
//  SIDEBAR LIST
// ═══════════════════════════════════════════════════════════════
function renderList(list, query) {
  const box = $('customerList');
  box.innerHTML = '';

  const q = (query || '').trim().toLowerCase();
  const filtered = q
    ? list.filter(c =>
        (c.CustomerName || '').toLowerCase().includes(q) ||
        (c.Number || '').toString().toLowerCase().includes(q) ||
        (c.Phone1 || '').toLowerCase().includes(q) ||
        (c.Mobile || '').toLowerCase().includes(q))
    : list;

  $('visibleCount').textContent = filtered.length;

  if (filtered.length === 0) {
    box.innerHTML = `<div style="color:#94a3b8;font-size:12px;padding:8px">
      ${q ? t('noMatch') : t('noResults')}
    </div>`;
    return;
  }

  for (const c of filtered) {
    const el = document.createElement('div');
    el.className = 'customer-item';
    el.dataset.guid = c.CustomerGUID;

    el.innerHTML = `
      <span class="name">${escapeHtml(c.CustomerName || '—')}</span>
      <span class="meta">
        <span>#${escapeHtml(String(c.Number || ''))}</span>
        <span class="badge">${c.TotalVisits || 0}</span>
      </span>
    `;

    el.addEventListener('click', () => {
      // Deselect any previous
      document.querySelectorAll('.customer-item.selected').forEach(x => x.classList.remove('selected'));
      el.classList.add('selected');

      map.setView([c.Lat, c.Lon], 17);
      showCrosshair(c.Lat, c.Lon);

      const marker = markerByGuid[c.CustomerGUID];
      if (marker) {
        markerCluster.zoomToShowLayer(marker, () => {
          marker.openPopup();
        });
      }

      if (window.matchMedia('(max-width: 700px)').matches) {
        document.body.classList.remove('side-open-mobile');
      }
    });

    box.appendChild(el);
  }
}

// ═══════════════════════════════════════════════════════════════
//  EVENT WIRING
// ═══════════════════════════════════════════════════════════════
$('search').addEventListener('input', (e) => {
  renderList(allCustomers, e.target.value);
});

$('distributor').addEventListener('change', () => {
  const guid = $('distributor').value;
  saveState({ distributor: guid });
  loadCustomers(guid, false);
});

$('refreshBtn').addEventListener('click', () => {
  const guid = $('distributor').value;
  loadCustomers(guid, true);
});

$('langBtn').addEventListener('click', () => {
  LANG = LANG === 'ar' ? 'en' : 'ar';
  localStorage.setItem('alameen_lang', LANG);
  applyLanguage();
  renderList(allCustomers, $('search').value);
});

const isMobile = () => window.matchMedia('(max-width: 700px)').matches;

$('sidebarToggle').addEventListener('click', () => {
  if (isMobile()) {
    document.body.classList.toggle('side-open-mobile');
  } else {
    document.body.classList.toggle('side-collapsed');
    setTimeout(() => map.invalidateSize(), 260);
  }
});

$('sideBackdrop').addEventListener('click', () => {
  document.body.classList.remove('side-open-mobile');
});

// ═══════════════════════════════════════════════════════════════
//  STATE
// ═══════════════════════════════════════════════════════════════
function saveState(patch = {}) {
  try {
    const cur = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    const next = { ...cur, ...patch, ts: Date.now() };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  } catch (e) {}
}
function loadState() {
  try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
  catch (e) { return {}; }
}

// ═══════════════════════════════════════════════════════════════
//  INIT
// ═══════════════════════════════════════════════════════════════
(async function init() {
  applyLanguage();
  const saved = loadState();

  try {
    const distData = await jsonFetch('api/distributors.php');
    const sel = $('distributor');

    let options = `<option value="">— ${t('allDist')} —</option>`;
    options += (distData.distributors || []).map(d =>
      `<option value="${escapeHtml(d.GUID)}">${escapeHtml(d.Code)} — ${escapeHtml(d.Name)}</option>`
    ).join('');
    sel.innerHTML = options;

    if (saved.distributor) {
      const found = [...sel.options].some(o => o.value === saved.distributor);
      if (found) sel.value = saved.distributor;
    }

    await loadCustomers(sel.value || '', false);

  } catch (e) {
    console.error('[init] failed:', e);
    $('customerList').innerHTML =
      `<div style="color:#ef4444;font-size:12px;padding:8px">
        <b>${t('error')}:</b> ${escapeHtml(e.message)}
       </div>`;
  }
})();
</script>
</body>
</html>