<?php require_once __DIR__ . '/../app/Auth.php'; Auth::require(); ?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title>Compare Distributors — Alameen Tracker</title>

<script>
(function() {
  try {
    var t = localStorage.getItem('alameen_theme');
    document.documentElement.setAttribute('data-theme', (t === 'light' || t === 'dark') ? t : 'dark');
  } catch (e) { document.documentElement.setAttribute('data-theme', 'dark'); }
})();
</script>

<link rel="stylesheet" href="vendor/leaflet.css">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
  :root {
    --bg-dark:#0f172a; --bg-panel:#1e293b; --bg-input:#0f172a;
    --border:#334155; --text:#e2e8f0; --text-dim:#94a3b8;
    --accent:#3b82f6; --accent-hover:#2563eb;
    --danger:#ef4444; --success:#10b981; --warning:#f59e0b; --purple:#8b5cf6;
    --sidebar-w:380px; --header-h:48px;
  }
  html[data-theme="light"] {
    --bg-dark:#f1f5f9; --bg-panel:#ffffff; --bg-input:#f8fafc;
    --border:#cbd5e1; --text:#0f172a; --text-dim:#64748b;
    --accent:#2563eb; --accent-hover:#1d4ed8;
    --danger:#dc2626; --success:#059669; --warning:#d97706; --purple:#7c3aed;
  }

  * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
  html, body {
    margin:0; height:100%; overflow:hidden;
    font-family:'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--bg-dark); color: var(--text);
  }

  /* Topbar */
  #topbar {
    position:fixed; top:0; left:0; right:0;
    height: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    padding-top: env(safe-area-inset-top, 0px);
    padding-left: max(12px, env(safe-area-inset-left, 0px));
    padding-right: max(12px, env(safe-area-inset-right, 0px));
    background: var(--bg-panel); border-bottom: 1px solid var(--border);
    display:flex; align-items:center; gap:10px; z-index:2000;
  }
  #topbar .bar-title { flex:1; display:flex; align-items:center; gap:10px;
    font-weight:700; font-size:14px; color:var(--text); min-width:0; }
  #topbar .bar-title .logo {
    width:30px; height:30px; border-radius:8px;
    background: linear-gradient(135deg, var(--accent), var(--purple));
    display:flex; align-items:center; justify-content:center;
    font-size:16px; flex-shrink:0;
  }
  #topbar .bar-title .text { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

  .tb-btn {
    background: transparent; border: 1px solid var(--border);
    color: var(--text); border-radius:8px; padding:6px 10px;
    font-family: inherit; font-size: 13px; font-weight:600;
    cursor: pointer; display:flex; align-items:center; gap:6px;
    text-decoration: none; transition: background .15s, border-color .15s;
  }
  .tb-btn:hover { background: var(--bg-dark); border-color: var(--accent); }
  .tb-btn.lang { min-width:52px; justify-content:center; }
  .tb-btn .icon { font-size:16px; line-height:1; }

  #app {
    position:fixed;
    top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    left:0; right:0; bottom:0; display:flex;
  }

  /* Sidebar */
  #side {
    width: var(--sidebar-w); flex-shrink:0;
    background: var(--bg-panel);
    display:flex; flex-direction:column; gap:14px; padding:16px;
    overflow-y:auto; border-inline-end:1px solid var(--border);
    transition: transform .25s ease, margin .25s ease; z-index:1500;
  }
  #side::-webkit-scrollbar { width:8px; }
  #side::-webkit-scrollbar-thumb { background: var(--border); border-radius:4px; }
  body.side-collapsed #side { margin-inline-start: calc(-1 * var(--sidebar-w)); }

  .field { display:flex; flex-direction:column; gap:6px; }
  .field label {
    font-size:11px; text-transform:uppercase; letter-spacing:.8px;
    color: var(--text-dim); font-weight:600;
  }
  select, input[type="date"] {
    width:100%; padding:10px 12px; border-radius:8px;
    border:1px solid var(--border); background: var(--bg-input); color: var(--text);
    font-family: inherit; font-size:14px; outline:none; cursor:pointer;
  }
  select:focus, input[type="date"]:focus { border-color: var(--accent); }

  .date-row { display:grid; grid-template-columns: 1fr 1fr; gap:10px; }

  .btn {
    padding:11px 14px; border-radius:8px; border:none;
    font-family: inherit; font-size:14px; font-weight:600;
    cursor:pointer; transition: background .15s, opacity .15s;
    display:flex; align-items:center; justify-content:center; gap:6px;
  }
  .btn-primary { background: var(--accent); color:#fff; }
  .btn-primary:hover:not(:disabled) { background: var(--accent-hover); }
  .btn-primary:disabled { opacity:.6; cursor:not-allowed; }

  .btn-small {
    padding:6px 10px; font-size:12px; background: transparent;
    border:1px solid var(--border); color: var(--text-dim);
  }
  .btn-small:hover { border-color: var(--accent); color: var(--accent); }

  /* Distributor list */
  .dist-header {
    display:flex; justify-content:space-between; align-items:center;
    font-size:11px; text-transform:uppercase; letter-spacing:.8px;
    color: var(--text-dim); font-weight:600; margin-bottom:8px;
  }
  .dist-actions { display:flex; gap:6px; }
  .dist-list {
    display:flex; flex-direction:column; gap:4px;
    max-height: 260px; overflow-y:auto; padding-inline-end:2px;
  }
  .dist-list::-webkit-scrollbar { width:6px; }
  .dist-list::-webkit-scrollbar-thumb { background: var(--border); border-radius:3px; }

  .dist-item {
    display:flex; align-items:center; gap:9px;
    padding:7px 10px; border-radius:7px; background: var(--bg-dark);
    cursor:pointer; transition: background .15s; user-select:none;
    border: 1px solid transparent;
  }
  .dist-item:hover { background: #253046; }
  html[data-theme="light"] .dist-item:hover { background: #e2e8f0; }
  .dist-item.checked { border-color: rgba(59,130,246,.4); }
  .dist-item input[type="checkbox"] {
    width:15px; height:15px; accent-color: var(--accent);
    cursor:pointer; flex-shrink:0; margin:0;
  }
  .dist-item .swatch {
    width:12px; height:12px; border-radius:50%; flex-shrink:0;
    box-shadow: 0 0 0 1px rgba(255,255,255,.2);
    opacity:.35; transition: opacity .15s, transform .15s;
  }
  .dist-item.checked .swatch { opacity:1; transform: scale(1.15); }
  .dist-item .name {
    flex:1; font-size:12.5px; color: var(--text);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  .dist-item.checked .name { font-weight:600; }

  /* Stats table */
  .stats-table { display:flex; flex-direction:column; gap:6px; }
  .stat-row-card {
    background: var(--bg-dark); border-radius:8px;
    padding:9px 12px; cursor:pointer;
    border-inline-start: 3px solid transparent;
    transition: background .15s, transform .08s;
  }
  .stat-row-card:hover { background: #253046; }
  html[data-theme="light"] .stat-row-card:hover { background: #e2e8f0; }
  .stat-row-card:active { transform: scale(.99); }
  .stat-row-card .head {
    display:flex; align-items:center; gap:8px;
    font-size:12.5px; font-weight:600; color:var(--text);
    margin-bottom:4px;
  }
  .stat-row-card .head .swatch {
    width:10px; height:10px; border-radius:50%; flex-shrink:0;
  }
  .stat-row-card .head .name {
    flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
  }
  .stat-row-card .nums {
    display:flex; gap:12px; font-size:11px; color: var(--text-dim);
    font-variant-numeric: tabular-nums;
  }
  .stat-row-card .nums b { color:#60a5fa; font-weight:600; }
  html[data-theme="light"] .stat-row-card .nums b { color:#1d4ed8; }
  .stat-row-card.error { opacity:.6; }
  .stat-row-card.error .nums { color: var(--danger); }

  .empty-hint {
    text-align:center; padding: 22px 14px; font-size:12.5px;
    color: var(--text-dim); font-style:italic;
    background: var(--bg-dark); border-radius:8px;
  }

  /* Map */
  #map { flex:1; position:relative; min-width:0; }
  #sidebarToggle {
    position:absolute; top:12px; right:12px;
    width:40px; height:40px; border-radius:8px;
    background: var(--bg-panel); border:1px solid var(--border);
    color: var(--text); cursor:pointer; z-index:1000;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; box-shadow: 0 2px 8px rgba(0,0,0,.4);
  }
  #sidebarToggle:hover { border-color: var(--accent); }

  #sideBackdrop {
    position:fixed; inset:0; background: rgba(0,0,0,.5);
    z-index:1400; display:none; backdrop-filter: blur(2px);
  }
  body.side-open-mobile #sideBackdrop { display:block; }

  .leaflet-popup-content {
    font-family: 'Cairo', sans-serif; margin:12px 14px; font-size:13px; min-width:180px;
  }
  .leaflet-popup-content b { color:#0f172a; font-size:14px; }
  .popup-meta { color:#64748b; font-size:11.5px; margin-top:4px; }

  /* Loading */
  .loading-overlay {
    position:absolute; inset:0; background: rgba(15,23,42,.55);
    display:none; align-items:center; justify-content:center; z-index:1000;
  }
  .loading-overlay.active { display:flex; }
  .spinner {
    width:44px; height:44px; border:4px solid rgba(255,255,255,.2);
    border-top-color: var(--accent); border-radius:50%;
    animation: spin .8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* Responsive */
  @media (max-width:900px) { :root { --sidebar-w: 340px; } }
  @media (max-width:700px) {
    :root { --sidebar-w: min(88vw, 360px); }
    #side {
      position:fixed;
      top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
      bottom:0; inset-inline-start:0;
      box-shadow: 4px 0 18px rgba(0,0,0,.5);
    }
    html[dir="rtl"] #side { box-shadow: -4px 0 18px rgba(0,0,0,.5); }
    body:not(.side-open-mobile) #side { transform: translateX(-105%); }
    html[dir="rtl"] body:not(.side-open-mobile) #side { transform: translateX(105%); }
    body.side-collapsed #side { margin-inline-start: 0; }
  }
</style>

<!-- ═══ ERROR BOUNDARY CSS ═══ -->
<style id="error-boundary-css">
  .eb-overlay { position:fixed; inset:0; background: rgba(15,23,42,.78);
    backdrop-filter: blur(4px); z-index:99999; display:none;
    align-items:center; justify-content:center; padding:20px;
    font-family:'Cairo',sans-serif; }
  .eb-overlay.active { display:flex; }
  .eb-card { background:#1e293b; border:1px solid #334155; border-radius:14px;
    max-width:560px; width:100%; max-height:90vh; overflow-y:auto;
    padding:24px; color:#e2e8f0; direction:inherit; }
  .eb-head { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
  .eb-icon { width:42px; height:42px; border-radius:10px;
    background: linear-gradient(135deg,#ef4444,#b91c1c); display:flex;
    align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
  .eb-title { font-size:17px; font-weight:700; color:#fff; margin:0; }
  .eb-sub { font-size:12.5px; color:#94a3b8; margin-top:2px; }
  .eb-msg { background:#0f172a; border-radius:8px; padding:12px 14px;
    font-size:13px; line-height:1.6; color:#e2e8f0; margin-bottom:14px; word-break:break-word; }
  .eb-details { margin-top:10px; }
  .eb-details summary { cursor:pointer; font-size:12px; color:#94a3b8; padding:4px 0; }
  .eb-details pre { background:#0f172a; border-radius:6px; padding:10px 12px;
    font-size:11px; line-height:1.5; color:#cbd5e1; overflow-x:auto; max-height:240px;
    margin:8px 0 0; white-space:pre-wrap; word-break:break-all; direction:ltr; text-align:left; }
  .eb-actions { display:flex; gap:10px; justify-content:flex-end;
    margin-top:18px; flex-wrap:wrap; }
  .eb-btn { padding:10px 18px; border-radius:8px; border:none;
    font-family:inherit; font-size:13.5px; font-weight:600; cursor:pointer; }
  .eb-btn-primary { background:#3b82f6; color:#fff; }
  .eb-btn-primary:hover { background:#2563eb; }
  .eb-btn-ghost { background:transparent; border:1px solid #334155; color:#cbd5e1; }
  .eb-btn-ghost:hover { background:#0f172a; }
  .eb-btn-copy { background:transparent; border:1px solid #334155; color:#94a3b8; }
  .eb-btn-copy:hover { background:#0f172a; color:#cbd5e1; }
</style>

<!-- ═══ TOAST CSS ═══ -->
<style id="toast-css">
  .toast-container { position:fixed;
    top: calc(var(--header-h) + env(safe-area-inset-top, 0px) + 12px);
    left:50%; transform: translateX(-50%); z-index:5000;
    display:flex; flex-direction:column; gap:8px;
    width: min(92vw, 420px); pointer-events:none; }
  .toast { pointer-events:auto; display:flex; align-items:flex-start; gap:10px;
    padding:12px 14px; border-radius:10px; background: var(--bg-panel);
    color: var(--text); border:1px solid var(--border);
    border-inline-start: 4px solid #3b82f6;
    box-shadow: 0 8px 24px rgba(0,0,0,.4); font-size:13px; line-height:1.5;
    position:relative; overflow:hidden; font-family:'Cairo',sans-serif;
    animation: toastIn .3s cubic-bezier(.2,.9,.3,1.15); }
  .toast.removing { animation: toastOut .22s ease-in forwards; }
  .toast-icon { width:20px; height:20px; flex-shrink:0; display:flex;
    align-items:center; justify-content:center; font-size:15px; font-weight:700; color:#3b82f6; }
  .toast-body { flex:1; min-width:0; word-break:break-word; }
  .toast-close { width:22px; height:22px; border-radius:6px; border:none;
    background:transparent; color: var(--text-dim); cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; flex-shrink:0; padding:0; line-height:1; }
  .toast-close:hover { background: var(--bg-dark); color: var(--text); }
  .toast-progress { position:absolute; bottom:0; left:0; right:0; height:2px;
    background:#3b82f6; opacity:.5; transform-origin:left;
    animation: toastProgress linear forwards; }
  html[dir="rtl"] .toast-progress { transform-origin:right; }
  .toast.success { border-inline-start-color:#10b981; }
  .toast.success .toast-icon { color:#10b981; }
  .toast.success .toast-progress { background:#10b981; }
  .toast.error { border-inline-start-color:#ef4444; }
  .toast.error .toast-icon { color:#ef4444; }
  .toast.error .toast-progress { background:#ef4444; }
  .toast.warning { border-inline-start-color:#f59e0b; }
  .toast.warning .toast-icon { color:#f59e0b; }
  .toast.warning .toast-progress { background:#f59e0b; }
  .toast.info { border-inline-start-color:#3b82f6; }
  .toast.info .toast-icon { color:#3b82f6; }
  .toast.info .toast-progress { background:#3b82f6; }
  @keyframes toastIn { from { opacity:0; transform: translateY(-10px); } to { opacity:1; transform: translateY(0); } }
  @keyframes toastOut { from { opacity:1; max-height:200px; margin-top:0; }
    to { opacity:0; transform: translateY(-10px); max-height:0; margin-top:-8px; } }
  @keyframes toastProgress { from { transform: scaleX(1); } to { transform: scaleX(0); } }
</style>
</head>
<body>

<!-- ═══ ERROR BOUNDARY JS ═══ -->
<script>
(function() {
  'use strict';
  let errCount = 0;
  function ensureOverlay() {
    let el = document.getElementById('ebOverlay');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'ebOverlay'; el.className = 'eb-overlay';
    el.innerHTML = `
      <div class="eb-card" role="alertdialog">
        <div class="eb-head">
          <div class="eb-icon">⚠️</div>
          <div style="flex:1;min-width:0">
            <h2 class="eb-title" id="ebTitle">Something went wrong</h2>
            <div class="eb-sub">The action could not be completed.</div>
          </div>
        </div>
        <div class="eb-msg" id="ebMsg">An unexpected error occurred.</div>
        <details class="eb-details"><summary>Technical details</summary><pre id="ebDetail"></pre></details>
        <div class="eb-actions">
          <button class="eb-btn eb-btn-copy" id="ebCopyBtn">📋 Copy</button>
          <button class="eb-btn eb-btn-ghost" id="ebDismissBtn">Dismiss</button>
          <button class="eb-btn eb-btn-primary" id="ebReloadBtn">🔄 Reload</button>
        </div>
      </div>`;
    document.body.appendChild(el);
    el.querySelector('#ebDismissBtn').addEventListener('click', () => el.classList.remove('active'));
    el.querySelector('#ebReloadBtn').addEventListener('click', () => location.reload());
    el.querySelector('#ebCopyBtn').addEventListener('click', () => {
      const txt = (document.getElementById('ebMsg').textContent || '') + '\n\n' + (document.getElementById('ebDetail').textContent || '');
      if (navigator.clipboard) navigator.clipboard.writeText(txt);
    });
    el.addEventListener('click', (ev) => { if (ev.target === el) el.classList.remove('active'); });
    return el;
  }
  function showOverlay(title, message, details) {
    errCount++;
    if (errCount >= 5) message = 'Multiple errors occurred. Reload the page to continue.';
    const el = ensureOverlay();
    el.querySelector('#ebTitle').textContent = title;
    el.querySelector('#ebMsg').textContent   = message;
    el.querySelector('#ebDetail').textContent = details;
    el.classList.add('active');
  }
  function fmtError(err) {
    if (!err) return '(no error object)';
    if (err instanceof Error) return (err.name || 'Error') + ': ' + err.message + (err.stack ? '\n\n' + err.stack : '');
    try { return JSON.stringify(err, null, 2); } catch (e) { return String(err); }
  }
  window.addEventListener('error', function(ev) {
    const src = ev.filename || '';
    if (/^(chrome|moz|safari|edge)-extension:|^webextension/.test(src)) return;
    showOverlay('Unexpected error', ev.message || 'A JavaScript error occurred.',
      'Message: ' + ev.message + '\nSource: ' + src + ':' + ev.lineno + ':' + ev.colno + '\n\n' + fmtError(ev.error));
  });
  window.addEventListener('unhandledrejection', function(ev) {
    showOverlay('Operation failed', (ev.reason && ev.reason.message) || 'A background operation failed.', fmtError(ev.reason));
  });
})();
</script>

<!-- ═══ TOAST JS ═══ -->
<script>
(function() {
  'use strict';
  function ensureContainer() {
    let el = document.getElementById('toastContainer');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'toastContainer'; el.className = 'toast-container';
    document.body.appendChild(el);
    return el;
  }
  const ICONS = { success:'✓', error:'✕', warning:'!', info:'ℹ' };
  const DUR = { success:3500, info:3500, warning:5000, error:6500 };
  function showToast(msg, type, dur) {
    type = type || 'info';
    if (!msg) return;
    const c = ensureContainer();
    const ms = dur != null ? dur : (DUR[type] || 4000);
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = `<div class="toast-icon">${ICONS[type]||'ℹ'}</div><div class="toast-body"></div><button class="toast-close">×</button><div class="toast-progress"></div>`;
    el.querySelector('.toast-body').textContent = msg;
    el.querySelector('.toast-progress').style.animationDuration = ms + 'ms';
    let gone = false;
    function remove() { if (gone) return; gone = true; el.classList.add('removing'); setTimeout(() => el.remove(), 240); }
    el.querySelector('.toast-close').addEventListener('click', remove);
    c.appendChild(el);
    setTimeout(remove, ms);
  }
  window.__toast = {
    show: showToast,
    success: (m,d) => showToast(m,'success',d),
    error:   (m,d) => showToast(m,'error',d),
    warning: (m,d) => showToast(m,'warning',d),
    info:    (m,d) => showToast(m,'info',d),
  };
})();
</script>

<div id="topbar">
  <div class="bar-title">
    <div class="logo">📊</div>
    <div class="text" data-i18n="pageTitle">Compare Distributors</div>
  </div>

  <a class="tb-btn" href="index.php" title="Routes" data-i18n-title="routesBtn">
    <span class="icon">🚚</span>
  </a>
  <a class="tb-btn" href="customers.php" title="Customers" data-i18n-title="customersBtn">
    <span class="icon">📍</span>
  </a>

  <button class="tb-btn" id="themeBtn" title="Theme">
    <span class="icon" id="themeIcon">☀️</span>
  </button>
  <button class="tb-btn lang" id="langBtn" title="Language">
    <span class="icon">🌐</span>
    <span id="langLabel">ع</span>
  </button>
</div>

<div id="app">

  <aside id="side">

    <div class="date-row">
      <div class="field">
        <label for="from" data-i18n="from">From</label>
        <input type="date" id="from">
      </div>
      <div class="field">
        <label for="to" data-i18n="to">To</label>
        <input type="date" id="to">
      </div>
    </div>

    <div>
      <div class="dist-header">
        <span data-i18n="distributors">Distributors</span>
        <div class="dist-actions">
          <button class="btn btn-small" id="selectAllBtn" data-i18n="selectAll">All</button>
          <button class="btn btn-small" id="clearAllBtn" data-i18n="clearAll">Clear</button>
        </div>
      </div>
      <div class="dist-list" id="distList"></div>
    </div>

    <button class="btn btn-primary" id="loadBtn" data-i18n="load">Load Routes</button>

    <div>
      <div class="dist-header" style="margin-top:4px;">
        <span data-i18n="summary">Summary</span>
      </div>
      <div class="stats-table" id="statsTable">
        <div class="empty-hint" id="emptyHint" data-i18n="noDataHint">Pick distributors and click Load</div>
      </div>
    </div>

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

<script>
// ═══════════════════════════════════════════════════════════════
//  CONFIG
// ═══════════════════════════════════════════════════════════════
const FALLBACK_CENTER = [35.52, 35.78];
const FALLBACK_ZOOM   = 11;
const STORAGE_KEY     = 'alameen_compare_state_v1';
const COLOR_PALETTE = [
  '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
  '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16',
  '#f97316', '#a855f7', '#14b8a6', '#6366f1'
];

// ═══════════════════════════════════════════════════════════════
//  i18n
// ═══════════════════════════════════════════════════════════════
const I18N = {
  en: {
    pageTitle:    'Compare Distributors',
    from:         'From',
    to:           'To',
    distributors: 'Distributors',
    selectAll:    'All',
    clearAll:     'Clear',
    load:         'Load Routes',
    summary:      'Summary',
    noDataHint:   'Pick distributors and click Load',
    loading:      'Loading…',
    error:        'Error',
    routesBtn:    'Routes',
    customersBtn: 'Customers',
    loaded:       'Loaded',
    routes:       'routes',
    pts:          'pts',
    km:           'km',
    visits:       'visits',
    noSelection:  'Please select at least one distributor.',
    noDate:       'Please select both dates.',
    fromAfterTo:  '"From" must be before or equal to "To".',
    darkMode:     'Dark mode',
    lightMode:    'Light mode',
    noDataFor:    'No data for',
    firstPoint:   'First GPS point',
    lastPoint:    'Last GPS point',
  },
  ar: {
    pageTitle:    'مقارنة المندوبين',
    from:         'من',
    to:           'إلى',
    distributors: 'الموزعون',
    selectAll:    'الكل',
    clearAll:     'مسح',
    load:         'تحميل المسارات',
    summary:      'الملخص',
    noDataHint:   'اختر المندوبين ثم اضغط تحميل',
    loading:      'جارٍ التحميل…',
    error:        'خطأ',
    routesBtn:    'المسارات',
    customersBtn: 'الزبائن',
    loaded:       'تم تحميل',
    routes:       'مسار',
    pts:          'نقطة',
    km:           'كم',
    visits:       'زيارة',
    noSelection:  'الرجاء اختيار مندوب واحد على الأقل.',
    noDate:       'الرجاء اختيار كلا التاريخين.',
    fromAfterTo:  'يجب أن يكون تاريخ "من" قبل أو يساوي تاريخ "إلى".',
    darkMode:     'الوضع الداكن',
    lightMode:    'الوضع الفاتح',
    noDataFor:    'لا توجد بيانات لـ',
    firstPoint:   'أول نقطة GPS',
    lastPoint:    'آخر نقطة GPS',
  }
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

const routesLayer = L.layerGroup().addTo(map);

// ═══════════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════════
const $ = id => document.getElementById(id);
function fmtDate(d) { return d.toISOString().slice(0, 10); }
function escapeHtml(s) {
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function toNum(v) {
  if (v == null) return NaN;
  const n = Number(String(v).replace(/[\s\u00A0,]/g,''));
  return Number.isFinite(n) ? n : NaN;
}
function isValidLatLon(lat, lon) {
  return Number.isFinite(lat) && Number.isFinite(lon) && lat >= -90 && lat <= 90 && lon >= -180 && lon <= 180 && !(lat === 0 && lon === 0);
}
function haversine([lat1,lon1],[lat2,lon2]) {
  const R = 6371, dLat = (lat2-lat1)*Math.PI/180, dLon = (lon2-lon1)*Math.PI/180;
  const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)**2;
  return R*2*Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}
async function jsonFetch(url) {
  const r = await fetch(url);
  const text = await r.text();
  let data;
  try { data = JSON.parse(text); } catch(e) { throw new Error('Invalid JSON from ' + url); }
  if (!r.ok || data.success === false) throw new Error(data.error || ('HTTP ' + r.status));
  return data;
}
function setLoading(on) { $('loading').classList.toggle('active', on); }

// ═══════════════════════════════════════════════════════════════
//  STATE
// ═══════════════════════════════════════════════════════════════
let allDistributors = [];
let selected = new Set();
let colorByGuid = {};
let loadedRoutes = {};   // guid -> {points, km, visits, bounds, layers}

function saveState() {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      selected: [...selected],
      from: $('from').value,
      to:   $('to').value,
      ts:   Date.now()
    }));
  } catch(e) {}
}
function loadState() {
  try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch(e) { return {}; }
}

// ═══════════════════════════════════════════════════════════════
//  RENDER DISTRIBUTOR LIST
// ═══════════════════════════════════════════════════════════════
function renderDistributorList() {
  const box = $('distList');
  box.innerHTML = '';
  allDistributors.forEach((d, idx) => {
    const color = COLOR_PALETTE[idx % COLOR_PALETTE.length];
    colorByGuid[d.GUID] = color;

    const el = document.createElement('label');
    el.className = 'dist-item' + (selected.has(d.GUID) ? ' checked' : '');
    el.innerHTML = `
      <input type="checkbox" value="${escapeHtml(d.GUID)}" ${selected.has(d.GUID) ? 'checked' : ''}>
      <span class="swatch" style="background:${color}"></span>
      <span class="name">${escapeHtml(d.Code)} — ${escapeHtml(d.Name)}</span>
    `;
    const cb = el.querySelector('input');
    cb.addEventListener('change', () => {
      if (cb.checked) selected.add(d.GUID);
      else            selected.delete(d.GUID);
      el.classList.toggle('checked', cb.checked);
      saveState();
    });
    box.appendChild(el);
  });
}

// ═══════════════════════════════════════════════════════════════
//  LOAD ROUTES
// ═══════════════════════════════════════════════════════════════
$('loadBtn').addEventListener('click', async () => {
  const from = $('from').value;
  const to   = $('to').value;

  if (selected.size === 0) { __toast.warning(t('noSelection')); return; }
  if (!from || !to)        { __toast.warning(t('noDate'));      return; }
  if (from > to)           { __toast.warning(t('fromAfterTo')); return; }

  saveState();

    // Clear previous
  routesLayer.clearLayers();
  loadedRoutes = {};
  const _eh = $('emptyHint'); if (_eh) _eh.style.display = 'none';
  $('statsTable').innerHTML = '<div class="empty-hint">' + t('loading') + '</div>';

  setLoading(true);
  $('loadBtn').disabled = true;
  $('loadBtn').textContent = t('loading');

  const guids = [...selected];
  const bounds_all = [];

  // Load in parallel with a conservative target for lighter payload
  const results = await Promise.all(guids.map(async (guid) => {
    const d = allDistributors.find(x => x.GUID === guid);
    const qs = new URLSearchParams({
      distributor: guid, from, to, target: '2000'
    });
    try {
      const data = await jsonFetch('api/route.php?' + qs.toString());
      return { guid, distributor: d, data, ok: true };
    } catch (e) {
      return { guid, distributor: d, error: e.message, ok: false };
    }
  }));

  // ── Draw results ──
  let drawnCount = 0;
  results.forEach(r => {
    const color = colorByGuid[r.guid] || '#3b82f6';
    if (!r.ok || !r.data.points || r.data.points.length === 0) {
      loadedRoutes[r.guid] = { error: r.error || 'no data', color };
      return;
    }

    const pts = r.data.points
      .map(p => [toNum(p.Lat), toNum(p.Lon), p.LogTime])
      .filter(([lat, lon]) => isValidLatLon(lat, lon));

    if (pts.length === 0) {
      loadedRoutes[r.guid] = { error: 'no valid points', color };
      return;
    }

    // Polyline
    const poly = L.polyline(pts.map(p => [p[0], p[1]]), {
      color, weight: 4, opacity: 0.85,
      lineJoin: 'round', lineCap: 'round'
    }).addTo(routesLayer);

    // Start / End markers
    const first = pts[0], last = pts[pts.length - 1];
    L.circleMarker([first[0], first[1]], {
      radius: 7, color, fillColor: color, fillOpacity: 1, weight: 3
    }).bindPopup(`<b>${escapeHtml(r.distributor.Name)}</b><div class="popup-meta">🚚 ${t('firstPoint')}<br>${escapeHtml(first[2])}</div>`).addTo(routesLayer);

    L.circleMarker([last[0], last[1]], {
      radius: 7, color, fillColor: color, fillOpacity: 1, weight: 3
    }).bindPopup(`<b>${escapeHtml(r.distributor.Name)}</b><div class="popup-meta">🏁 ${t('lastPoint')}<br>${escapeHtml(last[2])}</div>`).addTo(routesLayer);

    // Bounds
    const bounds = L.latLngBounds(pts.map(p => [p[0], p[1]]));
    bounds_all.push(bounds);

    loadedRoutes[r.guid] = {
      points: pts.length,
      km: r.data.distance_km || 0,
      days: r.data.days || 0,
      color, bounds, name: r.distributor.Name
    };
    drawnCount++;
  });

  // Fit map
  if (bounds_all.length > 0) {
    const combined = bounds_all.reduce((acc, b) => acc.extend(b), L.latLngBounds(bounds_all[0]));
    map.fitBounds(combined, { padding: [50, 50] });
  } else {
    map.setView(FALLBACK_CENTER, FALLBACK_ZOOM);
  }

  renderStatsTable();
  setLoading(false);
  $('loadBtn').disabled = false;
  $('loadBtn').textContent = t('load');

  if (drawnCount === 0) {
    __toast.warning(LANG === 'ar' ? 'لا توجد بيانات مسارات لهذه الفترة' : 'No route data for this range');
  } else {
    __toast.success(`${t('loaded')} ${drawnCount} ${t('routes')}`);
  }
});

// ═══════════════════════════════════════════════════════════════
//  RENDER STATS TABLE
// ═══════════════════════════════════════════════════════════════
function renderStatsTable() {
  const box = $('statsTable');
  box.innerHTML = '';

  const entries = [...selected].map(guid => {
    const r = loadedRoutes[guid] || {};
    const d = allDistributors.find(x => x.GUID === guid);
    return { guid, distributor: d, result: r };
  });

  if (entries.length === 0) {
    box.innerHTML = '<div class="empty-hint">' + t('noDataHint') + '</div>';
    return;
  }

  entries.forEach(({ guid, distributor, result }) => {
    const color = result.color || colorByGuid[guid] || '#3b82f6';
    const name  = distributor ? distributor.Name : '—';

    const el = document.createElement('div');
    el.className = 'stat-row-card' + (result.error ? ' error' : '');
    el.style.borderInlineStartColor = color;

    if (result.error) {
      el.innerHTML = `
        <div class="head"><span class="swatch" style="background:${color}"></span><span class="name">${escapeHtml(name)}</span></div>
        <div class="nums">${escapeHtml(result.error)}</div>
      `;
    } else {
      el.innerHTML = `
        <div class="head"><span class="swatch" style="background:${color}"></span><span class="name">${escapeHtml(name)}</span></div>
        <div class="nums">
          <span><b>${result.points}</b> ${t('pts')}</span>
          <span><b>${(result.km||0).toFixed(1)}</b> ${t('km')}</span>
          <span><b>${result.days||0}</b> ${t('days') || 'days'}</span>
        </div>
      `;
      el.addEventListener('click', () => {
        if (result.bounds && result.bounds.isValid()) {
          map.fitBounds(result.bounds, { padding: [50, 50] });
          __toast.info(name);
        }
      });
    }
    box.appendChild(el);
  });
}

// ═══════════════════════════════════════════════════════════════
//  TOPBAR ACTIONS
// ═══════════════════════════════════════════════════════════════
$('langBtn').addEventListener('click', () => {
  LANG = LANG === 'ar' ? 'en' : 'ar';
  localStorage.setItem('alameen_lang', LANG);
  applyLanguage();
  renderDistributorList();
  renderStatsTable();
});

function updateThemeIcon() {
  const theme = document.documentElement.getAttribute('data-theme') || 'dark';
  const icon = document.getElementById('themeIcon');
  if (icon) icon.textContent = theme === 'dark' ? '☀️' : '🌙';
}
updateThemeIcon();

$('themeBtn').addEventListener('click', () => {
  const cur = document.documentElement.getAttribute('data-theme') || 'dark';
  const nxt = cur === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', nxt);
  try { localStorage.setItem('alameen_theme', nxt); } catch(e) {}
  updateThemeIcon();
  __toast.info(nxt === 'dark' ? t('darkMode') : t('lightMode'));
});

// ═══════════════════════════════════════════════════════════════
//  SELECT ALL / CLEAR ALL
// ═══════════════════════════════════════════════════════════════
$('selectAllBtn').addEventListener('click', () => {
  allDistributors.forEach(d => selected.add(d.GUID));
  renderDistributorList();
  saveState();
});
$('clearAllBtn').addEventListener('click', () => {
  selected.clear();
  renderDistributorList();
  saveState();
});

// ═══════════════════════════════════════════════════════════════
//  SIDEBAR TOGGLE
// ═══════════════════════════════════════════════════════════════
const isMobile = () => window.matchMedia('(max-width: 700px)').matches;
$('sidebarToggle').addEventListener('click', () => {
  if (isMobile()) document.body.classList.toggle('side-open-mobile');
  else { document.body.classList.toggle('side-collapsed'); setTimeout(() => map.invalidateSize(), 260); }
});
$('sideBackdrop').addEventListener('click', () => document.body.classList.remove('side-open-mobile'));

// ═══════════════════════════════════════════════════════════════
//  INIT
// ═══════════════════════════════════════════════════════════════
(async function init() {
  applyLanguage();

  const saved = loadState();
  const today = new Date();
  const week  = new Date(); week.setDate(today.getDate() - 7);

  $('from').value = saved.from || fmtDate(week);
  $('to').value   = saved.to   || fmtDate(today);

  ['from','to'].forEach(id => {
    $(id).addEventListener('change', saveState);
    const el = $(id);
    el.addEventListener('click', () => {
      if (typeof el.showPicker === 'function') { try { el.showPicker(); } catch(e) {} }
    });
  });

  try {
    const data = await jsonFetch('api/distributors.php');
    allDistributors = data.distributors || [];

    // Restore previous selection
    if (Array.isArray(saved.selected)) {
      saved.selected.forEach(g => {
        if (allDistributors.some(d => d.GUID === g)) selected.add(g);
      });
    }

    renderDistributorList();
  } catch (e) {
    console.error('[init] failed:', e);
    __toast.error(t('error') + ': ' + e.message);
  }
})();
</script>
</body>
</html>