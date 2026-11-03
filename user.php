<?php
session_start();

// ═══════════════════════════════════════════════════════════════
//  INLINE API - Handle AJAX requests (staff)
// ═══════════════════════════════════════════════════════════════
if (isset($_GET['inline_api'])) {
    include 'db.php';
    header('Content-Type: application/json');
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') { http_response_code(401); json_err('Not authenticated'); }

    $action = $_GET['inline_api'];
    $data   = read_json();

    try {
        if ($action === 'get_orders')    json_out(load_orders_staff($conn));
        if ($action === 'get_products')  json_out(products_admin($conn));

        // Staff confirm: deducts stock (oldest batch first) and records the sale
        if ($action === 'approve') {
            order_do($conn, (int)($data['order_id'] ?? 0), 'approve');
            json_out(['success' => true]);
        }
        if ($action === 'start_processing') {
            order_do($conn, (int)($data['order_id'] ?? 0), 'start_processing');
            json_out(['success' => true]);
        }
        if ($action === 'out_for_delivery') {
            order_do($conn, (int)($data['order_id'] ?? 0), 'out_for_delivery');
            json_out(['success' => true]);
        }
        if ($action === 'complete_order') {
            order_do($conn, (int)($data['order_id'] ?? 0), 'complete');
            json_out(['success' => true]);
        }
        if ($action === 'reject') {
            order_do($conn, (int)($data['order_id'] ?? 0), 'cancel');
            json_out(['success' => true]);
        }

        if ($action === 'get_inv_stats')  json_out(inventory_stats($conn));
        if ($action === 'get_inventory') {
            $rows = products_admin($conn);
            usort($rows, fn($a, $b) => [$a['days_remaining'] ?? 999, $a['name']] <=> [$b['days_remaining'] ?? 999, $b['name']]);
            json_out($rows);
        }

        if ($action === 'discard_items') {
            json_out(product_discard($conn, (int)($data['product_id'] ?? 0), (int)($data['quantity'] ?? 0), (string)($data['reason'] ?? '')));
        }
        if ($action === 'get_discard_history') json_out(discard_history($conn, 50));

        if ($action === 'walkin_get_products') json_out(products_in_stock($conn));
        if ($action === 'walkin_checkout')     json_out(walkin_checkout($conn, $data));

        json_err('Unknown action');
    } catch (Throwable $e) {
        json_err($e->getMessage());
    }
}

// Only staff can access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: login.php");
    exit();
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
<script>
/* Apply theme immediately before any paint to avoid flash */
(function(){
    var t = localStorage.getItem('mj_theme');
    if (t === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.style.background = '#1A1426';
        document.documentElement.style.colorScheme = 'dark';
    }
})();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard – Majoy's Homemade Sweets</title>
<link rel="stylesheet" href="majoys.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<style>

/* ══════════════════════════════════════════════
   2-WAY THEME SYSTEM — Admin-matched #7B5EA7 purple palette
   ══════════════════════════════════════════════ */

/* LIGHT (default) */
:root, [data-theme="light"] {
    --bg:          #EDE7F6;
    --surface:     #FFFFFF;
    --surface2:    #F3EEF9;
    --plum:        #7B5EA7;
    --plum-mid:    #9B7FBF;
    --plum-light:  #C4A8E0;
    --plum-pale:   #EDE7F6;
    --gold:        #5A3F84;
    --gold-light:  #7B5EA7;
    --accent:      #5A3F84;
    --text:        #1A1A1A;
    --text-muted:  #757575;
    --border:      rgba(123,94,167,0.18);
    --border-soft: rgba(123,94,167,0.10);
    --shadow:      0 2px 12px rgba(123,94,167,0.10);
    --shadow-md:   0 4px 24px rgba(123,94,167,0.14);
    --shadow-lg:   0 8px 40px rgba(123,94,167,0.18);
    --header-bg:   #7B5EA7;
    --header-accent: #FFFFFF;
    --header-sub:  rgba(255,255,255,0.65);
    --green:       #4CAF50;
    --green-bg:    #E8F5E9;
    --red:         #F44336;
    --red-bg:      #FFEBEE;
    --orange:      #FF9800;
    --orange-bg:   #FFF3E0;
    --blue:        #1976D2;
    --blue-bg:     #E3F2FD;
}

/* DARK */
[data-theme="dark"] {
    --bg:          #1A1426;
    --surface:     #241C38;
    --surface2:    #2E2448;
    --plum:        #9B7FBF;
    --plum-mid:    #B89FD4;
    --plum-light:  #7B5EA7;
    --plum-pale:   #2E2448;
    --gold:        #C4A8E0;
    --gold-light:  #9B7FBF;
    --accent:      #C4A8E0;
    --text:        #F0EAF8;
    --text-muted:  #8E7BAA;
    --border:      rgba(155,127,191,0.20);
    --border-soft: rgba(155,127,191,0.10);
    --shadow:      0 2px 12px rgba(0,0,0,0.35);
    --shadow-md:   0 4px 24px rgba(0,0,0,0.45);
    --shadow-lg:   0 8px 40px rgba(0,0,0,0.55);
    --header-bg:   #1A1426;
    --header-accent: #EDE7F6;
    --header-sub:  rgba(237,231,246,0.55);
    --green:       #66BB6A;
    --green-bg:    rgba(102,187,106,0.15);
    --red:         #EF5350;
    --red-bg:      rgba(239,83,80,0.15);
    --orange:      #FFA726;
    --orange-bg:   rgba(255,167,38,0.15);
    --blue:        #42A5F5;
    --blue-bg:     rgba(66,165,245,0.15);
}

/* Header always uses theme vars */
.top-header { background: var(--header-bg) !important; }
.top-header h1 { color: var(--header-accent) !important; }
.header-right .user-chip strong { color: var(--header-accent) !important; }
.header-right .user-chip span   { color: var(--header-sub) !important; }

/* ── THEME TOGGLE BUTTON ── */
.theme-toggle-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 36px; height: 36px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50%; font-size: 16px; cursor: pointer;
    transition: all 0.2s; flex-shrink: 0; line-height:1;
}
.theme-toggle-btn:hover { background: rgba(255,255,255,0.22); transform: rotate(20deg) scale(1.1); }

:root {
    --plum:        #1e1040;
    --plum-mid:    #2e1a5a;
    --plum-light:  #4a2878;
    --plum-pale:   #e0d0ee;
    --bg:          #F4F0F8;
    --surface:     #FFFFFF;
    --surface2:    #f0e8f8;
    --gold:        #6b2048;
    --gold-light:  #7d2d55;
    --green:       #2D9D6E;
    --green-bg:    #D4F0E4;
    --red:         #C0392B;
    --red-bg:      #FADBD8;
    --orange:      #D17A22;
    --orange-bg:   #FEF3C7;
    --blue:        #2471A3;
    --blue-bg:     #D6EAF8;
    --text:        #100828;
    --text-muted:  #6a4a8a;
    --border:      var(--border);
    --border-soft: #eee5f5;
    --shadow:      0 2px 12px rgba(30,16,64,0.08);
    --shadow-md:   0 4px 24px rgba(30,16,64,0.13);
    --shadow-lg:   0 8px 40px rgba(30,16,64,0.18);
    --radius:      14px;
    --radius-sm:   9px;
    --radius-lg:   20px;
    --t:           all 0.2s cubic-bezier(0.4,0,0.2,1);
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html, body { background: var(--bg); }
body { font-family:'DM Sans',sans-serif; min-height:100vh; color:var(--text); -webkit-font-smoothing:antialiased; }
::-webkit-scrollbar { width:5px; } ::-webkit-scrollbar-thumb { background:var(--plum-pale); border-radius:3px; }
/* ── TOP HEADER ── */
.top-header {
    background:var(--plum); padding:0 28px; height:62px;
    display:flex; justify-content:space-between; align-items:center;
    position:sticky; top:0; z-index:100;
    box-shadow:0 2px 20px rgba(30,16,64,.3);
    border-bottom:2px solid rgba(107,32,72,.4);
}
.top-header h1 { font-size:17px; color:#d4a0b8; font-weight:700; font-style:italic; text-shadow:0 2px 8px rgba(0,0,0,.3); display:flex; align-items:center; gap:10px; }
.top-header h1 img { height:36px; object-fit:contain; border-radius:6px; }
.header-right { display:flex; align-items:center; gap:16px; }
.header-right .user-chip { text-align:right; line-height:1.35; }
.header-right .user-chip strong { color:#d4a0b8; font-size:14px; font-weight:700; }
.header-right .user-chip span  { color:rgba(255,255,255,.55); font-size:12px; }
.logout-btn {
    display:inline-flex; align-items:center; gap:6px; padding:8px 18px;
    background:rgba(255,255,255,.12); color:white; text-decoration:none;
    border-radius:var(--radius-sm); font-weight:600; font-size:13px;
    border:1px solid rgba(255,255,255,.18); transition:var(--t);
}
.logout-btn:hover { background:rgba(255,255,255,.22); border-color:rgba(107,32,72,.5); color:var(--gold-light); }

/* ── TABS ── */
.tabs-container {
    background:var(--surface); padding:0 28px;
    border-bottom:2px solid var(--border); display:flex; gap:4px;
    box-shadow:0 2px 8px rgba(30,16,64,.05);
}
.tab-btn {
    padding:14px 22px; background:none; border:none;
    color:var(--text-muted); font-weight:600; font-size:14px;
    cursor:pointer; border-bottom:3px solid transparent;
    transition:var(--t); white-space:nowrap; position:relative;
    font-family:'DM Sans',sans-serif;
}
.tab-btn:hover { color:var(--plum-mid); }
.tab-btn.active { color:var(--plum); border-bottom-color:var(--gold); font-weight:700; }
.tab-badge {
    position:absolute; top:10px; right:6px;
    background:#E53E3E; color:white; font-size:10px; font-weight:700;
    padding:2px 6px; border-radius:10px; min-width:18px; text-align:center;
}

/* ── CONTENT ── */
.tab-content { display:none; padding:28px; max-width:1400px; margin:0 auto; }
.tab-content.active { display:block; }
.section-title { font-size:19px; font-weight:700; color:var(--plum); margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid var(--border); display:flex; align-items:center; gap:8px; }

/* ── ORDER CARDS ── */
.order-card {
    background:var(--surface); padding:20px; border-radius:var(--radius);
    margin-bottom:14px; box-shadow:var(--shadow); border:1px solid var(--border); transition:var(--t);
}
.order-card:hover { box-shadow:var(--shadow-md); border-color:var(--plum-pale); transform:translateY(-2px); }
.order-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:12px; border-bottom:2px solid var(--border-soft); }
.order-number { font-weight:700; font-size:16px; color:var(--plum); }
.order-status { padding:5px 14px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
.status-pending    { background:#FEF3C7; color:#854D0E; }
.status-approved   { background:#DBEAFE; color:#1D4ED8; }
.status-processing { background:#EDE9FE; color:#5B21B6; }
.status-completed  { background:var(--green-bg); color:#065F46; }
.status-rejected   { background:var(--red-bg); color:var(--red); }
.status-cancelled  { background:#F1F5F9; color:#475569; }

.customer-info { background:var(--surface2); padding:12px 14px; border-radius:var(--radius-sm); margin-bottom:12px; font-size:13px; border:1px solid var(--border); }
.customer-info strong { color:var(--plum); }
.customer-info div { color:var(--text); margin-top:4px; }

.order-date { color:var(--text-muted); font-size:13px; margin-bottom:10px; }
.order-items { margin:12px 0; }
.order-item-row { display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid var(--border-soft); font-size:14px; color:var(--text); }
.order-item-row:last-child { border-bottom:none; }
.item-name { font-weight:600; color:var(--text); }
.item-details { color:var(--text-muted); font-size:12px; margin-top:2px; }
.order-total-row { display:flex; justify-content:space-between; align-items:center; padding-top:12px; margin-top:12px; border-top:2px solid var(--border); color:var(--text); }
.order-total-val { font-size:18px; font-weight:700; color:var(--plum-mid); }

/* ── ACTION BUTTONS ── */
/* ── MODAL ── */
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(30,16,64,.55); z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
.modal.active { display:flex; }
.modal-content {
    background:var(--surface); padding:32px; border-radius:var(--radius-lg);
    max-width:700px; width:90%; max-height:90vh; overflow-y:auto;
    box-shadow:var(--shadow-lg); animation:slideUp .25s ease;
}
.modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding-bottom:16px; border-bottom:2px solid var(--border); }
.modal-title { font-size:19px; font-weight:700; color:var(--plum); }
.modal-close { background:none; border:none; font-size:26px; color:var(--text-muted); cursor:pointer; line-height:1; padding:2px 6px; border-radius:6px; transition:var(--t); }
.modal-close:hover { background:var(--surface2); color:var(--plum); }

/* ── STOCK CHECK ── */
.stock-check-section { background:var(--surface2); padding:16px; border-radius:var(--radius-sm); margin:16px 0; border:1px solid var(--border); }
.stock-check-title { font-weight:700; color:var(--text); margin-bottom:12px; font-size:15px; }
.stock-item { background:var(--surface); padding:12px 14px; border-radius:var(--radius-sm); margin-bottom:8px; border-left:4px solid #CBD5E0; }
.stock-item.available { border-left-color:var(--green); }
.stock-item.low { border-left-color:var(--orange); }
.stock-item.unavailable { border-left-color:var(--red); }
.stock-item-name { font-weight:600; color:var(--text); margin-bottom:4px; }
.stock-item-info { font-size:13px; color:var(--text); }
.stock-ok   { color:var(--green) !important; font-weight:600; }
.stock-low  { color:var(--orange) !important; font-weight:600; }
.stock-out  { color:var(--red) !important; font-weight:600; }
.overall-status { padding:12px 16px; border-radius:var(--radius-sm); font-weight:700; text-align:center; margin-top:12px; font-size:14px; }
.overall-status.can-fulfill { background:var(--green-bg); color:#065F46; }
.overall-status.cannot-fulfill { background:var(--red-bg); color:var(--red); }

/* ── FORM ── */
.form-group { margin-bottom:20px; }
.form-group label { display:block; color:var(--plum); font-weight:600; font-size:13px; margin-bottom:8px; }
.form-group textarea {
    width:100%; padding:12px 15px; border:1.5px solid var(--border); border-radius:var(--radius-sm);
    font-size:14px; color:var(--text); transition:var(--t); font-family:'DM Sans',sans-serif;
    resize:vertical; min-height:100px; background:var(--surface2);
}
.form-group textarea:focus { outline:none; border-color:var(--gold); background:var(--surface); box-shadow:0 0 0 3px rgba(107,32,72,.12); }

/* ── EMPTY STATE ── */
.empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
.empty-state .icon { font-size:60px; margin-bottom:16px; display:block; opacity:.7; }
.empty-state p { font-size:15px; font-weight:500; }

/* ── ALERTS ── */
.alert { padding:12px 16px; border-radius:var(--radius-sm); margin-bottom:16px; font-size:14px; font-weight:500; border-left:4px solid; }
.alert-success { background:var(--green-bg);  color:#065F46; border-color:var(--green); }
.alert-warning { background:var(--orange-bg); color:#78350F; border-color:var(--orange); }
.alert-danger  { background:var(--red-bg);    color:var(--red); border-color:var(--red); }
.alert-info    { background:var(--blue-bg);   color:#1A4B7A; border-color:var(--blue); }

/* ── DATE BADGES ── */
.date-badge { display:inline-block; padding:5px 12px; border-radius:var(--radius-sm); font-size:12px; font-weight:600; margin:8px 0; }
.date-today   { background:var(--green-bg); color:#065F46; }
.date-advance { background:var(--orange-bg); color:#78350F; }

/* ── HAMBURGER ── */
.hamburger {
    display:none; flex-direction:column; justify-content:center; gap:5px;
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15);
    cursor:pointer; padding:8px 10px; border-radius:8px; transition:var(--t);
}
.hamburger:hover { background:rgba(255,255,255,.2); }
.hamburger span { display:block; width:22px; height:2.5px; background:var(--header-text); border-radius:2px; transition:all .3s; }
.hamburger.open span:nth-child(1) { transform:translateY(7.5px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity:0; }
.hamburger.open span:nth-child(3) { transform:translateY(-7.5px) rotate(-45deg); }

.nav-overlay { display:none; position:fixed; inset:0; background:rgba(30,16,64,.5); z-index:298; backdrop-filter:blur(2px); }
.nav-overlay.active { display:block; }

.nav-drawer {
    position:fixed; top:0; left:-280px; width:265px; height:100vh;
    background:var(--surface); z-index:299; padding:24px 14px;
    box-shadow:4px 0 24px rgba(30,16,64,.22); transition:left .3s ease;
    display:flex; flex-direction:column; gap:4px; overflow-y:auto;
}
.nav-drawer.open { left:0; }
.nav-drawer-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:14px; border-bottom:2px solid var(--border); }
.nav-drawer-title { font-weight:700; color:var(--plum); font-size:16px; }
.nav-drawer-close { background:none; border:none; font-size:22px; color:var(--text-muted); cursor:pointer; padding:2px 6px; border-radius:6px; transition:var(--t); }
.nav-drawer-close:hover { background:var(--surface2); }
.nav-drawer-btn {
    width:100%; padding:13px 14px; background:none; border:none; border-radius:var(--radius-sm);
    text-align:left; font-size:14px; font-weight:600; color:#6B5080; cursor:pointer; transition:var(--t);
    font-family:'DM Sans',sans-serif; position:relative;
}
.nav-drawer-btn:hover { background:var(--surface2); color:var(--plum); }
.nav-drawer-btn.active { background:linear-gradient(135deg,var(--surface2),var(--plum-pale)); color:var(--plum); }
.nav-drawer-badge { position:absolute; right:14px; top:50%; transform:translateY(-50%); background:#E53E3E; color:white; font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px; }
.nav-drawer-divider { height:1px; background:var(--border); margin:8px 0; }

@media (max-width:768px) { .hamburger { display:flex; } .tabs-container { display:none; } }
@media (min-width:769px) { .nav-drawer,.nav-overlay { display:none !important; } .tabs-container { display:flex !important; } }

/* ── INVENTORY ── */
.inv-stat {
    background:var(--surface); padding:18px 16px; border-radius:var(--radius-sm);
    box-shadow:var(--shadow); border-left:4px solid #7B5EA7; text-align:center;
    transition:var(--t);
}
.inv-stat:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
.inv-stat-val { font-size:34px; font-weight:700; color:#7B5EA7; margin:4px 0; }
.inv-stat-lbl { font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.06em; font-weight:600; }

.inv-product-card {
    background:var(--surface); border-radius:var(--radius); overflow:hidden;
    box-shadow:var(--shadow); border:1px solid var(--border); transition:var(--t);
}
.inv-product-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); border-color:var(--plum-pale); }
.inv-product-card.near-expiry { border:2px solid var(--red); }
.inv-product-img {
    width:100%; height:168px; background:linear-gradient(135deg,var(--surface2),var(--plum-pale));
    display:flex; align-items:center; justify-content:center;
    color:var(--text-muted); font-size:13px; font-weight:500; overflow:hidden;
}
.inv-product-img img { width:100%; height:100%; object-fit:cover; }
.inv-product-body { padding:14px; }
.inv-product-name { font-size:15px; font-weight:700; color:var(--plum); margin-bottom:5px; }
.inv-product-desc { font-size:12px; color:var(--text-muted); line-height:1.5; margin-bottom:10px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.inv-product-meta { display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid var(--border-soft); flex-wrap:wrap; gap:6px; }
.inv-price { font-size:19px; font-weight:700; color:var(--plum-mid); }
.inv-stock-badge { padding:4px 10px; border-radius:var(--radius-sm); font-size:11px; font-weight:700; text-transform:uppercase; }
.sGood { background:var(--green-bg); color:#065F46; }
.sLow  { background:var(--orange-bg); color:#78350F; }
.sOut  { background:var(--red-bg); color:var(--red); }

.inv-shelf { margin-top:10px; padding:9px 12px; background:var(--surface2); border-radius:var(--radius-sm); font-size:12px; border:1px solid var(--border); }
.shelf-tag { display:inline-block; padding:3px 9px; border-radius:6px; font-weight:700; font-size:11px; text-transform:uppercase; margin-top:4px; }
.sFresh { background:var(--green-bg); color:#065F46; }
.sWarn  { background:var(--orange-bg); color:#78350F; }
.sCrit  { background:var(--red-bg); color:var(--red); }

.d-product-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:13px 18px; border-bottom:1px solid var(--border-soft); gap:10px; flex-wrap:wrap;
    transition:var(--t);
}
.d-product-row:last-child { border-bottom:none; }
.d-product-row:hover { background:var(--surface2); }
.d-product-row.selected { background:linear-gradient(135deg,var(--surface2),var(--plum-pale)); }
.d-pname { font-weight:700; color:var(--plum); font-size:14px; }
.d-pmeta { font-size:12px; color:var(--text-muted); margin-top:3px; }
.d-badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; }
.db-danger  { background:var(--red-bg);    color:var(--red); }
.db-warning { background:var(--orange-bg); color:#78350F; }
.db-success { background:var(--green-bg);  color:#065F46; }

.d-history-item {
    background:var(--surface); border-radius:var(--radius-sm); padding:14px 18px; margin-bottom:10px;
    box-shadow:var(--shadow); border-left:4px solid var(--red);
}
.d-hist-hdr { display:flex; justify-content:space-between; flex-wrap:wrap; gap:6px; margin-bottom:6px; }
.d-hist-name { font-weight:700; color:var(--plum); font-size:15px; }
.d-hist-date { color:var(--text-muted); font-size:13px; }
.d-reason-tag { display:inline-block; padding:3px 10px; background:var(--red-bg); color:var(--red); border-radius:6px; font-size:11px; font-weight:600; margin-top:6px; }

/* ── RESPONSIVE ── */
@media (max-width:768px) {
    .top-header { padding:0 14px; height:56px; }
    .top-header h1 { font-size:15px; }
    .top-header h1 img { height:28px; }
    .user-chip span { display:none; }
    .logout-btn { padding:7px 12px; font-size:12px; }
    .tab-content { padding:14px; }
    .section-title { font-size:17px; }
    .btn { width:100%; padding:12px; font-size:14px; }
    .modal-content { padding:20px; border-radius:var(--radius); }
    .order-card { padding:14px; }
    .order-header { flex-wrap:wrap; gap:8px; }
    .form-group textarea { min-height:80px; }
    .stock-check-section { padding:12px; }
}
@media (max-width:480px) { .top-header h1 span { display:none; } }

/* ══ DARK THEME OVERRIDES ══ */
[data-theme="dark"],
[data-theme="dark"] html { background: #1A1426; }
[data-theme="dark"] body { background: #1A1426 !important; color: var(--text); }
[data-theme="dark"] .tabs-container { background: #241C38; border-bottom-color: rgba(155,127,191,0.15); }
[data-theme="dark"] .tab-btn { color: rgba(155,127,191,0.6); }
[data-theme="dark"] .tab-btn:hover { color: #B89FD4; }
[data-theme="dark"] .tab-btn.active { color: #F0EAF8; border-bottom-color: #9B7FBF; }
[data-theme="dark"] .product-card { background: #241C38; border-color: rgba(155,127,191,0.15); }
[data-theme="dark"] .product-card:hover { border-color: rgba(155,127,191,0.35); }
[data-theme="dark"] .product-name { color: #F0EAF8; }
[data-theme="dark"] .product-desc { color: rgba(240,234,248,0.6); }
[data-theme="dark"] .product-body { background: #241C38; }
[data-theme="dark"] .product-footer { border-top-color: rgba(155,127,191,0.12); }
[data-theme="dark"] .section-title { color: #B89FD4; border-bottom-color: rgba(155,127,191,0.2); }
[data-theme="dark"] .cart-panel { background: #241C38; border-color: rgba(155,127,191,0.15); }
[data-theme="dark"] .cart-panel h2 { color: #F0EAF8; border-bottom-color: rgba(155,127,191,0.15); }
[data-theme="dark"] .cart-item { background: #2E2448; border-color: rgba(155,127,191,0.15); }
[data-theme="dark"] .cart-item-name { color: #F0EAF8; }
[data-theme="dark"] .cart-total-row { border-top-color: rgba(155,127,191,0.2); }
[data-theme="dark"] .order-card { background: #241C38; border-color: rgba(155,127,191,0.15); }
[data-theme="dark"] .order-header { border-bottom-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .order-number { color: #B89FD4; }
[data-theme="dark"] .order-date { color: rgba(155,127,191,0.7); }
[data-theme="dark"] .order-item-row { border-bottom-color: rgba(155,127,191,0.1); color: #F0EAF8; }
[data-theme="dark"] .item-name { color: #F0EAF8; }
[data-theme="dark"] .item-details { color: #B89FD4; }
[data-theme="dark"] .order-total-row { border-top-color: rgba(155,127,191,0.2); color: #F0EAF8; }
[data-theme="dark"] .order-total-val { color: #C4A8E0; }
[data-theme="dark"] .modal-content, [data-theme="dark"] .modal-box { background: #2E2448; border: 1px solid rgba(155,127,191,0.2); color: #F0EAF8; }
[data-theme="dark"] .modal-header { border-bottom-color: rgba(155,127,191,0.15); }
[data-theme="dark"] .modal-title { color: #F0EAF8; }
[data-theme="dark"] .modal-close { color: rgba(155,127,191,0.6); }
[data-theme="dark"] .form-group label { color: rgba(240,234,248,0.8); }
[data-theme="dark"] .form-group input,
[data-theme="dark"] .form-group select,
[data-theme="dark"] .form-group textarea { background: #1A1426; border-color: rgba(155,127,191,0.2); color: #F0EAF8; }
[data-theme="dark"] .form-group input:focus,
[data-theme="dark"] .form-group select:focus,
[data-theme="dark"] .form-group textarea:focus { border-color: #9B7FBF; background: #241C38; }
[data-theme="dark"] .nav-drawer { background: #241C38; }
[data-theme="dark"] .nav-drawer-title { color: #F0EAF8; }
[data-theme="dark"] .nav-drawer-btn { color: rgba(155,127,191,0.7); }
[data-theme="dark"] .nav-drawer-btn:hover { background: #2E2448; color: #F0EAF8; }
[data-theme="dark"] .nav-drawer-btn.active { background: rgba(155,127,191,0.15); color: #B89FD4; }
[data-theme="dark"] .nav-drawer-divider { background: rgba(155,127,191,0.1); }
[data-theme="dark"] .empty-state { color: rgba(155,127,191,0.5); }
[data-theme="dark"] .profile-card { background: #241C38; border-color: rgba(155,127,191,0.15); }
[data-theme="dark"] .profile-form-group label { color: rgba(240,234,248,0.8); }
[data-theme="dark"] .profile-form-group input { background: #1A1426; border-color: rgba(155,127,191,0.2); color: #F0EAF8; }
[data-theme="dark"] .confirm-modal, [data-theme="dark"] .confirm-box { background: #2E2448; border: 1px solid rgba(155,127,191,0.2); }
[data-theme="dark"] .confirm-modal h3, [data-theme="dark"] .confirm-box h3 { color: #F0EAF8; }
[data-theme="dark"] .confirm-modal p, [data-theme="dark"] .confirm-box p { color: rgba(240,234,248,0.65); }
[data-theme="dark"] .confirm-no, [data-theme="dark"] .cbtn-no { background: #241C38; color: #9B7FBF; border-color: rgba(155,127,191,0.2); }
[data-theme="dark"] .menu-search { background-color: #241C38; border-color: rgba(155,127,191,0.2); color: #F0EAF8; }
[data-theme="dark"] .sidebar { background: #241C38; border-right-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .nav-item { color: rgba(155,127,191,0.65); }
[data-theme="dark"] .nav-item:hover { background: rgba(155,127,191,0.08); color: #F0EAF8; }
[data-theme="dark"] .nav-item.active { background: linear-gradient(135deg,rgba(155,127,191,0.2),rgba(155,127,191,0.1)); color: #B89FD4; }
[data-theme="dark"] .data-table { background: #241C38; border-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .data-table th { background: #2E2448; color: rgba(155,127,191,0.7); border-bottom-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .data-table td { border-bottom-color: rgba(155,127,191,0.08); color: #F0EAF8; }
[data-theme="dark"] .data-table tbody tr:hover td { background: rgba(155,127,191,0.05); }
[data-theme="dark"] .panel-card { background: #241C38; border-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .staff-item { background: #2E2448; border-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .staff-name { color: #F0EAF8; }
[data-theme="dark"] .customer-info { background: #2E2448; border-color: rgba(155,127,191,0.1); color: #F0EAF8; }
[data-theme="dark"] .customer-info strong { color: #C4A8E0; }
[data-theme="dark"] .customer-info div { color: #F0EAF8; }
[data-theme="dark"] .stock-check-section { background: #2E2448; border-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .stock-check-title { color: #F0EAF8; }
[data-theme="dark"] .stock-item { background: #241C38; color: #F0EAF8; }
[data-theme="dark"] .stock-item-name { color: #F0EAF8; }
[data-theme="dark"] .stock-item-info { color: #EDE7F6; }
[data-theme="dark"] .stock-ok  { color: #4ADE80 !important; }
[data-theme="dark"] .stock-low { color: #FBBF24 !important; }
[data-theme="dark"] .stock-out { color: #F87171 !important; }
[data-theme="dark"] .overall-status.can-fulfill { background: rgba(74,222,128,0.18); color: #86EFAC; }
[data-theme="dark"] .overall-status.cannot-fulfill { background: rgba(248,113,113,0.18); color: #FCA5A5; }
[data-theme="dark"] .om-filters { background: #241C38; border-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .om-filters input, [data-theme="dark"] .om-filters select { background: #1A1426; border-color: rgba(155,127,191,0.2); color: #F0EAF8; }
[data-theme="dark"] .om-table thead { background: #2E2448; }
[data-theme="dark"] .om-table th { color: rgba(155,127,191,0.7); border-bottom-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .om-table td { color: #F0EAF8; border-bottom-color: rgba(155,127,191,0.08); }
[data-theme="dark"] .om-table tbody tr:hover { background: rgba(155,127,191,0.05); }
[data-theme="dark"] .welcome-banner { background: linear-gradient(135deg, #2E2448, #3D2468); border-color: rgba(155,127,191,0.15); }
[data-theme="dark"] .welcome-banner h3 { color: #F0EAF8; }
[data-theme="dark"] .welcome-banner p { color: rgba(155,127,191,0.7); }
[data-theme="dark"] .form-card { background: #241C38; border-color: rgba(155,127,191,0.1); }
[data-theme="dark"] .alert, [data-theme="dark"] .alert-box { background: #2E2448; }

/* End of theme overrides */


/* ── ENHANCED ACTION BUTTONS ── */
.action-buttons { display: flex; gap: 10px; margin-top: 16px; padding-top: 16px; border-top: 2px solid var(--border-soft); }
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 10px 18px; border: 1.5px solid transparent; border-radius: 9px;
    font-weight: 600; font-size: 13.5px; cursor: pointer;
    transition: all 0.18s cubic-bezier(0.4,0,0.2,1); flex: 1;
    font-family: 'DM Sans', sans-serif; letter-spacing: 0.01em;
}
.btn:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.18); }
.btn:active { transform: scale(0.96); }
.btn:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }

.btn-review  { background: #7B5EA7; color: white; box-shadow: 0 2px 8px rgba(123,94,167,0.28); }
.btn-review:hover  { background: #5A3F84; box-shadow: 0 4px 14px rgba(123,94,167,0.38); }
.btn-approve { background: #E8F5E9; color: #1B5E20; border-color: rgba(76,175,80,0.28); box-shadow: 0 1px 4px rgba(76,175,80,0.15); }
.btn-approve:hover { background: #C8E6C9; border-color: rgba(76,175,80,0.45); box-shadow: 0 4px 12px rgba(76,175,80,0.25); }
.btn-reject  { background: #F44336; color: white; box-shadow: 0 2px 8px rgba(244,67,54,0.28); }
.btn-reject:hover  { background: #D32F2F; box-shadow: 0 4px 14px rgba(244,67,54,0.38); }
.btn-view    { background: #EDE7F6; color: #7B5EA7; border-color: rgba(123,94,167,0.28); }
.btn-view:hover    { background: #D1C4E9; border-color: rgba(123,94,167,0.45); }

/* ── ENHANCED INV FILTER / SUB BUTTONS ── */
.inv-sub-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border: 1.5px solid rgba(123,94,167,0.20); border-radius: 20px;
    background: #F3EEF9; color: #757575; font-weight: 600; font-size: 13px;
    cursor: pointer; transition: all 0.18s; font-family: 'DM Sans', sans-serif;
}
.inv-sub-btn:hover { border-color: rgba(123,94,167,0.40); color: #7B5EA7; background: #EDE7F6; transform: translateY(-1px); }
.inv-sub-btn.active { background: #7B5EA7; border-color: transparent; color: white; box-shadow: 0 2px 10px rgba(123,94,167,0.30); }

.inv-filter {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border: 1.5px solid rgba(123,94,167,0.18); border-radius: 20px;
    background: #F3EEF9; color: #757575; font-weight: 600; font-size: 12.5px;
    cursor: pointer; transition: all 0.18s; font-family: 'DM Sans', sans-serif;
}
.inv-filter:hover { border-color: rgba(123,94,167,0.38); color: #7B5EA7; background: #EDE7F6; transform: translateY(-1px); }
.inv-filter.active { background: #7B5EA7; border-color: transparent; color: white; box-shadow: 0 2px 8px rgba(123,94,167,0.28); }

/* ── ENHANCED DISCARD BUTTON ── */
.btn-discard {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    width: 110px; flex-shrink: 0; padding: 11px 18px; background: #F44336; color: white; border: none;
    border-radius: 10px; font-size: 14px; font-weight: 700;
    cursor: pointer; transition: all 0.18s;
    box-shadow: 0 2px 10px rgba(244,67,54,0.28);
}
.btn-discard:hover { background: #D32F2F; transform: translateY(-2px); box-shadow: 0 4px 14px rgba(244,67,54,0.38); }
.btn-discard:active { transform: scale(0.97); }


/* ══ WALK-IN POS ══ */
.wi-product-card {
    background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius);
    overflow:hidden;cursor:pointer;transition:var(--t);box-shadow:var(--shadow);
    display:flex;flex-direction:column;
}
.wi-product-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:#C4A8E0;}
.wi-product-card.wi-out{opacity:.5;cursor:not-allowed;pointer-events:none;}
.wi-product-img{width:100%;height:140px;background:linear-gradient(135deg,var(--surface2),#ede7f6);overflow:hidden;position:relative;}
.wi-product-img img{width:100%;height:100%;object-fit:cover;}
.wi-sbadge{position:absolute;top:8px;right:8px;padding:3px 9px;border-radius:12px;font-size:11px;font-weight:700;backdrop-filter:blur(4px);}
.wi-product-body{padding:12px;flex:1;display:flex;flex-direction:column;}
.wi-product-name{font-size:14px;font-weight:700;color:#5A3F84;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.wi-product-price{font-size:18px;font-weight:800;color:#7B5EA7;margin-top:auto;padding-top:8px;}
.wi-add-btn{width:100%;margin-top:10px;padding:9px;border:none;background:#7B5EA7;color:#fff;border-radius:var(--radius-sm);font-weight:700;font-size:13px;cursor:pointer;transition:var(--t);font-family:'DM Sans',sans-serif;}
.wi-add-btn:hover{background:#5A3F84;transform:scale(1.02);}

/* Cart items */
.wi-cart-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border-soft);}
.wi-cart-item:last-child{border-bottom:none;}
.wi-cart-name{flex:1;font-size:13px;font-weight:600;color:var(--text);}
.wi-cart-price{font-size:13px;color:#7B5EA7;font-weight:700;min-width:72px;text-align:right;}
.wi-qty-ctrl{display:flex;align-items:center;gap:4px;}
.wi-qty-btn{width:26px;height:26px;border:1.5px solid var(--border);background:var(--surface2);border-radius:6px;font-size:15px;font-weight:700;cursor:pointer;transition:var(--t);display:flex;align-items:center;justify-content:center;color:#7B5EA7;padding:0;line-height:1;}
.wi-qty-btn:hover{background:#7B5EA7;color:#fff;border-color:#7B5EA7;}
.wi-qty-num{min-width:24px;text-align:center;font-weight:700;font-size:14px;color:var(--text);}

/* Payment buttons */
.wi-pay-btn{padding:10px 8px;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface2);color:var(--text-muted);font-weight:700;font-size:14px;cursor:pointer;transition:var(--t);font-family:'DM Sans',sans-serif;}
.wi-pay-btn:hover{border-color:#9B7FBF;color:#7B5EA7;}
.wi-pay-active{background:#7B5EA7!important;color:#fff!important;border-color:#7B5EA7!important;box-shadow:0 2px 10px rgba(123,94,167,.35);}

/* Cart panel wrapper */
.wi-panel{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-md);overflow:hidden;}
.wi-panel-hdr{background:#7B5EA7;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;}

/* Label text */
.wi-label{display:block;font-size:11px;font-weight:700;color:#7B5EA7;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;}

/* Totals strip */
.wi-totals{padding:10px 20px;border-top:1.5px solid var(--border);background:var(--surface2);}
.wi-total-label{color:var(--text-muted);font-size:12px;font-weight:600;}
.wi-total-count{font-weight:700;color:#7B5EA7;}
.wi-total-text{font-size:15px;font-weight:700;color:var(--text);}
.wi-total-amt{font-size:21px;font-weight:800;color:#7B5EA7;}

/* ── DARK MODE OVERRIDES ── */
[data-theme="dark"] .wi-product-card{background:#241C38;border-color:rgba(155,127,191,.2);}
[data-theme="dark"] .wi-product-card:hover{border-color:rgba(155,127,191,.5);box-shadow:0 6px 24px rgba(0,0,0,.5);}
[data-theme="dark"] .wi-product-name{color:#C4A8E0;}
[data-theme="dark"] .wi-product-price{color:#D4B8F0;}
[data-theme="dark"] .wi-add-btn{background:#9B7FBF;}
[data-theme="dark"] .wi-add-btn:hover{background:#7B5EA7;}
[data-theme="dark"] .wi-panel{background:#241C38;border-color:rgba(155,127,191,.2);}
[data-theme="dark"] .wi-panel-hdr{background:#3D2468;}
[data-theme="dark"] .wi-cart-item{border-bottom-color:rgba(155,127,191,.12);}
[data-theme="dark"] .wi-cart-name{color:#F0EAF8;}
[data-theme="dark"] .wi-cart-price{color:#C4A8E0;}
[data-theme="dark"] .wi-qty-btn{background:#2E2448;border-color:rgba(155,127,191,.25);color:#B89FD4;}
[data-theme="dark"] .wi-qty-btn:hover{background:#7B5EA7;color:#fff;border-color:#7B5EA7;}
[data-theme="dark"] .wi-qty-num{color:#F0EAF8;}
[data-theme="dark"] .wi-pay-btn{background:#2E2448;border-color:rgba(155,127,191,.25);color:#B89FD4;}
[data-theme="dark"] .wi-pay-btn:hover{border-color:#9B7FBF;color:#C4A8E0;}
[data-theme="dark"] .wi-totals{background:#2E2448;border-top-color:rgba(155,127,191,.15);}
[data-theme="dark"] .wi-total-label{color:#8E7BAA;}
[data-theme="dark"] .wi-total-count{color:#C4A8E0;}
[data-theme="dark"] .wi-total-text{color:#F0EAF8;}
[data-theme="dark"] .wi-total-amt{color:#D4B8F0;}
[data-theme="dark"] .wi-label{color:#B89FD4;}
[data-theme="dark"] #wiSearch,
[data-theme="dark"] #wiCustName,
[data-theme="dark"] #wiGcashRef,
[data-theme="dark"] #wiTendered{background:#1A1426;border-color:rgba(155,127,191,.25);color:#F0EAF8;}
[data-theme="dark"] #wiSearch::placeholder,
[data-theme="dark"] #wiCustName::placeholder,
[data-theme="dark"] #wiGcashRef::placeholder,
[data-theme="dark"] #wiTendered::placeholder{color:rgba(155,127,191,.5);}
[data-theme="dark"] .wi-panel > div:last-child{background:#241C38;}
[data-theme="dark"] #wiChangeRow{background:rgba(102,187,106,.15)!important;}
[data-theme="dark"] #wiChangeRow span:first-child{color:#86EFAC!important;}
[data-theme="dark"] #wiChangeAmt{color:#86EFAC!important;}
[data-theme="dark"] #wiCheckoutBtn{background:#7B5EA7;}
[data-theme="dark"] #wiCheckoutBtn:hover{background:#9B7FBF;}

@media(max-width:860px){#wiLayout{grid-template-columns:1fr!important;}#wiLayout>div:last-child{position:static!important;}}
</style></head>
<body>

<!-- MAJOY LOADER -->
<div id="mjLoader" style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;overflow:hidden;transition:opacity .6s,visibility .6s;">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 20% 10%,rgba(107,32,72,.22) 0%,transparent 60%),radial-gradient(ellipse 60% 80% at 80% 90%,rgba(107,32,72,.15) 0%,transparent 60%),linear-gradient(160deg,#100828 0%,#1e1040 50%,#18102c 100%);animation:mjLbg 4s ease-in-out infinite alternate;"></div>
  <div style="position:absolute;inset:0;pointer-events:none;">
    <span style="position:absolute;left:12%;top:20%;font-size:26px;opacity:0;animation:mjP 5s ease-in-out infinite 0s">&#x1F370;</span>
    <span style="position:absolute;left:82%;top:15%;font-size:18px;opacity:0;animation:mjP 5s ease-in-out infinite .4s">&#x1F9C1;</span>
    <span style="position:absolute;left:72%;top:70%;font-size:24px;opacity:0;animation:mjP 5s ease-in-out infinite .8s">&#x1F353;</span>
    <span style="position:absolute;left:18%;top:75%;font-size:20px;opacity:0;animation:mjP 5s ease-in-out infinite 1.2s">&#x1F36E;</span>
    <span style="position:absolute;left:50%;top:10%;font-size:16px;opacity:0;animation:mjP 5s ease-in-out infinite .2s">&#x2728;</span>
    <span style="position:absolute;left:90%;top:50%;font-size:22px;opacity:0;animation:mjP 5s ease-in-out infinite .6s">&#x1F338;</span>
    <span style="position:absolute;left:5%;top:50%;font-size:19px;opacity:0;animation:mjP 5s ease-in-out infinite 1s">&#x1F36C;</span>
    <span style="position:absolute;left:40%;top:85%;font-size:28px;opacity:0;animation:mjP 5s ease-in-out infinite 1.4s">&#x1F382;</span>
    <span style="position:absolute;left:65%;top:88%;font-size:17px;opacity:0;animation:mjP 5s ease-in-out infinite .3s">&#x2B50;</span>
    <span style="position:absolute;left:28%;top:8%;font-size:21px;opacity:0;animation:mjP 5s ease-in-out infinite .9s">&#x1F337;</span>
  </div>
  <div style="position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;text-align:center;padding:0 24px;animation:mjCardIn .8s cubic-bezier(.34,1.56,.64,1) forwards;opacity:0;">
    <div style="position:relative;width:110px;height:110px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
      <div style="position:absolute;inset:0;animation:mjRing 2.8s linear infinite;">
        <svg viewBox="0 0 120 120" fill="none" style="width:100%;height:100%;"><circle cx="60" cy="60" r="54" stroke="url(#mjGrad)" stroke-width="2.5" stroke-dasharray="340" stroke-dashoffset="340" class="mjCirc" stroke-linecap="round"/><defs><linearGradient id="mjGrad" x1="0" y1="0" x2="120" y2="120"><stop offset="0%" stop-color="#d4a0b8"/><stop offset="50%" stop-color="#6b2048"/><stop offset="100%" stop-color="#4a1030"/></linearGradient></defs></svg>
      </div>
      <div style="position:relative;z-index:1;width:84px;height:84px;border-radius:50%;overflow:hidden;border:2px solid rgba(107,32,72,.35);box-shadow:0 0 0 6px rgba(107,32,72,.08),0 8px 32px rgba(0,0,0,.4);animation:mjLogoPulse 2.5s ease-in-out infinite;">
        <img src="logo.png" alt="Majoy" style="width:100%;height:100%;object-fit:cover;">
      </div>
    </div>
    <h1 style="font-family:Georgia,serif;font-size:36px;font-weight:700;font-style:italic;background:linear-gradient(135deg,#d4a0b8,#6b2048,#7d2d55);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;background-size:200% auto;animation:mjShimmer 2s linear infinite;margin-top:4px;">Majoy's</h1>
    <p style="margin-top:3px;font-size:11px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.4);">Homemade Sweets</p>
    <div style="display:flex;gap:7px;margin-top:22px;">
      <span style="width:7px;height:7px;border-radius:50%;background:linear-gradient(135deg,#6b2048,#7d2d55);display:inline-block;animation:mjDot 1.2s ease-in-out infinite 0s;"></span>
      <span style="width:7px;height:7px;border-radius:50%;background:linear-gradient(135deg,#6b2048,#7d2d55);display:inline-block;animation:mjDot 1.2s ease-in-out infinite .18s;"></span>
      <span style="width:7px;height:7px;border-radius:50%;background:linear-gradient(135deg,#6b2048,#7d2d55);display:inline-block;animation:mjDot 1.2s ease-in-out infinite .36s;"></span>
    </div>
    <p style="margin-top:12px;font-size:12px;color:rgba(255,255,255,.35);letter-spacing:.05em;">Freshly baked with love<span id="mjEll"></span></p>
  </div>
</div>
<style>
</style>
<script>
(function(){
  var el=document.getElementById("mjEll");
  if(el){var d=["",".","..","."],i=0;setInterval(function(){el.textContent=d[i=(i+1)%4]},450);}
  function hide(){var l=document.getElementById("mjLoader");if(!l)return;var waited=Date.now()-window._mjStart;setTimeout(function(){l.style.opacity="0";l.style.visibility="hidden";setTimeout(function(){if(l.parentNode)l.parentNode.removeChild(l);},650);},Math.max(0,1600-waited));}
  window._mjStart=Date.now();
  if(document.readyState==="complete")hide();
  else{window.addEventListener("load",hide);setTimeout(hide,4000);}
})();
</script>

<!-- TOP HEADER -->
<header class="top-header">
    <div style="display:flex;align-items:center;gap:10px;">
        <button class="hamburger" id="hamburgerBtn" onclick="toggleNavDrawer()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <h1>
            <img src="logo.png" alt="Logo">
            <span>Staff Dashboard</span>
        </h1>
    </div>
    <div class="header-right">
    <button class="theme-toggle-btn" id="mjThemeBtn" onclick="mjCycleTheme()" title="Switch theme">🌙</button>
        <div class="user-chip">
            <strong><?= htmlspecialchars($_SESSION['fullname']) ?></strong><br>
            <span>Staff Member</span>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</header>

<!-- Mobile Nav Overlay & Drawer -->
<div class="nav-overlay" id="navOverlay" onclick="closeNavDrawer()"></div>
<div class="nav-drawer" id="navDrawer">
    <div class="nav-drawer-header">
        <span class="nav-drawer-title">📋 Navigation</span>
        <button class="nav-drawer-close" onclick="closeNavDrawer()">✕</button>
    </div>
    <button class="nav-drawer-btn active" id="drawerIncoming" onclick="switchTab('incoming'); closeNavDrawer()">
        📥 Incoming
        <span class="nav-drawer-badge" id="drawerIncomingBadge" style="display:none;">0</span>
    </button>
    <button class="nav-drawer-btn" id="drawerApproved" onclick="switchTab('approved'); closeNavDrawer()">✅ Approved</button>
    <button class="nav-drawer-btn" id="drawerRejected" onclick="switchTab('rejected'); closeNavDrawer()">❌ Rejected</button>
    <button class="nav-drawer-btn" id="drawerAll" onclick="switchTab('all'); closeNavDrawer()">📋 All Orders</button>
    <button class="nav-drawer-btn" id="drawerInventory" onclick="switchTab('inventory'); closeNavDrawer()">📦 Inventory</button>
    <button class="nav-drawer-btn" id="drawerWalkin" onclick="switchTab('walkin'); closeNavDrawer()">🛒 Walk-in Customer</button>
    <button class="nav-drawer-btn" onclick="window.location.href='materials.php'">🧂 Raw Materials &amp; Purchasing</button>
    <div class="nav-drawer-divider"></div>
    <a href="logout.php" style="display:block; padding:14px 16px; color:#f56565; font-weight:600; text-decoration:none; border-radius:10px; font-size:15px;">🚪 Logout</a>
</div>

<!-- TABS -->
<div class="tabs-container">
    <button class="tab-btn active" onclick="switchTab('incoming')" style="position:relative;">
        📥 Incoming
        <span class="tab-badge" id="incomingBadge" style="display:none;">0</span>
    </button>
    <button class="tab-btn" onclick="switchTab('approved')">✅ Approved</button>
    <button class="tab-btn" onclick="switchTab('rejected')">❌ Rejected</button>
    <button class="tab-btn" onclick="switchTab('all')">📋 All</button>
    <button class="tab-btn" onclick="switchTab('inventory')" style="position:relative;">
        📦 Inventory
        <span class="tab-badge" id="expiryBadge" style="display:none;">!</span>
    </button>
    <button class="tab-btn" onclick="switchTab('walkin')">🛒 Walk-in</button>
</div>

<!-- INCOMING ORDERS TAB -->
<div id="incomingTab" class="tab-content active">
    <h2 class="section-title">📥 Incoming Customer Orders (Pending Review)</h2>
    <div id="incomingOrders">
        <div class="empty-state"><div class="icon">⏳</div><p>Loading orders...</p></div>
    </div>
</div>

<!-- APPROVED ORDERS TAB -->
<div id="approvedTab" class="tab-content">
    <h2 class="section-title">✅ Approved Orders (Sent to Admin)</h2>
    <div id="approvedOrders">
        <div class="empty-state"><div class="icon">⏳</div><p>Loading orders...</p></div>
    </div>
</div>

<!-- REJECTED ORDERS TAB -->
<div id="rejectedTab" class="tab-content">
    <h2 class="section-title">❌ Rejected Orders</h2>
    <div id="rejectedOrders">
        <div class="empty-state"><div class="icon">⏳</div><p>Loading orders...</p></div>
    </div>
</div>

<!-- ALL ORDERS TAB -->
<div id="allTab" class="tab-content">
    <h2 class="section-title">📋 All Orders</h2>
    <div id="allOrders">
        <div class="empty-state"><div class="icon">⏳</div><p>Loading orders...</p></div>
    </div>
</div>

<!-- INVENTORY TAB -->
<div id="inventoryTab" class="tab-content">
    <h2 class="section-title">📦 Inventory</h2>

    <!-- Sub-tabs -->
    <div style="display:flex; gap:6px; margin-bottom:20px; flex-wrap:wrap;">
        <button class="inv-sub-btn active" onclick="switchInvSub('view', this)">📋 View Inventory</button>
        <button class="inv-sub-btn" onclick="switchInvSub('discard', this)">🗑️ Discard Items</button>
        <button class="inv-sub-btn" onclick="switchInvSub('history', this)">📜 History</button>
    </div>

    <!-- ── VIEW INVENTORY ── -->
    <div id="invView" class="inv-sub-pane active">
        <div style="background:#e9f7ef;border-left:4px solid #38a169;padding:11px 16px;border-radius:8px;font-size:13px;color:#22543d;margin-bottom:18px;">
            ℹ️ <strong>View Only</strong> — You can view stock and expiry info. To add, edit, or archive products, contact the Admin/Owner.
        </div>
        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px;">
            <div class="inv-stat"><div class="inv-stat-val" id="iTotal">—</div><div class="inv-stat-lbl">Products</div></div>
            <div class="inv-stat"><div class="inv-stat-val" id="iStock">—</div><div class="inv-stat-lbl">Total Stock</div></div>
            <div class="inv-stat"><div class="inv-stat-val" id="iFresh" style="color:#38a169;">—</div><div class="inv-stat-lbl">Fresh</div></div>
            <div class="inv-stat"><div class="inv-stat-val" id="iExpiring" style="color:#dd6b20;">—</div><div class="inv-stat-lbl">Expiring Soon</div></div>
            <div class="inv-stat"><div class="inv-stat-val" id="iLow" style="color:#e53e3e;">—</div><div class="inv-stat-lbl">Low Stock</div></div>
        </div>
        <!-- Search -->
        <input type="text" id="invSearch" placeholder="🔍 Search products..."
               style="width:100%;padding:12px 16px;font-size:14px;border:2px solid var(--border);border-radius:10px;background:var(--surface2);color:var(--text);margin-bottom:16px;">
        <!-- Filter pills -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;" id="invFilters">
            <button class="inv-filter active" onclick="setInvFilter('all',this)">All</button>
            <button class="inv-filter" onclick="setInvFilter('expiring',this)">⚠ Near Expiry</button>
            <button class="inv-filter" onclick="setInvFilter('expired',this)">❌ Expired</button>
            <button class="inv-filter" onclick="setInvFilter('low',this)">📉 Low Stock</button>
            <button class="inv-filter" onclick="setInvFilter('out',this)">🚫 Out of Stock</button>
        </div>
        <div id="invGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;"></div>
    </div>

    <!-- ── DISCARD ── -->
    <div id="invDiscard" class="inv-sub-pane" style="display:none;">
        <div style="background:#cfe2ff;border-left:4px solid #0d6efd;padding:12px 16px;border-radius:8px;font-size:13px;color:#084298;margin-bottom:18px;">
            📌 Select a near-expiry or expired product, enter the quantity and reason, then confirm.
        </div>
        <!-- Discard form card -->
        <div id="dFormCard" style="display:none;background:var(--surface);border:2px solid var(--brand);border-radius:12px;padding:22px;margin-bottom:20px;animation:fadeIn .3s;">
            <h3 style="margin-bottom:14px;color:var(--text);">📋 Discard Details</h3>
            <div id="dSelInfo" style="background:#f7f5fa;border:2px solid #3d2268;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px;"></div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-weight:600;color:var(--text);margin-bottom:6px;font-size:14px;">Quantity to Discard *</label>
                <input type="number" id="dQty" min="1"
                       style="width:100%;padding:11px 14px;border:2px solid var(--border);border-radius:8px;font-size:14px;color:var(--text);background:var(--surface2);">
                <small style="color:var(--plum-mid);font-size:12px;">Available: <strong id="dAvail">0</strong></small>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-weight:600;color:var(--text);margin-bottom:6px;font-size:14px;">Reason *</label>
                <select id="dReason" onchange="onDReasonChange()"
                        style="width:100%;padding:11px 14px;border:2px solid var(--border);border-radius:8px;font-size:14px;color:var(--text);background:var(--surface2);">
                    <option value="">-- Select Reason --</option>
                    <option value="Expired">Expired — Past shelf life</option>
                    <option value="Damaged">Damaged — Physical damage</option>
                    <option value="Quality Issue">Quality Issue</option>
                    <option value="Contaminated">Contaminated — Food safety concern</option>
                    <option value="Overproduction">Overproduction</option>
                    <option value="Customer Return">Customer Return</option>
                    <option value="Other">Other (specify below)</option>
                </select>
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-weight:600;color:var(--text);margin-bottom:6px;font-size:14px;">Additional Notes</label>
                <textarea id="dNotes" rows="3"
                          style="width:100%;padding:11px 14px;border:2px solid var(--border);border-radius:8px;font-size:14px;color:var(--text);background:var(--surface2);resize:vertical;"
                          placeholder="Add any extra details..."></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button onclick="submitDiscard()"
                        style="flex:1;padding:11px;background:linear-gradient(135deg,#f56565,#e53e3e);color:white;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;">
                    🗑️ Confirm Discard
                </button>
                <button onclick="cancelDForm()"
                        style="padding:11px 20px;background:var(--surface2);border:2px solid var(--border);border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;color:var(--text);">
                    Cancel
                </button>
            </div>
        </div>
        <!-- Product list -->
        <div style="background:var(--surface);border-radius:12px;border:1px solid var(--border);overflow:hidden;">
            <div style="padding:12px 18px;background:var(--surface2);border-bottom:2px solid var(--border);font-weight:700;font-size:15px;color:var(--text);">
                Select a Product to Discard
            </div>
            <div id="dProductList"></div>
        </div>
    </div>

    <!-- ── HISTORY ── -->
    <div id="invHistory" class="inv-sub-pane" style="display:none;">
        <div id="dHistoryList"></div>
    </div>
</div>

<!-- ██ WALK-IN CUSTOMER TAB ██ -->
<div id="walkinTab" class="tab-content">
    <h2 class="section-title">🛒 Walk-in Customer — Point of Sale</h2>

    <div id="wiLayout" style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

        <!-- LEFT: Product menu -->
        <div>
            <div style="position:relative;margin-bottom:18px;">
                <input id="wiSearch" type="text" placeholder="🔍  Search products…" oninput="wiFilter()"
                    style="width:100%;padding:11px 16px 11px 14px;border:1.5px solid var(--border);
                           border-radius:var(--radius-sm);font-size:14px;background:var(--surface);
                           color:var(--text);font-family:'DM Sans',sans-serif;transition:var(--t);"
                    onfocus="this.style.borderColor='var(--plum-mid)'" onblur="this.style.borderColor='var(--border)'">
            </div>
            <div id="wiGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:16px;">
                <div class="empty-state" style="grid-column:1/-1;"><div class="icon">⏳</div><p>Loading menu…</p></div>
            </div>
        </div>

        <!-- RIGHT: Cart + payment -->
        <div style="position:sticky;top:80px;">
            <div class="wi-panel">

                <!-- Cart header -->
                <div class="wi-panel-hdr">
                    <span style="color:#fff;font-weight:700;font-size:16px;">🛒 Cart</span>
                    <button onclick="wiClearCart()"
                        style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
                               color:#fff;padding:5px 12px;border-radius:8px;cursor:pointer;
                               font-size:12px;font-weight:600;font-family:'DM Sans',sans-serif;"
                        onmouseover="this.style.background='rgba(255,255,255,.28)'"
                        onmouseout="this.style.background='rgba(255,255,255,.15)'">🗑 Clear</button>
                </div>

                <!-- Cart items -->
                <div id="wiCartItems" style="min-height:110px;max-height:250px;overflow-y:auto;padding:10px 16px;">
                    <div style="text-align:center;padding:26px 0;color:var(--text-muted);font-size:13px;">
                        Cart is empty — add products.
                    </div>
                </div>

                <!-- Totals -->
                <div class="wi-totals">
                    <div style="display:flex;justify-content:space-between;">
                        <span class="wi-total-label">ITEMS</span>
                        <span id="wiCount" class="wi-total-count">0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:5px;">
                        <span class="wi-total-text">TOTAL</span>
                        <span id="wiTotal" class="wi-total-amt">₱0.00</span>
                    </div>
                </div>

                <!-- Payment form -->
                <div style="padding:16px 20px;border-top:1.5px solid var(--border);">

                    <!-- Customer name -->
                    <div style="margin-bottom:13px;">
                        <label class="wi-label">
                            Customer Name <span style="color:var(--text-muted);font-weight:400;text-transform:none;">(optional)</span>
                        </label>
                        <input id="wiCustName" type="text" placeholder="Walk-in Customer"
                            style="width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);
                                   font-size:14px;color:var(--text);background:var(--surface);font-family:'DM Sans',sans-serif;transition:var(--t);"
                            onfocus="this.style.borderColor='#9B7FBF'" onblur="this.style.borderColor='var(--border)'">
                    </div>

                    <!-- Payment method -->
                    <div style="margin-bottom:13px;">
                        <label class="wi-label">Payment Method</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                            <button id="wiPayCash"  class="wi-pay-btn wi-pay-active" onclick="wiSetPay('cash')">💵 Cash</button>
                            <button id="wiPayGcash" class="wi-pay-btn"               onclick="wiSetPay('gcash')">📲 GCash</button>
                        </div>
                    </div>

                    <!-- GCash ref -->
                    <div id="wiGcashBox" style="display:none;margin-bottom:13px;">
                        <label class="wi-label">
                            GCash Reference No. <span style="color:var(--red);">*</span>
                        </label>
                        <input id="wiGcashRef" type="text" placeholder="e.g. 1234567890123"
                            maxlength="13" pattern="[0-9]{13}" inputmode="numeric"
                            oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,13); document.getElementById('wiGcashCount').textContent=this.value.length+'/13';"
                            style="width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);
                                   font-size:14px;color:var(--text);background:var(--surface);font-family:'DM Sans',sans-serif;transition:var(--t);"
                            onfocus="this.style.borderColor='#9B7FBF'" onblur="this.style.borderColor='var(--border)'">
                        <div style="text-align:right;font-size:11px;color:var(--text-muted);margin-top:4px;">
                            <span id="wiGcashCount">0/13</span> digits
                        </div>
                    </div>

                    <!-- Cash tendered -->
                    <div id="wiCashBox" style="margin-bottom:13px;">
                        <label class="wi-label">
                            Cash Tendered <span style="color:var(--text-muted);font-weight:400;text-transform:none;">(optional)</span>
                        </label>
                        <input id="wiTendered" type="number" placeholder="0.00" min="0" step="0.01" oninput="wiCalcChange()"
                            style="width:100%;padding:9px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);
                                   font-size:14px;color:var(--text);background:var(--surface);font-family:'DM Sans',sans-serif;transition:var(--t);"
                            onfocus="this.style.borderColor='#9B7FBF'" onblur="this.style.borderColor='var(--border)'">
                        <div id="wiChangeRow" style="display:none;margin-top:7px;padding:8px 12px;
                             background:#D4F0E4;border-radius:var(--radius-sm);
                             justify-content:space-between;align-items:center;">
                            <span style="font-size:13px;font-weight:600;color:#065F46;">Change:</span>
                            <span id="wiChangeAmt" style="font-size:15px;font-weight:800;color:#065F46;">₱0.00</span>
                        </div>
                    </div>

                    <!-- Checkout button -->
                    <button id="wiCheckoutBtn" onclick="wiCheckout()"
                        style="width:100%;padding:14px;background:#7B5EA7;color:#fff;border:none;
                               border-radius:var(--radius-sm);font-size:15px;font-weight:700;cursor:pointer;
                               transition:var(--t);font-family:'DM Sans',sans-serif;
                               box-shadow:0 3px 12px rgba(123,94,167,.30);"
                        onmouseover="this.style.background='#5A3F84'" onmouseout="this.style.background='#7B5EA7'">
                        ✅ Complete Transaction
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- WALK-IN RECEIPT MODAL -->
<div id="wiReceiptModal" class="modal">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3 class="modal-title">🧾 Transaction Receipt</h3>
            <button class="modal-close" onclick="wiCloseReceipt()">×</button>
        </div>
        <div id="wiReceiptBody"></div>
        <div style="margin-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <button onclick="wiCloseReceipt()"
                style="padding:11px;background:var(--surface2);border:1.5px solid var(--border);
                       border-radius:var(--radius-sm);font-weight:600;cursor:pointer;color:var(--plum);
                       font-family:'DM Sans',sans-serif;font-size:14px;">Close</button>
            <button onclick="wiNewTx()"
                style="padding:11px;background:var(--plum);color:#fff;border:none;
                       border-radius:var(--radius-sm);font-weight:700;cursor:pointer;
                       font-family:'DM Sans',sans-serif;font-size:14px;">🛒 New Transaction</button>
        </div>
    </div>
</div>

<!-- REVIEW ORDER MODAL -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">📋 Review Order</h3>
            <button class="modal-close" onclick="closeReviewModal()">&times;</button>
        </div>
        <div id="reviewModalContent">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<!-- REJECTION MODAL -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">❌ Reject Order</h3>
            <button class="modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <div id="rejectOrderInfo"></div>
        <div class="form-group">
            <label for="rejectionReason">Message to Customer *</label>
            <textarea id="rejectionReason" placeholder="Explain why the order cannot be fulfilled..." rows="4"></textarea>
        </div>
        <div class="action-buttons">
            <button class="btn btn-view" onclick="closeRejectModal()">Cancel</button>
            <button class="btn btn-reject" onclick="confirmReject()">Send Message & Reject Order</button>
        </div>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════
//  GLOBALS
// ═══════════════════════════════════════════════════════════════
let ordersCache = [];
let productsCache = [];
let currentReviewOrder = null;

// ═══════════════════════════════════════════════════════════════
//  API HELPER - Uses inline API
// ═══════════════════════════════════════════════════════════════
async function api(action, data = null) {
    const url = `?inline_api=${action}`;
    const opts = { method: data ? 'POST' : 'GET' };
    if (data) {
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(data);
    }
    const res = await fetch(url, opts);
    return await res.json();
}

// ═══════════════════════════════════════════════════════════════
//  NAV DRAWER
// ═══════════════════════════════════════════════════════════════
function toggleNavDrawer() {
    const drawer = document.getElementById('navDrawer');
    const overlay = document.getElementById('navOverlay');
    const btn = document.getElementById('hamburgerBtn');
    const isOpen = drawer.classList.contains('open');
    if (isOpen) { closeNavDrawer(); } else {
        drawer.classList.add('open');
        overlay.classList.add('active');
        btn.classList.add('open');
    }
}
function closeNavDrawer() {
    document.getElementById('navDrawer').classList.remove('open');
    document.getElementById('navOverlay').classList.remove('active');
    document.getElementById('hamburgerBtn').classList.remove('open');
}

// ═══════════════════════════════════════════════════════════════
//  TAB SWITCHING
// ═══════════════════════════════════════════════════════════════
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.nav-drawer-btn').forEach(b => b.classList.remove('active'));

    const map = {
        incoming:  { tab: 'incomingTab',  idx: 0, drawer: 'drawerIncoming'  },
        approved:  { tab: 'approvedTab',   idx: 1, drawer: 'drawerApproved'  },
        rejected:  { tab: 'rejectedTab',   idx: 2, drawer: 'drawerRejected'  },
        all:       { tab: 'allTab',        idx: 3, drawer: 'drawerAll'       },
        inventory: { tab: 'inventoryTab',  idx: 4, drawer: 'drawerInventory' },
        walkin:    { tab: 'walkinTab',     idx: 5, drawer: 'drawerWalkin'    },
    };

    if (map[tab]) {
        document.getElementById(map[tab].tab).classList.add('active');
        document.querySelectorAll('.tab-btn')[map[tab].idx].classList.add('active');
        const dr = document.getElementById(map[tab].drawer);
        if (dr) dr.classList.add('active');
    }

    if (tab === 'inventory') { loadInvStats(); loadInvProducts(); }
    if (tab === 'walkin')    { wiLoad(); }
}

// ═══════════════════════════════════════════════════════════════
//  INVENTORY - SUB TABS
// ═══════════════════════════════════════════════════════════════
function switchInvSub(name, btn) {
    document.querySelectorAll('.inv-sub-pane').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.inv-sub-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (name === 'view')    { document.getElementById('invView').style.display    = 'block'; loadInvStats(); loadInvProducts(); }
    if (name === 'discard') { document.getElementById('invDiscard').style.display = 'block'; loadDiscardProducts(); }
    if (name === 'history') { document.getElementById('invHistory').style.display = 'block'; loadDiscardHistory(); }
}

// ═══════════════════════════════════════════════════════════════
//  INVENTORY - VIEW
// ═══════════════════════════════════════════════════════════════
let invProducts = [];
let invFilter   = 'all';

async function loadInvStats() {
    const s = await api('get_inv_stats');
    document.getElementById('iTotal').textContent    = s.total_products;
    document.getElementById('iStock').textContent    = s.total_stock;
    document.getElementById('iFresh').textContent    = s.fresh_items;
    document.getElementById('iExpiring').textContent = s.expiring_items;
    document.getElementById('iLow').textContent      = s.low_stock;
    const badge = document.getElementById('expiryBadge');
    badge.style.display = Number(s.expiring_items) > 0 ? 'block' : 'none';
    badge.textContent   = s.expiring_items;
}

async function loadInvProducts() {
    invProducts = await api('get_inventory');
    renderInvGrid();
}

function setInvFilter(f, btn) {
    invFilter = f;
    document.querySelectorAll('.inv-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderInvGrid();
}

function renderInvGrid() {
    const grid   = document.getElementById('invGrid');
    const search = (document.getElementById('invSearch').value || '').toLowerCase();
    let list = invProducts.filter(p => {
        const ms = !search || p.name.toLowerCase().includes(search) || (p.description||'').toLowerCase().includes(search);
        const days = p.days_remaining !== null ? parseInt(p.days_remaining) : null;
        let mf = true;
        if (invFilter === 'expiring') mf = days !== null && days >= 0 && days <= 5;
        if (invFilter === 'expired')  mf = days !== null && days < 0;
        if (invFilter === 'low')      mf = parseInt(p.stock) > 0 && parseInt(p.stock) < 5;
        if (invFilter === 'out')      mf = parseInt(p.stock) === 0;
        return ms && mf;
    });

    if (!list.length) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><div class="icon">🍰</div><p>No products match this filter.</p></div>`;
        return;
    }

    grid.innerHTML = list.map(p => {
        const stock = parseInt(p.stock);
        const days  = p.days_remaining !== null ? parseInt(p.days_remaining) : null;
        let sClass = 'sGood', sText = `✓ ${stock} in stock`;
        if (stock === 0)    { sClass='sOut';  sText='✗ Out of stock'; }
        else if (stock < 5) { sClass='sLow';  sText=`⚠ ${stock} left`; }

        let shelfHtml = '';
        if (days !== null) {
            let tC='sFresh',tT='';
            if (days<0)       {tC='sCrit'; tT='⚠ EXPIRED';}
            else if(days===0) {tC='sCrit'; tT='⚠ Expires TODAY';}
            else if(days<=2)  {tC='sCrit'; tT=`⚠ ${days}d left`;}
            else if(days<=5)  {tC='sWarn'; tT=`⏰ ${days} days left`;}
            else              {tC='sFresh';tT=`✓ Fresh (${days}d)`;}
            const expD = new Date(p.expiry_date).toLocaleDateString();
            shelfHtml = `<div class="inv-shelf"><div style="color:var(--plum-mid);font-weight:600;margin-bottom:3px;">Shelf Life</div>
                <div style="color:var(--plum-mid);font-size:11px;">Expires: ${expD}</div>
                <span class="shelf-tag ${tC}">${tT}</span></div>`;
        }

        let imgH = '<span>No Image</span>';
        if (p.photo) {
            let url = p.photo;
            if (url.startsWith('0x')) url = hexToStr(url.slice(2));
            imgH = `<img src="${escH(url)}" alt="${escH(p.name)}">`;
        }

        const ne = days !== null && days <= 5;
        return `<div class="inv-product-card ${ne?'near-expiry':''}">
            <div class="inv-product-img">${imgH}</div>
            <div class="inv-product-body">
                <div class="inv-product-name">${escH(p.name)}</div>
                <div class="inv-product-desc">${escH(p.description||'No description')}</div>
                <div class="inv-product-meta">
                    <span class="inv-price">₱${Number(p.price).toFixed(2)}</span>
                    <span class="inv-stock-badge ${sClass}">${sText}</span>
                </div>
                ${shelfHtml}
                ${ne?`<div style="margin-top:8px;font-size:12px;color:#e53e3e;font-weight:600;">⚠ Consider discarding via Discard tab</div>`:''}
            </div>
        </div>`;
    }).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    const si = document.getElementById('invSearch');
    if (si) si.addEventListener('input', renderInvGrid);
});

// ═══════════════════════════════════════════════════════════════
//  INVENTORY - DISCARD
// ═══════════════════════════════════════════════════════════════
let discardProducts = [];
let selectedDiscard = null;

async function loadDiscardProducts() {
    discardProducts = await api('get_inventory');
    discardProducts = discardProducts.filter(p => parseInt(p.stock) > 0);
    renderDiscardList();
}

function renderDiscardList() {
    const list = document.getElementById('dProductList');
    if (!discardProducts.length) {
        list.innerHTML = `<div class="empty-state"><div class="icon">📦</div><p>No products available.</p></div>`;
        return;
    }
    list.innerHTML = discardProducts.map(p => {
        const days = p.days_remaining !== null ? parseInt(p.days_remaining) : null;
        let badge='', expT='';
        if (days!==null) {
            if(days<0)      {badge=`<span class="d-badge db-danger">⚠ EXPIRED</span>`;expT=`Expired ${Math.abs(days)}d ago`;}
            else if(days<=2){badge=`<span class="d-badge db-danger">⚠ CRITICAL</span>`;expT=`Expires in ${days}d`;}
            else if(days<=5){badge=`<span class="d-badge db-warning">⏰ NEAR EXPIRY</span>`;expT=`${days} days left`;}
            else            {badge=`<span class="d-badge db-success">✓ FRESH</span>`;expT=`${days} days remaining`;}
        }
        const isSel = selectedDiscard && selectedDiscard.id == p.id;
        return `<div class="d-product-row ${isSel?'selected':''}" id="drow-${p.id}">
            <div style="flex:1;min-width:140px;">
                <div class="d-pname">${escH(p.name)}</div>
                <div class="d-pmeta">Stock: ${p.stock} | ₱${Number(p.price).toFixed(2)}${expT?' | '+expT:''}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                ${badge}
                <button class="btn-discard" onclick="selectDiscard(${p.id})">🗑️ Select</button>
            </div>
        </div>`;
    }).join('');
}

function selectDiscard(pid) {
    selectedDiscard = discardProducts.find(p => p.id == pid);
    if (!selectedDiscard) return;
    document.querySelectorAll('.d-product-row').forEach(r => r.classList.remove('selected'));
    const row = document.getElementById('drow-' + pid);
    if (row) row.classList.add('selected');

    const days = selectedDiscard.days_remaining !== null ? parseInt(selectedDiscard.days_remaining) : null;
    let note = '';
    if (days!==null) {
        if(days<0)       note=`<span style="color:#e53e3e;font-weight:700;">⚠ EXPIRED ${Math.abs(days)} day${Math.abs(days)!==1?'s':''} ago</span>`;
        else if(days<=2) note=`<span style="color:#e53e3e;font-weight:700;">⚠ Expires in ${days} day${days!==1?'s':''}</span>`;
        else             note=`<span style="color:#dd6b20;">⏰ ${days} days until expiry</span>`;
    }
    document.getElementById('dSelInfo').innerHTML = `
        <strong style="font-size:15px;color:var(--text);">${escH(selectedDiscard.name)}</strong><br>
        <span style="font-size:13px;color:var(--plum-mid);">Stock: <strong>${selectedDiscard.stock}</strong> | ₱${Number(selectedDiscard.price).toFixed(2)}</span><br>${note}`;
    document.getElementById('dAvail').textContent = selectedDiscard.stock;
    document.getElementById('dQty').max   = selectedDiscard.stock;
    document.getElementById('dQty').value = 1;
    document.getElementById('dReason').value = (days!==null && days<=0) ? 'Expired' : '';
    document.getElementById('dNotes').value  = '';
    const fc = document.getElementById('dFormCard');
    fc.style.display = 'block';
    fc.scrollIntoView({ behavior:'smooth', block:'nearest' });
}

function cancelDForm() {
    selectedDiscard = null;
    document.getElementById('dFormCard').style.display = 'none';
    document.querySelectorAll('.d-product-row').forEach(r => r.classList.remove('selected'));
}

function onDReasonChange() {
    const r = document.getElementById('dReason').value;
    const n = document.getElementById('dNotes');
    n.placeholder = r === 'Other' ? 'Please specify the reason...' : 'Add any extra details...';
}

async function submitDiscard() {
    if (!selectedDiscard) { alert('Please select a product first.'); return; }
    const qty    = parseInt(document.getElementById('dQty').value);
    const reason = document.getElementById('dReason').value;
    const notes  = document.getElementById('dNotes').value.trim();
    if (!reason) { alert('Please select a reason.'); return; }
    if (reason === 'Other' && !notes) { alert('Please specify the reason in the notes.'); return; }
    if (qty > selectedDiscard.stock) { alert(`Cannot discard more than available stock (${selectedDiscard.stock}).`); return; }
    const fullReason = notes ? `${reason}: ${notes}` : reason;
    if (!confirm(`Discard ${qty} unit(s) of "${selectedDiscard.name}"?\nReason: ${fullReason}\n\nThis cannot be undone!`)) return;

    const res = await api('discard_items', { product_id: selectedDiscard.id, quantity: qty, reason: fullReason });
    if (res.success) {
        alert(`✅ Discarded ${qty} item(s) of "${selectedDiscard.name}".\nRemaining stock: ${res.remaining_stock}`);
        cancelDForm();
        loadDiscardProducts();
        loadInvStats();
    } else {
        alert('❌ Error: ' + (res.error || 'Failed to discard items.'));
    }
}

// ═══════════════════════════════════════════════════════════════
//  INVENTORY - HISTORY
// ═══════════════════════════════════════════════════════════════
async function loadDiscardHistory() {
    const history = await api('get_discard_history');
    const list    = document.getElementById('dHistoryList');
    if (!history.length) {
        list.innerHTML = `<div class="empty-state"><div class="icon">📋</div><p>No discard history yet.</p></div>`;
        return;
    }
    list.innerHTML = history.map(item => {
        const date  = new Date(item.discard_date).toLocaleDateString();
        const value = (item.quantity_discarded * item.price).toFixed(2);
        return `<div class="d-history-item">
            <div class="d-hist-hdr">
                <span class="d-hist-name">${escH(item.cake_name)}</span>
                <span class="d-hist-date">${date}</span>
            </div>
            <div style="font-size:13px;color:var(--plum-mid);">
                <strong>Qty:</strong> ${item.quantity_discarded} |
                <strong>Value Lost:</strong> ₱${value} |
                <strong>By:</strong> ${escH(item.discarded_by)}
            </div>
            <span class="d-reason-tag">${escH(item.reason)}</span>
        </div>`;
    }).join('');
}

function hexToStr(hex) {
    let s = '';
    for (let i=0; i<hex.length; i+=2) s += String.fromCharCode(parseInt(hex.substr(i,2),16));
    return s;
}

// ═══════════════════════════════════════════════════════════════
//  LOAD ORDERS
// ═══════════════════════════════════════════════════════════════
// Returns true if this is a custom cake order (staff must never see these)
function isCustomOrder(o) {
    return (o.review_notes && o.review_notes.includes('---CUSTOM---')) || o.is_custom == 1;
}

async function loadOrders() {
    const all = await api('get_orders');
    productsCache = await api('get_products');

    // Strip any custom orders that may have leaked through (double safety net)
    ordersCache = (all || []).filter(o => !isCustomOrder(o));

    const incoming = ordersCache.filter(o => o.status === 'pending');
    const approved = ordersCache.filter(o => o.status === 'approved');
    const rejected = ordersCache.filter(o => o.status === 'rejected');
    
    // Update badge
    const badge = document.getElementById('incomingBadge');
    const drawerBadge = document.getElementById('drawerIncomingBadge');
    if (incoming.length > 0) {
        badge.textContent = incoming.length;
        badge.style.display = 'block';
        drawerBadge.textContent = incoming.length;
        drawerBadge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
        drawerBadge.style.display = 'none';
    }
    
    // Render each tab
    document.getElementById('incomingOrders').innerHTML = renderOrders(incoming, '📥', 'No pending orders', true);
    document.getElementById('approvedOrders').innerHTML = renderOrders(approved, '✅', 'No approved orders');
    document.getElementById('rejectedOrders').innerHTML = renderOrders(rejected, '❌', 'No rejected orders');
    document.getElementById('allOrders').innerHTML = renderOrders(ordersCache, '📋', 'No orders yet');
}

// ═══════════════════════════════════════════════════════════════
//  RENDER ORDERS
// ═══════════════════════════════════════════════════════════════
function renderOrders(list, icon, emptyText, showReviewBtn = false) {
    if (!list.length) return `<div class="empty-state"><div class="icon">${icon}</div><p>${emptyText}</p></div>`;
    
    return list.map(o => {
        const itemRows = (o.items||[]).map(i =>
            `<div class="order-item-row">
                <div>
                    <div class="item-name">${escH(i.name)} ×${i.quantity}</div>
                    <div class="item-details">₱${Number(i.price).toFixed(2)} each</div>
                </div>
                <span>₱${(Number(i.price)*i.quantity).toFixed(2)}</span>
            </div>`
        ).join('');
        
        // Customer info
        let customerInfo = '';
        if (o.delivery_name) {
            const isPickup = o.fulfillment_method === 'pickup' || (o.delivery_address && o.delivery_address.startsWith('PICKUP'));
            const icon = isPickup ? '🏪' : '🚚';
            const label = isPickup ? 'Pickup' : 'Delivery';
            
            customerInfo = `
                <div class="customer-info">
                    <strong>${icon} ${label} Details:</strong>
                    <div>${escH(o.delivery_name)} | ${escH(o.delivery_phone)}</div>
                    ${!isPickup ? `<div>${escH(o.delivery_address)}</div>` : '<div>Store Pickup</div>'}
                </div>`;
        }
        
        // Date badge
        let dateBadge = '';
        if (o.preferred_date) {
            const prefDate = new Date(o.preferred_date + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            prefDate.setHours(0, 0, 0, 0);
            
            const daysAhead = Math.ceil((prefDate - today) / (1000 * 60 * 60 * 24));
            const isToday = daysAhead === 0;
            
            const dateOptions = { weekday: 'short', month: 'short', day: 'numeric' };
            const formattedDate = prefDate.toLocaleDateString('en-US', dateOptions);
            
            let timeText = '';
            if (o.preferred_time) {
                const timeLabels = {
                    'morning': '🌅 Morning',
                    'afternoon': '☀️ Afternoon',
                    'evening': '🌆 Evening'
                };
                timeText = ` | ${timeLabels[o.preferred_time] || o.preferred_time}`;
            }
            
            const badgeClass = isToday ? 'date-today' : 'date-advance';
            const label = isToday ? '📅 For Today' : `📅 ${formattedDate}`;
            
            dateBadge = `<div class="date-badge ${badgeClass}">${label}${timeText}</div>`;
        }
        
        // Review notes if rejected
        let reviewNotes = '';
        if (o.status === 'rejected' && o.review_notes) {
            reviewNotes = `
                <div class="alert alert-danger">
                    <strong>Rejection Reason:</strong><br>
                    ${escH(o.review_notes)}
                </div>`;
        }
        
        // Payment badge
        const paymentBadge = o.payment_method 
            ? `<span style="background:var(--border); color:var(--plum-mid); padding:4px 10px; border-radius:8px; font-size:11px; font-weight:600; margin-left:8px;">
                ${o.payment_method === 'gcash' ? '💳 GCash' : '💵 COD'}
            </span>`
            : '';
        
        // Action buttons
        let actions = '';
        if (showReviewBtn && o.status === 'pending') {
            actions = `
                <div class="action-buttons">
                    <button class="btn btn-review" onclick="reviewOrder(${o.id})">📋 Review Order</button>
                </div>`;
        }
        
        return `
        <div class="order-card">
            <div class="order-header">
                <span class="order-number">Order #${o.id}${paymentBadge}</span>
                <span class="order-status status-${o.status}">${o.status}</span>
            </div>
            <div class="order-date">Placed: ${new Date(o.order_date).toLocaleString()}</div>
            ${dateBadge}
            ${customerInfo}
            ${reviewNotes}
            ${itemRows}
            <div class="order-total-row">
                <span style="color:var(--plum-mid);font-size:13px;">Items: ${(o.items||[]).reduce((s,i)=>s+i.quantity,0)}</span>
                <span class="order-total-val">₱${Number(o.total).toFixed(2)}</span>
            </div>
            ${actions}
        </div>`;
    }).join('');
}

// ═══════════════════════════════════════════════════════════════
//  REVIEW ORDER
// ═══════════════════════════════════════════════════════════════
function reviewOrder(orderId) {
    // Find order - handle both string and number IDs
    const order = ordersCache.find(o => o.id == orderId);
    
    if (!order) {
        alert('Order not found!');
        return;
    }
    
    currentReviewOrder = order;
    
    // Check stock for each item
    let stockCheckHTML = '<div class="stock-check-section"><div class="stock-check-title">📦 Stock Availability Check:</div>';
    let allAvailable = true;
    
    for (const item of order.items || []) {
        const product = productsCache.find(p => p.id == item.product_id);
        
        let stockStatus = 'unavailable';
        let stockClass = 'stock-out';
        let stockText = 'Product not found';
        
        if (product) {
            // Check if this is a Custom product
            if (product.custom === 'Custom') {
                // Custom products don't need stock check
                stockStatus = 'available';
                stockClass = 'stock-ok';
                stockText = `✅ Custom Order (Will be prepared)`;
            } else {
                // Regular product - check stock normally
                const stock = parseInt(product.stock);
                const need = parseInt(item.quantity);
                const available = stock >= need;
                stockStatus = available ? 'available' : (stock > 0 ? 'low' : 'unavailable');
                
                if (available) {
                    stockClass = 'stock-ok';
                    stockText = `✅ ${stock} in stock (Need ${need})`;
                } else if (stock > 0) {
                    stockClass = 'stock-low';
                    stockText = `⚠️ Only ${stock} in stock (Need ${need}, Short ${need - stock})`;
                    allAvailable = false;
                } else {
                    stockClass = 'stock-out';
                    stockText = `❌ Out of stock (Need ${need})`;
                    allAvailable = false;
                }
                
                // Check expiry for regular products
                if (product.production_date && product.shelf_life_days) {
                    const daysLeft = calcDays(product);
                    if (daysLeft !== null && daysLeft <= 2) {
                        stockText += ` | ⚠️ Expires in ${daysLeft} day${daysLeft !== 1 ? 's' : ''}`;
                        if (daysLeft < 0) {
                            stockText = `❌ Expired`;
                            allAvailable = false;
                        }
                    }
                }
            }
        } else {
            allAvailable = false;
        }
        
        stockCheckHTML += `
            <div class="stock-item ${stockStatus}">
                <div class="stock-item-name">${escH(item.name)} ×${item.quantity}</div>
                <div class="stock-item-info ${stockClass}">${stockText}</div>
            </div>`;
    }
    
    // Determine if any item is truly out of stock
    const anyOutOfStock = (order.items || []).some(item => {
        const product = productsCache ? productsCache.find(p => p.id == item.product_id) : null;
        return !product || parseInt(product.stock) <= 0;
    });

    stockCheckHTML += `<div class="overall-status ${allAvailable ? 'can-fulfill' : 'cannot-fulfill'}">
        ${allAvailable ? '✅ ALL ITEMS AVAILABLE - Can fulfill order' : '⚠️ STOCK ISSUES - Some items are low or out of stock'}
    </div>
    ${anyOutOfStock ? `<div style="background:#fed7d7; border:1px solid #f56565; border-radius:8px; padding:12px 14px; margin-top:10px; font-size:13px; color:#742a2a;">
        <strong>🚫 Cannot Approve:</strong> One or more items are completely out of stock. Please reject this order.
    </div>` : (!allAvailable ? `<div style="background:var(--amber-bg); border:1px solid var(--amber); border-radius:8px; padding:12px 14px; margin-top:10px; font-size:13px; color:var(--amber-text);">
        <strong>⚠️ Staff Note:</strong> You can still approve this order. The admin will review stock and decide how to handle the shortage.
    </div>` : '')}
    </div>`;
    
    // Check if ANY item is completely out of stock (stock = 0)
    const hasOutOfStock = (order.items || []).some(item => {
        const product = productsCache ? productsCache.find(p => p.id == item.product_id) : null;
        return !product || parseInt(product.stock) <= 0;
    });
    
    // Build modal content
    const isPickup = order.fulfillment_method === 'pickup' || (order.delivery_address && order.delivery_address.startsWith('PICKUP'));
    const fulfillmentIcon = isPickup ? '🏪' : '🚚';
    const fulfillmentLabel = isPickup ? 'Pickup' : 'Delivery';
    
    let dateInfo = '';
    if (order.preferred_date) {
        const prefDate = new Date(order.preferred_date + 'T00:00:00');
        const dateOptions = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
        const formattedDate = prefDate.toLocaleDateString('en-US', dateOptions);
        dateInfo = `<div><strong>Scheduled For:</strong> ${formattedDate}</div>`;
        
        if (order.preferred_time) {
            const timeLabels = {
                'morning': 'Morning (8:00 AM - 12:00 PM)',
                'afternoon': 'Afternoon (12:00 PM - 4:00 PM)',
                'evening': 'Evening (4:00 PM - 6:00 PM)'
            };
            dateInfo += `<div><strong>Preferred Time:</strong> ${timeLabels[order.preferred_time] || order.preferred_time}</div>`;
        }
    }
    
    const modalContent = `
        <div class="customer-info">
            <strong>Customer Information:</strong>
            <div><strong>Name:</strong> ${escH(order.delivery_name)}</div>
            <div><strong>Phone:</strong> ${escH(order.delivery_phone)}</div>
            <div><strong>${fulfillmentIcon} ${fulfillmentLabel}:</strong> ${isPickup ? 'Store Pickup' : escH(order.delivery_address)}</div>
            ${dateInfo}
            <div><strong>Payment:</strong> ${order.payment_method === 'gcash' ? '💳 GCash' : '💵 Cash'}</div>
            ${order.payment_method === 'gcash' ? `
                <div style="margin-top:8px; background:var(--blue-bg); border:1.5px solid var(--blue); padding:10px; border-radius:8px; font-size:13px;">
                    <strong style="color:var(--blue);">💳 GCash Reference:</strong>
                    <span style="color:var(--text); font-weight:700; margin-left:6px;">${order.gcash_ref_number ? escH(order.gcash_ref_number) : '<em style="color:var(--text-muted);font-weight:400;">Not provided</em>'}</span>
                    ${order.gcash_screenshot ? `<br><a href="${escH(order.gcash_screenshot)}" target="_blank" style="color:#3182ce; font-size:12px; margin-top:4px; display:inline-block;">📸 View Payment Screenshot</a>` : ''}
                </div>` : ''}
        </div>
        
        ${stockCheckHTML}
        
        <div style="margin-top:20px;">
            <strong style="color:var(--text); font-size:15px;">Order Items:</strong>
            ${(order.items || []).map(i => `
                <div class="order-item-row">
                    <div>
                        <div class="item-name">${escH(i.name)} ×${i.quantity}</div>
                        <div class="item-details">₱${Number(i.price).toFixed(2)} each</div>
                    </div>
                    <span>₱${(Number(i.price)*i.quantity).toFixed(2)}</span>
                </div>
            `).join('')}
            <div class="order-total-row">
                <strong>Total:</strong>
                <span class="order-total-val">₱${Number(order.total).toFixed(2)}</span>
            </div>
        </div>
        
        <div class="action-buttons" style="margin-top:24px;">
            <button class="btn btn-view" onclick="closeReviewModal()">Close</button>
            <button class="btn btn-reject" onclick="openRejectModal(${order.id})">❌ Reject Order</button>
            ${hasOutOfStock 
                ? `<button class="btn" style="background:#cbd5e0;color:#718096;cursor:not-allowed;opacity:0.7;" disabled title="Cannot approve — one or more items are out of stock">
                    🚫 Cannot Approve (Out of Stock)
                   </button>`
                : `<button id="approveBtnMain" class="btn btn-approve" onclick="approveOrder(${order.id})" ${!allAvailable ? 'style="background:linear-gradient(135deg,#ed8936,#dd6b20);" title="Approve with stock warning"' : ''}>
                    ${allAvailable ? '✅ Approve & Send to Admin' : '⚠️ Approve with Warning'}
                   </button>`
            }
        </div>
    `;
    
    document.getElementById('reviewModalContent').innerHTML = modalContent;
    document.getElementById('reviewModal').classList.add('active');
}

function closeReviewModal(clearOrder = true) {
    document.getElementById('reviewModal').classList.remove('active');
    if (clearOrder) currentReviewOrder = null;
}

// ═══════════════════════════════════════════════════════════════
//  APPROVE ORDER
// ═══════════════════════════════════════════════════════════════
async function approveOrder(orderId) {
    const btn = document.getElementById('approveBtnMain');
    if (!btn) return;

    // Clear any previous error box
    const prevErr = document.getElementById('approveErrorBox');
    if (prevErr) prevErr.style.display = 'none';

    // Two-click confirmation — avoids browser confirm() being silently blocked
    if (btn.dataset.state !== 'confirm') {
        btn.dataset.state = 'confirm';
        btn.style.cssText = 'background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff;border:none;box-shadow:0 3px 10px rgba(27,94,32,0.4);';
        btn.textContent = '⚠️ Tap again to confirm';
        btn._resetTimer = setTimeout(() => resetApproveBtn(btn), 4000);
        return;
    }

    // Second click — actually submit
    clearTimeout(btn._resetTimer);
    btn.disabled = true;
    btn.dataset.state = 'loading';
    btn.style.cssText = 'background:#9e9e9e;color:#fff;border:none;cursor:not-allowed;';
    btn.textContent = '⏳ Sending to Admin…';

    try {
        const res = await api('approve', { order_id: orderId });

        if (res.success) {
            btn.style.cssText = 'background:linear-gradient(135deg,#4caf50,#2e7d32);color:#fff;border:none;';
            btn.textContent = '✅ Approved!';
            setTimeout(() => {
                closeReviewModal();
                loadOrders();
                switchTab('approved');
            }, 900);
        } else {
            const errMsg = res.error || 'Approval failed — the order may have already changed status.';
            showInlineApproveError(errMsg);
            resetApproveBtn(btn);
        }
    } catch (e) {
        showInlineApproveError('Network error — please check your connection and try again.');
        resetApproveBtn(btn);
    }
}

function resetApproveBtn(btn) {
    btn.disabled = false;
    btn.dataset.state = '';
    btn.style.cssText = '';
    btn.className = 'btn btn-approve';
    btn.innerHTML = '✅ Approve & Send to Admin';
}

function showInlineApproveError(msg) {
    let box = document.getElementById('approveErrorBox');
    if (!box) {
        box = document.createElement('div');
        box.id = 'approveErrorBox';
        box.style.cssText = 'margin:10px 0 0;padding:11px 14px;background:var(--red-bg);' +
            'color:var(--red);border:1px solid var(--red);border-radius:8px;font-size:13px;font-weight:600;';
        const actionBtns = document.querySelector('#reviewModalContent .action-buttons');
        if (actionBtns) actionBtns.insertAdjacentElement('afterend', box);
    }
    box.textContent = '\u274c ' + msg;
    box.style.display = 'block';
}

// ═══════════════════════════════════════════════════════════════
//  REJECT ORDER
// ═══════════════════════════════════════════════════════════════
function openRejectModal(orderId) {
    const order = ordersCache.find(o => o.id == orderId);
    if (!order) return;
    
    currentReviewOrder = order;
    
    document.getElementById('rejectOrderInfo').innerHTML = `
        <div class="alert alert-warning">
            <strong>⚠️ Rejecting Order #${order.id}</strong><br>
            Customer: ${escH(order.delivery_name)}<br>
            The customer will see your message in their order dashboard.
        </div>
    `;
    
    document.getElementById('rejectionReason').value = '';
    closeReviewModal(false);
    document.getElementById('rejectModal').classList.add('active');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('active');
}

async function confirmReject() {
    const reason = document.getElementById('rejectionReason').value.trim();
    
    if (!reason || reason.length < 3) {
        alert('Please enter a message to the customer explaining why the order is rejected (at least 3 characters).');
        document.getElementById('rejectionReason').focus();
        return;
    }
    
    if (!currentReviewOrder) {
        alert('No order selected.');
        return;
    }
    
    // Confirm before rejecting
    if (!confirm(`Reject Order #${currentReviewOrder.id}?\n\nThe customer will receive your message:\n"${reason}"`)) {
        return;
    }
    
    const res = await api('reject', {
        order_id: currentReviewOrder.id,
        reason: reason
    });
    
    if (res.success) {
        alert('❌ Order rejected. Customer has been notified.');
        closeRejectModal();
        loadOrders();
        switchTab('incoming');
    } else {
        alert('Error: ' + (res.error || 'Failed to reject order'));
    }
}

// ═══════════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════════
function escH(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function calcDays(p) {
    if (!p.production_date || !p.shelf_life_days) return null;
    const expiry = new Date(new Date(p.production_date).getTime() + p.shelf_life_days * 86400000);
    return Math.ceil((expiry - new Date()) / 86400000);
}

// ═══════════════════════════════════════════════════════════════
//  INIT
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
    
    // Auto-refresh every 30 seconds
    setInterval(loadOrders, 30000);
});

// Close modals when clicking outside
document.getElementById('reviewModal').addEventListener('click', (e) => {
    if (e.target.id === 'reviewModal') closeReviewModal();
});
document.getElementById('rejectModal').addEventListener('click', (e) => {
    if (e.target.id === 'rejectModal') closeRejectModal();
});
document.addEventListener('DOMContentLoaded', () => {
    const m = document.getElementById('wiReceiptModal');
    if (m) m.addEventListener('click', e => { if (e.target === m) wiCloseReceipt(); });
});

// ═══════════════════════════════════════════════════════════════
//  WALK-IN POS
// ═══════════════════════════════════════════════════════════════
let wiProducts = [];
let wiCart     = [];
let wiPay      = 'cash';

async function wiLoad() {
    const g = document.getElementById('wiGrid');
    g.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><div class="icon">⏳</div><p>Loading menu…</p></div>';
    wiProducts = await api('walkin_get_products');
    wiRenderGrid(wiProducts);
}

function wiFilter() {
    const q = (document.getElementById('wiSearch').value || '').toLowerCase();
    wiRenderGrid(q ? wiProducts.filter(p => p.name.toLowerCase().includes(q) || (p.description||'').toLowerCase().includes(q)) : wiProducts);
}

function wiRenderGrid(list) {
    const g = document.getElementById('wiGrid');
    if (!list.length) { g.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><div class="icon">🍰</div><p>No products available.</p></div>'; return; }
    g.innerHTML = list.map(p => {
        const stock = parseInt(p.stock);
        const out   = stock === 0;
        let sb = '';
        if (out)         sb = '<span class="wi-sbadge" style="background:rgba(244,67,54,.85);color:#fff;">Out of Stock</span>';
        else if (stock<5)sb = `<span class="wi-sbadge" style="background:rgba(255,152,0,.85);color:#fff;">${stock} left</span>`;
        else             sb = `<span class="wi-sbadge" style="background:rgba(76,175,80,.85);color:#fff;">${stock} in stock</span>`;

        let imgH = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:36px;">🍰</div>';
        if (p.photo) {
            let url = p.photo;
            if (url.startsWith('0x')) url = hexToStr(url.slice(2));
            imgH = `<img src="${escH(url)}" alt="${escH(p.name)}" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.innerHTML='<div style=\\'display:flex;align-items:center;justify-content:center;height:100%;font-size:36px;\\'>🍰</div>'">`;
        }

        let fresh = '';
        if (p.days_remaining !== null) {
            const d = parseInt(p.days_remaining);
            if (d<=0)       fresh='<div style="padding:3px 12px 0;font-size:11px;color:var(--red);font-weight:700;">⚠ Expired</div>';
            else if(d<=2)   fresh=`<div style="padding:3px 12px 0;font-size:11px;color:#e53e3e;font-weight:700;">⚠ Expires in ${d}d</div>`;
            else if(d<=5)   fresh=`<div style="padding:3px 12px 0;font-size:11px;color:var(--orange);font-weight:600;">⏰ ${d} days left</div>`;
        }
        return `
        <div class="wi-product-card${out?' wi-out':''}">
            <div class="wi-product-img">${imgH}${sb}</div>
            <div class="wi-product-body">
                <div class="wi-product-name" title="${escH(p.name)}">${escH(p.name)}</div>
                ${fresh}
                <div class="wi-product-price">₱${Number(p.price).toFixed(2)}</div>
                <button class="wi-add-btn" onclick="wiAdd(${p.id})">＋ Add to Cart</button>
            </div>
        </div>`;
    }).join('');
}

function wiAdd(pid) {
    const prod = wiProducts.find(p => p.id == pid);
    if (!prod) return;
    const ex = wiCart.find(c => c.pid == pid);
    const curQty = ex ? ex.qty : 0;
    if (curQty >= parseInt(prod.stock)) { wiToast(`Only ${prod.stock} in stock`, 'warn'); return; }
    if (ex) ex.qty++;
    else wiCart.push({ pid: prod.id, name: prod.name, price: Number(prod.price), qty: 1, stock: parseInt(prod.stock) });
    wiRenderCart();
    wiToast(`"${prod.name}" added`, 'ok');
}

function wiQty(pid, d) {
    const it = wiCart.find(c => c.pid == pid);
    if (!it) return;
    it.qty += d;
    if (it.qty <= 0)        wiCart = wiCart.filter(c => c.pid != pid);
    else if (it.qty > it.stock) { it.qty = it.stock; wiToast(`Max stock: ${it.stock}`, 'warn'); }
    wiRenderCart();
}

function wiClearCart() {
    if (!wiCart.length) return;
    if (!confirm('Clear the cart?')) return;
    wiCart = []; wiRenderCart();
}

function wiRenderCart() {
    const box = document.getElementById('wiCartItems');
    const cnt = document.getElementById('wiCount');
    const tot = document.getElementById('wiTotal');
    if (!wiCart.length) {
        box.innerHTML = '<div style="text-align:center;padding:26px 0;color:var(--text-muted);font-size:13px;">Cart is empty — add products.</div>';
        cnt.textContent = '0'; tot.textContent = '₱0.00'; wiCalcChange(); return;
    }
    let tq = 0, ta = 0;
    box.innerHTML = wiCart.map(it => {
        const line = it.price * it.qty; tq += it.qty; ta += line;
        return `<div class="wi-cart-item">
            <div class="wi-cart-name">${escH(it.name)}</div>
            <div class="wi-qty-ctrl">
                <button class="wi-qty-btn" onclick="wiQty(${it.pid},-1)">−</button>
                <span class="wi-qty-num">${it.qty}</span>
                <button class="wi-qty-btn" onclick="wiQty(${it.pid},1)">+</button>
            </div>
            <div class="wi-cart-price">₱${line.toFixed(2)}</div>
        </div>`;
    }).join('');
    cnt.textContent = tq;
    tot.textContent = `₱${ta.toFixed(2)}`;
    wiCalcChange();
}

function wiSetPay(m) {
    wiPay = m;
    document.getElementById('wiPayCash').className  = 'wi-pay-btn' + (m==='cash'  ? ' wi-pay-active':'');
    document.getElementById('wiPayGcash').className = 'wi-pay-btn' + (m==='gcash' ? ' wi-pay-active':'');
    document.getElementById('wiGcashBox').style.display = m==='gcash' ? 'block':'none';
    document.getElementById('wiCashBox').style.display  = m==='cash'  ? 'block':'none';
    wiCalcChange();
}

function wiCalcChange() {
    if (wiPay !== 'cash') return;
    const total    = wiCart.reduce((s,c) => s + c.price*c.qty, 0);
    const tendered = parseFloat(document.getElementById('wiTendered').value) || 0;
    const row = document.getElementById('wiChangeRow');
    if (tendered > 0 && tendered >= total) {
        document.getElementById('wiChangeAmt').textContent = `₱${(tendered-total).toFixed(2)}`;
        row.style.display = 'flex';
    } else { row.style.display = 'none'; }
}

async function wiCheckout() {
    if (!wiCart.length) { wiToast('Cart is empty!', 'err'); return; }
    const gcashRef = document.getElementById('wiGcashRef').value.trim();
    const custName = document.getElementById('wiCustName').value.trim() || 'Walk-in Customer';
    if (wiPay === 'gcash' && !gcashRef) {
        wiToast('Enter GCash reference number.', 'err');
        document.getElementById('wiGcashRef').focus();
        return;
    }
    if (wiPay === 'gcash' && gcashRef.length !== 13) {
        wiToast('GCash reference number must be exactly 13 digits.', 'err');
        document.getElementById('wiGcashRef').focus();
        return;
    }
    if (wiPay === 'gcash' && !/^\d{13}$/.test(gcashRef)) {
        wiToast('GCash reference number must contain numbers only.', 'err');
        document.getElementById('wiGcashRef').focus();
        return;
    }
    const total = wiCart.reduce((s,c) => s + c.price * c.qty, 0);

    const btn = document.getElementById('wiCheckoutBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Processing…';

    try {
        const res = await api('walkin_checkout', {
            items:          wiCart.map(c => ({ product_id: c.pid, quantity: c.qty })),
            payment_method: wiPay,
            gcash_ref:      wiPay === 'gcash' ? gcashRef : '',
            customer_name:  custName,
        });

        if (res && res.success) {
            btn.disabled = false;
            btn.textContent = '✅ Complete Transaction';
            wiShowReceipt(res.order_id, custName, total);
        } else {
            const errMsg = (res && res.error) ? res.error : 'Transaction failed. Please try again.';
            btn.disabled = false;
            btn.textContent = '✅ Complete Transaction';
            wiToast('❌ ' + errMsg, 'err');
            await wiLoad();
        }
    } catch(e) {
        btn.disabled = false;
        btn.textContent = '✅ Complete Transaction';
        wiToast('❌ Network error. Check your connection and try again.', 'err');
    }
}


function wiShowReceipt(orderId, custName, total) {
    const now    = new Date().toLocaleString('en-PH', { dateStyle:'medium', timeStyle:'short' });
    const method = wiPay === 'gcash' ? '📲 GCash' : '💵 Cash';
    const gcashRef  = wiPay === 'gcash' ? document.getElementById('wiGcashRef').value.trim() : '';
    const tendered  = parseFloat(document.getElementById('wiTendered').value) || 0;
    const change    = wiPay === 'cash' && tendered > total ? tendered - total : null;

    const rows = wiCart.map(c =>
        `<div style="display:flex;justify-content:space-between;padding:5px 0;font-size:13px;border-bottom:1px dashed var(--border);">
            <span>${escH(c.name)} × ${c.qty}</span><span>₱${(c.price*c.qty).toFixed(2)}</span>
         </div>`).join('');

    document.getElementById('wiReceiptBody').innerHTML = `
        <div style="text-align:center;margin-bottom:16px;">
            <div style="font-size:42px;">✅</div>
            <div style="font-size:17px;font-weight:800;color:var(--plum);">Transaction Complete!</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">Order #${orderId} · ${now}</div>
        </div>
        <div style="background:var(--surface2);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:12px;">
            <div style="font-size:11px;font-weight:700;color:var(--plum);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Items</div>
            ${rows}
            <div style="display:flex;justify-content:space-between;padding-top:9px;font-size:15px;font-weight:800;color:var(--plum);">
                <span>TOTAL</span><span>₱${total.toFixed(2)}</span>
            </div>
        </div>
        <div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 16px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                <span style="color:var(--text-muted);font-weight:600;">Customer</span>
                <span style="font-weight:700;">${escH(custName)}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                <span style="color:var(--text-muted);font-weight:600;">Payment</span>
                <span style="font-weight:700;">${method}</span>
            </div>
            ${gcashRef ? `<div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;"><span style="color:var(--text-muted);font-weight:600;">GCash Ref</span><span style="font-weight:700;">${escH(gcashRef)}</span></div>`:''}
            ${change!==null?`<div style="display:flex;justify-content:space-between;font-size:15px;margin-top:4px;"><span style="color:var(--text-muted);font-weight:600;">Change</span><span style="font-weight:800;color:#065F46;">₱${change.toFixed(2)}</span></div>`:''}
        </div>`;

    document.getElementById('wiReceiptModal').classList.add('active');
    // Locally update stock so grid reflects the change immediately
    wiCart.forEach(c => {
        const p = wiProducts.find(x => x.id == c.pid);
        if (p) p.stock = Math.max(0, parseInt(p.stock) - c.qty);
    });
    wiProducts = wiProducts.filter(p => parseInt(p.stock) > 0);
}

function wiCloseReceipt() {
    document.getElementById('wiReceiptModal').classList.remove('active');
}

function wiNewTx() {
    wiCloseReceipt();
    wiCart = [];
    document.getElementById('wiCustName').value  = '';
    document.getElementById('wiGcashRef').value  = '';
    document.getElementById('wiTendered').value  = '';
    document.getElementById('wiChangeRow').style.display = 'none';
    wiSetPay('cash');
    wiRenderCart();
    wiRenderGrid(wiProducts);
    document.getElementById('wiSearch').value = '';
}

function wiToast(msg, type) {
    const styles = {
        ok:   'background:var(--green-bg);color:#065F46;border:1.5px solid var(--green);',
        warn: 'background:var(--orange-bg);color:#78350F;border:1.5px solid var(--orange);',
        err:  'background:var(--red-bg);color:var(--red);border:1.5px solid var(--red);',
    };
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;${styles[type]||styles.ok}
        padding:12px 20px;border-radius:10px;font-size:14px;font-weight:600;
        box-shadow:0 4px 20px rgba(0,0,0,.15);max-width:320px;animation:slideUp .25s ease;`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2600);
}
</script>


<script>
function mjCycleTheme() {
    var themes = ['light', 'dark'];
    var icons  = { light: '☀️', dark: '🌙' };
    var cur    = document.documentElement.getAttribute('data-theme') || 'light';
    if (!themes.includes(cur)) cur = 'light';
    var next   = themes[(themes.indexOf(cur) + 1) % 2];
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('mj_theme', next);
    var btn = document.getElementById('mjThemeBtn');
    if (btn) btn.textContent = icons[next];
}
window.addEventListener('DOMContentLoaded', function(){
    var icons = { light: '☀️', dark: '🌙' };
    var cur   = localStorage.getItem('mj_theme') || 'light';
    if (!['light','dark'].includes(cur)) { cur = 'light'; localStorage.setItem('mj_theme','light'); }
    document.documentElement.setAttribute('data-theme', cur);
    var btn   = document.getElementById('mjThemeBtn');
    if (btn) btn.textContent = icons[cur];
});
</script>
</body>
</html>