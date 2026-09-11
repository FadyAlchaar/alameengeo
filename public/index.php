<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<title>Alameen Tracker</title>

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
    --danger-hover: #dc2626;
    --success:      #10b981;
    --warning:      #f59e0b;
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

  /* ═══════════════════════════════════════════════════════════
     TOP HEADER BAR
     ═══════════════════════════════════════════════════════════ */
    #topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    padding-top: env(safe-area-inset-top, 0px);
    padding-left: max(12px, env(safe-area-inset-left, 0px));
    padding-right: max(12px, env(safe-area-inset-right, 0px));
    background: var(--bg-panel);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 2000;
  }
  #topbar .bar-title {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 14px;
    color: #fff;
    min-width: 0;
  }
  #topbar .bar-title .logo {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--accent), var(--purple));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
  }
  #topbar .bar-title .text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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
    transition: background .15s, border-color .15s;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .tb-btn:hover { background: var(--bg-dark); border-color: var(--accent); }
  .tb-btn.lang { min-width: 52px; justify-content: center; }
  .tb-btn .icon { font-size: 16px; line-height: 1; }

  /* ═══════════════════════════════════════════════════════════
     APP LAYOUT
     ═══════════════════════════════════════════════════════════ */
    #app {
    position: fixed;
    top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
  }

  /* ─────────── Sidebar ─────────── */
  #side {
    width: var(--sidebar-w);
    flex-shrink: 0;
    background: var(--bg-panel);
    color: var(--text);
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 16px;
    overflow-y: auto;
    border-inline-end: 1px solid var(--border);
    transition: transform .25s ease, margin .25s ease;
    z-index: 1500;
  }
  #side::-webkit-scrollbar { width: 8px; }
  #side::-webkit-scrollbar-track { background: var(--bg-panel); }
  #side::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

  body.side-collapsed #side { margin-inline-start: calc(-1 * var(--sidebar-w)); }

  /* ─── Coverage banner ─── */
  .coverage {
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 12px;
    line-height: 1.55;
    border-inline-start: 3px solid var(--accent);
    background: linear-gradient(135deg, rgba(30,58,138,.35), rgba(15,23,42,.9));
  }
  .coverage.warn   { border-inline-start-color: var(--warning);
                     background: linear-gradient(135deg, rgba(120,53,15,.4), rgba(15,23,42,.9)); }
  .coverage.danger { border-inline-start-color: var(--danger);
                     background: linear-gradient(135deg, rgba(127,29,29,.4), rgba(15,23,42,.9)); }
  .coverage .title {
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #93c5fd;
    font-weight: 700;
    margin-bottom: 4px;
  }
  .coverage.warn   .title { color: #fcd34d; }
  .coverage.danger .title { color: #fca5a5; }
  .coverage b      { color: #fff; }
  .coverage .sub   { color: var(--text-dim); font-size: 11px; margin-top: 3px; }

  /* ─── Fields ─── */
  .field { display: flex; flex-direction: column; gap: 6px; }
  .field label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-dim);
    font-weight: 600;
  }
  select, input[type="date"] {
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
    cursor: pointer;
    -webkit-appearance: none;
    appearance: none;
  }
  input[type="date"] {
    position: relative;
    padding-inline-end: 30px;
    background-image:
      linear-gradient(45deg, transparent 50%, var(--text-dim) 50%),
      linear-gradient(135deg, var(--text-dim) 50%, transparent 50%);
    background-position:
      calc(100% - 16px) center,
      calc(100% - 11px) center;
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
  }
  html[dir="rtl"] input[type="date"] {
    background-position:
      16px center,
      11px center;
  }
  input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: 0.9;
    cursor: pointer;
    filter: invert(0.7);
  }
  select:focus, input[type="date"]:focus { border-color: var(--accent); }

  .date-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

  /* ─── Buttons ─── */
  .btn-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 8px;
  }
  .btn {
    padding: 11px 14px;
    border-radius: 8px;
    border: none;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, opacity .15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }
  .btn-primary { background: var(--accent); color: #fff; }
  .btn-primary:hover:not(:disabled) { background: var(--accent-hover); }
  .btn-primary:disabled { opacity: .6; cursor: not-allowed; }

  .btn-reset {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-dim);
    padding: 0 14px;
    font-size: 13px;
  }
  .btn-reset:hover { border-color: var(--danger); color: var(--danger); }

  /* ─── Toggles ─── */
  .toggles {
    display: flex;
    gap: 14px;
    padding: 4px 0;
    flex-wrap: wrap;
  }
  .toggles label {
    display: flex; align-items: center; gap: 7px;
    font-size: 13px; color: var(--text);
    cursor: pointer; user-select: none;
  }
  .toggles input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: var(--accent);
    cursor: pointer;
  }

  /* ─── Panels ─── */
  .panel {
    background: var(--bg-dark);
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 13px;
    line-height: 1.7;
  }
  .panel-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: var(--text-dim);
    font-weight: 600;
    margin-bottom: 8px;
  }
  .stat-row { display: flex; justify-content: space-between; gap: 10px; }
  .stat-row b { color: #60a5fa; font-weight: 600; }
  .stat-warn  { color: var(--warning); font-size: 12px; margin-top: 8px; line-height: 1.5; }
  .stat-error { color: var(--danger);  font-size: 12px; margin-top: 6px; }

  /* ─── Playback ─── */
  .playback-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
  }
  .play-btn {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: var(--accent);
    border: none;
    color: #fff;
    font-size: 15px;
    cursor: pointer;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .15s, transform .1s;
    padding: 0;
    padding-inline-start: 3px;
  }
  .play-btn:hover { background: var(--accent-hover); }
  .play-btn.playing { padding-inline-start: 0; }
  .play-btn:active { transform: scale(.94); }

  #playSlider {
    flex: 1;
    accent-color: var(--amber);
    cursor: pointer;
    height: 20px;
  }

  .playback-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: var(--text-dim);
    margin-bottom: 8px;
    gap: 8px;
  }
  .playback-time {
    font-size: 15px;
    color: var(--amber);
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    letter-spacing: .5px;
  }
  .playback-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
  }
  #playSpeed {
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 4px;
    background: var(--bg-panel);
    border: 1px solid var(--border);
    color: var(--text);
    cursor: pointer;
    outline: none;
  }
  .playback-toggle {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: var(--text-dim);
    cursor: pointer;
    user-select: none;
    padding-top: 8px;
    border-top: 1px solid #334155;
    margin-top: 8px;
  }
  .playback-toggle input {
    width: 15px; height: 15px;
    accent-color: var(--accent);
    cursor: pointer;
  }

  /* ═══ Selected point crosshair (RED) ═══ */
  .selection-crosshair {
    background: transparent !important;
    border: none !important;
    pointer-events: none;
  }
  .selection-crosshair .ring,
  .selection-crosshair .ring-pulse {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid #ef4444;
  }
  .selection-crosshair .ring {
    box-shadow: 0 0 14px rgba(239,68,68,.75),
                inset 0 0 10px rgba(239,68,68,.4);
  }
  .selection-crosshair .ring-pulse {
    animation: xhPulse 1.6s ease-out infinite;
  }
  .selection-crosshair .arm {
    position: absolute;
    background: #ef4444;
    box-shadow: 0 0 4px rgba(0,0,0,.6);
    border-radius: 1px;
  }
  .selection-crosshair .arm.n { top: -14px;    left: 50%; transform: translateX(-50%); width: 2px; height: 14px; }
  .selection-crosshair .arm.s { bottom: -14px; left: 50%; transform: translateX(-50%); width: 2px; height: 14px; }
  .selection-crosshair .arm.e { right: -14px;  top: 50%; transform: translateY(-50%); width: 14px; height: 2px; }
  .selection-crosshair .arm.w { left: -14px;   top: 50%; transform: translateY(-50%); width: 14px; height: 2px; }
  @keyframes xhPulse {
    0%   { transform: scale(0.65); opacity: 1; }
    100% { transform: scale(1.85); opacity: 0; }
  }

  /* ─── Legend ─── */
  .legend { display: flex; flex-direction: column; gap: 5px; max-height: 180px; overflow-y: auto; }
  .legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text); }
  .legend-swatch { width: 14px; height: 3px; border-radius: 2px; flex-shrink: 0; }
  .legend-dot {
    width: 12px; height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
    border: 2px solid #fff;
  }
  .legend-heat {
    width: 14px; height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
    background: linear-gradient(90deg, #3b82f6, #22d3ee, #84cc16, #f59e0b, #ef4444);
  }
  .legend-trail {
    width: 14px; height: 3px;
    border-radius: 2px;
    flex-shrink: 0;
    background: var(--amber);
  }

  /* ─── Visits list ─── */
  .visit-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 320px;
    overflow-y: auto;
    padding-inline-end: 4px;
  }
  .visit-item {
    background: var(--bg-dark);
    border-radius: 8px;
    padding: 8px 11px;
    font-size: 12.5px;
    cursor: pointer;
    border-inline-start: 3px solid var(--warning);
    transition: background .15s;
  }
  .visit-item:hover { background: #1a2235; }
  .visit-item.source-customer { border-inline-start-color: var(--purple); }
  .visit-item.no-gps {
    border-inline-start-color: var(--border);
    opacity: .55;
    cursor: pointer;
  }
  .visit-item.no-gps:hover { opacity: .8; }
  .visit-item.revealed-pulse {
    animation: pulseVisit .6s ease-out;
  }
  @keyframes pulseVisit {
    0%   { background: rgba(251,191,36,.4); }
    100% { background: var(--bg-dark); }
  }
  .visit-item .name { font-weight: 600; color: var(--text); display: block; margin-bottom: 2px; }
  .visit-item .time { font-size: 11px; color: var(--text-dim); }
  .visit-tag { font-size: 10px; color: var(--danger); margin-inline-start: 4px; }
  .visit-tag.approx { color: var(--warning); }

  /* ─────────── Map ─────────── */
  #map { flex: 1; position: relative; min-width: 0; }

  #sidebarToggle {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--bg-panel);
    border: 1px solid var(--border);
    color: var(--text);
    cursor: pointer;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,.4);
    transition: background .15s, border-color .15s;
  }
  #sidebarToggle:hover { background: var(--bg-dark); border-color: var(--accent); }

  #sideBackdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 1400;
    display: none;
    backdrop-filter: blur(2px);
  }
  body.side-open-mobile #sideBackdrop { display: block; }

  /* ─── Leaflet popup ─── */
  .leaflet-popup-content {
    font-family: 'Cairo', sans-serif;
    margin: 12px 14px;
    font-size: 13px;
    min-width: 200px;
    direction: inherit;
  }
  .leaflet-popup-content b { color: #0f172a; font-size: 14px; }
  .popup-meta { color: #64748b; font-size: 11.5px; margin-top: 4px; }
  .popup-source {
    display: inline-block;
    margin-top: 6px;
    font-size: 10.5px;
    padding: 2px 7px;
    border-radius: 4px;
    font-weight: 600;
  }
  .popup-source.customer { background: #ede9fe; color: #6d28d9; }
  .popup-source.route    { background: #fef3c7; color: #b45309; }
  .popup-link {
    display: inline-block;
    margin-top: 8px;
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    font-size: 12.5px;
  }
  .popup-link:hover { text-decoration: underline; }

  .vehicle-marker {
    background: transparent !important;
    border: none !important;
  }
  .vehicle-inner {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--amber), #f59e0b);
    border: 3px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    box-shadow: 0 3px 10px rgba(0,0,0,.55);
    animation: pulseVehicle 1.8s ease-in-out infinite;
  }
  @keyframes pulseVehicle {
    0%, 100% { box-shadow: 0 3px 10px rgba(0,0,0,.55), 0 0 0 0 rgba(251,191,36,.6); }
    50%      { box-shadow: 0 3px 10px rgba(0,0,0,.55), 0 0 0 12px rgba(251,191,36,0); }
  }

  /* ─── Loading ─── */
  .loading-overlay {
    position: absolute; inset: 0;
    background: rgba(15, 23, 42, .4);
    display: none;
    align-items: center; justify-content: center;
    z-index: 1000;
    pointer-events: none;
  }
  .loading-overlay.active { display: flex; }
  .spinner {
    width: 44px; height: 44px;
    border: 4px solid rgba(255, 255, 255, .2);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin .8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ─── Heat legend ─── */
  .heat-legend {
    position: absolute;
    inset-inline-end: 18px;
    bottom: 26px;
    background: rgba(15, 23, 42, .9);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 12px;
    z-index: 500;
    display: none;
    font-size: 11px;
    color: var(--text);
    min-width: 170px;
  }
  .heat-legend.active { display: block; }
  .heat-legend .title {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: var(--text-dim);
    font-weight: 700;
    margin-bottom: 6px;
  }
  .heat-gradient {
    height: 10px;
    border-radius: 5px;
    background: linear-gradient(90deg, #3b82f6, #22d3ee, #84cc16, #f59e0b, #ef4444);
    margin-bottom: 4px;
  }
  .heat-labels {
    display: flex;
    justify-content: space-between;
    color: var(--text-dim);
    font-size: 10px;
  }

  /* ═══════════════════════════════════════════════════════════
     RESPONSIVE
     ═══════════════════════════════════════════════════════════ */
  @media (max-width: 900px) {
    :root { --sidebar-w: 320px; }
  }

  @media (max-width: 700px) {
    :root { --sidebar-w: min(86vw, 340px); }

    #side {
      position: fixed;
      top: calc(var(--header-h) + env(safe-area-inset-top, 0px));
      bottom: 0;
      inset-inline-start: 0;
      transform: translateX(0);
      box-shadow: 4px 0 18px rgba(0,0,0,.5);
    }
    html[dir="rtl"] #side {
      box-shadow: -4px 0 18px rgba(0,0,0,.5);
    }

    body:not(.side-open-mobile) #side {
      transform: translateX(-105%);
    }
    html[dir="rtl"] body:not(.side-open-mobile) #side {
      transform: translateX(105%);
    }

    body.side-collapsed #side { margin-inline-start: 0; }

    .toggles { gap: 18px; }
    .toggles label { font-size: 14px; }
    .toggles input[type="checkbox"] { width: 18px; height: 18px; }

    .btn { padding: 12px 14px; font-size: 15px; }
    select, input[type="date"] { padding: 11px 12px; font-size: 15px; }

    .visit-list { max-height: 260px; }
  }

  @media (max-width: 380px) {
    #topbar .bar-title .text { font-size: 13px; }
    .tb-btn { padding: 6px 8px; font-size: 12px; }
  }
</style>
</head>
<body>

<div id="topbar">
  <div class="bar-title">
    <div class="logo">🚚</div>
    <div class="text" data-i18n="appTitle">Alameen Tracker</div>
  </div>
  <button class="tb-btn lang" id="langBtn" title="Language">
    <span class="icon">🌐</span>
    <span id="langLabel">ع</span>
  </button>
</div>

<div id="app">

  <aside id="side">

    <div class="coverage" id="coverageBanner" style="display:none;">
      <div class="title" id="coverageTitle" data-i18n="coverageTitle">Data Coverage</div>
      <div id="coverageText">…</div>
    </div>

    <form id="filterForm">
      <div class="field">
        <label for="distributor" data-i18n="distributor">Distributor</label>
        <select id="distributor"></select>
      </div>

      <div class="date-row" style="margin-top:12px">
        <div class="field">
          <label for="from" data-i18n="from">From</label>
          <input type="date" id="from">
        </div>
        <div class="field">
          <label for="to" data-i18n="to">To</label>
          <input type="date" id="to">
        </div>
      </div>

      <div class="toggles" style="margin-top:12px">
        <label><input type="checkbox" id="showRoute" checked> <span data-i18n="showRoute">Route</span></label>
        <label><input type="checkbox" id="showVisits" checked> <span data-i18n="showVisits">Visits</span></label>
        <label><input type="checkbox" id="showHeat"> <span data-i18n="showHeat">Heat map</span></label>
      </div>

      <div class="btn-row" style="margin-top:12px">
        <button type="submit" class="btn btn-primary" id="loadBtn" data-i18n="load">Load</button>
        <button type="button" class="btn btn-reset" id="resetBtn" title="Clear filters" data-i18n="reset">Reset</button>
      </div>
    </form>

    <div class="panel" id="playbackPanel" style="display:none;">
      <div class="panel-title"><span data-i18n="playback">Playback</span></div>

      <div class="playback-controls">
        <button class="play-btn" id="playBtn" title="Play / Pause">▶</button>
        <input type="range" id="playSlider" min="0" max="0" value="0" step="1">
      </div>

      <div class="playback-info">
        <span class="playback-time" id="playTime">--:--</span>
        <div class="playback-meta">
          <span id="playProgress">0 / 0</span>
          <select id="playSpeed" title="Speed">
            <option value="0.5">0.5×</option>
            <option value="1" selected>1×</option>
            <option value="2">2×</option>
            <option value="4">4×</option>
            <option value="8">8×</option>
          </select>
        </div>
      </div>

      <label class="playback-toggle">
        <input type="checkbox" id="playRevealVisits" checked>
        <span data-i18n="revealVisits">Reveal visits as time advances</span>
      </label>
    </div>

    <div class="panel" id="stats" data-i18n="noData">No data loaded.</div>

    <div class="panel" id="legendPanel" style="display:none;">
      <div class="panel-title" data-i18n="daysInView">Days in view</div>
      <div class="legend" id="legend"></div>
      <div style="margin-top:10px;padding-top:8px;border-top:1px solid #334155">
        <div class="legend-item">
          <span class="legend-dot" style="background:#8b5cf6"></span>
          <span data-i18n="visitCustomer">Visit (customer GPS)</span>
        </div>
        <div class="legend-item">
          <span class="legend-dot" style="background:#f59e0b"></span>
          <span data-i18n="visitNear">Visit (near route point)</span>
        </div>
        <div class="legend-item">
          <span class="legend-trail"></span>
          <span data-i18n="playbackTrail">Playback trail</span>
        </div>
        <div class="legend-item">
          <span class="legend-heat"></span>
          <span data-i18n="gpsDensity">GPS density (heat)</span>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-title">
        <span data-i18n="visits">Visits</span> (<span id="visitCount">0</span>)
      </div>
      <div class="visit-list" id="visitList"></div>
    </div>

  </aside>

  <div id="map">
    <button id="sidebarToggle" title="Toggle sidebar">☰</button>
    <div class="loading-overlay" id="loading">
      <div class="spinner"></div>
    </div>
    <div class="heat-legend" id="heatLegend">
      <div class="title" data-i18n="gpsDensity">GPS density</div>
      <div class="heat-gradient"></div>
      <div class="heat-labels">
        <span data-i18n="low">Low</span>
        <span data-i18n="high">High</span>
      </div>
    </div>
  </div>

  <div id="sideBackdrop"></div>
</div>

<script src="vendor/leaflet.js"></script>
<script src="vendor/leaflet.markercluster.js"></script>
<script src="vendor/leaflet-heat.js"></script>

<script>
// ═══════════════════════════════════════════════════════════════
//  CONFIG
// ═══════════════════════════════════════════════════════════════
const SWAP_ROUTE = false;
const SWAP_VISIT = false;

const DAY_COLORS = [
  '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b',
  '#10b981', '#ef4444', '#06b6d4', '#a855f7',
  '#84cc16', '#f97316', '#14b8a6', '#6366f1',
];

const FALLBACK_CENTER = [35.52, 35.78];
const FALLBACK_ZOOM   = 11;

const HEAT_RADIUS = 22;
const HEAT_BLUR   = 18;
const HEAT_MAX    = 1.0;
const HEAT_GRADIENT = {
  0.2: '#3b82f6',
  0.4: '#22d3ee',
  0.6: '#84cc16',
  0.8: '#f59e0b',
  1.0: '#ef4444',
};

const PLAYBACK_TARGET_MS = 40000;
const PLAYBACK_MIN_MS    = 20;
const PLAYBACK_MAX_MS    = 600;

const STORAGE_KEY = 'alameen_tracker_state_v1';

// ═══════════════════════════════════════════════════════════════
//  i18n
// ═══════════════════════════════════════════════════════════════
const I18N = {
  en: {
    appTitle:        'Alameen Tracker',
    coverageTitle:   'Data Coverage',
    distributor:     'Distributor',
    from:            'From',
    to:              'To',
    showRoute:       'Route',
    showVisits:      'Visits',
    showHeat:        'Heat map',
    load:            'Load',
    reset:           'Reset',
    playback:        '▶ Playback',
    revealVisits:    'Reveal visits as time advances',
    daysInView:      'Days in view',
    visitCustomer:   'Visit (customer GPS)',
    visitNear:       'Visit (near route point)',
    playbackTrail:   'Playback trail',
    gpsDensity:      'GPS density (heat)',
    visits:          'Visits',
    low:             'Low',
    high:            'High',
    noData:          'No data loaded.',
    loading:         'Loading…',
    initError:       'Init error',
    errorLabel:      'Error',
    selectBoth:      'Please select a distributor and both dates.',
    fromAfterTo:     '"From" must be before or equal to "To".',
    gpsPoints:       'GPS points',
    distance:        'Distance',
    days:            'Days',
    onMap:           'On map',
    customerGps:     'Customer GPS',
    noDataRange:     'No data for this range.',
    noDataRangeSub:  'Either this distributor has never sent GPS, or there are no visits in the chosen dates.',
    visitsOnlyTitle: 'Visits only — no GPS trace.',
    visitsOnlySub:   'The visits below can still be opened from the list, but no route is available.',
    noCoords:        'Visits exist but none have usable coordinates.',
    thinned:         'Route thinned for display: showing',
    thinnedMid:      'of',
    thinnedEnd:      'points. Distance & stats are exact.',
    noGpsIn:         'No visits in this range.',
    firstGps:        '🚚 First GPS point',
    lastGps:         '🏁 Last GPS point',
    viewDetails:     'View details →',
    storedGps:       '📍 Stored GPS',
    nearRoute:       '📍 Near route point',
    noGpsTag:        '(no GPS)',
    approxTag:       '(approx)',
    unknown:         '(unknown)',
    coverageEmpty:   'No GPS points recorded in the database.',
    coverageEmptySub:'The tracking pipeline has never produced data.',
    gpsPointsWord:   'GPS points',
    distributorsWord:'distributors',
    noNewGpsDays:    '⚠ No new GPS data for',
    daysWord:        'days',
    dayWord:         'day',
    noNewGpsDaysSm:  'No new GPS data for',
    staleSuffix:     '.',
    pickDate:        'Pick a date',
  },
  ar: {
    appTitle:        'متتبع الموزع',
    coverageTitle:   'تغطية البيانات',
    distributor:     'الموزّع / المندوب',
    from:            'من',
    to:              'إلى',
    showRoute:       'المسار',
    showVisits:      'الزيارات',
    showHeat:        'الخريطة الحرارية',
    load:            'تحميل',
    reset:           'تفريغ',
    playback:        '▶ إعادة التشغيل',
    revealVisits:    'إظهار الزيارات مع تقدّم الوقت',
    daysInView:      'الأيام المعروضة',
    visitCustomer:   'زيارة (إحداثيات العميل)',
    visitNear:       'زيارة (أقرب نقطة على المسار)',
    playbackTrail:   'أثر إعادة التشغيل',
    gpsDensity:      'كثافة الإحداثيات',
    visits:          'الزيارات',
    low:             'منخفض',
    high:            'مرتفع',
    noData:          'لا توجد بيانات محمّلة.',
    loading:         'جارٍ التحميل…',
    initError:       'خطأ في التهيئة',
    errorLabel:      'خطأ',
    selectBoth:      'الرجاء اختيار المندوب وكلا التاريخين.',
    fromAfterTo:     'يجب أن يكون تاريخ "من" قبل أو يساوي تاريخ "إلى".',
    gpsPoints:       'نقاط GPS',
    distance:        'المسافة',
    days:            'الأيام',
    onMap:           'على الخريطة',
    customerGps:     'إحداثيات العملاء',
    noDataRange:     'لا توجد بيانات ضمن هذا النطاق.',
    noDataRangeSub:  'إما أن هذا المندوب لم يرسل أي بيانات GPS، أو لا توجد زيارات في التواريخ المحدّدة.',
    visitsOnlyTitle: 'زيارات فقط — بدون مسار GPS.',
    visitsOnlySub:   'يمكن فتح الزيارات أدناه من القائمة، لكن لا يتوفّر مسار.',
    noCoords:        'هناك زيارات لكن لا توجد إحداثيات قابلة للاستخدام.',
    thinned:         'تم تخفيف المسار للعرض: يُعرض',
    thinnedMid:      'من أصل',
    thinnedEnd:      'نقطة. المسافة والإحصاءات دقيقة.',
    noGpsIn:         'لا توجد زيارات ضمن هذا النطاق.',
    firstGps:        '🚚 أول نقطة GPS',
    lastGps:         '🏁 آخر نقطة GPS',
    viewDetails:     'عرض التفاصيل ←',
    storedGps:       '📍 إحداثيات مخزّنة',
    nearRoute:       '📍 نقطة قريبة على المسار',
    noGpsTag:        '(بدون GPS)',
    approxTag:       '(تقريبي)',
    unknown:         '(غير معروف)',
    coverageEmpty:   'لا توجد نقاط GPS مسجّلة في قاعدة البيانات.',
    coverageEmptySub:'لم ينتج نظام التتبّع أي بيانات بعد.',
    gpsPointsWord:   'نقطة GPS',
    distributorsWord:'موزّع',
    noNewGpsDays:    '⚠ لا بيانات GPS جديدة منذ',
    daysWord:        'يومًا',
    dayWord:         'يوم',
    noNewGpsDaysSm:  'لا بيانات GPS جديدة منذ',
    staleSuffix:     '.',
    pickDate:        'اختر تاريخًا',
  },
};

let LANG = localStorage.getItem('alameen_lang') || 'ar';
function t(key) { return (I18N[LANG] && I18N[LANG][key]) || I18N.en[key] || key; }

function applyLanguage() {
  document.documentElement.lang = LANG;
  document.documentElement.dir  = LANG === 'ar' ? 'rtl' : 'ltr';

  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    el.textContent = t(key);
  });

  document.getElementById('langLabel').textContent = LANG === 'ar' ? 'EN' : 'ع';

  ['from', 'to'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.title = t('pickDate');
  });
}

// ═══════════════════════════════════════════════════════════════
//  MAP
// ═══════════════════════════════════════════════════════════════
const map = L.map('map', { zoomControl: true }).setView(FALLBACK_CENTER, FALLBACK_ZOOM);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

const routeLayer = L.layerGroup().addTo(map);
const trailLayer = L.layerGroup().addTo(map);
const visitLayer = L.markerClusterGroup({
  maxClusterRadius: 45,
  showCoverageOnHover: false,
  iconCreateFunction: function(cluster) {
    const count = cluster.getChildCount();
    return L.divIcon({
      html: `<div style="
        width:36px;height:36px;border-radius:50%;
        background:rgba(245,158,11,.9);border:3px solid #fff;
        color:#fff;font-weight:700;font-size:13px;
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 2px 6px rgba(0,0,0,.4);
      ">${count}</div>`,
      className: 'visit-cluster',
      iconSize: [36, 36],
    });
  },
}).addTo(map);

let heatLayer = null;
let currentVisitMarkers = {};
let selectionMarker = null;          // ← crosshair marker (bug-fix)

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

const playback = {
  points: [],
  visits: [],
  index: 0,
  playing: false,
  timer: null,
  speed: 1,
  marker: null,
  trail: null,
  baseIntervalMs: 400,
};

// ═══════════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════════
const $ = id => document.getElementById(id);

function fmtDate(d) { return d.toISOString().slice(0, 10); }

async function jsonFetch(url) {
  const r    = await fetch(url);
  const text = await r.text();
  let data;
  try { data = JSON.parse(text); }
  catch (e) { throw new Error('Invalid JSON from ' + url + ': ' + text.slice(0, 200)); }
  if (!r.ok || data.success === false) throw new Error(data.error || ('HTTP ' + r.status));
  return data;
}

function toNum(v) {
  if (v === null || v === undefined) return NaN;
  const cleaned = String(v).replace(/[\s\u00A0,]/g, '');
  const n = Number(cleaned);
  return Number.isFinite(n) ? n : NaN;
}

function isValidLatLon(lat, lon) {
  if (!Number.isFinite(lat) || !Number.isFinite(lon)) return false;
  if (lat < -90  || lat > 90)  return false;
  if (lon < -180 || lon > 180) return false;
  if (lat === 0 && lon === 0)  return false;
  return true;
}

function haversine([lat1, lon1], [lat2, lon2]) {
  const R    = 6371;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a    = Math.sin(dLat / 2) ** 2 +
               Math.cos(lat1 * Math.PI / 180) *
               Math.cos(lat2 * Math.PI / 180) *
               Math.sin(dLon / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function escapeHtml(s) {
  if (s === null || s === undefined) return '';
  return String(s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function daysSince(dateStr) {
  if (!dateStr) return null;
  const d = new Date(dateStr.replace(' ', 'T'));
  if (isNaN(d)) return null;
  return Math.floor((Date.now() - d.getTime()) / 86400000);
}

function timeMs(str) {
  if (!str) return NaN;
  return new Date(String(str).replace(' ', 'T')).getTime();
}

function formatClock(str) {
  const s = String(str || '');
  const m = s.match(/\d{2}:\d{2}/);
  return m ? m[0] : '--:--';
}

// ═══════════════════════════════════════════════════════════════
//  STATE PERSISTENCE
// ═══════════════════════════════════════════════════════════════
function saveState(patch = {}) {
  try {
    const cur = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    const next = { ...cur, ...patch, ts: Date.now() };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  } catch (e) { /* ignore */ }
}

function loadState() {
  try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
  catch (e) { return {}; }
}

function clearState() {
  try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
}

// ═══════════════════════════════════════════════════════════════
//  LANGUAGE
// ═══════════════════════════════════════════════════════════════
$('langBtn').addEventListener('click', () => {
  LANG = LANG === 'ar' ? 'en' : 'ar';
  localStorage.setItem('alameen_lang', LANG);
  applyLanguage();
  saveState({ lang: LANG });
});

// ═══════════════════════════════════════════════════════════════
//  DATE PICKER
// ═══════════════════════════════════════════════════════════════
['from', 'to'].forEach(id => {
  const el = $(id);
  el.addEventListener('click', () => {
    if (typeof el.showPicker === 'function') {
      try { el.showPicker(); } catch (e) { /* ignore */ }
    }
  });
});

// ═══════════════════════════════════════════════════════════════
//  SIDEBAR TOGGLE
// ═══════════════════════════════════════════════════════════════
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
//  INIT
// ═══════════════════════════════════════════════════════════════
(async function init() {
  applyLanguage();

  const saved = loadState();
  const today = new Date();
  const week  = new Date(); week.setDate(today.getDate() - 7);
  $('from').value = saved.from || fmtDate(week);
  $('to').value   = saved.to   || fmtDate(today);
  if (saved.showRoute  !== undefined) $('showRoute').checked  = !!saved.showRoute;
  if (saved.showVisits !== undefined) $('showVisits').checked = !!saved.showVisits;
  if (saved.showHeat   !== undefined) $('showHeat').checked   = !!saved.showHeat;

  try {
    const [distData, covData] = await Promise.all([
      jsonFetch('api/distributors.php'),
      jsonFetch('api/coverage.php'),
    ]);

    const sel = $('distributor');
    if (!distData.distributors || distData.distributors.length === 0) {
      sel.innerHTML = '<option value="">(no distributors)</option>';
    } else {
      sel.innerHTML = distData.distributors.map(d =>
        `<option value="${escapeHtml(d.GUID)}">${escapeHtml(d.Code)} — ${escapeHtml(d.Name)}</option>`
      ).join('');

      if (saved.distributor) {
        const found = [...sel.options].some(o => o.value === saved.distributor);
        if (found) sel.value = saved.distributor;
      }
    }

    renderCoverage(covData);

    if (saved.distributor && saved.from && saved.to && sel.value) {
      setTimeout(() => load(true), 250);
    }

  } catch (e) {
    console.error('[init] failed:', e);
    $('stats').innerHTML =
      `<div class="stat-error"><b>${t('initError')}:</b><br>${escapeHtml(e.message)}</div>`;
  }
})();

// ═══════════════════════════════════════════════════════════════
//  COVERAGE BANNER
// ═══════════════════════════════════════════════════════════════
function renderCoverage(cov) {
  const banner = $('coverageBanner');
  banner.style.display = '';

  if (!cov.total_points || cov.total_points === 0) {
    banner.className = 'coverage danger';
    $('coverageTitle').textContent = t('coverageTitle');
    $('coverageText').innerHTML =
      `<b>${t('coverageEmpty')}</b>
       <div class="sub">${t('coverageEmptySub')}</div>`;
    return;
  }

  const lastDate  = (cov.last_gps || '').slice(0, 10);
  const firstDate = (cov.first_gps || '').slice(0, 10);
  const gap       = daysSince(cov.last_gps);

  let cls = 'coverage';
  let gapLine = '';
  if (gap !== null && gap > 60) {
    cls = 'coverage warn';
    gapLine = `<div class="sub">${t('noNewGpsDays')} <b>${gap} ${t('daysWord')}</b>.</div>`;
  } else if (gap !== null && gap > 14) {
    cls = 'coverage warn';
    gapLine = `<div class="sub">${t('noNewGpsDaysSm')} ${gap} ${t('daysWord')}.</div>`;
  }

  banner.className = cls;
  $('coverageTitle').textContent = t('coverageTitle');
  $('coverageText').innerHTML = `
    <b>${cov.total_points.toLocaleString()}</b> ${t('gpsPointsWord')}
    &middot; <b>${cov.distributors_with_data}</b>/${cov.distributors_total} ${t('distributorsWord')}
    <div class="sub">${escapeHtml(firstDate)} → ${escapeHtml(lastDate)}</div>
    ${gapLine}
  `;
}

// ═══════════════════════════════════════════════════════════════
//  FORM
// ═══════════════════════════════════════════════════════════════
$('filterForm').addEventListener('submit', (e) => {
  e.preventDefault();
  load(false);
});

$('resetBtn').addEventListener('click', () => {
  stopPlayback();
  teardownPlayback();

  routeLayer.clearLayers();
  trailLayer.clearLayers();
  visitLayer.clearLayers();
  if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }
  currentVisitMarkers = {};
  clearCrosshair();

  const today = new Date();
  const week  = new Date(); week.setDate(today.getDate() - 7);
  $('from').value = fmtDate(week);
  $('to').value   = fmtDate(today);
  $('showRoute').checked  = true;
  $('showVisits').checked = true;
  $('showHeat').checked   = false;

  $('stats').innerHTML    = t('noData');
  $('legend').innerHTML   = '';
  $('legendPanel').style.display = 'none';
  $('playbackPanel').style.display = 'none';
  $('visitList').innerHTML = '';
  $('visitCount').textContent = '0';
  $('heatLegend').classList.remove('active');

  map.setView(FALLBACK_CENTER, FALLBACK_ZOOM);

  clearState();
  saveState({ lang: LANG });
});

['distributor', 'from', 'to'].forEach(id => {
  $(id).addEventListener('change', () => {
    saveState({
      distributor: $('distributor').value,
      from: $('from').value,
      to:   $('to').value,
    });
  });
});
['showRoute', 'showVisits', 'showHeat'].forEach(id => {
  $(id).addEventListener('change', () => {
    saveState({
      showRoute:  $('showRoute').checked,
      showVisits: $('showVisits').checked,
      showHeat:   $('showHeat').checked,
    });
  });
});

// ═══════════════════════════════════════════════════════════════
//  LOAD
// ═══════════════════════════════════════════════════════════════
async function load(isAuto) {
  const distributor = $('distributor').value;
  const from        = $('from').value;
  const to          = $('to').value;

  if (!distributor || !from || !to) {
    $('stats').innerHTML = `<div class="stat-warn">${t('selectBoth')}</div>`;
    return;
  }
  if (from > to) {
    $('stats').innerHTML = `<div class="stat-error">${t('fromAfterTo')}</div>`;
    return;
  }

  saveState({ distributor, from, to });

  stopPlayback();
  teardownPlayback();

  setLoading(true);
  $('loadBtn').disabled = true;
  $('loadBtn').textContent = t('loading');

  routeLayer.clearLayers();
  trailLayer.clearLayers();
  visitLayer.clearLayers();
  if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }
  clearCrosshair();
  currentVisitMarkers = {};
  $('visitList').innerHTML   = '';
  $('legend').innerHTML      = '';
  $('legendPanel').style.display = 'none';
  $('heatLegend').classList.remove('active');
  $('stats').innerHTML       = `<div>${t('loading')}</div>`;

  try {
    const qs = `?distributor=${encodeURIComponent(distributor)}&from=${from}&to=${to}`;
    const [route, visits] = await Promise.all([
      jsonFetch('api/route.php'  + qs),
      jsonFetch('api/visits.php' + qs),
    ]);

    const routePoints = (route.points || [])
      .map(p => {
        const rawLat = SWAP_ROUTE ? p.Lon : p.Lat;
        const rawLon = SWAP_ROUTE ? p.Lat : p.Lon;
        return { lat: toNum(rawLat), lon: toNum(rawLon), time: p.LogTime };
      })
      .filter(p => isValidLatLon(p.lat, p.lon));

    routePoints.sort((a, b) => timeMs(a.time) - timeMs(b.time));

    const daysMap = new Map();
    for (const p of routePoints) {
      const day = String(p.time).slice(0, 10);
      if (!daysMap.has(day)) daysMap.set(day, []);
      daysMap.get(day).push(p);
    }
    const sortedDays = [...daysMap.keys()].sort();

    const serverDayMap = new Map();
    if (Array.isArray(route.day_stats)) {
      route.day_stats.forEach(d => serverDayMap.set(d.day, d));
    }

    let totalKm = 0;
    const dayStats = [];

    sortedDays.forEach((day, idx) => {
      const pts   = daysMap.get(day).map(p => [p.lat, p.lon]);
      const color = DAY_COLORS[idx % DAY_COLORS.length];

      if (pts.length > 1) {
        L.polyline(pts, {
          color, weight: 3.5, opacity: .9,
          lineJoin: 'round', lineCap: 'round',
        }).addTo(routeLayer);
      }

      const srv = serverDayMap.get(day);
      let dayKm;
      if (srv) dayKm = srv.km;
      else {
        dayKm = 0;
        for (let i = 1; i < pts.length; i++) dayKm += haversine(pts[i - 1], pts[i]);
      }
      totalKm += dayKm;
      dayStats.push({ day, count: pts.length, km: dayKm, color });
    });

    if (typeof route.distance_km === 'number') totalKm = route.distance_km;

    if (routePoints.length > 0) {
      const first = routePoints[0];
      const last  = routePoints[routePoints.length - 1];

      L.circleMarker([first.lat, first.lon], {
        radius: 8, color: '#16a34a', fillColor: '#16a34a',
        fillOpacity: 1, weight: 3,
      }).bindPopup(
        `<b>${t('firstGps')}</b><div class="popup-meta">${escapeHtml(first.time)}</div>`
      ).addTo(routeLayer);

      L.circleMarker([last.lat, last.lon], {
        radius: 8, color: '#dc2626', fillColor: '#dc2626',
        fillOpacity: 1, weight: 3,
      }).bindPopup(
        `<b>${t('lastGps')}</b><div class="popup-meta">${escapeHtml(last.time)}</div>`
      ).addTo(routeLayer);
    }

    if ($('showHeat').checked && routePoints.length > 0) {
      const heatPoints = routePoints.map(p => [p.lat, p.lon, 0.6]);
      heatLayer = L.heatLayer(heatPoints, {
        radius:  HEAT_RADIUS,
        blur:    HEAT_BLUR,
        maxZoom: 17,
        max:     HEAT_MAX,
        minOpacity: 0.3,
        gradient: HEAT_GRADIENT,
      }).addTo(map);
      $('heatLegend').classList.add('active');
    }

    const visitList = [];
    let visitsWithGps = 0;
    let visitsCustomerGps = 0;
    const playbackVisits = [];

    (visits.visits || []).forEach(v => {
      const rawX = SWAP_VISIT ? v.GPSY : v.GPSX;
      const rawY = SWAP_VISIT ? v.GPSX : v.GPSY;
      const lat  = toNum(rawY);
      const lon  = toNum(rawX);
      const hasGps = isValidLatLon(lat, lon);
      const source = v.GPSSource || (hasGps ? 'route_near' : null);

      let marker = null;
      if (hasGps) {
        let color;
        switch (source) {
          case 'customer':   color = '#8b5cf6'; break;
          case 'route_near': color = '#f59e0b'; break;
          default:           color = '#f59e0b';
        }

        marker = L.marker([lat, lon], {
          icon: L.divIcon({
            className: 'visit-marker',
            html: `<div style="
              width:16px;height:16px;border-radius:50%;
              background:${color};border:3px solid #fff;
              box-shadow:0 1px 4px rgba(0,0,0,.5);
            "></div>`,
            iconSize: [16, 16],
            iconAnchor: [8, 8],
          }),
        }).bindPopup(popupHtml(v, source));

        visitLayer.addLayer(marker);
        currentVisitMarkers[v.VisitGUID] = marker;
        visitsWithGps++;
        if (source === 'customer') visitsCustomerGps++;
      }

      visitList.push({ v, hasGps, lat, lon, source });

      playbackVisits.push({
        timeMs:  timeMs(v.StartTime),
        guid:    v.VisitGUID,
        marker:  marker,
        hasGps:  hasGps,
      });
    });

    playbackVisits.sort((a, b) => a.timeMs - b.timeMs);

    renderVisitList(visitList);

    const bounds = [];
    routePoints.forEach(p => bounds.push([p.lat, p.lon]));
    visitList.forEach(({ hasGps, lat, lon }) => { if (hasGps) bounds.push([lat, lon]); });

    if (bounds.length > 0) map.fitBounds(bounds, { padding: [40, 40] });
    else                   map.setView(FALLBACK_CENTER, FALLBACK_ZOOM);

    const routeEmpty  = routePoints.length === 0;
    const visitsEmpty = (visits.visits || []).length === 0;

    let statsHtml = `
      <div class="stat-row"><span>${t('gpsPoints')}</span><b>${routePoints.length}</b></div>
      <div class="stat-row"><span>${t('distance')}</span><b>${totalKm.toFixed(2)} km</b></div>
      <div class="stat-row"><span>${t('days')}</span><b>${sortedDays.length}</b></div>
      <div class="stat-row"><span>${t('visits')}</span><b>${visits.count || 0}</b></div>
      <div class="stat-row"><span>${t('onMap')}</span><b>${visitsWithGps}</b></div>
      <div class="stat-row"><span>${t('customerGps')}</span><b>${visitsCustomerGps}</b></div>
    `;

    if (routeEmpty && visitsEmpty) {
      statsHtml += `<div class="stat-warn">
        <b>${t('noDataRange')}</b><br>${t('noDataRangeSub')}
      </div>`;
    } else if (routeEmpty && !visitsEmpty) {
      statsHtml += `<div class="stat-warn">
        <b>${t('visitsOnlyTitle')}</b><br>${t('visitsOnlySub')}
      </div>`;
    } else if (!routeEmpty && visitsWithGps === 0 && !visitsEmpty) {
      statsHtml += `<div class="stat-warn">${t('noCoords')}</div>`;
    }

    if (route.decimated) {
      statsHtml += `<div class="stat-warn">${t('thinned')} <b>${route.count}</b> ${t('thinnedMid')} <b>${route.total}</b> ${t('thinnedEnd')}</div>`;
    }

    $('stats').innerHTML = statsHtml;
    $('visitCount').textContent = visits.count || 0;

    if (dayStats.length > 0) {
      $('legend').innerHTML = dayStats.map(d => `
        <div class="legend-item">
          <span class="legend-swatch" style="background:${d.color}"></span>
          <span>${escapeHtml(d.day)}</span>
          <span style="margin-inline-start:auto;color:#94a3b8;font-size:11px">
            ${d.count} pts · ${d.km.toFixed(1)} km
          </span>
        </div>
      `).join('');
      $('legendPanel').style.display = '';
    }

    setupPlayback(routePoints, playbackVisits);

    if (isMobile() && document.body.classList.contains('side-open-mobile')) {
      document.body.classList.remove('side-open-mobile');
    }

  } catch (e) {
    console.error('[load] failed:', e);
    $('stats').innerHTML =
      `<div class="stat-error"><b>${t('errorLabel')}:</b><br>${escapeHtml(e.message)}</div>`;
  } finally {
    setLoading(false);
    $('loadBtn').disabled = false;
    $('loadBtn').textContent = t('load');
  }
}

// ═══════════════════════════════════════════════════════════════
//  VISIT LIST
// ═══════════════════════════════════════════════════════════════
function renderVisitList(list) {
  const box = $('visitList');
  if (list.length === 0) {
    box.innerHTML = `<div style="color:#94a3b8;font-size:12px">${t('noGpsIn')}</div>`;
    return;
  }
  box.innerHTML = '';

  list.forEach(({ v, hasGps, lat, lon, source }) => {
    const el = document.createElement('div');
    let cls = 'visit-item';
    if (!hasGps) cls += ' no-gps';
    else if (source === 'customer') cls += ' source-customer';
    el.className = cls;
    el.dataset.visitGuid = v.VisitGUID;

    const time = (v.StartTime || '').slice(0, 16);

    let tag = '';
    if (!hasGps)                     tag = `<span class="visit-tag">${t('noGpsTag')}</span>`;
    else if (source === 'route_near') tag = `<span class="visit-tag approx">${t('approxTag')}</span>`;

    el.innerHTML = `
      <span class="name">${escapeHtml(v.CustomerName || t('unknown'))}</span>
      <span class="time">${escapeHtml(time)}</span>
      ${tag}
    `;

    el.addEventListener('click', () => {
      if (hasGps) {
        map.setView([lat, lon], 16);
        showCrosshair(lat, lon);
        const m = currentVisitMarkers[v.VisitGUID];
        if (m) m.openPopup();
        if (isMobile()) document.body.classList.remove('side-open-mobile');
      } else {
        window.open('customer.php?id=' + encodeURIComponent(v.CustomerGUID || ''), '_blank');
      }
    });

    box.appendChild(el);
  });
}

// ═══════════════════════════════════════════════════════════════
//  POPUP
// ═══════════════════════════════════════════════════════════════
function popupHtml(v, source) {
  const name = escapeHtml(v.CustomerName || t('unknown'));
  const time = escapeHtml((v.StartTime || '').slice(0, 16));
  const id   = encodeURIComponent(v.CustomerGUID || '');

  let sourceBadge = '';
  if (source === 'customer')        sourceBadge = `<span class="popup-source customer">${t('storedGps')}</span>`;
  else if (source === 'route_near') sourceBadge = `<span class="popup-source route">${t('nearRoute')}</span>`;

  return `
    <b>${name}</b>
    <div class="popup-meta">🕒 ${time}</div>
    ${sourceBadge}
    <a class="popup-link" href="customer.php?id=${id}" target="_blank">
      ${t('viewDetails')}
    </a>
  `;
}

// ═══════════════════════════════════════════════════════════════
//  PLAYBACK
// ═══════════════════════════════════════════════════════════════
function setupPlayback(points, visits) {
  teardownPlayback();

  if (!points || points.length < 2) {
    $('playbackPanel').style.display = 'none';
    return;
  }

  playback.points = points.slice();
  playback.visits = visits.slice();
  playback.index  = 0;
  playback.playing = false;
  playback.speed   = parseFloat($('playSpeed').value) || 1;

  let base = PLAYBACK_TARGET_MS / playback.points.length;
  base = Math.max(PLAYBACK_MIN_MS, Math.min(PLAYBACK_MAX_MS, base));
  playback.baseIntervalMs = base;

  const slider = $('playSlider');
  slider.min = 0;
  slider.max = points.length - 1;
  slider.value = 0;

  playback.marker = L.marker([points[0].lat, points[0].lon], {
    icon: L.divIcon({
      className: 'vehicle-marker',
      html: `<div class="vehicle-inner">🚚</div>`,
      iconSize: [34, 34],
      iconAnchor: [17, 17],
    }),
    zIndexOffset: 1000,
    interactive: false,
  }).addTo(map);

  playback.trail = L.polyline([], {
    color: '#fbbf24',
    weight: 6,
    opacity: 0.85,
    lineJoin: 'round',
    lineCap: 'round',
  }).addTo(trailLayer);

  if ($('playRevealVisits').checked) {
    playback.visits.forEach(item => {
      if (item.marker && visitLayer.hasLayer(item.marker)) {
        visitLayer.removeLayer(item.marker);
      }
      item.revealed = false;
    });
  } else {
    playback.visits.forEach(item => item.revealed = !!item.marker);
  }

  $('playbackPanel').style.display = '';
  updatePlaybackUI();
}

function teardownPlayback() {
  stopPlayback();
  if (playback.marker) { map.removeLayer(playback.marker); playback.marker = null; }
  if (playback.trail)  { trailLayer.clearLayers(); playback.trail = null; }
  playback.points = [];
  playback.visits = [];
  playback.index  = 0;
}

function updatePlaybackUI() {
  const pts = playback.points;
  if (!pts.length) return;

  const idx = Math.max(0, Math.min(pts.length - 1, playback.index));
  const p = pts[idx];

  $('playTime').textContent     = formatClock(p.time);
  $('playProgress').textContent = `${idx + 1} / ${pts.length}`;
  $('playSlider').value         = idx;

  if (playback.marker) playback.marker.setLatLng([p.lat, p.lon]);

  if (playback.trail) {
    const trailPts = pts.slice(0, idx + 1).map(pt => [pt.lat, pt.lon]);
    playback.trail.setLatLngs(trailPts);
  }

  if ($('playRevealVisits').checked) updateVisitReveals(p.time);
}

function updateVisitReveals(currentTimeStr) {
  const curMs = timeMs(currentTimeStr);
  if (!Number.isFinite(curMs)) return;

  playback.visits.forEach(item => {
    if (!item.marker) return;

    const shouldShow = item.timeMs <= curMs;

    if (shouldShow && !item.revealed) {
      visitLayer.addLayer(item.marker);
      item.revealed = true;
      const el = document.querySelector(`.visit-item[data-visit-guid="${item.guid}"]`);
      if (el) {
        el.classList.remove('revealed-pulse');
        void el.offsetWidth;
        el.classList.add('revealed-pulse');
      }
    } else if (!shouldShow && item.revealed) {
      visitLayer.removeLayer(item.marker);
      item.revealed = false;
    }
  });
}

function startPlayback() {
  if (playback.playing) return;
  if (!playback.points.length) return;

  if (playback.index >= playback.points.length - 1) {
    playback.index = 0;
    if ($('playRevealVisits').checked) {
      playback.visits.forEach(item => {
        if (item.marker && visitLayer.hasLayer(item.marker)) {
          visitLayer.removeLayer(item.marker);
        }
        item.revealed = false;
      });
    }
    updatePlaybackUI();
  }

  playback.playing = true;
  $('playBtn').classList.add('playing');
  $('playBtn').textContent = '❚❚';
  tick();
}

function stopPlayback() {
  playback.playing = false;
  if (playback.timer) {
    clearTimeout(playback.timer);
    playback.timer = null;
  }
  const btn = $('playBtn');
  if (btn) {
    btn.classList.remove('playing');
    btn.textContent = '▶';
  }
}

function tick() {
  if (!playback.playing) return;
  if (playback.index >= playback.points.length - 1) {
    stopPlayback();
    return;
  }
  playback.index++;
  updatePlaybackUI();
  const interval = playback.baseIntervalMs / playback.speed;
  playback.timer = setTimeout(tick, interval);
}

$('playBtn').addEventListener('click', () => {
  if (playback.playing) stopPlayback();
  else startPlayback();
});

$('playSlider').addEventListener('input', (e) => {
  stopPlayback();
  playback.index = parseInt(e.target.value, 10) || 0;
  updatePlaybackUI();
});

$('playSpeed').addEventListener('change', (e) => {
  playback.speed = parseFloat(e.target.value) || 1;
});

$('playRevealVisits').addEventListener('change', (e) => {
  if (e.target.checked) {
    playback.visits.forEach(item => {
      if (item.marker && visitLayer.hasLayer(item.marker)) {
        visitLayer.removeLayer(item.marker);
      }
      item.revealed = false;
    });
    const p = playback.points[playback.index];
    if (p) updateVisitReveals(p.time);
  } else {
    playback.visits.forEach(item => {
      if (item.marker && !item.revealed) {
        visitLayer.addLayer(item.marker);
      }
      item.revealed = true;
    });
  }
});

// ═══════════════════════════════════════════════════════════════
//  UI TOGGLES
// ═══════════════════════════════════════════════════════════════
function setLoading(on) { $('loading').classList.toggle('active', on); }

$('showRoute').addEventListener('change', e => {
  if (e.target.checked) map.addLayer(routeLayer);
  else                  map.removeLayer(routeLayer);
});

$('showVisits').addEventListener('change', e => {
  if (e.target.checked) map.addLayer(visitLayer);
  else                  map.removeLayer(visitLayer);
});

$('showHeat').addEventListener('change', e => {
  if (e.target.checked && heatLayer) {
    map.addLayer(heatLayer);
    $('heatLegend').classList.add('active');
  } else if (heatLayer) {
    map.removeLayer(heatLayer);
    $('heatLegend').classList.remove('active');
  }
});

// ═══════════════════════════════════════════════════════════════
//  RESIZE
// ═══════════════════════════════════════════════════════════════
let resizeTimer;
window.addEventListener('resize', () => {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(() => {
    map.invalidateSize();
    if (!isMobile()) {
      document.body.classList.remove('side-open-mobile');
    }
  }, 180);
});
</script>
</body>
</html>