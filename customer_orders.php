<?php
session_start();

if (isset($_GET['customer_api'])) {
    include 'db.php';
    header('Content-Type: application/json');
    $action = $_GET['customer_api'];
    if ($action === 'get_my_orders') {
        if (!me_id()) json_err('Not logged in');
        try { json_out(load_orders($conn, 'o.customer_id = ?', 'i', [me_id()])); }
        catch (Throwable $e) { json_err($e->getMessage()); }
    }
    json_err('Unknown action');
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<script>
(function(){
  var s = localStorage.getItem('mj_theme') || 'light';
  document.documentElement.setAttribute('data-theme', s);
})();
</script>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders – Majoy's Homemade Sweets</title>
<style>
/* ══════════════════════════════════════
   THEME SYSTEM
   ══════════════════════════════════════ */
:root, [data-theme="light"] {
    --bg:           #EDE7F6;
    --surface:      #FFFFFF;
    --surface2:     #F3EEF9;
    --surface3:     #E8DFF5;
    --brand:        #7B5EA7;
    --brand-mid:    #9B7FBF;
    --brand-light:  #C4A8E0;
    --brand-dark:   #5A3F84;
    --text:         #1A1A1A;
    --text-mid:     #4A3B6B;
    --text-muted:   #757575;
    --border:       rgba(123,94,167,0.18);
    --border-soft:  rgba(123,94,167,0.10);
    --shadow:       0 2px 12px rgba(123,94,167,0.10);
    --shadow-md:    0 4px 24px rgba(123,94,167,0.14);
    --green:        #4CAF50;
    --green-bg:     #E8F5E9;
    --green-text:   #1B5E20;
    --red:          #F44336;
    --red-bg:       #FFEBEE;
    --red-text:     #B71C1C;
    --amber:        #FF9800;
    --amber-bg:     #FFF3E0;
    --amber-text:   #E65100;
    --grey-bg:      #F5F5F5;
    --grey-text:    #616161;
    --header-bg:    #7B5EA7;
    --header-text:  #FFFFFF;
    --radius:       14px;
    --radius-sm:    9px;
}
[data-theme="dark"] {
    --bg:           #1A1426;
    --surface:      #241C38;
    --surface2:     #2E2448;
    --surface3:     #1A1426;
    --brand:        #9B7FBF;
    --brand-mid:    #B89FD4;
    --brand-light:  #7B5EA7;
    --brand-dark:   #C4A8E0;
    --text:         #F0EAF8;
    --text-mid:     #C4A8E0;
    --text-muted:   #8E7BAA;
    --border:       rgba(155,127,191,0.20);
    --border-soft:  rgba(155,127,191,0.10);
    --shadow:       0 2px 12px rgba(0,0,0,0.35);
    --shadow-md:    0 4px 24px rgba(0,0,0,0.45);
    --green:        #66BB6A;
    --green-bg:     rgba(102,187,106,0.15);
    --green-text:   #A5D6A7;
    --red:          #EF5350;
    --red-bg:       rgba(239,83,80,0.15);
    --red-text:     #EF9A9A;
    --amber:        #FFA726;
    --amber-bg:     rgba(255,167,38,0.15);
    --amber-text:   #FFCC80;
    --grey-bg:      rgba(255,255,255,0.07);
    --grey-text:    #9E9E9E;
    --header-bg:    #1A1426;
    --header-text:  #EDE7F6;
    --radius:       14px;
    --radius-sm:    9px;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',system-ui,sans-serif; background:var(--bg); min-height:100vh; color:var(--text); transition:background .3s,color .3s; -webkit-font-smoothing:antialiased; }

/* ── SCROLLBAR ── */
::-webkit-scrollbar { width:6px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:var(--brand-light); border-radius:3px; }

/* ── HEADER ── */
.top-header {
    background: var(--header-bg);
    padding: 0 24px;
    height: 60px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 16px rgba(123,94,167,0.25);
}
.top-header h1 { font-size:17px; font-weight:700; color:var(--header-text); display:flex; align-items:center; gap:8px; font-style:italic; }
.header-right { display:flex; align-items:center; gap:10px; }
.back-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; background:rgba(255,255,255,0.15); color:white; text-decoration:none; border-radius:var(--radius-sm); font-weight:600; font-size:13px; border:1px solid rgba(255,255,255,0.22); transition:all .2s; }
.back-btn:hover { background:rgba(255,255,255,0.25); }
.theme-btn { width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.22); color:white; font-size:15px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; line-height:1; }
.theme-btn:hover { background:rgba(255,255,255,0.25); transform:rotate(20deg); }

/* ── CONTAINER ── */
.container { max-width:860px; margin:0 auto; padding:24px 16px 40px; }

/* ── TABS BAR ── */
.tabs-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}
.tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border-radius: 24px;
    border: 2px solid var(--border);
    background: var(--surface);
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    font-family: inherit;
    position: relative;
}
.tab-btn:hover { border-color:var(--brand); color:var(--brand); background:var(--surface2); }
.tab-btn.active { background:var(--brand); color:white; border-color:var(--brand); box-shadow:0 3px 12px rgba(123,94,167,0.30); }
.tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    padding: 0 5px;
    background: rgba(255,255,255,0.25);
    color: inherit;
}
.tab-btn:not(.active) .tab-count { background:var(--surface3); color:var(--brand); }

/* ── TOOLBAR (sort + filter) ── */
.toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.tool-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 14px;
    border-radius: 20px;
    border: 1.5px solid var(--border);
    background: var(--surface);
    color: var(--text-mid);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    font-family: inherit;
}
.tool-btn:hover { border-color:var(--brand); color:var(--brand); background:var(--surface2); }
.tool-btn.has-filter { background:var(--brand); color:white; border-color:var(--brand); }
.filter-badge {
    background: rgba(255,255,255,0.25);
    border-radius: 10px;
    padding: 1px 6px;
    font-size: 10px;
    font-weight: 700;
}
.active-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    background: var(--surface2);
    border: 1.5px solid var(--brand-light);
    color: var(--brand-dark);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
[data-theme="dark"] .active-chip { color:var(--brand-mid); }
.active-chip button { background:none; border:none; cursor:pointer; color:inherit; font-size:13px; line-height:1; padding:0; display:flex; align-items:center; }
.clear-all-btn { background:none; border:none; cursor:pointer; color:var(--red); font-size:12px; font-weight:700; font-family:inherit; padding:4px 8px; border-radius:8px; transition:all .2s; }
.clear-all-btn:hover { background:var(--red-bg); }

/* ── BOTTOM SHEET OVERLAY ── */
.sheet-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(26,20,38,0.55);
    z-index: 500;
    backdrop-filter: blur(3px);
    align-items: flex-end;
    justify-content: center;
}
.sheet-overlay.active { display: flex; }
.bottom-sheet {
    background: var(--surface);
    border-radius: 20px 20px 0 0;
    padding: 20px 24px 32px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 -8px 40px rgba(0,0,0,0.25);
    animation: slideUp .25s ease;
    border: 1px solid var(--border-soft);
    border-bottom: none;
}
.sheet-handle { width:36px; height:4px; background:var(--border); border-radius:2px; margin:0 auto 18px; }
.sheet-title { font-size:16px; font-weight:700; color:var(--brand); margin-bottom:16px; }
@keyframes slideUp { from { transform:translateY(100%); } to { transform:translateY(0); } }

/* Sort options */
.sort-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 16px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: var(--text);
    transition: all .15s;
    border: 1.5px solid transparent;
    margin-bottom: 6px;
}
.sort-option:hover { background:var(--surface2); border-color:var(--border); }
.sort-option.active { background:var(--surface2); border-color:var(--brand); color:var(--brand); font-weight:700; }
.sort-check { color:var(--brand); font-size:18px; font-weight:700; }

/* Filter chips */
.filter-chips { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; }
.filter-chip {
    padding: 8px 16px;
    border-radius: 20px;
    border: 2px solid var(--border);
    background: var(--surface2);
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
    font-family: inherit;
}
.filter-chip:hover { border-color:var(--brand); color:var(--brand); }
.filter-chip.active { background:var(--brand); color:white; border-color:var(--brand); }
.sheet-apply-btn {
    width: 100%;
    padding: 13px;
    background: var(--brand);
    color: white;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: all .2s;
    margin-top: 8px;
}
.sheet-apply-btn:hover { background:var(--brand-dark); transform:translateY(-1px); }

/* ── ORDER CARD ── */
.order-card {
    background: var(--surface);
    border-radius: var(--radius);
    margin-bottom: 14px;
    border: 1.5px solid var(--border-soft);
    box-shadow: var(--shadow);
    transition: all .2s;
    overflow: hidden;
}
.order-card:hover { border-color:var(--border); box-shadow:var(--shadow-md); }
.order-card.archived-card { opacity:0.72; border-color:rgba(123,94,167,0.08); }
.order-card-inner { padding: 18px 20px; }

.order-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; gap:10px; flex-wrap:wrap; }
.order-number { font-weight:700; font-size:16px; color:var(--brand); display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.archived-label { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; background:var(--grey-bg); color:var(--grey-text); border-radius:10px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
.order-date { font-size:12px; color:var(--text-muted); margin-bottom:10px; }

/* Status badges */
.order-status { display:inline-flex; padding:5px 12px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.status-pending    { background:var(--amber-bg);  color:var(--amber-text); }
.status-approved   { background:#E3F2FD; color:#0D47A1; }
.status-staff_approved { background:#F3E8FF; color:#6B21A8; }
.status-processing { background:#EDE9FE; color:#4C1D95; }
.status-completed  { background:var(--green-bg);  color:var(--green-text); }
.status-rejected   { background:var(--red-bg);    color:var(--red-text); }
.status-cancelled  { background:var(--grey-bg);   color:var(--grey-text); }
.status-archived   { background:var(--grey-bg);   color:var(--grey-text); }

/* History summary banners */
.history-banner {
    padding: 10px 14px;
    border-radius: 8px;
    font-size:13px;
    font-weight:600;
    margin-bottom:12px;
    display:flex;
    align-items:center;
    gap:8px;
}
.banner-completed { background:var(--green-bg); color:var(--green-text); border-left:4px solid var(--green); }
.banner-cancelled { background:var(--grey-bg);  color:var(--grey-text);  border-left:4px solid #9E9E9E; }
.banner-rejected  { background:var(--red-bg);   color:var(--red-text);   border-left:4px solid var(--red); }

/* Items list */
.items-list { margin:10px 0; }
.order-item-row { display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border-soft); font-size:13px; color:var(--text-mid); }
.order-item-row:last-child { border-bottom:none; }
.see-all-btn { background:none; border:none; color:var(--brand); font-size:12px; font-weight:700; cursor:pointer; font-family:inherit; padding:6px 0 0; display:inline-flex; align-items:center; gap:4px; transition:all .15s; }
.see-all-btn:hover { color:var(--brand-dark); }

/* Order total */
.order-total-row { display:flex; justify-content:space-between; align-items:center; padding-top:10px; margin-top:8px; border-top:2px solid var(--border-soft); }
.order-total-val { font-size:20px; font-weight:700; color:var(--brand); }

/* Archive button row */
.card-action-row { padding:10px 20px 14px; border-top:1px solid var(--border-soft); display:flex; justify-content:flex-end; gap:8px; }
.archive-btn {
    display:inline-flex; align-items:center; gap:5px;
    padding:7px 14px;
    border-radius:var(--radius-sm);
    font-size:12px; font-weight:700;
    cursor:pointer;
    font-family:inherit;
    transition:all .2s;
    border:none;
}
.btn-archive-do  { background:var(--grey-bg); color:var(--grey-text); border:1.5px solid rgba(158,158,158,0.25); }
.btn-archive-do:hover  { background:#EEEEEE; color:#424242; }
[data-theme="dark"] .btn-archive-do:hover { background:rgba(255,255,255,0.12); }
.btn-unarchive   { background:var(--surface2); color:var(--brand); border:1.5px solid var(--border); }
.btn-unarchive:hover { background:var(--brand); color:white; }

/* Delivery info */
.delivery-info-box { background:var(--surface2); padding:10px 12px; border-radius:8px; margin:10px 0; font-size:13px; border:1px solid var(--border-soft); }
.delivery-info-box strong { color:var(--text); }
.delivery-info-box span   { color:var(--text-muted); }

/* Notif strips */
.notif-strip { padding:10px 14px; border-radius:8px; font-size:13px; font-weight:600; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
.notif-pending   { background:var(--amber-bg); border-left:4px solid var(--amber); color:var(--amber-text); }
.notif-confirmed { background:#E3F2FD; border-left:4px solid #42A5F5; color:#0D47A1; }
.notif-preparing { background:#EDE9FE; border-left:4px solid #9C27B0; color:#4A148C; }
.notif-delivery  { background:#E0F7FA; border-left:4px solid #00ACC1; color:#006064; }
.notif-pickup    { background:#EDE9FE; border-left:4px solid #7C3AED; color:#5B21B6; }
.notif-delivered { background:var(--green-bg); border-left:4px solid var(--green); color:var(--green-text); }
.notif-rejected  { background:var(--red-bg); border-left:4px solid var(--red); color:var(--red-text); }
[data-theme="dark"] .notif-confirmed { background:rgba(66,165,245,0.15); color:#90CAF9; }
[data-theme="dark"] .notif-preparing { background:rgba(156,39,176,0.15); color:#CE93D8; }
[data-theme="dark"] .notif-delivery  { background:rgba(0,172,193,0.15);  color:#80DEEA; }
[data-theme="dark"] .notif-pickup    { background:rgba(124,58,237,0.15); color:#C4B5FD; }

/* Schedule tag */
.schedule-tag { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:8px; font-size:12px; font-weight:600; margin-bottom:10px; }
.schedule-future  { background:var(--amber-bg); color:var(--amber-text); }
.schedule-today   { background:var(--green-bg);  color:var(--green-text); }

/* STEPPER */
.delivery-stepper { background:var(--surface2); border-radius:10px; padding:14px 10px; margin:10px 0; }
.stepper-header { font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:10px; }
.stepper-track { display:flex; align-items:flex-start; }
.stepper-step { display:flex; flex-direction:column; align-items:center; flex:1; position:relative; }
.stepper-step:not(:last-child)::after { content:''; position:absolute; top:13px; left:58%; width:84%; height:2px; z-index:0; }
.step-done::after    { background:var(--green) !important; }
.step-current::after { background:var(--border-soft) !important; }
.step-pending::after { background:var(--border-soft) !important; }
.stepper-circle { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; z-index:1; font-weight:700; position:relative; }
.step-done .stepper-circle    { background:var(--green); color:white; }
.step-current .stepper-circle { background:var(--brand); color:white; animation:stepPulse 1.8s infinite; }
.step-pending .stepper-circle { background:var(--surface3); color:var(--text-muted); }
.stepper-label { font-size:9px; font-weight:600; text-align:center; margin-top:5px; line-height:1.3; color:var(--text-muted); }
.step-done .stepper-label    { color:var(--green-text); }
.step-current .stepper-label { color:var(--brand); }
@keyframes stepPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.18)} }

/* Delivered banner */
.delivered-banner { background:linear-gradient(135deg,var(--green-bg),rgba(102,187,106,0.25)); border-radius:10px; padding:12px; text-align:center; margin-top:10px; }

/* Empty state */
.empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
.empty-state .icon { font-size:56px; margin-bottom:12px; opacity:0.6; }
.empty-state p { font-size:15px; font-weight:500; }
.empty-state a { display:inline-block; margin-top:16px; padding:10px 24px; background:var(--brand); color:white; text-decoration:none; border-radius:var(--radius-sm); font-weight:700; }

/* Last updated */
.last-updated-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12px; color:var(--text-muted); }

/* Responsive */
@media(max-width:600px) {
    .top-header { padding:0 14px; }
    .container { padding:16px 12px 40px; }
    .order-card-inner { padding:14px 14px; }
    .card-action-row { padding:8px 14px 12px; }
}
</style>
</head>
<body>

<header class="top-header">
    <h1>📦 My Orders</h1>
    <div class="header-right">
        <strong style="color:var(--header-text);font-size:13px;display:none;" id="headerName"><?= htmlspecialchars($_SESSION['fullname'] ?? '') ?></strong>
        <button class="theme-btn" id="mjThemeBtn" onclick="mjCycleTheme()" title="Toggle theme">🌙</button>
        <a href="customer.php" class="back-btn">← Shop</a>
    </div>
</header>

<div class="container">

    <!-- Last updated + tab bar -->
    <div class="last-updated-bar">
        <span id="lastUpdated" style="font-style:italic;"></span>
    </div>

    <!-- TABS -->
    <div class="tabs-bar">
        <button class="tab-btn active" id="tabActive" onclick="switchTab('active')">
            ⚡ Active <span class="tab-count" id="countActive">0</span>
        </button>
        <button class="tab-btn" id="tabHistory" onclick="switchTab('history')">
            📋 History <span class="tab-count" id="countHistory">0</span>
        </button>
        <button class="tab-btn" id="tabArchived" onclick="switchTab('archived')">
            🗄️ Archived <span class="tab-count" id="countArchived">0</span>
        </button>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar" id="toolbar">
        <button class="tool-btn" id="sortBtn" onclick="openSortSheet()">
            ⇅ Sort: <span id="sortLabel">Newest</span>
        </button>
        <button class="tool-btn" id="filterBtn" onclick="openFilterSheet()">
            🔽 Filter
        </button>
        <div id="activeChips" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;"></div>
    </div>

    <div id="ordersList">
        <div class="empty-state"><div class="icon">⏳</div><p>Loading your orders…</p></div>
    </div>
</div>

<!-- SORT BOTTOM SHEET -->
<div class="sheet-overlay" id="sortSheet" onclick="closeSortSheet(event)">
    <div class="bottom-sheet">
        <div class="sheet-handle"></div>
        <div class="sheet-title">Sort Orders</div>
        <div id="sortOptions"></div>
    </div>
</div>

<!-- FILTER BOTTOM SHEET -->
<div class="sheet-overlay" id="filterSheet" onclick="closeFilterSheet(event)">
    <div class="bottom-sheet">
        <div class="sheet-handle"></div>
        <div class="sheet-title">Filter by Status</div>
        <div class="filter-chips" id="filterChips"></div>
        <button class="sheet-apply-btn" onclick="applyFilter()">Apply Filters</button>
    </div>
</div>

<script>
// ══════════════════════════════════════════════
//  STATE
// ══════════════════════════════════════════════
let allOrders      = [];
let currentTab     = 'active';
let currentSort    = 'newest';
let currentFilters = [];
let expandedItems  = {};     // order IDs where items are expanded
let archivedIds    = new Set(JSON.parse(localStorage.getItem('mj_archived_orders') || '[]'));

const SORT_OPTIONS = [
    { key:'newest',  label:'📅 Newest First'    },
    { key:'oldest',  label:'📅 Oldest First'    },
    { key:'highest', label:'💰 Highest Amount'  },
    { key:'lowest',  label:'💰 Lowest Amount'   },
];

const FILTER_CONFIGS = {
    active:   ['pending','approved','staff_approved','processing'],
    history:  ['completed','cancelled','rejected'],
    archived: [],
};

const STATUS_LABELS = {
    pending:       '⏳ Pending',
    approved:      '✅ Approved',
    staff_approved:'👤 Staff Approved',
    processing:    '👨‍🍳 Processing',
    completed:     '🎉 Completed',
    cancelled:     '🚫 Cancelled',
    rejected:      '❌ Rejected',
};

// ══════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════
function escH(s){ if(!s)return''; const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function saveArchived(){ localStorage.setItem('mj_archived_orders', JSON.stringify([...archivedIds])); }

function isActiveOrder(o){
    const active = ['pending','approved','staff_approved','processing'];
    return active.includes(o.status) && !archivedIds.has(o.id);
}
function isHistoryOrder(o){
    const done = ['completed','cancelled','rejected'];
    return done.includes(o.status) && !archivedIds.has(o.id);
}
function isArchivedOrder(o){
    return archivedIds.has(o.id);
}

function canArchive(o){
    // Only completed, cancelled, or rejected orders can be archived
    return ['completed','cancelled','rejected'].includes(o.status);
}

// ══════════════════════════════════════════════
//  TAB SWITCHING
// ══════════════════════════════════════════════
function switchTab(tab){
    currentTab = tab;
    currentFilters = [];
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
    renderOrders();
    renderActiveChips();
    // Hide sort/filter for archived tab
    document.getElementById('toolbar').style.display = tab === 'archived' ? 'none' : 'flex';
}

// ══════════════════════════════════════════════
//  SORT SHEET
// ══════════════════════════════════════════════
function openSortSheet(){
    document.getElementById('sortOptions').innerHTML = SORT_OPTIONS.map(s => `
        <div class="sort-option ${currentSort===s.key?'active':''}" onclick="selectSort('${s.key}')">
            ${s.label}
            ${currentSort===s.key ? '<span class="sort-check">✓</span>' : ''}
        </div>`).join('');
    document.getElementById('sortSheet').classList.add('active');
}
function closeSortSheet(e){
    if(e.target === document.getElementById('sortSheet')) document.getElementById('sortSheet').classList.remove('active');
}
function selectSort(key){
    currentSort = key;
    document.getElementById('sortLabel').textContent = {newest:'Newest',oldest:'Oldest',highest:'Highest ₱',lowest:'Lowest ₱'}[key];
    document.getElementById('sortSheet').classList.remove('active');
    renderOrders();
}

// ══════════════════════════════════════════════
//  FILTER SHEET
// ══════════════════════════════════════════════
let tempFilters = [];
function openFilterSheet(){
    const available = FILTER_CONFIGS[currentTab] || [];
    tempFilters = [...currentFilters];
    document.getElementById('filterChips').innerHTML = available.map(f => `
        <button class="filter-chip ${tempFilters.includes(f)?'active':''}" onclick="toggleTempFilter('${f}',this)">
            ${STATUS_LABELS[f]||f}
        </button>`).join('');
    document.getElementById('filterSheet').classList.add('active');
}
function closeFilterSheet(e){
    if(e.target === document.getElementById('filterSheet')) document.getElementById('filterSheet').classList.remove('active');
}
function toggleTempFilter(f, btn){
    btn.classList.toggle('active');
    if(tempFilters.includes(f)) tempFilters = tempFilters.filter(x=>x!==f);
    else tempFilters.push(f);
}
function applyFilter(){
    currentFilters = [...tempFilters];
    document.getElementById('filterSheet').classList.remove('active');
    renderOrders();
    renderActiveChips();
    // Update filter button style
    const btn = document.getElementById('filterBtn');
    if(currentFilters.length){
        btn.classList.add('has-filter');
        btn.innerHTML = `🔽 Filter <span class="filter-badge">${currentFilters.length}</span>`;
    } else {
        btn.classList.remove('has-filter');
        btn.innerHTML = '🔽 Filter';
    }
}
function removeFilter(f){
    currentFilters = currentFilters.filter(x=>x!==f);
    renderOrders();
    renderActiveChips();
    if(!currentFilters.length){
        const btn = document.getElementById('filterBtn');
        btn.classList.remove('has-filter');
        btn.innerHTML = '🔽 Filter';
    } else {
        document.getElementById('filterBtn').querySelector('.filter-badge').textContent = currentFilters.length;
    }
}
function clearAllFilters(){
    currentFilters = [];
    renderOrders();
    renderActiveChips();
    const btn = document.getElementById('filterBtn');
    btn.classList.remove('has-filter');
    btn.innerHTML = '🔽 Filter';
}
function renderActiveChips(){
    const el = document.getElementById('activeChips');
    if(!currentFilters.length){ el.innerHTML=''; return; }
    el.innerHTML = currentFilters.map(f => `
        <div class="active-chip">
            ${STATUS_LABELS[f]||f}
            <button onclick="removeFilter('${f}')">✕</button>
        </div>`).join('')
        + `<button class="clear-all-btn" onclick="clearAllFilters()">Clear All</button>`;
}

// ══════════════════════════════════════════════
//  ARCHIVE / UNARCHIVE
// ══════════════════════════════════════════════
function archiveOrder(id){
    archivedIds.add(id);
    saveArchived();
    updateTabCounts();
    renderOrders();
}
function unarchiveOrder(id){
    archivedIds.delete(id);
    saveArchived();
    updateTabCounts();
    renderOrders();
}

// ══════════════════════════════════════════════
//  TAB COUNTS
// ══════════════════════════════════════════════
function updateTabCounts(){
    document.getElementById('countActive').textContent   = allOrders.filter(isActiveOrder).length;
    document.getElementById('countHistory').textContent  = allOrders.filter(isHistoryOrder).length;
    document.getElementById('countArchived').textContent = allOrders.filter(isArchivedOrder).length;
}

// ══════════════════════════════════════════════
//  STEPPER
// ══════════════════════════════════════════════
const DELIVERY_STEPS = [
    {key:'placed',           label:'Order Placed',    icon:'📝'},
    {key:'confirmed',        label:'Confirmed',       icon:'✅'},
    {key:'preparing',        label:'Preparing',       icon:'👨‍🍳'},
    {key:'out_for_delivery', label:'Out for Delivery',icon:'🚚'},
    {key:'delivered',        label:'Delivered',       icon:'🎉'},
];
const PICKUP_STEPS = [
    {key:'placed',           label:'Order Placed',    icon:'📝'},
    {key:'confirmed',        label:'Confirmed',       icon:'✅'},
    {key:'preparing',        label:'Preparing',       icon:'👨‍🍳'},
    {key:'out_for_delivery', label:'Ready for Pickup',icon:'🏪'},
    {key:'delivered',        label:'Picked Up',       icon:'🎉'},
];
function isPickup(o){
    if(o.fulfillment_method==='pickup') return true;
    if(o.delivery_address && o.delivery_address.toUpperCase().includes('PICKUP')) return true;
    return false;
}
function getDS(o){
    if(o.delivery_status) return o.delivery_status;
    const m={pending:'placed',approved:'confirmed',processing:'preparing',completed:'delivered'};
    return m[o.status]||'placed';
}
function renderStepper(o){
    // Only show stepper for active orders, not cancelled/rejected
    if(['rejected','cancelled'].includes(o.status)) return '';
    const pickup = isPickup(o);
    const STEPS  = pickup ? PICKUP_STEPS : DELIVERY_STEPS;
    const ds     = getDS(o);
    const ci     = STEPS.findIndex(s=>s.key===ds);
    const done   = ds==='delivered';
    let h = `<div class="delivery-stepper">
        <div class="stepper-header">${pickup?'🏪 Pickup':'🚚 Delivery'} Progress</div>
        <div class="stepper-track">`;
    STEPS.forEach((step,idx)=>{
        const isDone = idx<ci || done;
        const isCurr = idx===ci && !done;
        const cls    = isDone?'step-done':isCurr?'step-current':'step-pending';
        h += `<div class="stepper-step ${cls}">
            <div class="stepper-circle">${isDone?'✓':step.icon}</div>
            <div class="stepper-label">${step.label}</div>
        </div>`;
    });
    h += `</div>`;
    if(done){
        h += `<div class="delivered-banner"><div style="font-size:24px;margin-bottom:4px;">${pickup?'🏪':'🎉'}</div>
            <div style="font-weight:700;color:var(--green-text);font-size:14px;">${pickup?'Order Picked Up!':'Order Delivered!'}</div>
            <div style="font-size:12px;color:var(--green-text);opacity:.8;margin-top:2px;">Thank you for ordering from Majoy's!</div></div>`;
    }
    h += `</div>`;
    return h;
}

// ══════════════════════════════════════════════
//  NOTIF STRIP
// ══════════════════════════════════════════════
function renderNotif(o){
    const ds = getDS(o);
    const pickup = isPickup(o);
    if(o.status==='rejected')   return `<div class="notif-strip notif-rejected">❌ Your order was rejected.${o.review_notes?' Reason: '+escH(o.review_notes):''}</div>`;
    if(o.status==='cancelled') return `<div class="notif-strip notif-rejected">🚫 This order was cancelled.</div>`;
    const msgs = pickup ? {
        placed:          ['notif-pending',   '⏳ Your order is waiting for staff review.'],
        confirmed:       ['notif-confirmed', '✅ Your order has been confirmed!'],
        preparing:       ['notif-preparing', '👨‍🍳 Your order is being prepared!'],
        out_for_delivery:['notif-pickup',    '🏪 Your order is ready for pickup at the store!'],
        delivered:       ['notif-delivered', '🎉 Order picked up. Thank you!'],
    } : {
        placed:          ['notif-pending',   '⏳ Your order is waiting for staff review.'],
        confirmed:       ['notif-confirmed', '✅ Your order has been confirmed!'],
        preparing:       ['notif-preparing', '👨‍🍳 Your order is being prepared!'],
        out_for_delivery:['notif-delivery',  '🚚 Your order is on its way!'],
        delivered:       ['notif-delivered', '🎉 Your order was delivered. Enjoy!'],
    };
    const [cls,msg] = msgs[ds]||msgs.placed;
    return `<div class="notif-strip ${cls}">${msg}</div>`;
}

// ══════════════════════════════════════════════
//  RENDER ITEMS LIST (collapsible at 2)
// ══════════════════════════════════════════════
function renderItems(o){
    const items = o.items || [];
    if(!items.length) return '';
    const expanded = !!expandedItems[o.id];
    const show     = expanded ? items : items.slice(0,2);
    let html = `<div class="items-list">`;
    html += show.map(i=>`
        <div class="order-item-row">
            <span>${escH(i.name)} ×${i.quantity}</span>
            <span>₱${(Number(i.price)*i.quantity).toFixed(2)}</span>
        </div>`).join('');
    if(items.length > 2){
        if(!expanded){
            html += `<button class="see-all-btn" onclick="toggleItems(${o.id})">▾ See all ${items.length} items</button>`;
        } else {
            html += `<button class="see-all-btn" onclick="toggleItems(${o.id})">▴ Show less</button>`;
        }
    }
    html += `</div>`;
    return html;
}
function toggleItems(id){
    expandedItems[id] = !expandedItems[id];
    renderOrders();
}

// ══════════════════════════════════════════════
//  RENDER ONE CARD
// ══════════════════════════════════════════════
function renderCard(o, tab){
    const archived = archivedIds.has(o.id);
    const isArchTab = (tab==='archived');
    const payBadge = o.payment_method
        ? `<span style="padding:2px 8px;border-radius:8px;font-size:11px;font-weight:600;background:${o.payment_method==='gcash'?'#E3F2FD':'#E8F5E9'};color:${o.payment_method==='gcash'?'#0D47A1':'#1B5E20'};">${o.payment_method==='gcash'?'💳 GCash':'💵 Cash'}</span>`
        : '';

    // History summary banners
    let histBanner = '';
    if(tab==='history' || isArchTab){
        if(o.status==='completed') histBanner = `<div class="history-banner banner-completed">🎉 Order completed — Thank you for your purchase!</div>`;
        else if(o.status==='cancelled') histBanner = `<div class="history-banner banner-cancelled">🚫 This order was cancelled.</div>`;
        else if(o.status==='rejected') histBanner = `<div class="history-banner banner-rejected">❌ Order rejected${o.review_notes?' — '+escH(o.review_notes):''}</div>`;
    }

    // Schedule tag
    let schedTag = '';
    if(o.preferred_date){
        const d    = new Date(o.preferred_date+'T00:00:00');
        const today= new Date(); today.setHours(0,0,0,0);
        const days = Math.ceil((d-today)/86400000);
        const lbl  = days>0
            ? '📅 Scheduled: '+d.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric',year:'numeric'})
            : '📅 Today';
        schedTag = `<div class="schedule-tag ${days>0?'schedule-future':'schedule-today'}">${lbl}${o.preferred_time?' · '+escH(o.preferred_time):''}</div>`;
    }

    // Delivery info
    let dInfo = '';
    if(o.delivery_name){
        const pku = isPickup(o);
        dInfo = `<div class="delivery-info-box">
            <strong>${pku?'🏪 Pickup':'🚚 Delivery'} Details:</strong><br>
            <span>${escH(o.delivery_name)} | ${escH(o.delivery_phone)}<br>
            ${pku ? '<strong>Store Pickup</strong>' : escH(o.delivery_address)}
            ${o.delivery_notes && !o.delivery_notes.startsWith('QUOTED_') ? '<br>Notes: '+escH(o.delivery_notes) : ''}</span>
        </div>`;
    }

    // Archive / Unarchive button
    let archiveBtn = '';
    if(!isArchTab){
        if(canArchive(o)){
            archiveBtn = `<button class="archive-btn btn-archive-do" onclick="archiveOrder(${o.id})">🗄️ Archive</button>`;
        }
        // else: no archive button for active/in-progress orders
    } else {
        archiveBtn = `<button class="archive-btn btn-unarchive" onclick="unarchiveOrder(${o.id})">↩ Unarchive</button>`;
    }

    // Stepper — only on active tab, not history/archived, not cancelled/rejected
    const stepper = (tab==='active') ? renderStepper(o) : '';

    // Notif — only on active tab
    const notif = (tab==='active') ? renderNotif(o) : '';

    return `<div class="order-card${archived&&isArchTab?' archived-card':''}">
        <div class="order-card-inner">
            <div class="order-header">
                <div class="order-number">
                    Order #${o.id}
                    ${archived&&isArchTab?'<span class="archived-label">🗄️ Archived</span>':''}
                    ${payBadge}
                </div>
                <span class="order-status status-${o.status.replace(/ /g,'_')}">${STATUS_LABELS[o.status]||o.status}</span>
            </div>
            <div class="order-date">${new Date(o.order_date).toLocaleString()}</div>
            ${histBanner}${notif}${schedTag}${stepper}${dInfo}
            ${renderItems(o)}
            <div class="order-total-row">
                <span style="color:var(--text-muted);font-size:13px;">
                    Items: ${(o.items||[]).reduce((s,i)=>s+i.quantity,0)}
                </span>
                <span class="order-total-val">₱${Number(o.total).toFixed(2)}</span>
            </div>
        </div>
        ${archiveBtn ? `<div class="card-action-row">${archiveBtn}</div>` : ''}
    </div>`;
}

// ══════════════════════════════════════════════
//  MAIN RENDER
// ══════════════════════════════════════════════
function renderOrders(){
    // Get base list for current tab
    let list;
    if(currentTab==='active')   list = allOrders.filter(isActiveOrder);
    if(currentTab==='history')  list = allOrders.filter(isHistoryOrder);
    if(currentTab==='archived') list = allOrders.filter(isArchivedOrder);

    // Apply filters (not for archived tab)
    if(currentTab!=='archived' && currentFilters.length){
        list = list.filter(o => currentFilters.includes(o.status));
    }

    // Apply sort
    list = [...list].sort((a,b)=>{
        if(currentSort==='newest')  return new Date(b.order_date)-new Date(a.order_date);
        if(currentSort==='oldest')  return new Date(a.order_date)-new Date(b.order_date);
        if(currentSort==='highest') return Number(b.total)-Number(a.total);
        if(currentSort==='lowest')  return Number(a.total)-Number(b.total);
        return 0;
    });

    const el = document.getElementById('ordersList');
    if(!list.length){
        const msgs = {
            active:   {icon:'⚡', text:'No active orders.', link:true},
            history:  {icon:'📋', text:'No order history yet.', link:false},
            archived: {icon:'🗄️', text:'No archived orders.', link:false},
        };
        const m = msgs[currentTab];
        el.innerHTML = `<div class="empty-state">
            <div class="icon">${m.icon}</div>
            <p>${currentFilters.length?'No orders match the selected filters.':m.text}</p>
            ${m.link&&!currentFilters.length?'<a href="customer.php">Start Shopping</a>':''}
        </div>`;
        return;
    }
    el.innerHTML = list.map(o => renderCard(o, currentTab)).join('');
}

// ══════════════════════════════════════════════
//  FETCH ORDERS
// ══════════════════════════════════════════════
async function loadOrders(){
    try{
        const res    = await fetch('?customer_api=get_my_orders');
        const orders = await res.json();
        document.getElementById('lastUpdated').textContent = 'Updated: '+new Date().toLocaleTimeString();
        if(!orders || orders.error){ allOrders=[]; }
        else { allOrders = orders; }
        updateTabCounts();
        renderOrders();
    } catch(e){ console.error(e); }
}

// ══════════════════════════════════════════════
//  THEME
// ══════════════════════════════════════════════
function mjCycleTheme(){
    const cur  = document.documentElement.getAttribute('data-theme')||'light';
    const next = cur==='light'?'dark':'light';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('mj_theme', next);
    document.getElementById('mjThemeBtn').textContent = next==='dark'?'☀️':'🌙';
}
window.addEventListener('DOMContentLoaded', function(){
    const cur = localStorage.getItem('mj_theme')||'light';
    document.documentElement.setAttribute('data-theme', cur);
    document.getElementById('mjThemeBtn').textContent = cur==='dark'?'☀️':'🌙';
});

// ══════════════════════════════════════════════
//  INIT
// ══════════════════════════════════════════════
loadOrders();
setInterval(loadOrders, 30000);
</script>
</body>
</html>