
<?php
session_start();

// ── Role check ────────────────────────────────────────────────
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'owner')) {
    header("Location: login.php");
    exit();
}

// ══════════════════════════════════════════════════════════════
//  INLINE API  –  handles AJAX from ALL merged sections
// ══════════════════════════════════════════════════════════════
if (isset($_GET['admin_api'])) {
    include 'db.php';
    header('Content-Type: application/json');
    $action = $_GET['admin_api'];
    $data   = read_json();

    try {
        // ── Staff accounts (USER + STAFF) ─────────────────────
        if ($action === 'create_staff') {
            $fullname = trim($data['fullname'] ?? '');
            $first = trim($data['first_name'] ?? ''); $last = trim($data['last_name'] ?? '');
            if ($first === '' && $last === '') [$first, $last] = split_name($fullname);
            $username = trim($data['username'] ?? '');
            $password = $data['password'] ?? '';
            if ($first === '' || $last === '' || $username === '' || $password === '') json_err('All fields are required');
            if (strlen($password) < 6) json_err('Password must be at least 6 characters');
            if (db_one($conn, "SELECT id FROM user WHERE username = ?", 's', [$username])) json_err('Username already exists');
            $conn->begin_transaction();
            try {
                db_exec($conn, "INSERT INTO user (username, first_name, last_name, password_hash, user_type, status) VALUES (?,?,?,?,'staff','Active')",
                    'ssss', [$username, $first, $last, password_hash($password, PASSWORD_DEFAULT)]);
                $uid = (int)$conn->insert_id;
                db_exec($conn, "INSERT INTO staff (user_id, job_title, hired_date) VALUES (?, ?, CURDATE())", 'is', [$uid, trim($data['job_title'] ?? '') ?: null]);
                $conn->commit();
            } catch (Throwable $e) { $conn->rollback(); throw $e; }
            json_out(['success' => true, 'user_id' => (string)$uid]);
        }

        if ($action === 'get_staff_full') {
            json_out(db_all($conn, "SELECT CAST(u.id AS CHAR) AS User_id, u.username, CONCAT(u.first_name,' ',IF(u.last_name='-','',u.last_name)) AS Fullname,
                                           u.status AS ActiveStatus, s.job_title
                                    FROM user u JOIN staff s ON s.user_id = u.id ORDER BY u.id"));
        }

        if ($action === 'toggle_staff_status') {
            $uid = (int)($data['user_id'] ?? 0);
            $n = db_exec($conn, "UPDATE user SET status = IF(status='Active','Inactive','Active') WHERE id = ? AND user_type = 'staff' AND status IN ('Active','Inactive')", 'i', [$uid]);
            json_out(['success' => $n > 0]);
        }

        // Users are archived, never deleted (CRUA) — "delete" sets status Archived (logged as ARCHIVE in the audit trail)
        if ($action === 'delete_staff_member') {
            $uid = (int)($data['user_id'] ?? 0);
            db_exec($conn, "UPDATE user SET status = 'Archived' WHERE id = ? AND user_type = 'staff'", 'i', [$uid]);
            json_out(['success' => true]);
        }

        // ── Discard items (DISCARDED_ITEMS, per batch) ────────
        if ($action === 'get_discard_products') {
            $rows = products_in_stock($conn);
            usort($rows, fn($a, $b) => [$a['days_remaining'] ?? 9999, $a['name']] <=> [$b['days_remaining'] ?? 9999, $b['name']]);
            json_out($rows);
        }
        if ($action === 'do_discard') {
            json_out(product_discard($conn, (int)($data['product_id'] ?? 0), (int)($data['quantity'] ?? 0), (string)($data['reason'] ?? '')));
        }
        if ($action === 'get_discard_history') json_out(discard_history($conn, isset($_GET['limit']) ? (int)$_GET['limit'] : 50));
        if ($action === 'get_discard_stats')   json_out(discard_stats($conn));

        // ── Orders management ─────────────────────────────────
        if ($action === 'get_all_orders_admin') json_out(load_orders($conn));
        if ($action === 'get_orders_statistics') json_out(orders_statistics($conn));

        // ── Sales (SALES is written when a staff member confirms an order) ──
        if ($action === 'get_sales') json_out(sales_list($conn, $_GET['period'] ?? 'today'));
        if ($action === 'record_sale') {
            json_err('Sales are recorded automatically when staff confirm an order, so manual entries are not supported.');
        }
        if ($action === 'delete_sale') {
            json_err('Records are archived, never deleted (CRUA policy).');
        }

        // ── Walk-in POS ───────────────────────────────────────
        if ($action === 'admin_walkin_get_products') json_out(products_in_stock($conn));
        if ($action === 'admin_walkin_checkout')     json_out(walkin_checkout($conn, $data));

        json_err('Unknown action');
    } catch (Throwable $e) {
        json_err($e->getMessage());
    }
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
<title>Admin Dashboard – Majoy's Homemade Sweets</title>
<link rel="stylesheet" href="majoys.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<style>

/* ══════════════════════════════════════════════
   THEME SYSTEM — 2 themes only: light · dark
   Flutter-matched palette (#7B5EA7 purple family)
   ══════════════════════════════════════════════ */

/* ── LIGHT (default) ── */
:root, [data-theme="light"] {
    --bg:            #EDE7F6;
    --surface:       #FFFFFF;
    --surface2:      #F3EEF9;
    --surface3:      #E8DFF5;
    --brand:         #7B5EA7;
    --brand-mid:     #9B7FBF;
    --brand-light:   #C4A8E0;
    --brand-pale:    #EDE7F6;
    --brand-dark:    #5A3F84;
    --brand-deeper:  #3D2468;
    --text:          #1A1A1A;
    --text-mid:      #4A3B6B;
    --text-muted:    #757575;
    --text-on-brand: #FFFFFF;
    --green:         #4CAF50;
    --green-bg:      #E8F5E9;
    --green-text:    #1B5E20;
    --amber:         #FF9800;
    --amber-bg:      #FFF3E0;
    --amber-text:    #E65100;
    --red:           #F44336;
    --red-bg:        #FFEBEE;
    --red-text:      #B71C1C;
    --blue:          #1976D2;
    --blue-bg:       #E3F2FD;
    --blue-text:     #0D47A1;
    --border:        rgba(123,94,167,0.18);
    --border-soft:   rgba(123,94,167,0.10);
    --border-strong: rgba(123,94,167,0.35);
    --shadow:        0 2px 12px rgba(123,94,167,0.10);
    --shadow-md:     0 4px 24px rgba(123,94,167,0.14);
    --shadow-lg:     0 8px 40px rgba(123,94,167,0.18);
    --radius:        14px;
    --radius-sm:     9px;
    --radius-lg:     20px;
    --sidebar-w:     256px;
    --header-h:      64px;
    --transition:    all 0.2s cubic-bezier(0.4,0,0.2,1);
    --header-bg:     #7B5EA7;
    --header-text:   #FFFFFF;
    --header-sub:    rgba(255,255,255,0.65);
}

/* ── DARK ── */
[data-theme="dark"] {
    --bg:            #1A1426;
    --surface:       #241C38;
    --surface2:      #2E2448;
    --surface3:      #1A1426;
    --brand:         #9B7FBF;
    --brand-mid:     #B89FD4;
    --brand-light:   #7B5EA7;
    --brand-pale:    #2E2448;
    --brand-dark:    #C4A8E0;
    --brand-deeper:  #EDE7F6;
    --text:          #F0EAF8;
    --text-mid:      #C4A8E0;
    --text-muted:    #8E7BAA;
    --text-on-brand: #FFFFFF;
    --green:         #66BB6A;
    --green-bg:      rgba(102,187,106,0.15);
    --green-text:    #A5D6A7;
    --amber:         #FFA726;
    --amber-bg:      rgba(255,167,38,0.15);
    --amber-text:    #FFCC80;
    --red:           #EF5350;
    --red-bg:        rgba(239,83,80,0.15);
    --red-text:      #EF9A9A;
    --blue:          #42A5F5;
    --blue-bg:       rgba(66,165,245,0.15);
    --blue-text:     #90CAF9;
    --border:        rgba(155,127,191,0.20);
    --border-soft:   rgba(155,127,191,0.10);
    --border-strong: rgba(155,127,191,0.40);
    --shadow:        0 2px 12px rgba(0,0,0,0.35);
    --shadow-md:     0 4px 24px rgba(0,0,0,0.45);
    --shadow-lg:     0 8px 40px rgba(0,0,0,0.55);
    --header-bg:     #1A1426;
    --header-text:   #EDE7F6;
    --header-sub:    rgba(237,231,246,0.55);
}

/* ── RESET ── */
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
body { background: var(--bg); min-height: 100vh; color: var(--text); -webkit-font-smoothing: antialiased; }

/* ── SCROLLBAR ── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--brand-light); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--brand); }

/* ── TOP HEADER ── */
.top-header {
    height: var(--header-h); background: var(--header-bg);
    padding: 0 28px; display: flex; justify-content: space-between;
    align-items: center; position: sticky; top: 0; z-index: 100;
    box-shadow: 0 2px 16px rgba(123,94,167,0.25);
}
.top-header::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
}
.top-header h1 { font-size: 18px; font-weight: 700; color: var(--header-text); display: flex; align-items: center; gap: 10px; letter-spacing: 0.01em; font-style: italic; }
.top-header h1 img { height: 38px; object-fit: contain; border-radius: 6px; }
.header-right { display: flex; align-items: center; gap: 16px; }
.header-right .user-chip { text-align: right; line-height: 1.35; }
.header-right .user-chip strong { color: var(--header-text); font-size: 14px; font-weight: 700; }
.header-right .user-chip span   { color: var(--header-sub); font-size: 12px; }

.logout-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; background: rgba(255,255,255,0.15); color: white; text-decoration: none; border-radius: var(--radius-sm); font-weight: 600; font-size: 13px; border: 1px solid rgba(255,255,255,0.22); transition: var(--transition); backdrop-filter: blur(8px); }
.logout-btn:hover { background: rgba(255,255,255,0.25); border-color: rgba(255,255,255,0.35); }

.theme-toggle-btn { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.22); border-radius: 50%; font-size: 16px; cursor: pointer; transition: var(--transition); flex-shrink: 0; line-height: 1; }
.theme-toggle-btn:hover { background: rgba(255,255,255,0.25); transform: rotate(20deg) scale(1.1); }

/* ── LAYOUT ── */
.dashboard-layout { display: grid; grid-template-columns: var(--sidebar-w) 1fr; min-height: calc(100vh - var(--header-h)); }
.main-content { padding: 28px 32px; overflow-y: auto; }
.section { display: none; }
.section.active { display: block; animation: sectionFadeIn .3s ease; }

/* ── SIDEBAR ── */
.sidebar { background: var(--surface); border-right: 1px solid var(--border-soft); padding: 20px 12px; overflow-y: auto; position: sticky; top: var(--header-h); height: calc(100vh - var(--header-h)); }
.sidebar-section-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.10em; color: var(--brand-light); padding: 16px 14px 6px; }
.sidebar-section-label:first-child { padding-top: 4px; }

.nav-item { padding: 11px 14px; margin-bottom: 2px; border-radius: var(--radius-sm); cursor: pointer; color: var(--text-muted); font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 10px; transition: var(--transition); border: none; background: none; width: 100%; text-align: left; text-decoration: none; position: relative; }
.nav-item:hover { background: var(--surface2); color: var(--brand); }
.nav-item.active { background: var(--brand); color: white; box-shadow: 0 3px 12px rgba(123,94,167,0.30); font-weight: 600; }
.nav-item.active::after { content: ''; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 6px; height: 6px; background: rgba(255,255,255,0.7); border-radius: 50%; }

.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(26,20,38,0.5); z-index: 199; backdrop-filter: blur(2px); }
.sidebar-overlay.active { display: block; }

/* ── SECTION TITLE ── */
.section-title { font-size: 22px; font-weight: 700; color: var(--brand); margin-bottom: 22px; padding-bottom: 14px; border-bottom: 2px solid var(--border-soft); display: flex; align-items: center; gap: 10px; }

/* ── WELCOME BANNER ── */
.welcome-banner { background: var(--brand); padding: 22px 28px; border-radius: var(--radius-lg); margin-bottom: 24px; position: relative; overflow: hidden; }
.welcome-banner::after { content: ''; position: absolute; top: 0; right: 0; width: 200px; height: 100%; background: radial-gradient(ellipse at right center, rgba(255,255,255,0.10) 0%, transparent 70%); }
.welcome-banner h3 { color: white; font-size: 18px; font-weight: 600; margin-bottom: 4px; }
.welcome-banner p  { color: rgba(255,255,255,0.70); font-size: 14px; }

/* ── STAT CARDS ── */
.stat-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.om-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
.om-stat { background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius-sm); padding: 18px 20px; text-align: center; box-shadow: var(--shadow); transition: var(--transition); }
.om-stat:hover { border-color: var(--border); transform: translateY(-2px); }
.om-stat h4 { color: var(--text-muted); font-size: 12px; font-weight: 500; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.06em; }
.om-stat-val { font-size: 30px; font-weight: 700; color: var(--brand); }

/* ── TABS ── */
.tabs-container { display: flex; background: var(--surface); border-bottom: 2px solid var(--border-soft); padding: 0 28px; gap: 4px; overflow-x: auto; scrollbar-width: none; }
.tabs-container::-webkit-scrollbar { display: none; }
.tab-btn { padding: 16px 20px; border: none; background: none; color: var(--text-muted); font-size: 14px; font-weight: 500; cursor: pointer; transition: var(--transition); white-space: nowrap; border-bottom: 3px solid transparent; margin-bottom: -2px; font-family: 'DM Sans', sans-serif; }
.tab-btn:hover  { color: var(--brand); }
.tab-btn.active { color: var(--brand); border-bottom-color: var(--brand); font-weight: 700; }
.tab-content { display: none; }
.tab-content.active { display: block; }

/* ── PRODUCTS GRID ── */
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
.product-card { background: var(--surface); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); border: 1px solid var(--border-soft); transition: var(--transition); display: flex; flex-direction: column; }
.product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--border); }
.product-card.archived-card { opacity: 0.8; border: 2px solid var(--brand-pale); }
.product-img { width: 100%; height: 180px; background: var(--surface2); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 13px; font-weight: 500; overflow: hidden; position: relative; }
.product-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
.product-card:hover .product-img img { transform: scale(1.05); }
.product-body  { padding: 16px; flex: 1; display: flex; flex-direction: column; }
.product-name  { font-weight: 700; font-size: 16px; color: var(--text); margin-bottom: 6px; }
.product-desc  { color: var(--text-muted); font-size: 13px; line-height: 1.5; margin-bottom: 12px; }
.product-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid var(--border-soft); margin-top: auto; }
.product-price { font-size: 20px; font-weight: 700; color: var(--brand); }
.product-stock { font-size: 11px; font-weight: 700; color: var(--green); text-transform: uppercase; letter-spacing: 0.05em; }
.product-stock.low { color: var(--amber); }
.product-stock.out { color: var(--red); }
/* ── SHELF BADGES ── */
.archive-badge { display: inline-flex; padding: 3px 9px; background: var(--brand); color: white; border-radius: 10px; font-size: 10px; font-weight: 700; margin-left: 6px; text-transform: uppercase; letter-spacing: 0.04em; vertical-align: middle; }

/* ── FORMS ── */
.form-card { background: var(--surface); padding: 28px; border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border-soft); max-width: 680px; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; margin-bottom: 7px; font-weight: 600; color: var(--brand-deeper); font-size: 13px; }
[data-theme="dark"] .form-group label { color: var(--brand-dark); }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 11px 15px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-family: 'DM Sans', sans-serif; background: var(--surface2); color: var(--text); transition: var(--transition); outline: none; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--brand); background: var(--surface); box-shadow: 0 0 0 3px rgba(123,94,167,0.14); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.btn-primary { padding: 12px 24px; background: var(--brand); color: white; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: 'DM Sans', sans-serif; box-shadow: 0 3px 12px rgba(123,94,167,0.30); width: 100%; }
.btn-primary:hover { background: var(--brand-dark); transform: translateY(-2px); box-shadow: 0 5px 16px rgba(123,94,167,0.38); }
.btn-success { padding: 12px 24px; background: var(--green); color: white; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: 'DM Sans', sans-serif; width: 100%; }
.btn-success:hover { filter: brightness(0.90); transform: translateY(-2px); }
.btn-danger-solid { padding: 10px 20px; background: var(--red); color: white; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: 'DM Sans', sans-serif; }
.btn-danger-solid:hover { filter: brightness(0.90); transform: translateY(-2px); }
.btn-outline { padding: 10px 20px; background: var(--surface); color: var(--brand); border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: 'DM Sans', sans-serif; }
.btn-outline:hover { background: var(--surface2); border-color: var(--brand); }
.image-preview { width: 100%; height: 160px; border: 2px dashed var(--border); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; margin-top: 10px; overflow: hidden; background: var(--surface2); color: var(--text-muted); font-size: 13px; transition: var(--transition); }
.image-preview:hover { border-color: var(--brand); }
.image-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
.success-msg { background: var(--green-bg); color: var(--green-text); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 18px; border: 1px solid var(--green); font-weight: 600; display: none; font-size: 14px; }

/* ── DATA TABLE ── */
.data-table { width: 100%; border-collapse: separate; border-spacing: 0; background: var(--surface); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); border: 1px solid var(--border-soft); }
.data-table th, .data-table td { padding: 13px 16px; text-align: left; border-bottom: 1px solid var(--border-soft); font-size: 14px; }
.data-table th { background: var(--brand); color: white; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tbody tr:hover td { background: var(--surface2); }

/* ── ORDER CARDS ── */
.order-card { background: var(--surface); padding: 22px; border-radius: var(--radius); margin-bottom: 14px; box-shadow: var(--shadow); border: 1px solid var(--border-soft); transition: var(--transition); }
.order-card:hover { border-color: var(--border); box-shadow: var(--shadow-md); }
.order-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
.order-number { font-weight: 700; font-size: 17px; color: var(--brand); }
.order-meta   { color: var(--text-muted); font-size: 13px; margin-bottom: 10px; }
.order-meta strong { color: var(--text); }
.order-item-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border-soft); font-size: 14px; }
.order-item-row:last-child { border-bottom: none; }
.order-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 14px; border-top: 2px solid var(--border-soft); }
.order-total  { font-size: 20px; font-weight: 700; color: var(--brand); }
.order-actions { display: flex; gap: 8px; flex-wrap: wrap; }
/* ── ORDER STATUS BADGES ── */
.order-status, .status-badge { display: inline-flex; padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.status-pending    { background: var(--amber-bg);  color: var(--amber-text); }
.status-approved   { background: var(--blue-bg);   color: var(--blue-text); }
.status-processing { background: #F3E8FF; color: #6B21A8; }
.status-out_for_delivery { background: #E0F2FE; color: #0369A1; }
.status-completed  { background: var(--green-bg);  color: var(--green-text); }
.status-cancelled  { background: var(--surface2);  color: var(--text-muted); }
.status-rejected   { background: var(--red-bg);    color: var(--red-text); }
.sb-completed  { background: var(--green-bg);  color: var(--green-text); }
.sb-pending    { background: var(--amber-bg);  color: var(--amber-text); }
.sb-approved   { background: var(--blue-bg);   color: var(--blue-text); }
.sb-processing { background: #F3E8FF; color: #6B21A8; }
.sb-cancelled, .sb-rejected { background: var(--red-bg); color: var(--red-text); }

/* ── ORDER FILTER BUTTONS ── */
.order-filter-btn { padding: 8px 16px; border: 1.5px solid var(--border); background: var(--surface); border-radius: 20px; font-size: 13px; font-weight: 500; color: var(--text-muted); cursor: pointer; transition: var(--transition); font-family: 'DM Sans', sans-serif; }
.order-filter-btn:hover  { border-color: var(--brand); color: var(--brand); background: var(--surface2); }
.order-filter-btn.active { background: var(--brand); color: white; border-color: transparent; box-shadow: 0 2px 8px rgba(123,94,167,0.28); }
.sale-period-btn { padding: 8px 16px; border: 1.5px solid var(--border); background: var(--surface); border-radius: 20px; font-size: 13px; font-weight: 500; color: var(--text-muted); cursor: pointer; transition: var(--transition); font-family: 'DM Sans', sans-serif; }
.sale-period-btn:hover  { border-color: var(--green); color: var(--green-text); }
.sale-period-btn.active { background: var(--green); color: white; border-color: transparent; }

/* ── ALERT BOXES ── */
.alert, .alert-box { padding: 13px 18px; border-radius: var(--radius-sm); margin-bottom: 12px; border-left: 4px solid; font-weight: 600; font-size: 14px; }
.alert-danger  { background: var(--red-bg);   border-color: var(--red);   color: var(--red-text); }
.alert-warning { background: var(--amber-bg); border-color: var(--amber); color: var(--amber-text); }
.alert-success-box { background: var(--green-bg); border-left: 4px solid var(--green); color: var(--green-text); padding: 13px 18px; border-radius: var(--radius-sm); margin-bottom: 12px; font-weight: 600; }

/* ── EMPTY STATE ── */
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.empty-state .icon { font-size: 48px; margin-bottom: 12px; opacity: 0.6; display: block; }
.empty-state p { font-size: 15px; font-weight: 500; }

/* ── MODAL ── */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(26,20,38,0.55); backdrop-filter: blur(4px); z-index: 500; align-items: center; justify-content: center; }
.modal-overlay.active { display: flex; }
.modal-box { background: var(--surface); border-radius: var(--radius-lg); padding: 32px; width: 90%; max-width: 560px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-lg), 0 0 0 1px var(--border); animation: slideUp 0.25s ease; }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid var(--border-soft); }
.modal-header h2, .modal-title { color: var(--brand); font-size: 19px; font-weight: 700; }
.modal-close { background: none; border: none; font-size: 24px; color: var(--text-muted); cursor: pointer; padding: 2px 8px; border-radius: 6px; line-height: 1; transition: var(--transition); }
.modal-close:hover { background: var(--surface2); color: var(--brand); }

/* ── TWO-COLUMN LAYOUT ── */
.two-col-layout { display: grid; grid-template-columns: 400px 1fr; gap: 24px; }
.panel-card { background: var(--surface); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border-soft); }

/* ── STAFF ITEMS ── */
.staff-item { background: var(--surface2); padding: 15px 18px; border-radius: var(--radius-sm); border: 1.5px solid var(--border-soft); margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; transition: var(--transition); }
.staff-item:hover { border-color: var(--border); box-shadow: var(--shadow); }
.staff-name { font-weight: 700; color: var(--text); font-size: 15px; }
.staff-meta { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
.staff-btns { display: flex; gap: 8px; flex-wrap: wrap; }

/* ── STRENGTH BAR ── */
.strength-bar { height: 4px; border-radius: 2px; background: var(--border); margin-top: 6px; overflow: hidden; }
.strength-fill { height: 100%; transition: all .3s; border-radius: 2px; }
.sw { background: var(--red);   width: 33%; }
.sm { background: var(--amber); width: 66%; }
.ss { background: var(--green); width: 100%; }

/* ── DISCARD ── */
.discard-tabs { display: flex; gap: 8px; margin-bottom: 22px; flex-wrap: wrap; }
.dtab { padding: 9px 20px; border: 1.5px solid var(--border); border-radius: 20px; font-size: 13px; font-weight: 500; cursor: pointer; transition: var(--transition); background: var(--surface); color: var(--text-muted); font-family: 'DM Sans', sans-serif; }
.dtab.active { background: var(--brand); color: white; border-color: transparent; box-shadow: 0 2px 8px rgba(123,94,167,0.28); font-weight: 600; }
.dtab-content { display: none; }
.dtab-content.active { display: block; }
.product-item-d { background: var(--surface); padding: 15px 18px; border: 1.5px solid var(--border-soft); border-radius: var(--radius-sm); margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; transition: var(--transition); }
.product-item-d:hover { border-color: var(--border); box-shadow: var(--shadow); }
.discard-form-box { background: var(--surface2); padding: 22px; border-radius: var(--radius-sm); border: 1.5px solid var(--border); margin-top: 18px; }
.history-item-d { background: var(--surface); padding: 16px; border-radius: var(--radius-sm); border-left: 4px solid var(--red); margin-bottom: 10px; box-shadow: var(--shadow); }
.stat-grid-d { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap: 14px; margin-bottom: 22px; }
.stat-card-d { background: var(--surface); padding: 20px; border-radius: var(--radius-sm); border: 1.5px solid var(--border-soft); text-align: center; box-shadow: var(--shadow); transition: var(--transition); }
.stat-card-d:hover { border-color: var(--border); transform: translateY(-2px); }
.stat-val { font-size: 34px; font-weight: 700; color: var(--brand); margin: 8px 0; }
.stat-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.06em; }
.reason-tag { display: inline-block; padding: 4px 12px; background: var(--red-bg); color: var(--red-text); border-radius: 8px; font-size: 12px; font-weight: 600; margin-top: 8px; }

/* ── ORDERS MANAGEMENT ── */
.om-filters { display: flex; gap: 12px; align-items: center; margin-bottom: 18px; padding: 16px 18px; background: var(--surface); border-radius: var(--radius-sm); border: 1px solid var(--border-soft); flex-wrap: wrap; box-shadow: var(--shadow); }
.om-filters input, .om-filters select { padding: 9px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; outline: none; transition: var(--transition); font-family: 'DM Sans', sans-serif; background: var(--surface2); color: var(--text); }
.om-filters input:focus, .om-filters select:focus { border-color: var(--brand); background: var(--surface); }
.om-filters input { flex: 1; min-width: 200px; }
.om-table-wrap { overflow-x: auto; border-radius: var(--radius-sm); border: 1.5px solid var(--border-soft); box-shadow: var(--shadow); }
.om-table { width: 100%; border-collapse: collapse; }
.om-table thead { background: var(--brand); }
.om-table th { padding: 13px 16px; text-align: left; color: rgba(255,255,255,0.92); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; }
.om-table tbody tr { border-bottom: 1px solid var(--border-soft); transition: var(--transition); }
.om-table tbody tr:hover { background: var(--surface2); }
.om-table tbody tr:last-child { border-bottom: none; }
.om-table td { padding: 13px 16px; color: var(--text); font-size: 14px; }

/* ── CART PANEL ── */
.cart-panel { background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); padding: 22px; box-shadow: var(--shadow); height: fit-content; position: sticky; top: calc(var(--header-h) + 16px); }
.cart-panel h2 { color: var(--brand); font-size: 18px; font-weight: 700; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 2px solid var(--border-soft); }
.cart-item { background: var(--surface2); border: 1px solid var(--border-soft); border-radius: var(--radius-sm); padding: 12px 14px; margin-bottom: 10px; }
.cart-item-name { font-weight: 600; color: var(--text); font-size: 14px; margin-bottom: 4px; }
.cart-item-price { color: var(--brand); font-weight: 700; font-size: 15px; }
.cart-qty { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
.cart-qty button { width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid var(--border); background: var(--surface); color: var(--brand); font-size: 16px; cursor: pointer; font-weight: 700; transition: var(--transition); display: flex; align-items: center; justify-content: center; }
.cart-qty button:hover { background: var(--brand); color: white; border-color: var(--brand); }
.cart-qty span { font-weight: 600; font-size: 14px; color: var(--text); min-width: 24px; text-align: center; }
.cart-total-row { display: flex; justify-content: space-between; padding: 12px 0; border-top: 2px solid var(--border-soft); margin-top: 10px; font-weight: 700; font-size: 17px; color: var(--brand); }
.cart-empty { text-align: center; padding: 40px 20px; color: var(--text-muted); font-size: 14px; }

/* ── PROFILE ── */
.profile-card { background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); padding: 28px; box-shadow: var(--shadow); max-width: 520px; }
.profile-avatar { width: 80px; height: 80px; border-radius: 50%; background: var(--brand); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; margin: 0 auto 18px; box-shadow: 0 4px 18px rgba(123,94,167,0.30); }
.profile-name { text-align: center; font-size: 20px; font-weight: 700; color: var(--brand); margin-bottom: 4px; }
.profile-role { text-align: center; font-size: 13px; color: var(--text-muted); margin-bottom: 24px; }
.profile-form-group { margin-bottom: 16px; }
.profile-form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--brand-deeper); margin-bottom: 6px; }
[data-theme="dark"] .profile-form-group label { color: var(--brand-dark); }
.profile-form-group input { width: 100%; padding: 10px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); background: var(--surface2); color: var(--text); font-size: 14px; font-family: 'DM Sans', sans-serif; outline: none; transition: var(--transition); }
.profile-form-group input:focus { border-color: var(--brand); background: var(--surface); box-shadow: 0 0 0 3px rgba(123,94,167,0.12); }

/* ── INSPIRATION GRID ── */
.inspiration-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
.inspiration-card { background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); transition: var(--transition); }
.inspiration-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--border); }
.inspiration-card img { width: 100%; height: 200px; object-fit: cover; display: block; }
.inspiration-card-body { padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; }
.inspiration-card-title { font-weight: 600; font-size: 14px; color: var(--text); }
.inspiration-card-delete { background: var(--red-bg); color: var(--red-text); border: none; border-radius: 7px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: 'DM Sans', sans-serif; }
.inspiration-card-delete:hover { filter: brightness(0.92); }

/* ── SALES ── */
.sales-row { background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; transition: var(--transition); }
.sales-row:hover { border-color: var(--border); box-shadow: var(--shadow); }
.sales-amount { font-size: 20px; font-weight: 700; color: var(--brand); }
.sales-note   { font-size: 13px; color: var(--text-muted); margin-top: 3px; }
.sales-date   { font-size: 12px; color: var(--text-muted); }

/* ── NAV DRAWER (mobile) ── */
.nav-drawer { position: fixed; top: 0; left: -290px; width: 270px; height: 100vh; background: var(--surface); z-index: 299; padding: 24px 14px; transition: left .3s ease; display: flex; flex-direction: column; gap: 4px; overflow-y: auto; border-right: 1px solid var(--border-soft); box-shadow: 4px 0 24px rgba(26,20,38,0.20); }
.nav-drawer.open { left: 0; }
.nav-overlay { display: none; position: fixed; inset: 0; z-index: 298; background: rgba(26,20,38,0.50); backdrop-filter: blur(2px); }
.nav-overlay.active { display: block; }
.nav-drawer-title { font-size: 16px; font-weight: 700; color: var(--brand); font-style: italic; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 2px solid var(--border-soft); }
.nav-drawer-btn { padding: 11px 14px; border-radius: var(--radius-sm); border: none; background: none; font-size: 14px; font-weight: 500; color: var(--text-muted); cursor: pointer; width: 100%; text-align: left; display: flex; align-items: center; gap: 10px; transition: var(--transition); font-family: 'DM Sans', sans-serif; }
.nav-drawer-btn:hover { background: var(--surface2); color: var(--brand); }
.nav-drawer-btn.active { background: var(--brand); color: white; font-weight: 600; }
.nav-drawer-divider { height: 1px; background: var(--border-soft); margin: 8px 0; }

/* ── HAMBURGER ── */
.hamburger { display: none; flex-direction: column; justify-content: center; gap: 5px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.22); cursor: pointer; padding: 8px 10px; border-radius: 8px; transition: var(--transition); }
.hamburger:hover { background: rgba(255,255,255,0.25); }
.hamburger span { display: block; width: 22px; height: 2.5px; background: var(--header-text); border-radius: 2px; transition: all .3s; }
.hamburger.open span:nth-child(1) { transform: translateY(7.5px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; }
.hamburger.open span:nth-child(3) { transform: translateY(-7.5px) rotate(-45deg); }

/* ── CONFIRM MODAL ── */
.confirm-overlay { position: fixed; inset: 0; background: rgba(26,20,38,0.60); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px); }
.confirm-modal, .confirm-box { background: var(--surface); border-radius: var(--radius); padding: 28px; max-width: 380px; width: 100%; box-shadow: var(--shadow-lg); border: 1px solid var(--border); animation: slideUp .2s ease; }
.confirm-modal h3, .confirm-box h3 { color: var(--brand); margin-bottom: 10px; font-size: 17px; font-weight: 700; }
.confirm-modal p,  .confirm-box p  { color: var(--text-muted); font-size: 14px; line-height: 1.6; margin-bottom: 22px; white-space: pre-line; }
.confirm-box-btns { display: flex; gap: 10px; justify-content: flex-end; }
.confirm-box-btns button { padding: 10px 20px; border-radius: var(--radius-sm); font-weight: 700; font-size: 14px; cursor: pointer; border: none; font-family: 'DM Sans', sans-serif; transition: var(--transition); }
.cbtn-yes        { background: var(--brand); color: white; }
.cbtn-yes:hover  { background: var(--brand-dark); transform: translateY(-2px); }
.cbtn-yes.danger { background: var(--red); }
.cbtn-no, .confirm-no  { background: var(--surface2); color: var(--brand); border: 1.5px solid var(--border); padding: 10px 20px; border-radius: var(--radius-sm); font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: var(--transition); border: none; }
.cbtn-no:hover, .confirm-no:hover { background: var(--surface3); }
.confirm-yes { background: var(--red); color: white; border: none; padding: 10px 20px; border-radius: var(--radius-sm); font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: var(--transition); }
.confirm-yes:hover { filter: brightness(0.90); transform: translateY(-2px); }

/* ── TOAST ── */
#toastContainer { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
.toast { min-width: 260px; max-width: 340px; padding: 13px 16px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; box-shadow: var(--shadow-md); display: flex; align-items: flex-start; gap: 10px; animation: toastIn .3s ease; pointer-events: all; line-height: 1.4; font-family: 'DM Sans', sans-serif; }
.toast.success { background: #1B5E20; color: white; }
.toast.error   { background: #B71C1C; color: white; }
.toast.info    { background: #0D47A1; color: white; }
.toast.warning { background: #E65100; color: white; }

/* ── MENU SEARCH ── */
.menu-search { background: var(--surface2); border: 1.5px solid var(--border); color: var(--text); }
.menu-search:focus { border-color: var(--brand); background: var(--surface); }

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
    .two-col-layout { grid-template-columns: 1fr; }
    .om-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .hamburger { display: flex; }
    .user-chip span { display: none; }
    .top-header { padding: 0 16px; }
    .top-header h1 { font-size: 16px; }
    .top-header h1 img { height: 30px; }
    .logout-btn { padding: 7px 12px; font-size: 12px; }
    .dashboard-layout { grid-template-columns: 1fr; }
    .sidebar { position: fixed; top: 0; left: 0; width: 270px; height: 100vh; z-index: 200; transform: translateX(-100%); transition: transform .3s ease; overflow-y: auto; padding: 20px 12px; border-right: 1px solid var(--border-soft); box-shadow: 4px 0 24px rgba(26,20,38,0.20); }
    .sidebar.open { transform: translateX(0); }
    .sidebar-close { display: flex; justify-content: flex-end; margin-bottom: 12px; }
    .sidebar-close button { background: none; border: none; font-size: 22px; color: var(--text-muted); cursor: pointer; padding: 4px 8px; border-radius: 6px; }
    .main-content { padding: 16px; }
    .form-row { grid-template-columns: 1fr; }
    .om-stats { grid-template-columns: repeat(2, 1fr); }
    .products-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
    .stat-grid-d { grid-template-columns: repeat(2, 1fr); }
    .staff-item { flex-direction: column; align-items: flex-start; }
    .discard-tabs { flex-wrap: wrap; }
    .om-table th, .om-table td { padding: 10px 12px; }
    }
@media (min-width: 769px) {
    .sidebar-close { display: none; }
    .sidebar { transform: none !important; position: sticky; height: calc(100vh - var(--header-h)); }
}
@media (max-width: 480px) {
    .om-stats { grid-template-columns: repeat(2, 1fr); }
    .products-grid { grid-template-columns: 1fr 1fr; }
    .section-title { font-size: 18px; }
}

/* ── DARK MODE OVERRIDES ── */
[data-theme="dark"] body { background: var(--bg); color: var(--text); }
[data-theme="dark"] .tabs-container { background: var(--surface); border-bottom-color: var(--border-soft); }
[data-theme="dark"] .tab-btn { color: var(--text-muted); }
[data-theme="dark"] .tab-btn:hover { color: var(--brand-dark); }
[data-theme="dark"] .tab-btn.active { color: var(--text); border-bottom-color: var(--brand); }
[data-theme="dark"] .product-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .product-card:hover { border-color: var(--border); }
[data-theme="dark"] .product-name { color: var(--text); }
[data-theme="dark"] .product-desc { color: var(--text-muted); }
[data-theme="dark"] .product-body { background: var(--surface); }
[data-theme="dark"] .product-footer { border-top-color: var(--border-soft); }
[data-theme="dark"] .section-title { color: var(--brand); border-bottom-color: var(--border-soft); }
[data-theme="dark"] .cart-panel { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .cart-panel h2 { color: var(--brand); border-bottom-color: var(--border-soft); }
[data-theme="dark"] .cart-item { background: var(--surface2); border-color: var(--border-soft); }
[data-theme="dark"] .cart-item-name { color: var(--text); }
[data-theme="dark"] .cart-total-row { border-top-color: var(--border-soft); }
[data-theme="dark"] .order-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .order-number { color: var(--brand); }
[data-theme="dark"] .order-item-row { border-bottom-color: var(--border-soft); color: var(--text-muted); }
[data-theme="dark"] .modal-overlay { background: rgba(0,0,0,0.70); }
[data-theme="dark"] .modal-content, [data-theme="dark"] .modal-box { background: var(--surface); border: 1px solid var(--border); }
[data-theme="dark"] .modal-header { border-bottom-color: var(--border-soft); }
[data-theme="dark"] .modal-title, [data-theme="dark"] .modal-header h2 { color: var(--brand); }
[data-theme="dark"] .modal-close { color: var(--text-muted); }
[data-theme="dark"] .form-group label { color: var(--brand-dark); }
[data-theme="dark"] .form-group input, [data-theme="dark"] .form-group select, [data-theme="dark"] .form-group textarea { background: var(--surface3); border-color: var(--border); color: var(--text); }
[data-theme="dark"] .form-group input:focus, [data-theme="dark"] .form-group select:focus, [data-theme="dark"] .form-group textarea:focus { border-color: var(--brand); background: var(--surface); }
[data-theme="dark"] .nav-drawer { background: var(--surface); border-right-color: var(--border-soft); }
[data-theme="dark"] .nav-drawer-title { color: var(--brand); }
[data-theme="dark"] .nav-drawer-btn { color: var(--text-muted); }
[data-theme="dark"] .nav-drawer-btn:hover { background: var(--surface2); color: var(--text); }
[data-theme="dark"] .nav-drawer-btn.active { background: var(--brand); color: white; }
[data-theme="dark"] .nav-drawer-divider { background: var(--border-soft); }
[data-theme="dark"] .empty-state { color: var(--text-muted); }
[data-theme="dark"] .profile-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .profile-form-group label { color: var(--brand-dark); }
[data-theme="dark"] .profile-form-group input { background: var(--surface3); border-color: var(--border); color: var(--text); }
[data-theme="dark"] .confirm-modal, [data-theme="dark"] .confirm-box { background: var(--surface); border: 1px solid var(--border); }
[data-theme="dark"] .confirm-modal h3, [data-theme="dark"] .confirm-box h3 { color: var(--brand); }
[data-theme="dark"] .confirm-modal p, [data-theme="dark"] .confirm-box p { color: var(--text-muted); }
[data-theme="dark"] .cbtn-no, [data-theme="dark"] .confirm-no { background: var(--surface2); color: var(--brand); border-color: var(--border); }
[data-theme="dark"] .menu-search { background: var(--surface3); border-color: var(--border); color: var(--text); }
[data-theme="dark"] .sidebar { background: var(--surface); border-right-color: var(--border-soft); }
[data-theme="dark"] .nav-item { color: var(--text-muted); }
[data-theme="dark"] .nav-item:hover { background: var(--surface2); color: var(--brand-dark); }
[data-theme="dark"] .nav-item.active { background: var(--brand); color: white; }
[data-theme="dark"] .data-table { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .data-table th { background: var(--brand); color: white; }
[data-theme="dark"] .data-table td { border-bottom-color: var(--border-soft); color: var(--text); }
[data-theme="dark"] .data-table tbody tr:hover td { background: var(--surface2); }
[data-theme="dark"] .panel-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .staff-item { background: var(--surface2); border-color: var(--border-soft); }
[data-theme="dark"] .staff-name { color: var(--text); }
[data-theme="dark"] .om-filters { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .om-filters input, [data-theme="dark"] .om-filters select { background: var(--surface3); border-color: var(--border); color: var(--text); }
[data-theme="dark"] .om-table thead { background: var(--brand); }
[data-theme="dark"] .om-table th { color: white; }
[data-theme="dark"] .om-table td { color: var(--text); border-bottom-color: var(--border-soft); }
[data-theme="dark"] .om-table tbody tr:hover { background: var(--surface2); }
[data-theme="dark"] .welcome-banner { background: var(--brand); }
[data-theme="dark"] .welcome-banner h3 { color: white; }
[data-theme="dark"] .welcome-banner p { color: rgba(255,255,255,0.65); }
[data-theme="dark"] .form-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .alert, [data-theme="dark"] .alert-box { background: var(--surface2); }
[data-theme="dark"] .status-processing { background: rgba(107,33,168,0.25); color: #D8B4FE; }
[data-theme="dark"] .status-out_for_delivery { background: rgba(3,105,161,0.25); color: #BAE6FD; }
[data-theme="dark"] .sb-processing { background: rgba(107,33,168,0.25); color: #D8B4FE; }
[data-theme="dark"] .discard-form-box { background: var(--surface2); border-color: var(--border); }
[data-theme="dark"] .product-item-d, [data-theme="dark"] .stat-card-d { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .image-preview { background: var(--surface2); border-color: var(--border); }
[data-theme="dark"] .om-stat { background: var(--surface); border-color: var(--border-soft); }


/* ── ENHANCED BUTTONS ── */
.product-actions { display: flex; gap: 7px; margin-top: 12px; }
.product-actions button {
    flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    padding: 9px 8px; border-radius: 9px; font-size: 12.5px; font-weight: 600;
    cursor: pointer; transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
    font-family: 'DM Sans', sans-serif; letter-spacing: 0.01em;
    position: relative; overflow: hidden;
}
.product-actions button:active { transform: scale(0.96) !important; }

.btn-edit    { background: #FF9800; color: #fff; border: none; box-shadow: 0 2px 8px rgba(255,152,0,0.35); }
.btn-edit:hover { background: #E65100; box-shadow: 0 4px 14px rgba(255,152,0,0.45); transform: translateY(-2px); }

.btn-archive { background: #7B5EA7; color: #fff; border: none; box-shadow: 0 2px 8px rgba(123,94,167,0.32); }
.btn-archive:hover { background: #5A3F84; box-shadow: 0 4px 14px rgba(123,94,167,0.42); transform: translateY(-2px); }

.btn-restore { background: #1976D2; color: #fff; border: none; box-shadow: 0 2px 8px rgba(25,118,210,0.32); }
.btn-restore:hover { background: #0D47A1; box-shadow: 0 4px 14px rgba(25,118,210,0.42); transform: translateY(-2px); }

.btn-delete  { background: #F44336; color: #fff; border: none; box-shadow: 0 2px 8px rgba(244,67,54,0.32); }
.btn-delete:hover  { background: #B71C1C; box-shadow: 0 4px 14px rgba(244,67,54,0.42); transform: translateY(-2px); }

/* ── ENHANCED SHELF BADGES ── */
.shelf-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em;
    border: 1.5px solid transparent;
}
.shelf-badge::before {
    content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
}
.shelf-fresh    { background: var(--green-bg);  color: var(--green-text); border-color: rgba(76,175,80,0.25); }
.shelf-fresh::before    { background: var(--green); }
.shelf-warning  { background: var(--amber-bg);  color: var(--amber-text); border-color: rgba(255,152,0,0.28); }
.shelf-warning::before  { background: var(--amber); }
.shelf-critical { background: var(--red-bg);    color: var(--red-text);   border-color: rgba(244,67,54,0.25); }
.shelf-critical::before { background: var(--red); }
.shelf-expired  { background: var(--red);       color: white;             border-color: transparent; }
.shelf-expired::before  { background: rgba(255,255,255,0.75); }

/* ── ENHANCED SMALL BUTTONS ── */
.btn-sm {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 13px; border: 1.5px solid transparent; border-radius: 8px;
    font-size: 12px; font-weight: 600; cursor: pointer;
    transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
    font-family: 'DM Sans', sans-serif;
}
.btn-sm:hover { transform: translateY(-2px); }
.btn-sm:active { transform: scale(0.96); }
.btn-warn  { background: #FF9800; color: #fff; border-color: transparent; box-shadow: 0 2px 6px rgba(255,152,0,0.32); }
.btn-warn:hover { background: #E65100; box-shadow: 0 3px 10px rgba(255,152,0,0.42); }
.btn-act   { background: #4CAF50; color: #fff; border-color: transparent; box-shadow: 0 2px 6px rgba(76,175,80,0.30); }
.btn-act:hover { background: #2E7D32; box-shadow: 0 3px 10px rgba(76,175,80,0.40); }
.btn-del   { background: #F44336; color: #fff; border-color: transparent; box-shadow: 0 2px 6px rgba(244,67,54,0.30); }
.btn-del:hover { background: #B71C1C; box-shadow: 0 3px 10px rgba(244,67,54,0.40); }

/* ── ENHANCED ORDER ACTION BUTTONS ── */
.order-actions button {
    display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    padding: 8px 14px; border: none; border-radius: 9px; font-size: 12.5px;
    font-weight: 600; cursor: pointer; transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
    color: white; font-family: 'DM Sans', sans-serif; letter-spacing: 0.01em;
}
.order-actions button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.22); }
.order-actions button:active { transform: scale(0.96); }
.btn-process   { background: var(--blue);  box-shadow: 0 1px 4px rgba(25,118,210,0.28); }
.btn-delivery  { background: var(--green); box-shadow: 0 1px 4px rgba(76,175,80,0.28); }
.btn-cash-sale { background: var(--amber); box-shadow: 0 1px 4px rgba(255,152,0,0.28); }
.btn-complete  { background: var(--green); box-shadow: 0 1px 4px rgba(76,175,80,0.28); }
.btn-cancel    { background: var(--red);   box-shadow: 0 1px 4px rgba(244,67,54,0.28); }
.btn-del-order { background: #8E8E8E;      box-shadow: 0 1px 4px rgba(0,0,0,0.18); }

</style>
</head>
<body>

<?php include 'loader.php'; ?>

<!-- ═══ TOP HEADER ═══ -->
<div class="top-header">
    <div style="display:flex;align-items:center;gap:12px;">
        <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <h1>
            <img src="logo.png" alt="Majoy's">
            Admin Dashboard
        </h1>
    </div>
    <div class="header-right">
    <button class="theme-toggle-btn" id="mjThemeBtn" onclick="mjCycleTheme()" title="Switch theme">🌙</button>
        <div class="user-chip">
            <strong><?= htmlspecialchars($_SESSION['fullname']) ?></strong><br>
            <span><?= ucfirst($_SESSION['role']) ?> &nbsp;|&nbsp; <?= htmlspecialchars($_SESSION['user_id']) ?></span>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ═══ LAYOUT ═══ -->
<div class="dashboard-layout">

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-close"><button onclick="closeSidebar()">✕</button></div>
    <div class="sidebar-section-label">Products</div>
    <button class="nav-item active" onclick="switchSection('pos',this)">🛒 POS / Products</button>
    <button class="nav-item" onclick="switchSection('inventory',this)">📦 Add New Stock</button>
    <button class="nav-item" onclick="switchSection('shelflife',this)">⏰ Shelf Life</button>
    <button class="nav-item" onclick="switchSection('archive',this)">🗄️ Archive</button>

    <div class="sidebar-section-label">Orders</div>
    <button class="nav-item" onclick="switchSection('orders',this)">📋 Orders</button>
    <button class="nav-item" onclick="switchSection('customorders',this)">🎨 Custom Orders</button>
    <button class="nav-item" onclick="switchSection('ordermgmt',this)">📦 Orders Management</button>
    <button class="nav-item" onclick="switchSection('sales',this)">💰 Sales Summary</button>
    <button class="nav-item" onclick="switchSection('walkin',this)">🛒 Walk-in Customer</button>

    <div class="sidebar-section-label">Staff & Inventory</div>
    <button class="nav-item" onclick="switchSection('createstaff',this)">👥 Staff</button>
    <button class="nav-item" onclick="switchSection('discard',this)">🗑️ Discard Items</button>
    <button class="nav-item" onclick="window.location.href='materials.php'">🧂 Raw Materials &amp; Purchasing</button>
</aside>

<!-- ── MAIN CONTENT ── -->
<main class="main-content">

    <!-- ██ WELCOME ██ -->
    <div class="welcome-banner" id="welcomeBanner">
        <h3>Welcome, <?= htmlspecialchars($_SESSION['fullname']) ?>! 👋</h3>
        <p>You have full access to Majoy's Homemade Sweets management system.</p>
    </div>

    <!-- ██ POS / PRODUCTS ██ -->
    <div id="sec-pos" class="section active">
        <h2 class="section-title">Available Cake Products</h2>
        <div id="posGrid" class="products-grid">
            <div class="empty-state"><div class="icon">🍰</div><p>Loading…</p></div>
        </div>
    </div>

    <!-- ██ ADD NEW STOCK ██ -->
    <div id="sec-inventory" class="section">
        <h2 class="section-title">Add New Cake Stock</h2>
        <div id="addSuccessMsg" class="success-msg">✅ Product added successfully!</div>
        <div class="form-card">
            <div class="form-group"><label>Cake Name</label><input type="text" id="cakeName" placeholder="e.g. Chocolate Fudge Cake"></div>
            <div class="form-row">
                <div class="form-group"><label>Price (₱)</label><input type="number" id="cakePrice" placeholder="0.00" step="0.01" min="0"></div>
                <div class="form-group"><label>Stock Quantity</label><input type="number" id="cakeStock" placeholder="0" min="0"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Production Date</label><input type="date" id="cakeProdDate"></div>
                <div class="form-group"><label>Shelf Life (days)</label><input type="number" id="cakeShelfLife" placeholder="7" min="1"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea id="cakeDesc" rows="3" placeholder="Describe the cake…"></textarea></div>
            <div class="form-group">
                <label>Product Photo</label>
                <input type="file" id="cakePhoto" accept="image/*" onchange="previewImage(this, 'addImgPreview')"
                    style="padding:10px; border:2px dashed #3d2268; border-radius:10px; width:100%; cursor:pointer; background:var(--surface2);">
                <div class="image-preview" id="addImgPreview"><span>Preview will appear here</span></div>
            </div>
            <button class="btn-primary" onclick="addProduct()">Add Product</button>
        </div>
    </div>

    <!-- ██ SHELF LIFE ██ -->
    <div id="sec-shelflife" class="section">
        <h2 class="section-title">Shelf Life Monitor</h2>
        <p style="color:var(--plum-mid);margin-bottom:16px;font-size:14px;">Products that expire are auto-archived. Archive manually if needed.</p>
        <div id="shelfAlerts"></div>
        <table class="data-table">
            <thead><tr><th>Cake</th><th>Produced</th><th>Shelf (days)</th><th>Expires</th><th>Days Left</th><th>Status</th><th>Stock</th><th>Action</th></tr></thead>
            <tbody id="shelfBody"></tbody>
        </table>
    </div>

    <!-- ██ ARCHIVE ██ -->
    <div id="sec-archive" class="section">
        <h2 class="section-title">Archived Products</h2>
        <p style="color:var(--plum-mid);margin-bottom:16px;font-size:14px;">Expired or manually archived items. Restore or permanently delete them here.</p>
        <div id="archiveGrid" class="products-grid"></div>
    </div>

    <!-- ██ ORDERS ██ -->
    <div id="sec-orders" class="section">
        <h2 class="section-title">All Orders</h2>

        <!-- Filter tabs -->
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px;">
            <button class="order-filter-btn active" onclick="filterOrders('all',this)">📋 All</button>
            <button class="order-filter-btn" onclick="filterOrders('approved',this)">✅ Approved</button>
            <button class="order-filter-btn" onclick="filterOrders('processing',this)">👨‍🍳 Processing</button>
            <button class="order-filter-btn" onclick="filterOrders('out_for_delivery',this)">🚚 Out for Delivery</button>
            <button class="order-filter-btn" onclick="filterOrders('completed',this)">🎉 Completed</button>
            <button class="order-filter-btn" onclick="filterOrders('cancelled',this)">🚫 Cancelled</button>
            <button class="order-filter-btn" onclick="filterOrders('pending',this)" style="margin-left:auto;">⏳ Pending</button>
        </div>

        <div id="ordersList"></div>
    </div>

    <!-- ██ CUSTOM ORDERS ██ -->
    <div id="sec-customorders" class="section">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <h2 class="section-title" style="margin-bottom:0;border:none;padding:0;">🎨 Custom Cake Orders</h2>
            <button class="btn-primary" onclick="openPostInspirationModal()" style="padding:10px 20px;font-size:14px;">
                📸 Post Cake Inspiration
            </button>
        </div>
        <p style="color:var(--plum-mid);margin-bottom:16px;font-size:14px;">
            Custom cake requests from customers. Set a price quote — the customer will then accept or decline.
        </p>

        <!-- STATUS FILTER PILLS -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;" id="customOrderFilters">
            <button class="order-filter-btn active" onclick="filterCustomOrders('all',this)">📋 All</button>
            <button class="order-filter-btn" onclick="filterCustomOrders('pending_quote',this)">⏳ Awaiting Quote</button>
            <button class="order-filter-btn" onclick="filterCustomOrders('quoted',this)">💰 Quoted</button>
            <button class="order-filter-btn" onclick="filterCustomOrders('accepted',this)">✅ Accepted</button>
            <button class="order-filter-btn" onclick="filterCustomOrders('declined',this)">❌ Declined</button>
            <button class="order-filter-btn" onclick="filterCustomOrders('cancelled',this)">🚫 Cancelled</button>
        </div>

        <!-- POSTED INSPIRATIONS (collapsible) -->
        <div style="margin-bottom:24px;background:var(--surface);border:1px solid var(--border-soft);border-radius:var(--radius);overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;cursor:pointer;user-select:none;"
                 onclick="toggleInspirationPanel()">
                <h3 style="font-size:14px;font-weight:700;color:var(--text);margin:0;">📌 Posted Inspirations</h3>
                <span id="inspToggleIcon" style="color:var(--brand);font-size:18px;line-height:1;">▾</span>
            </div>
            <div id="inspirationPanelBody" style="padding:0 18px 18px;">
                <div id="adminInspirationGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">
                    <div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">No inspirations posted yet.</div>
                </div>
            </div>
        </div>

        <div id="customOrdersList"><div class="empty-state"><div class="icon">⏳</div><p>Loading…</p></div></div>
    </div>

    <!-- ═══ SET QUOTE MODAL ═══ -->
    <div id="quoteModal" class="modal-overlay">
        <div class="modal-box" style="max-width:480px;">
            <div class="modal-header">
                <h2>💰 Set Price Quote</h2>
                <button class="modal-close" onclick="closeQuoteModal()">&times;</button>
            </div>
            <input type="hidden" id="quoteRequestId">
            <div id="quoteRequestSummary" style="background:var(--surface2);border-radius:var(--radius-sm);padding:14px;margin-bottom:18px;font-size:13px;line-height:1.7;color:var(--text);"></div>
            <div class="form-group">
                <label>Your Price Quote (₱) *</label>
                <input type="number" id="quotePrice" placeholder="e.g. 850.00" step="0.01" min="1"
                    style="font-size:22px;font-weight:700;color:var(--brand);">
            </div>
            <div class="form-group">
                <label>Notes for Customer <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
                <textarea id="quoteNotes" rows="3" placeholder="e.g. Price includes 2-tier design, fondant decorations…"></textarea>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px;">
                <button class="btn-primary" onclick="submitQuote()" style="flex:2;">✅ Send Quote to Customer</button>
                <button class="btn-outline" onclick="closeQuoteModal()" style="flex:1;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- POST INSPIRATION MODAL -->
    <div id="postInspirationModal" style="display:none;position:fixed;inset:0;background:rgba(22,14,48,.55);z-index:2000;align-items:center;justify-content:center;">
        <div style="background:var(--surface);border-radius:16px;padding:28px;max-width:460px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg);color:var(--text);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid #e0d0ee;">
                <h3 style="font-size:18px;font-weight:700;color:var(--text);">📸 Post Cake Inspiration</h3>
                <button onclick="closePostInspirationModal()" style="background:none;border:none;font-size:24px;color:var(--plum-mid);cursor:pointer;line-height:1;">✕</button>
            </div>
            <p style="color:var(--plum-mid);font-size:13px;margin-bottom:18px;">Upload a photo customers will see as a reference in the Custom section.</p>
            <div class="form-group">
                <label>Cake Name *</label>
                <input type="text" id="inspName" placeholder="e.g., Floral Birthday Cake" style="width:100%;padding:11px 14px;border:2px solid var(--border);border-radius:8px;font-size:14px;color:var(--text);outline:none;">
            </div>
            <div class="form-group">
                <label>Description <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
                <textarea id="inspDesc" placeholder="Style, flavor, occasion..." style="width:100%;padding:11px 14px;border:2px solid var(--border);border-radius:8px;font-size:14px;color:var(--text);resize:vertical;min-height:70px;font-family:inherit;outline:none;"></textarea>
            </div>
            <div class="form-group">
                <label>Photo *</label>
                <input type="file" id="inspPhoto" accept="image/*" onchange="previewInspPhoto(this)" style="width:100%;padding:8px;border:2px solid var(--border);border-radius:8px;font-size:14px;">
                <div id="inspPreviewWrap" style="margin-top:10px;display:none;">
                    <img id="inspPreviewImg" src="" style="width:100%;max-height:200px;object-fit:cover;border-radius:8px;border:2px solid var(--border);">
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:8px;">
                <button onclick="closePostInspirationModal()" style="flex:1;padding:12px;background:var(--surface2);border:none;border-radius:8px;font-weight:700;color:var(--text);cursor:pointer;font-size:14px;">Cancel</button>
                <button onclick="submitInspiration()" style="flex:2;padding:12px;background:linear-gradient(135deg,#3d2268,#4a2878);color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:14px;">📸 Post</button>
            </div>
        </div>
    </div>

    <!-- ██ ORDERS MANAGEMENT ██ -->
    <div id="sec-ordermgmt" class="section">
        <h2 class="section-title">📦 Orders Management</h2>
        <div class="om-stats">
            <div class="om-stat"><h4>Total Orders Today</h4><div class="om-stat-val" id="omTotalToday">-</div></div>
            <div class="om-stat"><h4>Completed Today</h4><div class="om-stat-val" id="omCompletedToday">-</div></div>
            <div class="om-stat"><h4>Pending Today</h4><div class="om-stat-val" id="omPendingToday">-</div></div>
            <div class="om-stat"><h4>Revenue Today</h4><div class="om-stat-val" id="omRevenueToday">₱0</div></div>
        </div>
        <div class="om-filters">
            <input type="text" id="omSearch" placeholder="🔍 Search by Order ID or Customer…">
            <select id="omStatus">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="staff_approved">Staff Approved</option>
                <option value="approved">Approved</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div class="om-table-wrap">
            <table class="om-table">
                <thead><tr><th>Order ID</th><th>Date &amp; Time</th><th>Customer</th><th>Products</th><th>Total</th><th>Status</th></tr></thead>
                <tbody id="omTable"><tr><td colspan="6"><div class="empty-state"><div class="icon">⏳</div><p>Loading orders…</p></div></td></tr></tbody>
            </table>
        </div>
    </div>


    <!-- ██ SALES SUMMARY ██ -->
    <div id="sec-sales" class="section">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
            <h2 class="section-title" style="margin:0;border:none;padding:0;">💰 Sales Summary</h2>
        </div>

        <!-- Period filter -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
            <button class="sale-period-btn active" onclick="loadSales('today',this)">📅 Today</button>
            <button class="sale-period-btn" onclick="loadSales('week',this)">📆 This Week</button>
            <button class="sale-period-btn" onclick="loadSales('month',this)">🗓️ This Month</button>
            <button class="sale-period-btn" onclick="loadSales('all',this)">📊 All Time</button>
        </div>

        <!-- Summary cards -->
        <div class="om-stats" style="margin-bottom:20px;">
            <div class="om-stat">
                <h4>Total Sales</h4>
                <div class="om-stat-val" id="salesTotalAmt">₱0.00</div>
                <small id="salesTotalCount" style="color:var(--plum-mid);font-size:12px;">0 transactions</small>
            </div>
            <div class="om-stat">
                <h4>💵 Cash Sales</h4>
                <div class="om-stat-val" id="salesCashAmt" style="color:#48bb78;">₱0.00</div>
            </div>
            <div class="om-stat">
                <h4>💳 GCash Sales</h4>
                <div class="om-stat-val" id="salesGcashAmt" style="color:#4299e1;">₱0.00</div>
            </div>
        </div>

        <!-- Transactions table -->
        <div class="om-table-wrap">
            <table class="om-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date &amp; Time</th>
                        <th>Description</th>
                        <th>Order Ref</th>
                        <th>Method</th>
                        <th>Recorded By</th>
                        <th>Notes</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="salesTable">
                    <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted);">Loading sales...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Manual Sale Modal -->
    <div id="manualSaleModal" style="display:none;position:fixed;inset:0;background:rgba(22,14,48,.6);z-index:3000;align-items:center;justify-content:center;">
        <div style="background:var(--surface);border-radius:18px;padding:28px;max-width:480px;width:94%;box-shadow:var(--shadow-lg);max-height:90vh;overflow-y:auto;color:var(--text);">
            <h3 style="font-size:18px;font-weight:700;color:var(--text);margin-bottom:20px;">💰 Record Sale</h3>
            <div id="saleModalOrderInfo" style="display:none;background:#e8f5e9;border:1px solid #a5d6a7;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#2e7d32;font-weight:600;"></div>
            <div class="form-group">
                <label style="font-weight:600;color:var(--text);font-size:13px;display:block;margin-bottom:6px;">Description *</label>
                <input type="text" id="saleDesc" placeholder="e.g. Chocolate Cake x1 — Cash on Pickup" style="width:100%;padding:10px 14px;border:2px solid var(--border);border-radius:8px;font-size:14px;color:var(--text);outline:none;">
            </div>
            <div class="form-group" style="margin-top:14px;">
                <label style="font-weight:600;color:var(--text);font-size:13px;display:block;margin-bottom:6px;">Amount (₱) *</label>
                <input type="number" id="saleAmount" placeholder="0.00" min="0" step="0.01" style="width:100%;padding:10px 14px;border:2px solid var(--border);border-radius:8px;font-size:18px;font-weight:700;color:var(--text);outline:none;">
            </div>
            <div class="form-group" style="margin-top:14px;">
                <label style="font-weight:600;color:var(--text);font-size:13px;display:block;margin-bottom:6px;">Payment Method</label>
                <div style="display:flex;gap:10px;">
                    <label style="flex:1;cursor:pointer;">
                        <input type="radio" name="saleMethod" value="cash" checked style="margin-right:6px;">
                        💵 Cash
                    </label>
                    <label style="flex:1;cursor:pointer;">
                        <input type="radio" name="saleMethod" value="gcash" style="margin-right:6px;">
                        💳 GCash
                    </label>
                </div>
            </div>
            <div class="form-group" style="margin-top:14px;">
                <label style="font-weight:600;color:var(--text);font-size:13px;display:block;margin-bottom:6px;">Notes (optional)</label>
                <textarea id="saleNotes" placeholder="Any extra notes..." rows="2" style="width:100%;padding:10px 14px;border:2px solid var(--border);border-radius:8px;font-size:13px;color:var(--text);resize:vertical;outline:none;"></textarea>
            </div>
            <input type="hidden" id="saleOrderId" value="">
            <div style="display:flex;gap:10px;margin-top:20px;">
                <button onclick="submitManualSale()" style="flex:2;padding:12px;background:linear-gradient(135deg,#48bb78,#38a169);color:white;border:none;border-radius:10px;font-weight:700;font-size:15px;cursor:pointer;">
                    ✅ Save Sale
                </button>
                <button onclick="closeManualSaleModal()" style="flex:1;padding:12px;background:var(--surface2);border:none;border-radius:10px;font-weight:700;color:var(--text);cursor:pointer;">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- ██ STAFF LIST ██ -->
    <!-- ██ CREATE STAFF ██ -->
    <div id="sec-createstaff" class="section">
        <h2 class="section-title">👥 Staff Management</h2>
        <div class="two-col-layout">
            <!-- Form -->
            <div class="panel-card">
                <h3 style="font-size:17px;font-weight:700;color:var(--text);margin-bottom:20px;">New Staff Member</h3>
                <div id="csAlert"></div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="csFullname" placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" id="csUsername" placeholder="Choose username">
                    <small style="color:var(--plum-mid);font-size:12px;display:block;margin-top:4px;">Username must be unique</small>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" id="csPassword" placeholder="Create password">
                    <div id="csStrengthWrap" style="display:none;margin-top:6px;">
                        <div class="strength-bar"><div class="strength-fill" id="csStrengthFill"></div></div>
                        <small id="csStrengthText" style="color:var(--plum-mid);"></small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" id="csConfirm" placeholder="Re-enter password">
                </div>
                <button class="btn-success" onclick="createStaff()">✓ Create Staff Account</button>
            </div>
            <!-- Staff list -->
            <div class="panel-card">
                <h3 style="font-size:17px;font-weight:700;color:var(--text);margin-bottom:20px;">📋 Current Staff Members</h3>
                <div id="csStaffList"></div>
            </div>
        </div>
    </div>

    <!-- ██ DISCARD ITEMS ██ -->
    <div id="sec-discard" class="section">
        <h2 class="section-title">🗑️ Discard Items</h2>
        <div class="discard-tabs">
            <button class="dtab active" onclick="switchDTab('dDiscard',this)">🗑️ Discard Items</button>
            <button class="dtab" onclick="switchDTab('dHistory',this)">📋 History</button>
            <button class="dtab" onclick="switchDTab('dStats',this)">📊 Statistics</button>
        </div>

        <!-- Discard Tab -->
        <div id="dDiscard" class="dtab-content active">
            <h3 class="section-title" style="font-size:18px;">Select Product to Discard</h3>
            <div id="dProductList"></div>
            <div id="dFormWrap" style="display:none;">
                <div class="discard-form-box">
                    <h4 style="margin-bottom:16px;color:var(--text);">Discard Details</h4>
                    <div id="dSelectedInfo" style="margin-bottom:16px;"></div>
                    <div class="form-group">
                        <label>Quantity to Discard *</label>
                        <input type="number" id="dQty" min="1">
                        <small style="color:var(--plum-mid);font-size:12px;display:block;margin-top:4px;">Maximum: <span id="dMaxQty">0</span></small>
                    </div>
                    <div class="form-group">
                        <label>Reason *</label>
                        <select id="dReasonSelect" onchange="updateDReason()">
                            <option value="">-- Select Reason --</option>
                            <option value="Expired">Expired – Past shelf life</option>
                            <option value="Damaged">Damaged – Physical damage</option>
                            <option value="Quality Issue">Quality Issue – Does not meet standards</option>
                            <option value="Contaminated">Contaminated – Food safety concern</option>
                            <option value="Overproduction">Overproduction – Excess inventory</option>
                            <option value="Customer Return">Customer Return</option>
                            <option value="Other">Other (specify below)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Additional Notes</label>
                        <textarea id="dNotes" rows="3" placeholder="Add any additional details…"></textarea>
                    </div>
                    <div style="display:flex;gap:12px;">
                        <button class="btn-danger-solid" style="flex:1;" onclick="submitDiscard()">🗑️ Discard Items</button>
                        <button class="btn-outline" onclick="cancelDDiscard()">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Tab -->
        <div id="dHistory" class="dtab-content">
            <h3 class="section-title" style="font-size:18px;">Discard History</h3>
            <div id="dHistoryList"></div>
        </div>

        <!-- Stats Tab -->
        <div id="dStats" class="dtab-content">
            <h3 class="section-title" style="font-size:18px;">Discard Statistics</h3>
            <div class="stat-grid-d">
                <div class="stat-card-d"><div class="stat-lbl">Total Discarded</div><div class="stat-val" id="dStatTotal">0</div></div>
                <div class="stat-card-d"><div class="stat-lbl">Value Lost</div><div class="stat-val" id="dStatValue">₱0</div></div>
                <div class="stat-card-d"><div class="stat-lbl">This Month</div><div class="stat-val" id="dStatMonth">0</div></div>
                <div class="stat-card-d"><div class="stat-lbl">This Week</div><div class="stat-val" id="dStatWeek">0</div></div>
            </div>
            <div class="panel-card" style="margin-top:4px;">
                <p style="font-size:13px;color:var(--plum-mid);font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Most Common Reason</p>
                <p style="font-size:22px;font-weight:700;color:#f56565;margin-top:8px;" id="dStatReason">Loading…</p>
            </div>
        </div>
    </div>

    <!-- ██ WALK-IN CUSTOMER ██ -->
    <div id="sec-walkin" class="section">
        <h2 class="section-title">🛒 Walk-in Customer — Point of Sale</h2>

        <div id="awiLayout" style="display:grid;grid-template-columns:1fr 370px;gap:24px;align-items:start;">

            <!-- LEFT: Product menu -->
            <div>
                <div style="position:relative;margin-bottom:18px;">
                    <input id="awiSearch" type="text" placeholder="🔍  Search products…" oninput="awiFilter()"
                        style="width:100%;padding:11px 16px;border:1.5px solid var(--border-soft);
                               border-radius:var(--radius-sm);font-size:14px;background:var(--surface);
                               color:var(--text);font-family:'DM Sans',sans-serif;transition:var(--transition);"
                        onfocus="this.style.borderColor='var(--brand)'" onblur="this.style.borderColor='var(--border-soft)'">
                </div>
                <div id="awiGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px;">
                    <div class="empty-state"><span class="icon">⏳</span><p>Loading menu…</p></div>
                </div>
            </div>

            <!-- RIGHT: Cart + payment panel -->
            <div style="position:sticky;top:80px;">
                <div style="background:var(--surface);border:1.5px solid var(--border-soft);border-radius:var(--radius);box-shadow:var(--shadow-md);overflow:hidden;">

                    <!-- Cart header -->
                    <div style="background:var(--brand);padding:16px 20px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="color:#fff;font-weight:700;font-size:16px;">🛒 Cart</span>
                        <button onclick="awiClearCart()"
                            style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
                                   color:#fff;padding:5px 12px;border-radius:8px;cursor:pointer;
                                   font-size:12px;font-weight:600;font-family:'DM Sans',sans-serif;"
                            onmouseover="this.style.background='rgba(255,255,255,.28)'"
                            onmouseout="this.style.background='rgba(255,255,255,.15)'">🗑 Clear</button>
                    </div>

                    <!-- Cart items -->
                    <div id="awiCartItems" style="min-height:110px;max-height:260px;overflow-y:auto;padding:10px 16px;">
                        <div style="text-align:center;padding:28px 0;color:var(--text-muted);font-size:13px;">
                            Cart is empty — add products from the menu.
                        </div>
                    </div>

                    <!-- Totals -->
                    <div style="padding:10px 20px;border-top:1.5px solid var(--border-soft);background:var(--surface2);">
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-muted);font-size:12px;font-weight:600;">ITEMS</span>
                            <span id="awiCount" style="font-weight:700;color:var(--brand);">0</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:5px;">
                            <span style="font-size:15px;font-weight:700;color:var(--text);">TOTAL</span>
                            <span id="awiTotal" style="font-size:22px;font-weight:800;color:var(--brand);">₱0.00</span>
                        </div>
                    </div>

                    <!-- Payment form -->
                    <div style="padding:16px 20px;border-top:1.5px solid var(--border-soft);">

                        <!-- Customer name -->
                        <div style="margin-bottom:13px;">
                            <label style="display:block;font-size:11px;font-weight:700;color:var(--brand);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">
                                Customer Name <span style="color:var(--text-muted);font-weight:400;text-transform:none;">(optional)</span>
                            </label>
                            <input id="awiCustName" type="text" placeholder="Walk-in Customer"
                                style="width:100%;padding:9px 13px;border:1.5px solid var(--border-soft);border-radius:var(--radius-sm);
                                       font-size:14px;color:var(--text);background:var(--surface);font-family:'DM Sans',sans-serif;transition:var(--transition);"
                                onfocus="this.style.borderColor='var(--brand)'" onblur="this.style.borderColor='var(--border-soft)'">
                        </div>

                        <!-- Payment method -->
                        <div style="margin-bottom:13px;">
                            <label style="display:block;font-size:11px;font-weight:700;color:var(--brand);text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px;">Payment Method</label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                <button id="awiPayCash"  class="awi-pay-btn awi-pay-active" onclick="awiSetPay('cash')">💵 Cash</button>
                                <button id="awiPayGcash" class="awi-pay-btn"                onclick="awiSetPay('gcash')">📲 GCash</button>
                            </div>
                        </div>

                        <!-- GCash ref -->
                        <div id="awiGcashBox" style="display:none;margin-bottom:13px;">
                            <label style="display:block;font-size:11px;font-weight:700;color:var(--brand);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">
                                GCash Reference No. <span style="color:#f56565;">*</span>
                            </label>
                            <input id="awiGcashRef" type="text" placeholder="e.g. 1234567890123"
                                maxlength="13" pattern="[0-9]{13}" inputmode="numeric"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,13); document.getElementById('awiGcashCount').textContent=this.value.length+'/13';"
                                style="width:100%;padding:9px 13px;border:1.5px solid var(--border-soft);border-radius:var(--radius-sm);
                                       font-size:14px;color:var(--text);background:var(--surface);font-family:'DM Sans',sans-serif;transition:var(--transition);"
                                onfocus="this.style.borderColor='var(--brand)'" onblur="this.style.borderColor='var(--border-soft)'">
                            <div style="text-align:right;font-size:11px;color:var(--text-muted);margin-top:4px;">
                                <span id="awiGcashCount">0/13</span> digits
                            </div>
                        </div>

                        <!-- Cash tendered -->
                        <div id="awiCashBox" style="margin-bottom:13px;">
                            <label style="display:block;font-size:11px;font-weight:700;color:var(--brand);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">
                                Cash Tendered <span style="color:var(--text-muted);font-weight:400;text-transform:none;">(optional)</span>
                            </label>
                            <input id="awiTendered" type="number" placeholder="0.00" min="0" step="0.01" oninput="awiCalcChange()"
                                style="width:100%;padding:9px 13px;border:1.5px solid var(--border-soft);border-radius:var(--radius-sm);
                                       font-size:14px;color:var(--text);background:var(--surface);font-family:'DM Sans',sans-serif;transition:var(--transition);"
                                onfocus="this.style.borderColor='var(--brand)'" onblur="this.style.borderColor='var(--border-soft)'">
                            <div id="awiChangeRow" style="display:none;margin-top:7px;padding:8px 12px;
                                 background:#d1fae5;border-radius:var(--radius-sm);
                                 display:flex;justify-content:space-between;align-items:center;">
                                <span style="font-size:13px;font-weight:600;color:#065F46;">Change:</span>
                                <span id="awiChangeAmt" style="font-size:15px;font-weight:800;color:#065F46;">₱0.00</span>
                            </div>
                        </div>

                        <!-- Checkout button -->
                        <button id="awiCheckoutBtn" onclick="awiCheckout()"
                            style="width:100%;padding:14px;background:var(--brand);color:#fff;border:none;
                                   border-radius:var(--radius-sm);font-size:15px;font-weight:700;cursor:pointer;
                                   transition:var(--transition);font-family:'DM Sans',sans-serif;
                                   box-shadow:0 3px 12px rgba(123,94,167,.30);"
                            onmouseover="this.style.background='var(--brand-dark)'" onmouseout="this.style.background='var(--brand)'">
                            ✅ Complete Transaction
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>
</div><!-- end layout -->

<!-- ═══ WALK-IN RECEIPT MODAL ═══ -->
<div id="awiReceiptModal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:440px;">
        <div class="modal-header">
            <h2>🧾 Transaction Receipt</h2>
            <button class="modal-close" onclick="awiCloseReceipt()">×</button>
        </div>
        <div id="awiReceiptBody"></div>
        <div style="margin-top:16px;">
            <button onclick="awiPrintReceipt()"
                style="width:100%;padding:12px;background:linear-gradient(135deg,#7B5EA7,#5A3F84);color:#fff;border:none;
                       border-radius:var(--radius-sm);font-weight:700;cursor:pointer;font-size:15px;
                       font-family:'DM Sans',sans-serif;margin-bottom:10px;letter-spacing:0.03em;
                       box-shadow:0 3px 12px rgba(123,94,167,0.35);">🖨️ Print Receipt</button>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <button onclick="awiCloseReceipt()"
                    style="padding:11px;background:var(--surface2);border:1.5px solid var(--border-soft);
                           border-radius:var(--radius-sm);font-weight:600;cursor:pointer;color:var(--brand);
                           font-family:'DM Sans',sans-serif;font-size:14px;">Close</button>
                <button onclick="awiNewTx()"
                    style="padding:11px;background:var(--brand);color:#fff;border:none;
                           border-radius:var(--radius-sm);font-weight:700;cursor:pointer;
                           font-family:'DM Sans',sans-serif;font-size:14px;">🛒 New Transaction</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ EDIT PRODUCT MODAL ═══ -->
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h2>✏️ Edit Product</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <input type="hidden" id="editId">
        <div class="form-group"><label>Cake Name</label><input type="text" id="editName"></div>
        <div class="form-row">
            <div class="form-group"><label>Price (₱)</label><input type="number" id="editPrice" step="0.01"></div>
            <div class="form-group"><label>Stock</label><input type="number" id="editStock"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Production Date</label><input type="date" id="editProdDate"></div>
            <div class="form-group"><label>Shelf Life (days)</label><input type="number" id="editShelfLife" min="1"></div>
        </div>
        <div class="form-group"><label>Description</label><textarea id="editDesc" rows="3"></textarea></div>
        <div class="form-group">
            <label>Product Photo</label>
            <div id="editImgPreview" class="image-preview" style="margin-bottom:10px;"><span>No current image</span></div>
            <input type="hidden" id="editPhoto">
            <input type="file" id="editPhotoFile" accept="image/*" onchange="previewImage(this, 'editImgPreview')"
                style="padding:10px; border:2px dashed #3d2268; border-radius:10px; width:100%; cursor:pointer; background:var(--surface2);">
            <small style="color:var(--plum-mid); font-size:12px; margin-top:4px; display:block;">Leave empty to keep current photo</small>
        </div>
        <button class="btn-primary" onclick="updateProduct()">Save Changes</button>
    </div>
</div>

<!-- ═══ JAVASCRIPT ═══ -->
<script>
// ══════════════════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════════════════
function escH(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function emptyState(icon, text) { return `<div class="empty-state"><div class="icon">${icon}</div><p>${text}</p></div>`; }

async function apiMain(action, body = null) {
    const opts = { headers: {'Content-Type':'application/json'} };
    let url = 'Api.php?action=' + action;
    if (body) { opts.method = 'POST'; opts.body = JSON.stringify(body); }
    const res = await fetch(url, opts);
    return res.json();
}

async function apiAdmin(action, body = null) {
    const url = '?admin_api=' + action;
    const opts = { method: body ? 'POST' : 'GET' };
    if (body) { opts.headers = {'Content-Type':'application/json'}; opts.body = JSON.stringify(body); }
    const res = await fetch(url, opts);
    return res.json();
}

// ══════════════════════════════════════════════════════════════
//  MOBILE SIDEBAR
// ══════════════════════════════════════════════════════════════
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const btn = document.getElementById('hamburgerBtn');
    const isOpen = sidebar.classList.contains('open');
    if (isOpen) { closeSidebar(); } else {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        btn.classList.add('open');
    }
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('active');
    document.getElementById('hamburgerBtn').classList.remove('open');
}

// ══════════════════════════════════════════════════════════════
//  NAVIGATION
// ══════════════════════════════════════════════════════════════
function switchSection(id, btn) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('sec-' + id).classList.add('active');
    btn.classList.add('active');
    document.getElementById('welcomeBanner').style.display = 'none';
    // Close sidebar on mobile after selection
    if (window.innerWidth <= 768) closeSidebar();

    const loaders = {
        pos:          loadProducts,
        inventory:    () => { document.getElementById('cakeProdDate').value = new Date().toISOString().split('T')[0]; },
        shelflife:    loadShelfLife,
        archive:      loadArchive,
        orders:       loadOrders,
        customorders: loadCustomOrders,
        ordermgmt:    loadOrderMgmt,
        sales:        function(){ loadSales('today', document.querySelector('.sale-period-btn')); },
        createstaff:  loadCSStaff,
        discard:      loadDiscardProducts,
        walkin:       awiLoad,
    };
    if (loaders[id]) loaders[id]();
}

// ══════════════════════════════════════════════════════════════
//  POS / PRODUCTS
// ══════════════════════════════════════════════════════════════
function shelfStatus(days) {
    if (days === null) return { cls:'', txt:'N/A' };
    if (days < 0)      return { cls:'shelf-expired',  txt:'EXPIRED' };
    if (days === 0)    return { cls:'shelf-critical', txt:'Expires Today' };
    if (days <= 2)     return { cls:'shelf-critical', txt: days+'d left' };
    if (days <= 5)     return { cls:'shelf-warning',  txt: days+'d left' };
    return { cls:'shelf-fresh', txt: days+'d left' };
}

function shelfInfo(p) {
    if (!p.production_date || !p.shelf_life_days) return '';
    const expiry = new Date(new Date(p.production_date).getTime() + p.shelf_life_days*86400000);
    const days   = Math.ceil((expiry - new Date()) / 86400000);
    const st     = shelfStatus(days);
    return `<div style="margin-top:8px;padding:8px;background:var(--surface2);border-radius:7px;font-size:12px;">
                <div style="font-weight:600;color:var(--text);margin-bottom:3px;">📅 Freshness</div>
                <div style="color:var(--plum-mid);">Expires: ${expiry.toLocaleDateString()}</div>
                <span class="shelf-badge ${st.cls}" style="margin-top:4px;">${st.txt}</span>
            </div>`;
}

async function loadProducts() {
    const all = await apiMain('get_products');
    const products = all.filter(p => p.custom === 'Regular');
    const grid = document.getElementById('posGrid');
    if (!products.length) { grid.innerHTML = emptyState('🍰', 'No active products'); return; }
    grid.innerHTML = products.map(p => {
        const stockClass = p.stock == 0 ? 'out' : p.stock < 5 ? 'low' : '';
        const stockText  = p.stock == 0 ? 'Out of Stock' : p.stock + ' in stock';
        return `
        <div class="product-card">
            <div class="product-img">${p.photo ? '<img src="'+escH(p.photo)+'" alt="'+escH(p.name)+'">' : '<span>No Image</span>'}</div>
            <div class="product-body">
                <div class="product-name">${escH(p.name)}</div>
                <div class="product-desc">${escH(p.description||'')}</div>
                <div class="product-footer">
                    <span class="product-price">₱${Number(p.price).toFixed(2)}</span>
                    <span class="product-stock ${stockClass}">${stockText}</span>
                </div>
                ${shelfInfo(p)}
                <div class="product-actions">
                    <button class="btn-edit" onclick="openEdit(${p.id})">Edit</button>
                    <button class="btn-archive" onclick="archiveProduct(${p.id})">Archive</button>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function addProduct() {
    const name         = document.getElementById('cakeName').value.trim();
    const price        = parseFloat(document.getElementById('cakePrice').value);
    const stock        = parseInt(document.getElementById('cakeStock').value);
    const description  = document.getElementById('cakeDesc').value.trim();
    const prodDate     = document.getElementById('cakeProdDate').value;
    const shelfLife    = parseInt(document.getElementById('cakeShelfLife').value);
    const fileInput    = document.getElementById('cakePhoto');

    if (!name || isNaN(price) || isNaN(stock) || !prodDate || isNaN(shelfLife)) {
        showToast('Please fill in all required fields.', 'error'); return;
    }

    // Upload image first if a file was selected
    let photoPath = '';
    if (fileInput.files && fileInput.files[0]) {
        const formData = new FormData();
        formData.append('photo', fileInput.files[0]);
        const uploadRes = await fetch('upload_image.php', { method: 'POST', body: formData });
        const uploadData = await uploadRes.json();
        if (!uploadData.success) { showToast('Image upload failed: ' + (uploadData.error || 'Unknown error'), 'error'); return; }
        photoPath = uploadData.path;
    }

    const vals = { name, price, stock, description, photo: photoPath, production_date: prodDate, shelf_life_days: shelfLife };
    const res  = await apiMain('add_product', vals);
    if (res.success) {
        document.getElementById('addSuccessMsg').style.display = 'block';
        setTimeout(() => document.getElementById('addSuccessMsg').style.display = 'none', 3000);
        ['cakeName','cakePrice','cakeStock','cakeDesc','cakeShelfLife'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('cakeProdDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('cakePhoto').value = '';
        document.getElementById('addImgPreview').innerHTML = '<span>Preview will appear here</span>';
        loadProducts();
    } else { showToast('Error: ' + (res.error || 'Unknown'), 'error'); }
}

async function openEdit(id) {
    const products = await apiMain('get_products');
    const p = products.find(x => x.id == id);
    if (!p) return;
    document.getElementById('editId').value        = p.id;
    document.getElementById('editName').value      = p.name;
    document.getElementById('editPrice').value     = p.price;
    document.getElementById('editStock').value     = p.stock;
    document.getElementById('editProdDate').value  = p.production_date || '';
    document.getElementById('editShelfLife').value = p.shelf_life_days || '';
    document.getElementById('editDesc').value      = p.description || '';
    document.getElementById('editPhoto').value     = p.photo || '';
    document.getElementById('editPhotoFile').value = '';
    document.getElementById('editImgPreview').innerHTML = p.photo ? '<img src="'+escH(p.photo)+'" alt="Current photo">' : '<span>No current image</span>';
    document.getElementById('editModal').classList.add('active');
}

function closeModal() { document.getElementById('editModal').classList.remove('active'); }

async function updateProduct() {
    const id           = parseInt(document.getElementById('editId').value);
    const name         = document.getElementById('editName').value.trim();
    const price        = parseFloat(document.getElementById('editPrice').value);
    const stock        = parseInt(document.getElementById('editStock').value);
    const description  = document.getElementById('editDesc').value.trim();
    const prodDate     = document.getElementById('editProdDate').value;
    const shelfLife    = parseInt(document.getElementById('editShelfLife').value);
    const fileInput    = document.getElementById('editPhotoFile');
    let   photo        = document.getElementById('editPhoto').value; // current path

    if (!name || isNaN(price) || isNaN(stock) || !prodDate || isNaN(shelfLife)) {
        showToast('Please fill in all required fields.', 'error'); return;
    }

    // Upload new image if a new file was selected
    if (fileInput.files && fileInput.files[0]) {
        const formData = new FormData();
        formData.append('photo', fileInput.files[0]);
        const uploadRes  = await fetch('upload_image.php', { method: 'POST', body: formData });
        const uploadData = await uploadRes.json();
        if (!uploadData.success) { showToast('Image upload failed: ' + (uploadData.error || 'Unknown error'), 'error'); return; }
        photo = uploadData.path;
    }

    const vals = { id, name, price, stock, description, photo, production_date: prodDate, shelf_life_days: shelfLife };
    const res  = await apiMain('update_product', vals);
    if (res.success) { closeModal(); loadProducts(); showToast('Product updated!', 'success'); }
    else showToast('Update failed.', 'error');
}

async function archiveProduct(id) {
    const ok = await showConfirm('Archive Product', 'Archive this product? It will no longer appear in the menu.', true);
    if (!ok) return;
    const res = await apiMain('archive_product', { id, reason: 'Manually archived by admin' });
    if (res.success) { loadProducts(); showToast('Product archived.', 'info'); }
}

async function loadArchive() {
    const products = await apiMain('get_archived_products');
    const grid = document.getElementById('archiveGrid');
    if (!products.length) { grid.innerHTML = emptyState('🗄️', 'No archived products'); return; }
    grid.innerHTML = products.map(p => {
        const expiry = p.production_date && p.shelf_life_days ? new Date(new Date(p.production_date).getTime() + p.shelf_life_days*86400000).toLocaleDateString() : 'N/A';
        return `
        <div class="product-card archived-card">
            <div class="product-img">${p.photo ? '<img src="'+escH(p.photo)+'" alt="'+escH(p.name)+'">' : '<span>No Image</span>'}</div>
            <div class="product-body">
                <div class="product-name">${escH(p.name)}<span class="archive-badge">Archived</span></div>
                <div class="product-desc">${escH(p.description||'')}</div>
                <div style="margin-top:8px;padding:8px;background:var(--surface2);border-radius:7px;font-size:12px;color:var(--plum-mid);">
                    <div><strong>Archived:</strong> ${p.archived_date||'N/A'}</div>
                    <div><strong>Reason:</strong> ${escH(p.archive_reason||'–')}</div>
                    <div><strong>Expiry:</strong> ${expiry}</div>
                </div>
                <div class="product-footer" style="margin-top:10px;">
                    <span class="product-price">₱${Number(p.price).toFixed(2)}</span>
                    <span class="product-stock">${p.stock} units</span>
                </div>
                <div class="product-actions">
                    <button class="btn-restore" onclick="unarchiveProduct(${p.id})">Restore</button>
                    <button class="btn-delete" onclick="deleteArchived(${p.id})">Delete</button>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function unarchiveProduct(id) {
    const ok = await showConfirm('Restore Product', 'Restore this product to the menu?');
    if (!ok) return;
    const res = await apiMain('unarchive_product', { id });
    if (res.success) { loadArchive(); showToast('Product restored!', 'success'); }
}

async function deleteArchived(id) {
    const ok = await showConfirm('Delete Permanently', 'Permanently delete this product? This cannot be undone.', true);
    if (!ok) return;
    const res = await apiMain('delete_archived_product', { id });
    if (res.success) { loadArchive(); showToast('Product permanently deleted.', 'info'); }
}

async function loadShelfLife() {
    const rows = await apiMain('get_shelf_life');
    const tbody = document.getElementById('shelfBody');
    const alertsEl = document.getElementById('shelfAlerts');
    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">No products.</td></tr>';
        alertsEl.innerHTML = ''; return;
    }
    let critical = 0, warning = 0;
    tbody.innerHTML = rows.map(p => {
        const days = p.days_remaining !== null ? parseInt(p.days_remaining) : null;
        const st = shelfStatus(days);
        if (days !== null && days >= 0 && days <= 2) critical++;
        else if (days !== null && days > 2 && days <= 5) warning++;
        return `<tr>
            <td><strong>${escH(p.name)}</strong></td>
            <td>${p.production_date||'–'}</td>
            <td>${p.shelf_life_days||'–'}</td>
            <td>${p.expiry_date||'–'}</td>
            <td><strong>${days!==null?days:'–'}</strong></td>
            <td><span class="shelf-badge ${st.cls}">${st.txt}</span></td>
            <td>${p.stock}</td>
            <td><button class="btn-archive" style="padding:6px 12px;font-size:12px;border:none;border-radius:6px;color:white;cursor:pointer;" onclick="archiveProduct(${p.id})">Archive</button></td>
        </tr>`;
    }).join('');
    let alerts = '';
    if (critical) alerts += `<div class="alert-box alert-danger">🔴 <strong>CRITICAL:</strong> ${critical} product(s) expire within 2 days.</div>`;
    if (warning)  alerts += `<div class="alert-box alert-warning">⚠️ <strong>WARNING:</strong> ${warning} product(s) expire within 5 days.</div>`;
    alertsEl.innerHTML = alerts;
}

// ══════════════════════════════════════════════════════════════
//  ORDERS
// ══════════════════════════════════════════════════════════════
let allOrdersCache = [];
let currentOrderFilter = 'all';

async function loadOrders() {
    allOrdersCache = await apiMain('get_orders');
    renderFilteredOrders();
}

function filterOrders(filter, btn) {
    currentOrderFilter = filter;
    document.querySelectorAll('.order-filter-btn').forEach(function(b){ b.classList.remove('active'); });
    if (btn) btn.classList.add('active');
    renderFilteredOrders();
}

function renderMiniStepper(o) {
    if (['rejected','cancelled','pending','staff_approved'].indexOf(o.status) >= 0) return '';
    var isPickup = (o.fulfillment_method === 'pickup') || (o.delivery_address && o.delivery_address.startsWith('PICKUP'));
    var stepKeys, labels, icons;
    if (isPickup) {
        stepKeys = ['approved','processing','out_for_delivery','completed'];
        labels   = ['Confirmed','Preparing','Ready for Pickup','Picked Up'];
        icons    = ['&#9989;','&#128104;','&#127978;','&#127881;'];
    } else {
        stepKeys = ['approved','processing','out_for_delivery','completed'];
        labels   = ['Confirmed','Preparing','Out for Delivery','Delivered'];
        icons    = ['&#9989;','&#128104;','&#128666;','&#127881;'];
    }
    var ci = stepKeys.indexOf(o.status);
    if (o.delivery_status === 'out_for_delivery' && ci < 2) ci = 2;
    var headerColor = isDark ? 'rgba(196,168,224,0.9)' : (isPickup ? '#2e1a5a' : '#2c5282');
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var headerBg    = isDark ? 'rgba(155,127,191,0.20)' : (isPickup ? 'rgba(123,94,167,0.15)' : 'rgba(25,118,210,0.12)');
    var headerLabel = isPickup ? '&#127978; Pickup Order' : '&#128666; Delivery Order';
    var h = '<div style="margin:10px 0 6px;background:var(--surface2);border-radius:10px;overflow:hidden;">'
          + '<div style="background:' + headerBg + ';color:' + headerColor + ';font-size:10px;font-weight:700;padding:5px 10px;letter-spacing:.04em;">' + headerLabel + ' — Progress</div>'
          + '<div style="display:flex;padding:10px 8px;">';
    for (var idx = 0; idx < stepKeys.length; idx++) {
        var done  = (idx < ci) || (o.status === 'completed');
        var curr  = (idx === ci) && (o.status !== 'completed');
        var col   = done ? '#48bb78' : (curr ? (isPickup ? '#9B7FBF' : '#60A5FA') : (isDark ? 'rgba(155,127,191,0.35)' : '#ddd6fe'));
        var lbl   = done ? (isDark ? '#86EFAC' : '#22543d') : (curr ? (isDark ? '#EDE7F6' : (isPickup ? '#1e1040' : '#2c5282')) : (isDark ? 'rgba(196,168,224,0.7)' : '#6a4a8a'));
        var lineC = idx < ci ? '#48bb78' : 'rgba(123,94,167,0.25)';
        var line  = (idx < stepKeys.length - 1)
            ? '<div style="position:absolute;top:13px;left:60%;width:80%;height:2px;background:' + lineC + ';z-index:0;"></div>'
            : '';
        var pulse = curr ? 'animation:stepPulse 1.8s infinite;' : '';
        h += '<div style="display:flex;flex-direction:column;align-items:center;flex:1;position:relative;">'
           + line
           + '<div style="width:26px;height:26px;border-radius:50%;background:' + col + ';color:white;display:flex;align-items:center;justify-content:center;font-size:11px;z-index:1;font-weight:700;' + pulse + '">'
           + (done ? '&#10003;' : icons[idx])
           + '</div>'
           + '<div style="font-size:9px;font-weight:600;color:' + lbl + ';margin-top:4px;text-align:center;line-height:1.2;">' + labels[idx] + '</div>'
           + '</div>';
    }
    h += '</div></div>';
    return h;
}

function renderFilteredOrders() {
    var el = document.getElementById('ordersList');
    var list = currentOrderFilter === 'all'
        ? allOrdersCache
        : allOrdersCache.filter(function(o){
            if (currentOrderFilter === 'out_for_delivery') {
                return o.delivery_status === 'out_for_delivery' || o.status === 'out_for_delivery';
            }
            return o.status === currentOrderFilter;
          });

    if (!list.length) { el.innerHTML = emptyState('📋', 'No orders in this category'); return; }

    el.innerHTML = list.map(function(o) {
        var btns = '';
        if (o.status === 'staff_approved') {
            var isCustom = (o.items||[]).some(function(i){ return i.name && i.name.toLowerCase().includes('custom'); });
            var customNote = isCustom
                ? '<div style="background:var(--amber-bg);border:1px solid var(--amber);border-radius:6px;padding:6px 10px;font-size:12px;color:var(--amber-text);font-weight:600;margin-bottom:8px;">&#10024; Custom Cake &mdash; Customer accepted your quote. Click Approve to begin processing.</div>'
                : '';
            btns = customNote
                + '<button class="btn-complete" onclick="adminApproveOrder(' + o.id + ')">&#9989; Approve &amp; Process</button>'
                + ' <button class="btn-cancel" onclick="adminRejectOrder(' + o.id + ')">&#10060; Reject</button>';
        } else if (o.status === 'approved') {
            btns = '<button class="btn-process" onclick="changeStatus(' + o.id + ',\'processing\')">&#128104; Start Processing</button>'
                 + ' <button class="btn-cancel" onclick="changeStatus(' + o.id + ',\'cancelled\')">Cancel</button>';
        } else if (o.status === 'processing') {
            var isPickupBtn = (o.fulfillment_method === 'pickup') || (o.delivery_address && o.delivery_address.startsWith('PICKUP'));
            var atOutForDelivery = (o.delivery_status === 'out_for_delivery');
            if (atOutForDelivery) {
                if (isPickupBtn) {
                    btns = '<button class="btn-complete" style="background:linear-gradient(135deg,#2e1a5a,#2a1550);" onclick="changeStatus(' + o.id + ',\'completed\')">&#127978; Confirm Picked Up</button>'
                         + ' <button class="btn-cash-sale" onclick="openSaleFromOrder(' + o.id + ',\'out_for_delivery\',\''+escH(o.staff_name)+'\','+o.total+')">&#128181; Record Cash Sale</button>'
                         + ' <button class="btn-cancel" onclick="changeStatus(' + o.id + ',\'cancelled\')">Cancel</button>';
                } else {
                    btns = '<button class="btn-complete" onclick="changeStatus(' + o.id + ',\'completed\')">&#9989; Confirm Delivered</button>'
                         + ' <button class="btn-cash-sale" onclick="openSaleFromOrder(' + o.id + ',\'out_for_delivery\',\''+escH(o.staff_name)+'\','+o.total+')">&#128181; Record Cash Sale</button>'
                         + ' <button class="btn-cancel" onclick="changeStatus(' + o.id + ',\'cancelled\')">Cancel</button>';
                }
            } else {
                if (isPickupBtn) {
                    btns = '<button class="btn-delivery" style="background:linear-gradient(135deg,#2e1a5a,#2a1550);" onclick="changeStatus(' + o.id + ',\'out_for_delivery\')">&#127978; Ready for Pickup</button>'
                         + ' <button class="btn-cancel" onclick="changeStatus(' + o.id + ',\'cancelled\')">Cancel</button>';
                } else {
                    btns = '<button class="btn-delivery" onclick="changeStatus(' + o.id + ',\'out_for_delivery\')">&#128666; Out for Delivery</button>'
                         + ' <button class="btn-cancel" onclick="changeStatus(' + o.id + ',\'cancelled\')">Cancel</button>';
                }
            }
        } else if (o.status === 'out_for_delivery') {
            var isPickupBtn2 = (o.fulfillment_method === 'pickup') || (o.delivery_address && o.delivery_address.startsWith('PICKUP'));
            if (isPickupBtn2) {
                btns = '<button class="btn-complete" onclick="changeStatus(' + o.id + ',\'completed\')">&#127978; Confirm Picked Up</button>'
                     + ' <button class="btn-cash-sale" onclick="openSaleFromOrder(' + o.id + ',\'out_for_delivery\',\''+escH(o.staff_name)+'\','+o.total+')">&#128181; Record Cash Sale</button>'
                     + ' <button class="btn-cancel" onclick="changeStatus(' + o.id + ',\'cancelled\')">Cancel</button>';
            } else {
                btns = '<button class="btn-complete" onclick="changeStatus(' + o.id + ',\'completed\')">&#9989; Confirm Delivered</button>'
                     + ' <button class="btn-cash-sale" onclick="openSaleFromOrder(' + o.id + ',\'out_for_delivery\',\''+escH(o.staff_name)+'\','+o.total+')">&#128181; Record Cash Sale</button>'
                     + ' <button class="btn-cancel" onclick="changeStatus(' + o.id + ',\'cancelled\')">Cancel</button>';
            }
        } else if (o.status === 'completed') {
            var isCash = (o.payment_method === 'cod' || o.payment_method === 'cop' || o.payment_method === 'cash');
            btns = isCash
                ? '<button class="btn-cash-sale" onclick="openSaleFromOrder(' + o.id + ',\'completed\',\''+escH(o.staff_name)+'\','+o.total+')">&#128181; Record Cash Sale</button>'
                  + ' <button class="btn-del-order" onclick="deleteOrder(' + o.id + ')">Delete</button>'
                : '<button class="btn-del-order" onclick="deleteOrder(' + o.id + ')">Delete</button>';
        } else {
            btns = '<button class="btn-del-order" onclick="deleteOrder(' + o.id + ')">Delete</button>';
        }

        var itemRows = (o.items||[]).map(function(i){
            return '<div class="order-item-row"><span>' + escH(i.name) + ' &times;' + i.quantity + '</span><span>&#8369;' + (Number(i.price)*i.quantity).toFixed(2) + '</span></div>';
        }).join('');

        var staffReview = o.reviewed_by
            ? '<div style="background:var(--border);padding:8px 12px;border-radius:6px;font-size:12px;margin-top:8px;"><strong style="color:var(--plum-mid);">&#9989; Staff Approved by:</strong> ' + escH(o.reviewed_by) + ' on ' + new Date(o.reviewed_at).toLocaleString() + '</div>'
            : '';

        var customerInfo = '';
        if (o.delivery_name) {
            var isPickup = (o.fulfillment_method === 'pickup') || (o.delivery_address && o.delivery_address.startsWith('PICKUP'));
            var detailBorder = isPickup ? '1px solid rgba(74,144,226,.35)' : '1px solid var(--border-soft)';
            var detailLabel  = isPickup ? '&#127978; Pickup Details:' : '&#128666; Delivery Details:';
            var detailColor  = isPickup ? '#4a90e2' : 'var(--plum-mid)';
            var detailBody   = isPickup ? '<strong style="color:var(--text);">Store Pickup</strong>' : escH(o.delivery_address);
            customerInfo = '<div style="background:var(--surface2);padding:10px;border-radius:8px;margin-top:8px;font-size:13px;border:' + detailBorder + '">'
                + '<strong style="color:' + detailColor + ';">' + detailLabel + '</strong><br>'
                + '<span style="color:var(--text);">' + escH(o.delivery_name) + ' | ' + escH(o.delivery_phone) + '<br>'
                + detailBody + '</span></div>';
        }

        var dateInfo = '';
        if (o.preferred_date) {
            var pd = new Date(o.preferred_date + 'T00:00:00');
            var tl = {'morning':'Morning (8AM-12PM)','afternoon':'Afternoon (12PM-4PM)','evening':'Evening (4PM-6PM)'};
            dateInfo = '<div style="background:var(--amber-bg);color:var(--amber-text);padding:6px 10px;border-radius:6px;font-size:12px;font-weight:600;margin-top:8px;">&#128197; Scheduled: '
                + pd.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'})
                + (o.preferred_time ? ' | ' + (tl[o.preferred_time] || o.preferred_time) : '') + '</div>';
        }

        var isPickupOrder = (o.fulfillment_method === 'pickup') || (o.delivery_address && o.delivery_address.startsWith('PICKUP'));
        var deliveredLine = o.delivery_confirmed_at
            ? '<div style="color:#48bb78;font-size:12px;margin-top:6px;">&#10003; ' + (isPickupOrder ? 'Picked Up' : 'Delivered') + ': ' + new Date(o.delivery_confirmed_at).toLocaleString() + '</div>'
            : '';

        var isGcash = (o.payment_method === 'gcash');

        var payBadge = o.payment_method
            ? '<span style="background:var(--border);color:var(--plum-mid);padding:4px 10px;border-radius:8px;font-size:11px;font-weight:600;margin-left:8px;">'
              + (isGcash ? '&#128179; GCash' : '&#128181; ' + (o.fulfillment_method==='pickup' ? 'COP' : 'COD'))
              + '</span>'
            : '';

        var gcashBtn = (isGcash && (o.gcash_ref_number || o.gcash_screenshot))
            ? '<button onclick="showGcashDetails(' + o.id + ')" '
              + 'style="display:inline-flex;align-items:center;gap:5px;margin-left:8px;padding:4px 11px;background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;vertical-align:middle;">'
              + '&#128179; GCash Proof</button>'
            : '';

        var statusLabel = o.status.replace(/_/g,' ');

        return '<div class="order-card">'
            + '<div class="order-header">'
            + '<div>'
            + '<div class="order-number">Order #' + o.id + payBadge + gcashBtn + '</div>'
            + '<div class="order-meta"><strong>' + escH(o.staff_name) + '</strong> (' + o.staff_user_id + ') &nbsp;|&nbsp; ' + new Date(o.order_date).toLocaleString() + '</div>'
            + deliveredLine
            + '</div>'
            + '<span class="order-status status-' + o.status + '">' + statusLabel + '</span>'
            + '</div>'
            + dateInfo + customerInfo + staffReview
            + renderMiniStepper(o)
            + itemRows
            + '<div class="order-footer">'
            + '<span class="order-total">&#8369;' + Number(o.total).toFixed(2) + '</span>'
            + '<div class="order-actions">' + btns + '</div>'
            + '</div>'
            + '</div>';
    }).join('');
}


// ── GCash Proof Modal ────────────────────────────────────────
function showGcashDetails(orderId) {
    var order = allOrdersCache.find(function(o){ return o.id == orderId; });
    if (!order) return;

    var ref  = order.gcash_ref_number || '';
    var shot = order.gcash_screenshot || '';

    var refHtml = ref
        ? '<div style="font-size:26px;font-weight:800;color:var(--blue);letter-spacing:3px;font-family:monospace;">' + escH(ref) + '</div>'
        : '<div style="font-size:14px;color:var(--blue);font-style:italic;">No reference number provided</div>';

    var shotHtml = shot
        ? '<img src="' + escH(shot) + '" id="gcashShotImg" style="width:100%;max-height:320px;object-fit:contain;border-radius:10px;border:2px solid var(--border);cursor:pointer;" title="Click to open full size"><div style="font-size:11px;color:var(--text-muted);margin-top:8px;text-align:center;">Click image to open full size</div>'
        : '<div style="text-align:center;padding:24px;color:var(--text-muted);font-size:13px;">&#128247; No screenshot uploaded</div>';

    var overlay = document.createElement('div');
    overlay.id = 'gcashProofOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(26,20,38,.65);z-index:9000;display:flex;align-items:center;justify-content:center;padding:20px;';
    overlay.onclick = function(e) { if (e.target === overlay) closeGcashProof(); };

    overlay.innerHTML =
        '<div style="background:var(--surface);border-radius:18px;padding:28px;max-width:460px;width:100%;box-shadow:var(--shadow-lg);border:1px solid var(--border);max-height:90vh;overflow-y:auto;">'
      + '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:2px solid var(--border-soft);">'
      + '<h3 style="color:var(--brand);font-size:18px;font-weight:700;">&#128179; GCash Proof — Order #' + orderId + '</h3>'
      + '<button onclick="closeGcashProof()" style="background:none;border:none;font-size:24px;color:var(--text-muted);cursor:pointer;line-height:1;">&#10005;</button>'
      + '</div>'
      + '<div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:12px;padding:16px;margin-bottom:16px;">'
      + '<div style="font-size:12px;font-weight:700;color:#1e40af;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em;">&#128179; Reference Number</div>'
      + refHtml
      + '</div>'
      + '<div style="background:var(--surface2);border:1px solid var(--border-soft);border-radius:12px;padding:16px;">'
      + '<div style="font-size:12px;font-weight:700;color:var(--text-muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">&#128247; Payment Screenshot</div>'
      + shotHtml
      + '</div>'
      + '<button onclick="closeGcashProof()" style="width:100%;margin-top:18px;padding:12px;background:var(--brand);color:white;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;">Close</button>'
      + '</div>';

    document.body.appendChild(overlay);

    // Make screenshot clickable to open full size
    var img = document.getElementById('gcashShotImg');
    if (img) { img.onclick = function(){ window.open(shot, '_blank'); }; }
}

function closeGcashProof() {
    var el = document.getElementById('gcashProofOverlay');
    if (el) el.remove();
}


async function changeStatus(id, status) {
    // Find the order to check if it's pickup
    var order = allOrdersCache.find(function(o){ return o.id == id; });
    var isPickup = order && ((order.fulfillment_method === 'pickup') || (order.delivery_address && order.delivery_address.startsWith('PICKUP')));
    var msgs = {
        processing:       'Mark as Processing?',
        out_for_delivery: isPickup ? 'Mark as Ready for Pickup? Customer will be notified to collect their order.' : 'Mark as Out for Delivery? Customer will be notified.',
        completed:        isPickup ? 'Confirm the customer has picked up their order?' : 'Confirm order has been delivered and complete this order?',
        cancelled:        'Cancel this order?'
    };
    const ok = await showConfirm('Update Order', msgs[status] || 'Update status?', status === 'cancelled');
    if (!ok) return;

    try {
        const res = await apiMain('update_order_status', {id, status});
        if (res && res.success) {
            loadOrders();
            showToast(isPickup && status === 'completed' ? '✅ Order marked as Picked Up!' : '✅ Order status updated.', 'success');
        } else {
            const errMsg = (res && res.error) ? res.error : 'Failed to update status.';
            showToast('❌ ' + errMsg, 'error');
        }
    } catch(e) {
        showToast('❌ Network error. Please try again.', 'error');
    }
}

async function deleteOrder(id) {
    const ok = await showConfirm('Delete Order', 'Delete this order permanently? This cannot be undone.', true);
    if (!ok) return;
    const res = await apiMain('delete_order', {id});
    if (res.success) { loadOrders(); showToast('Order deleted.', 'info'); }
}

async function adminApproveOrder(orderId) {
    const ok = await showConfirm('Approve Order', 'Approve this order and move it to processing?');
    if (!ok) return;
    const res = await apiMain('update_order_status', {id: orderId, status: 'approved'});
    if (res.success) { loadOrders(); showToast('Order approved!', 'success'); }
}

async function adminRejectOrder(orderId) {
    const ok = await showConfirm('Reject Order', 'Reject and cancel this order?', true);
    if (!ok) return;
    const res = await apiMain('update_order_status', {id: orderId, status: 'cancelled'});
    if (res.success) { loadOrders(); showToast('Order rejected.', 'info'); }
}

// ══════════════════════════════════════════════════════════════
//  CUSTOM ORDERS
// ══════════════════════════════════════════════════════════════
// ══════════════════════════════════════════════════════════════
//  CUSTOM ORDERS (Admin)
// ══════════════════════════════════════════════════════════════
let allCustomOrders = [];
let currentCustomFilter = 'all';

function toggleInspirationPanel() {
    const body = document.getElementById('inspirationPanelBody');
    const icon = document.getElementById('inspToggleIcon');
    const isHidden = body.style.display === 'none';
    body.style.display = isHidden ? '' : 'none';
    icon.textContent = isHidden ? '▾' : '▸';
}

async function loadCustomOrders() {
    loadAdminInspirations();
    allCustomOrders = await apiMain('get_custom_orders_admin');
    if (allCustomOrders.error) { allCustomOrders = []; }
    renderCustomOrders();
}

function filterCustomOrders(filter, btn) {
    currentCustomFilter = filter;
    document.querySelectorAll('#customOrderFilters .order-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderCustomOrders();
}

function renderCustomOrders() {
    const el = document.getElementById('customOrdersList');
    const list = currentCustomFilter === 'all'
        ? allCustomOrders
        : allCustomOrders.filter(o => o.quote_status === currentCustomFilter);

    if (!list.length) {
        el.innerHTML = emptyState('🎨', currentCustomFilter === 'all' ? 'No custom requests yet' : 'No requests in this category');
        return;
    }

    const statusConfig = {
        pending_quote: { cls: 'shelf-warning',  label: '⏳ Awaiting Quote' },
        quoted:        { cls: 'shelf-fresh',    label: '💰 Quoted'         },
        accepted:      { cls: 'sb-completed',   label: '✅ Accepted'       },
        declined:      { cls: 'sb-cancelled',   label: '❌ Declined'       },
        cancelled:     { cls: 'sb-cancelled',   label: '🚫 Cancelled'      },
    };

    el.innerHTML = list.map(o => {
        const sc = statusConfig[o.quote_status] || { cls:'sb-pending', label: o.quote_status };
        const isPending  = o.quote_status === 'pending_quote';
        const isQuoted   = o.quote_status === 'quoted';
        const isAccepted = o.quote_status === 'accepted';

        // Specs come from decoded cake_specs JSON (PHP sends it as o.specs)
        const sp = o.specs || {};

        const specLines = [
            sp.size        && `<b>Size:</b> ${escH(sp.size)}`,
            sp.flavor      && `<b>Flavor:</b> ${escH(sp.flavor)}`,
            sp.layers      && `<b>Layers:</b> ${escH(sp.layers)}`,
            sp.frosting    && `<b>Frosting:</b> ${escH(sp.frosting)}`,
            sp.color_theme && `<b>Color/Theme:</b> ${escH(sp.color_theme)}`,
            sp.message     && `<b>Message:</b> ${escH(sp.message)}`,
            sp.decorations && `<b>Decorations:</b> ${escH(sp.decorations)}`,
        ].filter(Boolean).join('<br>');

        const scheduleInfo = o.preferred_date
            ? `<div style="background:var(--amber-bg);color:var(--amber-text);padding:8px 12px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:10px;">
                 📅 Needed by: ${new Date(o.preferred_date+'T00:00:00').toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'})}
                 ${o.preferred_time ? ' · ' + escH(o.preferred_time) : ''}
                 · ${o.fulfillment_method === 'pickup' ? '🏪 Pickup' : '🚚 Delivery'}
               </div>` : '';

        const contactInfo = (o.delivery_phone || o.delivery_address)
            ? `<div style="background:var(--surface2);padding:10px 12px;border-radius:8px;font-size:12px;color:var(--text-muted);margin-bottom:10px;">
                 📞 ${escH(o.delivery_phone||'—')}
                 ${o.delivery_address && !o.delivery_address.startsWith('PICKUP')
                   ? `<br>📍 ${escH(o.delivery_address)}` : (o.delivery_address ? '<br>🏪 Store Pickup' : '')}
               </div>` : '';

        const refImg = (sp.reference_image)
            ? `<div style="margin-bottom:10px;">
                 <div style="font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">📷 Reference Image:</div>
                 <img src="${escH(sp.reference_image)}" style="max-width:200px;max-height:160px;object-fit:cover;border-radius:8px;border:2px solid var(--border);cursor:pointer;" onclick="window.open('${escH(sp.reference_image)}','_blank')">
               </div>` : '';

        const quoteBlock = isQuoted
            ? `<div style="background:var(--green-bg);border:1px solid var(--green);border-radius:8px;padding:12px 16px;margin:10px 0;">
                 <div style="font-size:12px;color:var(--green-text);font-weight:600;margin-bottom:2px;">💰 Your quote sent to customer:</div>
                 <div style="font-size:26px;font-weight:700;color:var(--green-text);">₱${Number(o.quoted_price).toFixed(2)}</div>
                 ${o.quote_notes ? `<div style="font-size:12px;color:var(--green-text);margin-top:4px;">${escH(o.quote_notes)}</div>` : ''}
                 <div style="font-size:11px;color:var(--green-text);margin-top:4px;opacity:.7;">Quoted by ${escH(o.quoted_by)} · ${new Date(o.quoted_at).toLocaleString()}</div>
               </div>` : '';

        const acceptedBlock = isAccepted
            ? `<div style="background:var(--green-bg);border:1px solid var(--green);border-radius:8px;padding:12px 16px;margin:10px 0;">
                 <div style="font-size:12px;font-weight:700;color:var(--green-text);margin-bottom:4px;">✅ Customer accepted · Order #${o.id}</div>
                 <div style="font-size:20px;font-weight:700;color:var(--green-text);">₱${Number(o.quoted_price||0).toFixed(2)}</div>
                 <div style="font-size:12px;color:var(--green-text);margin-top:4px;">
                   Payment: ${o.payment_method === 'gcash' ? '💳 GCash' : '💵 Cash'}
                   ${o.gcash_ref_number ? `· Ref: <b>${escH(o.gcash_ref_number)}</b>` : ''}
                 </div>
                 ${o.gcash_screenshot
                   ? `<div style="margin-top:8px;"><img src="${escH(o.gcash_screenshot)}" style="max-width:160px;border-radius:8px;border:2px solid var(--green);cursor:pointer;" onclick="window.open('${escH(o.gcash_screenshot)}','_blank')"></div>` : ''}
               </div>` : '';

        const declinedBlock = o.quote_status === 'declined'
            ? `<div style="background:var(--red-bg);border-left:4px solid var(--red);border-radius:8px;padding:10px 14px;margin:10px 0;font-size:13px;color:var(--red-text);">
                 ❌ Customer declined the quote of ₱${Number(o.quoted_price||0).toFixed(2)}
               </div>` : '';

        // Action buttons
        let actions = '';
        if (isPending) {
            actions = `<button class="btn-sm btn-act" onclick="openQuoteModal(${o.id})">💰 Set Price Quote</button>
                       <button class="btn-sm btn-del" onclick="adminCancelCustomOrder(${o.id})">Cancel</button>`;
        } else if (isQuoted) {
            actions = `<button class="btn-sm btn-warn" onclick="openReviseModal(${o.id}, ${o.quoted_price})">✏️ Revise Quote</button>
                       <button class="btn-sm btn-del" onclick="adminCancelCustomOrder(${o.id})">Cancel</button>`;
        } else if (o.quote_status === 'declined') {
            actions = `<button class="btn-sm btn-warn" onclick="openReviseModal(${o.id}, ${o.quoted_price||0})">✏️ Offer New Price</button>`;
        } else if (isAccepted) {
            actions = `<button class="btn-sm btn-act" onclick="switchSection('orders', document.querySelector('.nav-item'))">📋 View in Orders</button>`;
        }

        return `
        <div class="order-card" style="margin-bottom:16px;">
            <div class="order-header">
                <div>
                    <div class="order-number">🎨 Custom Request #${o.id} — ${escH(o.staff_name)}</div>
                    <div class="order-meta">Customer ID: ${escH(o.staff_user_id)} · Submitted: ${new Date(o.order_date).toLocaleString()}</div>
                </div>
                <span class="shelf-badge ${sc.cls}">${sc.label}</span>
            </div>
            ${scheduleInfo}${contactInfo}
            <div style="background:var(--surface2);padding:14px;border-radius:8px;font-size:13px;line-height:1.7;margin-bottom:10px;">${specLines}</div>
            ${refImg}${quoteBlock}${acceptedBlock}${declinedBlock}
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid var(--border-soft);">
                ${actions}
            </div>
        </div>`;
    }).join('');
}

// ── Quote Modal ───────────────────────────────────────────────
function openQuoteModal(requestId) {
    const req = allCustomOrders.find(o => o.id == requestId);
    if (!req) return;
    document.getElementById('quoteRequestId').value = requestId;
    document.getElementById('quotePrice').value = '';
    document.getElementById('quoteNotes').value = '';
    var sp = req.specs || {};
    document.getElementById('quoteRequestSummary').innerHTML =
        `<strong>${escH(req.staff_name)}</strong> · ${escH(sp.size||'?')} ${escH(sp.flavor||'?')} Cake · ${sp.layers||'?'} layer(s)<br>
         ${req.preferred_date ? '📅 Needed by: ' + new Date(req.preferred_date+'T00:00:00').toLocaleDateString() : ''}
         · ${req.fulfillment_method === 'pickup' ? '🏪 Pickup' : '🚚 Delivery'}`;
    document.getElementById('quoteModal').classList.add('active');
    setTimeout(() => document.getElementById('quotePrice').focus(), 100);
}

function closeQuoteModal() {
    const modal = document.getElementById('quoteModal');
    modal.classList.remove('active');
    modal.dataset.revise = '0';
    modal.querySelector('h2').textContent = '💰 Set Price Quote';
}

async function submitQuote() {
    const id    = parseInt(document.getElementById('quoteRequestId').value);
    const price = parseFloat(document.getElementById('quotePrice').value);
    const notes = document.getElementById('quoteNotes').value.trim();
    if (!price || price <= 0) { showToast('Please enter a valid price.', 'error'); return; }
    const isRevise = document.getElementById('quoteModal').dataset.revise === '1';
    const action = isRevise ? 'revise_custom_quote' : 'set_custom_quote';
    const res = await apiMain(action, { id, price, notes });
    if (res.success) {
        showToast(`Quote of ₱${price.toFixed(2)} sent to customer!`, 'success');
        closeQuoteModal();
        loadCustomOrders();
    } else {
        showToast('Error: ' + (res.error || 'Failed to send quote'), 'error');
    }
}

function openReviseModal(requestId, currentPrice) {
    const req = allCustomOrders.find(o => o.id == requestId);
    if (!req) return;
    document.getElementById('quoteRequestId').value = requestId;
    document.getElementById('quotePrice').value = currentPrice || '';
    document.getElementById('quoteNotes').value = req.quote_notes || '';
    document.getElementById('quoteModal').dataset.revise = '1';
    document.getElementById('quoteModal').querySelector('h2').textContent = '✏️ Revise Price Quote';
    document.getElementById('quoteRequestSummary').innerHTML =
        `<strong>${escH(req.staff_name)}</strong> · Revising quote from ₱${Number(req.quoted_price||0).toFixed(2)}`;
    document.getElementById('quoteModal').classList.add('active');
    setTimeout(() => document.getElementById('quotePrice').focus(), 100);
}

async function adminCancelCustomOrder(id) {
    const ok = await showConfirm('Cancel Request', 'Cancel this custom cake request?', true);
    if (!ok) return;
    const res = await apiMain('cancel_custom_order_admin', { id });
    if (res.success) { loadCustomOrders(); showToast('Request cancelled.', 'info'); }
    else showToast('Error: ' + (res.error || 'Failed'), 'error');
}

// ══════════════════════════════════════════════════════════════
//  INSPIRATION FUNCTIONS
// ══════════════════════════════════════════════════════════════
async function loadAdminInspirations() {
    const grid = document.getElementById('adminInspirationGrid');
    if (!grid) return;
    const res = await apiMain('get_inspirations');
    if (!res || res.error || !res.length) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">No inspirations posted yet.</div>';
        return;
    }
    grid.innerHTML = res.map(img => `
        <div style="border-radius:12px;overflow:hidden;box-shadow:var(--shadow);border:1px solid var(--border);background:var(--surface);position:relative;">
            <div style="height:150px;overflow:hidden;">
                <img src="${escH(img.photo)}" alt="${escH(img.name)}" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div style="padding:10px 12px;">
                <div style="font-size:13px;font-weight:700;color:var(--text);">${escH(img.name)}</div>
                ${img.description ? `<div style="font-size:12px;color:var(--plum-mid);margin-top:2px;">${escH(img.description)}</div>` : ''}
            </div>
            <button onclick="deleteInspiration(${img.id})" title="Remove" style="position:absolute;top:6px;right:6px;background:rgba(245,101,101,.9);color:white;border:none;border-radius:50%;width:26px;height:26px;font-size:13px;cursor:pointer;font-weight:700;line-height:1;">✕</button>
        </div>`
    ).join('');
}

function openPostInspirationModal() {
    document.getElementById('postInspirationModal').style.display = 'flex';
    document.getElementById('inspName').value = '';
    document.getElementById('inspDesc').value = '';
    document.getElementById('inspPhoto').value = '';
    document.getElementById('inspPreviewWrap').style.display = 'none';
}
function closePostInspirationModal() {
    document.getElementById('postInspirationModal').style.display = 'none';
}
function previewInspPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('inspPreviewImg').src = e.target.result;
        document.getElementById('inspPreviewWrap').style.display = 'block';
    };
    reader.readAsDataURL(file);
}
async function submitInspiration() {
    const name = document.getElementById('inspName').value.trim();
    const desc = document.getElementById('inspDesc').value.trim();
    const fileInput = document.getElementById('inspPhoto');
    if (!name) { showToast('Please enter a cake name.', 'error'); return; }
    if (!fileInput.files[0]) { showToast('Please select a photo.', 'error'); return; }
    // Upload photo
    const fd = new FormData();
    fd.append('photo', fileInput.files[0]);
    let photoPath = '';
    try {
        const upRes = await fetch('upload_image.php', { method: 'POST', body: fd });
        const upData = await upRes.json();
        if (!upData.success) { showToast('Upload failed: ' + (upData.error || ''), 'error'); return; }
        photoPath = upData.path;
    } catch(e) { showToast('Upload error.', 'error'); return; }
    const res = await apiMain('add_inspiration', { name, description: desc, photo: photoPath });
    if (res.success) {
        showToast('Inspiration posted! Customers can now see it.', 'success');
        closePostInspirationModal();
        loadAdminInspirations();
    } else {
        showToast('Error: ' + (res.error || 'Failed to post.'), 'error');
    }
}
async function deleteInspiration(id) {
    const ok = await showConfirm('Remove Inspiration', 'Remove this inspiration photo?', true);
    if (!ok) return;
    const res = await apiMain('delete_inspiration', { id });
    if (res.success) { showToast('Removed.', 'info'); loadAdminInspirations(); }
    else showToast('Error: ' + (res.error || ''), 'error');
}

// ══════════════════════════════════════════════════════════════
//  STAFF LIST
// ══════════════════════════════════════════════════════════════
async function deleteStaff(uid) {
    const ok = await showConfirm('Delete Staff', 'Delete this staff member? This cannot be undone.', true);
    if (!ok) return;
    const res = await apiMain('delete_staff', {user_id: uid});
    if (res.success) { loadCSStaff(); showToast('Staff member deleted.', 'info'); }
}

// ══════════════════════════════════════════════════════════════
//  CREATE STAFF
// ══════════════════════════════════════════════════════════════
function csShowAlert(msg, type) {
    const el = document.getElementById('csAlert');
    el.innerHTML = `<div class="${type==='success'?'alert-success-box':'alert-box alert-danger'}">${type==='success'?'✓':'✗'} ${msg}</div>`;
    setTimeout(() => el.innerHTML = '', 5000);
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('csPassword').addEventListener('input', function() {
        const pw = this.value;
        const wrap = document.getElementById('csStrengthWrap');
        const fill = document.getElementById('csStrengthFill');
        const text = document.getElementById('csStrengthText');
        if (!pw) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';
        let s = 0;
        if (pw.length >= 8) s++;
        if (pw.match(/[a-z]/) && pw.match(/[A-Z]/)) s++;
        if (pw.match(/[0-9]/)) s++;
        if (pw.match(/[^a-zA-Z0-9]/)) s++;
        fill.className = 'strength-fill ' + (s<=1?'sw':s<=3?'sm':'ss');
        text.textContent = s<=1?'⚠ Weak':s<=3?'⚡ Medium':'✓ Strong';
        text.style.color = s<=1?'#f56565':s<=3?'#ed8936':'#48bb78';
    });
});

async function loadCSStaff() {
    const staff = await apiAdmin('get_staff_full');
    const el = document.getElementById('csStaffList');
    if (!staff || !staff.length) { el.innerHTML = emptyState('👥', 'No staff members yet'); return; }
    el.innerHTML = staff.map(s => `
        <div class="staff-item">
            <div>
                <div class="staff-name">${escH(s.Fullname)}</div>
                <div class="staff-meta">ID: ${escH(s.User_id)} | @${escH(s.username)} | <span style="color:${s.ActiveStatus==='Active'?'#48bb78':'#f56565'}">● ${s.ActiveStatus}</span></div>
            </div>
            <div class="staff-btns">
                <button class="btn-sm ${s.ActiveStatus==='Active'?'btn-warn':'btn-act'}" onclick="toggleStaff('${s.User_id}')">
                    ${s.ActiveStatus==='Active'?'⏸ Deactivate':'▶ Activate'}
                </button>
                <button class="btn-sm btn-del" onclick="deleteCSStaff('${s.User_id}','${escH(s.Fullname)}')">🗑 Delete</button>
            </div>
        </div>`
    ).join('');
}

async function createStaff() {
    const fullname = document.getElementById('csFullname').value.trim();
    const username = document.getElementById('csUsername').value.trim();
    const password = document.getElementById('csPassword').value;
    const confirm  = document.getElementById('csConfirm').value;
    if (!fullname || !username || !password) { csShowAlert('All fields are required', 'danger'); return; }
    if (password !== confirm) { csShowAlert('Passwords do not match', 'danger'); return; }
    if (password.length < 6) { csShowAlert('Password must be at least 6 characters', 'danger'); return; }
    const res = await apiAdmin('create_staff', {fullname, username, password});
    if (res.success) {
        csShowAlert(`Staff created! User ID: ${res.user_id}`, 'success');
        document.getElementById('csFullname').value = '';
        document.getElementById('csUsername').value = '';
        document.getElementById('csPassword').value = '';
        document.getElementById('csConfirm').value = '';
        document.getElementById('csStrengthWrap').style.display = 'none';
        loadCSStaff();
    } else {
        csShowAlert(res.error || 'Failed to create staff', 'danger');
    }
}

async function toggleStaff(uid) {
    const ok = await showConfirm('Change Status', 'Change this staff member\'s active status?');
    if (!ok) return;
    const res = await apiAdmin('toggle_staff_status', {user_id: uid});
    if (res.success) { loadCSStaff(); showToast('Staff status updated.', 'success'); }
}

async function deleteCSStaff(uid, name) {
    const ok = await showConfirm('Delete Staff', `Delete ${name}? This cannot be undone!`, true);
    if (!ok) return;
    const res = await apiAdmin('delete_staff_member', {user_id: uid});
    if (res.success) loadCSStaff();
}

// ══════════════════════════════════════════════════════════════
//  DISCARD ITEMS
// ══════════════════════════════════════════════════════════════
let dProducts = [];
let dSelected = null;

function switchDTab(id, btn) {
    document.querySelectorAll('.dtab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.dtab').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
    if (id === 'dHistory') loadDHistory();
    if (id === 'dStats') loadDStats();
}

async function loadDiscardProducts() {
    dProducts = await apiAdmin('get_discard_products');
    renderDProducts();
}

function renderDProducts() {
    const el = document.getElementById('dProductList');
    if (!dProducts.length) { el.innerHTML = emptyState('📦', 'No products available for discarding'); return; }
    el.innerHTML = dProducts.map(p => {
        let badge = '', expInfo = '';
        if (p.days_remaining !== null) {
            const d = parseInt(p.days_remaining);
            if (d < 0) { badge = '<span class="shelf-badge shelf-expired">⚠ EXPIRED</span>'; expInfo = `Expired ${Math.abs(d)} day(s) ago`; }
            else if (d <= 2) { badge = '<span class="shelf-badge shelf-critical">⚠ CRITICAL</span>'; expInfo = `Expires in ${d} day(s)`; }
            else if (d <= 5) { badge = '<span class="shelf-badge shelf-warning">⏰ EXPIRING SOON</span>'; expInfo = `${d} days until expiry`; }
            else { badge = '<span class="shelf-badge shelf-fresh">✓ FRESH</span>'; expInfo = `${d} days remaining`; }
        }
        return `
        <div class="product-item-d">
            <div style="flex:1;">
                <div style="font-weight:700;color:var(--text);font-size:15px;">${escH(p.name)}</div>
                <div style="font-size:12px;color:var(--plum-mid);margin-top:3px;">
                    Stock: ${p.stock} | Price: ₱${Number(p.price).toFixed(2)}${expInfo?' | '+expInfo:''}
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                ${badge}
                <button class="btn-danger-solid" onclick="selectDProduct(${p.id})" style="font-size:13px;">🗑️ Discard</button>
            </div>
        </div>`;
    }).join('');
}

function selectDProduct(pid) {
    dSelected = dProducts.find(p => p.id == pid);
    if (!dSelected) return;
    document.getElementById('dMaxQty').textContent = dSelected.stock;
    document.getElementById('dQty').max = dSelected.stock;
    document.getElementById('dQty').value = 1;
    document.getElementById('dReasonSelect').value = '';
    document.getElementById('dNotes').value = '';
    document.getElementById('dSelectedInfo').innerHTML = `
        <div style="background:var(--border);padding:14px;border-radius:10px;border:2px solid #3d2268;">
            <h4 style="color:var(--text);margin-bottom:6px;">${escH(dSelected.name)}</h4>
            <p style="color:var(--plum-mid);font-size:13px;">Available: ${dSelected.stock} items | Price: ₱${Number(dSelected.price).toFixed(2)} each</p>
        </div>`;
    document.getElementById('dFormWrap').style.display = 'block';
    document.getElementById('dFormWrap').scrollIntoView({behavior:'smooth', block:'nearest'});
}

function updateDReason() {
    const sel = document.getElementById('dReasonSelect');
    const notes = document.getElementById('dNotes');
    notes.required = sel.value === 'Other';
    notes.placeholder = sel.value === 'Other' ? 'Please specify the reason…' : 'Add any additional details…';
}

function cancelDDiscard() {
    dSelected = null;
    document.getElementById('dFormWrap').style.display = 'none';
}

async function submitDiscard() {
    if (!dSelected) { showToast('Please select a product first.', 'warning'); return; }
    const qty       = parseInt(document.getElementById('dQty').value);
    const reasonSel = document.getElementById('dReasonSelect').value;
    const notes     = document.getElementById('dNotes').value.trim();
    if (!reasonSel)                          { showToast('Please select a reason.', 'warning'); return; }
    if (reasonSel === 'Other' && !notes)     { showToast('Please specify the reason in the notes field.', 'warning'); return; }
    if (isNaN(qty) || qty <= 0)              { showToast('Please enter a valid quantity.', 'warning'); return; }
    if (qty > dSelected.stock)               { showToast('Cannot discard more than available stock (' + dSelected.stock + ').', 'error'); return; }
    const reason = notes ? `${reasonSel}: ${notes}` : reasonSel;
    const ok = await showConfirm('Confirm Discard', `Discard ${qty} unit(s) of ${dSelected.name}?\nReason: ${reason}\n\nThis action cannot be undone!`, true);
    if (!ok) return;
    const res = await apiAdmin('do_discard', {product_id: dSelected.id, quantity: qty, reason});
    if (res.success) {
        showToast(`Successfully discarded ${qty} item(s). Remaining stock: ${res.remaining_stock}`, 'success');
        cancelDDiscard();
        loadDiscardProducts();
    } else {
        showToast('Error: ' + (res.error || 'Failed to discard items'), 'error');
    }
}

async function loadDHistory() {
    const history = await apiAdmin('get_discard_history');
    const el = document.getElementById('dHistoryList');
    if (!history.length) { el.innerHTML = emptyState('📋', 'No discard history'); return; }
    el.innerHTML = history.map(item => {
        const valueLost = (item.quantity_discarded * item.price).toFixed(2);
        return `
        <div class="history-item-d">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                <div style="font-weight:700;color:var(--text);font-size:15px;">${escH(item.cake_name)}</div>
                <div style="font-size:12px;color:var(--plum-mid);">${new Date(item.discard_date).toLocaleDateString()}</div>
            </div>
            <div style="font-size:13px;color:var(--plum-mid);margin-top:6px;">
                <strong>Qty:</strong> ${item.quantity_discarded} | <strong>Value Lost:</strong> ₱${valueLost} | <strong>By:</strong> ${escH(item.discarded_by)}
            </div>
            <div class="reason-tag">${escH(item.reason)}</div>
        </div>`;
    }).join('');
}

async function loadDStats() {
    const stats = await apiAdmin('get_discard_stats');
    document.getElementById('dStatTotal').textContent = stats.total_discarded;
    document.getElementById('dStatValue').textContent = '₱' + Number(stats.total_value_lost).toFixed(2);
    document.getElementById('dStatMonth').textContent = stats.monthly_discarded;
    document.getElementById('dStatWeek').textContent = stats.weekly_discarded;
    document.getElementById('dStatReason').textContent = stats.common_reason;
}

// ══════════════════════════════════════════════════════════════
//  ORDERS MANAGEMENT (table view)
// ══════════════════════════════════════════════════════════════

// ══════════════════════════════════════════════════════════════
//  SALES SUMMARY
// ══════════════════════════════════════════════════════════════
var currentSalePeriod = 'today';

async function loadSales(period, btn) {
    currentSalePeriod = period || 'today';
    if (btn) {
        document.querySelectorAll('.sale-period-btn').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
    }
    var tbody = document.getElementById('salesTable');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted);">Loading...</td></tr>';
    try {
        var res = await (await fetch('?admin_api=get_sales&period=' + encodeURIComponent(currentSalePeriod))).json();
        var totals = res.totals || {};
        var sales  = res.sales  || [];
        document.getElementById('salesTotalAmt').textContent   = '\u20B1' + parseFloat(totals.total||0).toFixed(2);
        document.getElementById('salesTotalCount').textContent = (totals.count||0) + ' transaction' + (totals.count != 1 ? 's' : '');
        document.getElementById('salesCashAmt').textContent    = '\u20B1' + parseFloat(totals.cash||0).toFixed(2);
        document.getElementById('salesGcashAmt').textContent   = '\u20B1' + parseFloat(totals.gcash||0).toFixed(2);
        if (!sales.length) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted);">No sales recorded for this period.</td></tr>';
            return;
        }
        tbody.innerHTML = sales.map(function(s) {
            var dt  = new Date(s.sale_date).toLocaleString('en-US',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit',hour12:true});
            var ref = s.order_id ? 'Order #' + s.order_id : '<span style="color:var(--text-muted)">Manual</span>';
            var badge = s.payment_method === 'gcash'
                ? '<span style="background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:700;">&#128179; GCash</span>'
                : '<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:700;">&#128181; Cash</span>';
            return '<tr>'
                + '<td><strong>#' + s.id + '</strong></td>'
                + '<td style="font-size:12px;">' + dt + '</td>'
                + '<td><strong>' + escH(s.description) + '</strong></td>'
                + '<td style="font-size:12px;color:var(--plum-mid);">' + ref + '</td>'
                + '<td>' + badge + '</td>'
                + '<td style="font-size:12px;color:var(--plum-mid);">' + escH(s.recorded_by||'-') + '</td>'
                + '<td style="font-size:12px;color:var(--plum-mid);">' + escH(s.notes||'-') + '</td>'
                + '<td><strong style="color:#38a169;">\u20B1' + parseFloat(s.amount).toFixed(2) + '</strong></td>'
                + '<td><button onclick="deleteSale(' + s.id + ')" style="background:#fed7d7;color:#c53030;border:none;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;cursor:pointer;">Delete</button></td>'
                + '</tr>';
        }).join('');
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:red;">Error loading sales.</td></tr>';
    }
}

function openManualSaleModal() {
    document.getElementById('saleOrderId').value = '';
    document.getElementById('saleDesc').value = '';
    document.getElementById('saleAmount').value = '';
    document.getElementById('saleNotes').value = '';
    document.querySelector('input[name="saleMethod"][value="cash"]').checked = true;
    document.getElementById('saleModalOrderInfo').style.display = 'none';
    document.getElementById('manualSaleModal').style.display = 'flex';
}

function openSaleFromOrder(orderId, status, customerName, total) {
    document.getElementById('saleOrderId').value = orderId;
    document.getElementById('saleDesc').value = 'Order #' + orderId + ' — ' + customerName + ' (Cash on ' + (status === 'out_for_delivery' ? 'Delivery' : 'Pickup') + ')';
    document.getElementById('saleAmount').value = parseFloat(total).toFixed(2);
    document.querySelector('input[name="saleMethod"][value="cash"]').checked = true;
    document.getElementById('saleNotes').value = '';
    var info = document.getElementById('saleModalOrderInfo');
    info.textContent = '\uD83D\uDCCB Linked to Order #' + orderId + ' — \u20B1' + parseFloat(total).toFixed(2) + ' — ' + customerName;
    info.style.display = 'block';
    document.getElementById('manualSaleModal').style.display = 'flex';
}

function closeManualSaleModal() {
    document.getElementById('manualSaleModal').style.display = 'none';
}

async function submitManualSale() {
    var desc    = document.getElementById('saleDesc').value.trim();
    var amount  = parseFloat(document.getElementById('saleAmount').value);
    var method  = document.querySelector('input[name="saleMethod"]:checked').value;
    var notes   = document.getElementById('saleNotes').value.trim();
    var orderId = document.getElementById('saleOrderId').value;

    if (!desc) { showToast('Please enter a description.', 'error'); return; }
    if (!amount || amount <= 0) { showToast('Please enter a valid amount.', 'error'); return; }

    var payload = { description: desc, amount: amount, payment_method: method, notes: notes };
    if (orderId) payload.order_id = parseInt(orderId);

    var res = await apiAdmin('record_sale', payload);
    if (res.success) {
        closeManualSaleModal();
        showToast('Sale recorded successfully!', 'success');
        loadSales(currentSalePeriod, null);
    } else {
        showToast(res.error || 'Failed to record sale.', 'error');
    }
}

async function deleteSale(id) {
    var ok = await showConfirm('Delete Sale', 'Remove this sales record permanently?', true);
    if (!ok) return;
    var res = await apiAdmin('delete_sale', { id: id });
    if (res.success) { showToast('Sale deleted.', 'info'); loadSales(currentSalePeriod, null); }
    else showToast('Failed to delete.', 'error');
}

async function loadOrderMgmt() {
    loadOMStats();
    loadOMTable();
}

async function loadOMStats() {
    try {
        const stats = await apiAdmin('get_orders_statistics');
        if (stats) {
            document.getElementById('omTotalToday').textContent = stats.total_today || '0';
            document.getElementById('omCompletedToday').textContent = stats.completed_today || '0';
            document.getElementById('omPendingToday').textContent = stats.pending_today || '0';
            document.getElementById('omRevenueToday').textContent = '₱' + parseFloat(stats.revenue_today||0).toFixed(2);
        }
    } catch(e) {}
}

async function loadOMTable() {
    const tbody = document.getElementById('omTable');
    try {
        const orders = await apiAdmin('get_all_orders_admin');
        const searchText = document.getElementById('omSearch').value.toLowerCase();
        const statusFilter = document.getElementById('omStatus').value.toLowerCase();
        const filtered = (orders || []).filter(o => {
            const ms = !searchText || o.id.toString().includes(searchText) || (o.staff_name && o.staff_name.toLowerCase().includes(searchText));
            const mst = !statusFilter || (o.status && o.status.toLowerCase() === statusFilter);
            return ms && mst;
        });
        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="icon">📦</div><p>No orders found</p></div></td></tr>'; return;
        }
        tbody.innerHTML = filtered.map(o => {
            const orderId = 'ORD-' + String(o.id).padStart(5, '0');
            const d = new Date(o.order_date);
            const dt = d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) + ' • ' + d.toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit',hour12:true});
            const items = (o.items||[]).length;
            const status = (o.status||'pending').toLowerCase();
            const sbClass = {completed:'sb-completed',pending:'sb-pending',approved:'sb-approved',processing:'sb-processing',cancelled:'sb-cancelled',rejected:'sb-rejected'}[status]||'sb-pending';
            return `<tr>
                <td><strong>${orderId}</strong></td>
                <td>${dt}</td>
                <td>${escH(o.staff_name||'N/A')}</td>
                <td>${items} product${items!==1?'s':''}</td>
                <td><strong>₱${parseFloat(o.total).toFixed(2)}</strong></td>
                <td><span class="status-badge ${sbClass}">${status.toUpperCase().replace(/_/g,' ')}</span></td>
            </tr>`;
        }).join('');
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="icon">❌</div><p>Error loading orders</p></div></td></tr>';
    }
}

// ══════════════════════════════════════════════════════════════
//  INIT
// ── IMAGE PREVIEW HELPER ─────────────────────────────────────
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">'; };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── TOAST ────────────────────────────────────────────────────
function showToast(message, type = 'success', duration = 4000) {
    const icons = { success:'✅', error:'❌', info:'ℹ️', warning:'⚠️' };
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span>${icons[type]||'📢'}</span><span>${message}</span>`;
    c.appendChild(t);
    setTimeout(() => { t.style.animation = 'toastOut .3s ease forwards'; setTimeout(() => t.remove(), 300); }, duration);
}

// ── CONFIRM MODAL ────────────────────────────────────────────
let _confirmResolve = null;
function showConfirm(title, message, danger = false) {
    return new Promise(resolve => {
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMsg').textContent   = message;
        const yesBtn = document.getElementById('confirmYes');
        yesBtn.className = danger ? 'cbtn-yes danger' : 'cbtn-yes';
        yesBtn.textContent = danger ? 'Yes, proceed' : 'Confirm';
        document.getElementById('confirmOverlay').style.display = 'flex';
        _confirmResolve = (val) => { document.getElementById('confirmOverlay').style.display = 'none'; resolve(val); };
    });
}

// ══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {

    document.getElementById('omSearch').addEventListener('input', loadOMTable);
    document.getElementById('omStatus').addEventListener('change', loadOMTable);
    document.getElementById('cakeProdDate').value = new Date().toISOString().split('T')[0];
    loadProducts();

    // Walk-in receipt modal close on backdrop click
    const awiModal = document.getElementById('awiReceiptModal');
    if (awiModal) awiModal.addEventListener('click', e => { if (e.target === awiModal) awiCloseReceipt(); });
});

// ══════════════════════════════════════════════════════════════
//  WALK-IN POS — ADMIN
// ══════════════════════════════════════════════════════════════

/* ── Inline CSS for walk-in cards ── */
(function(){
    const s = document.createElement('style');
    s.textContent = `
    .awi-card{background:var(--surface);border:1.5px solid var(--border-soft);border-radius:var(--radius);
        overflow:hidden;cursor:pointer;transition:var(--transition);box-shadow:var(--shadow);display:flex;flex-direction:column;}
    .awi-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:var(--brand-light);}
    .awi-card.awi-out{opacity:.5;cursor:not-allowed;pointer-events:none;}
    .awi-img{width:100%;height:145px;background:linear-gradient(135deg,var(--surface2),#ede7f6);overflow:hidden;position:relative;}
    .awi-img img{width:100%;height:100%;object-fit:cover;}
    .awi-sbadge{position:absolute;top:8px;right:8px;padding:3px 9px;border-radius:12px;font-size:11px;font-weight:700;backdrop-filter:blur(4px);}
    .awi-body{padding:12px;flex:1;display:flex;flex-direction:column;}
    .awi-name{font-size:14px;font-weight:700;color:var(--brand);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .awi-price{font-size:18px;font-weight:800;color:var(--brand-light);margin-top:auto;padding-top:8px;}
    .awi-add{width:100%;margin-top:10px;padding:9px;border:none;background:var(--brand);color:#fff;
        border-radius:var(--radius-sm);font-weight:700;font-size:13px;cursor:pointer;
        transition:var(--transition);font-family:'DM Sans',sans-serif;}
    .awi-add:hover{background:var(--brand-dark);transform:scale(1.02);}
    .awi-ci{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border-soft);}
    .awi-ci:last-child{border-bottom:none;}
    .awi-cn{flex:1;font-size:13px;font-weight:600;color:var(--text);}
    .awi-cp{font-size:13px;color:var(--brand-light);font-weight:700;min-width:72px;text-align:right;}
    .awi-qc{display:flex;align-items:center;gap:4px;}
    .awi-qb{width:26px;height:26px;border:1.5px solid var(--border-soft);background:var(--surface2);
        border-radius:6px;font-size:15px;font-weight:700;cursor:pointer;transition:var(--transition);
        display:flex;align-items:center;justify-content:center;color:var(--brand);padding:0;line-height:1;}
    .awi-qb:hover{background:var(--brand);color:#fff;border-color:var(--brand);}
    .awi-qn{min-width:24px;text-align:center;font-weight:700;font-size:14px;color:var(--text);}
    .awi-pay-btn{padding:10px 8px;border:1.5px solid var(--border-soft);border-radius:var(--radius-sm);
        background:var(--surface2);color:var(--text-muted);font-weight:700;font-size:14px;
        cursor:pointer;transition:var(--transition);font-family:'DM Sans',sans-serif;}
    .awi-pay-btn:hover{border-color:var(--brand-light);color:var(--brand);}
    .awi-pay-active{background:var(--brand)!important;color:#fff!important;border-color:var(--brand)!important;
        box-shadow:0 2px 10px rgba(123,94,167,.35);}
    [data-theme="dark"] .awi-card{background:#241C38;border-color:rgba(155,127,191,.15);}
    [data-theme="dark"] .awi-card:hover{border-color:rgba(155,127,191,.4);}
    [data-theme="dark"] .awi-name{color:#F0EAF8;}
    [data-theme="dark"] .awi-ci{border-bottom-color:rgba(155,127,191,.1);}
    [data-theme="dark"] .awi-cn{color:#F0EAF8;}
    [data-theme="dark"] .awi-qb{background:#2E2448;border-color:rgba(155,127,191,.2);color:#B89FD4;}
    [data-theme="dark"] .awi-pay-btn{background:#2E2448;border-color:rgba(155,127,191,.2);color:rgba(155,127,191,.7);}
    [data-theme="dark"] #awiSearch,[data-theme="dark"] #awiCustName,[data-theme="dark"] #awiGcashRef,[data-theme="dark"] #awiTendered{background:#1A1426;border-color:rgba(155,127,191,.2);color:#F0EAF8;}
    [data-theme="dark"] #awiReceiptModal .modal-box{background:#2E2448;border:1px solid rgba(155,127,191,.2);}
    @media(max-width:900px){#awiLayout{grid-template-columns:1fr!important;}#awiLayout>div:last-child{position:static!important;}}
    `;
    document.head.appendChild(s);
})();

let awiProducts   = [];
let awiCart       = [];
let awiPay        = 'cash';
let awiLastReceipt = null;  // stores data for thermal print

async function awiLoad() {
    const g = document.getElementById('awiGrid');
    g.innerHTML = '<div class="empty-state"><span class="icon">⏳</span><p>Loading menu…</p></div>';
    awiProducts = await apiAdmin('admin_walkin_get_products');
    awiRenderGrid(awiProducts);
}

function awiFilter() {
    const q = (document.getElementById('awiSearch').value || '').toLowerCase();
    awiRenderGrid(q ? awiProducts.filter(p => p.name.toLowerCase().includes(q) || (p.description||'').toLowerCase().includes(q)) : awiProducts);
}

function awiRenderGrid(list) {
    const g = document.getElementById('awiGrid');
    if (!list || !list.length) {
        g.innerHTML = '<div class="empty-state"><span class="icon">🍰</span><p>No products available.</p></div>';
        return;
    }
    g.innerHTML = list.map(p => {
        const stock = parseInt(p.stock);
        const out   = stock === 0;
        let sb = '';
        if (out)          sb='<span class="awi-sbadge" style="background:rgba(244,67,54,.85);color:#fff;">Out of Stock</span>';
        else if(stock < 5)sb=`<span class="awi-sbadge" style="background:rgba(255,152,0,.85);color:#fff;">${stock} left</span>`;
        else              sb=`<span class="awi-sbadge" style="background:rgba(76,175,80,.85);color:#fff;">${stock} in stock</span>`;

        let imgH = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:36px;">🍰</div>';
        if (p.photo) {
            let url = p.photo;
            imgH = `<img src="${escH(url)}" alt="${escH(p.name)}" style="width:100%;height:100%;object-fit:cover;"
                onerror="this.parentElement.innerHTML='<div style=\\'display:flex;align-items:center;justify-content:center;height:100%;font-size:36px;\\'>🍰</div>'">`;
        }

        let fresh = '';
        if (p.days_remaining !== null) {
            const d = parseInt(p.days_remaining);
            if (d<=0)     fresh='<div style="padding:3px 12px 0;font-size:11px;color:#e53e3e;font-weight:700;">⚠ Expired</div>';
            else if(d<=2) fresh=`<div style="padding:3px 12px 0;font-size:11px;color:#e53e3e;font-weight:700;">⚠ Expires in ${d}d</div>`;
            else if(d<=5) fresh=`<div style="padding:3px 12px 0;font-size:11px;color:#dd6b20;font-weight:600;">⏰ ${d} days left</div>`;
        }

        return `
        <div class="awi-card${out?' awi-out':''}">
            <div class="awi-img">${imgH}${sb}</div>
            <div class="awi-body">
                <div class="awi-name" title="${escH(p.name)}">${escH(p.name)}</div>
                ${fresh}
                <div class="awi-price">₱${Number(p.price).toFixed(2)}</div>
                <button class="awi-add" onclick="awiAdd(${p.id})">＋ Add to Cart</button>
            </div>
        </div>`;
    }).join('');
}

function awiAdd(pid) {
    const prod = awiProducts.find(p => p.id == pid);
    if (!prod) return;
    const ex = awiCart.find(c => c.pid == pid);
    const curQty = ex ? ex.qty : 0;
    if (curQty >= parseInt(prod.stock)) { awiToast(`Only ${prod.stock} in stock`, 'warn'); return; }
    if (ex) ex.qty++;
    else awiCart.push({ pid: prod.id, name: prod.name, price: Number(prod.price), qty: 1, stock: parseInt(prod.stock) });
    awiRenderCart();
    awiToast(`"${prod.name}" added`, 'ok');
}

function awiQty(pid, d) {
    const it = awiCart.find(c => c.pid == pid);
    if (!it) return;
    it.qty += d;
    if (it.qty <= 0)         awiCart = awiCart.filter(c => c.pid != pid);
    else if (it.qty > it.stock) { it.qty = it.stock; awiToast(`Max stock: ${it.stock}`, 'warn'); }
    awiRenderCart();
}

function awiClearCart() {
    if (!awiCart.length) return;
    if (!confirm('Clear the cart?')) return;
    awiCart = []; awiRenderCart();
}

function awiRenderCart() {
    const box = document.getElementById('awiCartItems');
    const cnt = document.getElementById('awiCount');
    const tot = document.getElementById('awiTotal');
    if (!awiCart.length) {
        box.innerHTML = '<div style="text-align:center;padding:28px 0;color:var(--text-muted);font-size:13px;">Cart is empty — add products from the menu.</div>';
        cnt.textContent = '0'; tot.textContent = '₱0.00'; awiCalcChange(); return;
    }
    let tq = 0, ta = 0;
    box.innerHTML = awiCart.map(it => {
        const line = it.price * it.qty; tq += it.qty; ta += line;
        return `<div class="awi-ci">
            <div class="awi-cn">${escH(it.name)}</div>
            <div class="awi-qc">
                <button class="awi-qb" onclick="awiQty(${it.pid},-1)">−</button>
                <span class="awi-qn">${it.qty}</span>
                <button class="awi-qb" onclick="awiQty(${it.pid},1)">+</button>
            </div>
            <div class="awi-cp">₱${line.toFixed(2)}</div>
        </div>`;
    }).join('');
    cnt.textContent = tq;
    tot.textContent = `₱${ta.toFixed(2)}`;
    awiCalcChange();
}

function awiSetPay(m) {
    awiPay = m;
    document.getElementById('awiPayCash').className  = 'awi-pay-btn' + (m==='cash'  ? ' awi-pay-active':'');
    document.getElementById('awiPayGcash').className = 'awi-pay-btn' + (m==='gcash' ? ' awi-pay-active':'');
    document.getElementById('awiGcashBox').style.display = m==='gcash' ? 'block':'none';
    document.getElementById('awiCashBox').style.display  = m==='cash'  ? 'block':'none';
    awiCalcChange();
}

function awiCalcChange() {
    if (awiPay !== 'cash') return;
    const total    = awiCart.reduce((s,c) => s + c.price*c.qty, 0);
    const tendered = parseFloat(document.getElementById('awiTendered').value) || 0;
    const row = document.getElementById('awiChangeRow');
    if (tendered > 0 && tendered >= total) {
        document.getElementById('awiChangeAmt').textContent = `₱${(tendered - total).toFixed(2)}`;
        row.style.display = 'flex';
    } else { row.style.display = 'none'; }
}

async function awiCheckout() {
    if (!awiCart.length) { awiToast('Cart is empty!', 'err'); return; }
    const gcashRef = document.getElementById('awiGcashRef').value.trim();
    const custName = document.getElementById('awiCustName').value.trim() || 'Walk-in Customer';
    if (awiPay === 'gcash' && !gcashRef) {
        awiToast('Enter GCash reference number.', 'err');
        document.getElementById('awiGcashRef').focus();
        return;
    }
    if (awiPay === 'gcash' && gcashRef.length !== 13) {
        awiToast('GCash reference number must be exactly 13 digits.', 'err');
        document.getElementById('awiGcashRef').focus();
        return;
    }
    if (awiPay === 'gcash' && !/^\d{13}$/.test(gcashRef)) {
        awiToast('GCash reference number must contain numbers only.', 'err');
        document.getElementById('awiGcashRef').focus();
        return;
    }
    const total = awiCart.reduce((s,c) => s + c.price * c.qty, 0);

    const btn = document.getElementById('awiCheckoutBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Processing…';

    try {
        const res = await apiAdmin('admin_walkin_checkout', {
            items:          awiCart.map(c => ({ product_id: c.pid, quantity: c.qty })),
            payment_method: awiPay,
            gcash_ref:      awiPay === 'gcash' ? gcashRef : '',
            customer_name:  custName,
        });

        if (res && res.success) {
            btn.disabled = false;
            btn.textContent = '✅ Complete Transaction';
            awiShowReceipt(res.order_id, custName, total);
        } else {
            const errMsg = (res && res.error) ? res.error : 'Transaction failed. Please try again.';
            btn.disabled = false;
            btn.textContent = '✅ Complete Transaction';
            awiToast('❌ ' + errMsg, 'err');
            await awiLoad();
        }
    } catch(e) {
        btn.disabled = false;
        btn.textContent = '✅ Complete Transaction';
        awiToast('❌ Network error. Check your connection and try again.', 'err');
    }
}


function awiShowReceipt(orderId, custName, total) {
    const now      = new Date().toLocaleString('en-PH', { dateStyle:'medium', timeStyle:'short' });
    const method   = awiPay === 'gcash' ? '📲 GCash' : '💵 Cash';
    const gcashRef = awiPay === 'gcash' ? document.getElementById('awiGcashRef').value.trim() : '';
    const tendered = parseFloat(document.getElementById('awiTendered').value) || 0;
    const change   = awiPay === 'cash' && tendered > total ? tendered - total : null;

    // Save for thermal printing
    awiLastReceipt = {
        orderId,
        custName,
        total,
        method: awiPay,
        gcashRef,
        tendered,
        change,
        now,
        items: awiCart.map(c => ({ name: c.name, price: c.price, qty: c.qty }))
    };

    const rows = awiCart.map(c =>
        `<div style="display:flex;justify-content:space-between;padding:5px 0;font-size:13px;border-bottom:1px dashed var(--border-soft);">
            <span>${escH(c.name)} × ${c.qty}</span><span>₱${(c.price*c.qty).toFixed(2)}</span>
         </div>`).join('');

    document.getElementById('awiReceiptBody').innerHTML = `
        <div style="text-align:center;margin-bottom:16px;">
            <div style="font-size:42px;">✅</div>
            <div style="font-size:17px;font-weight:800;color:var(--brand);">Transaction Complete!</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">Order #${orderId} · ${now}</div>
        </div>
        <div style="background:var(--surface2);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:12px;">
            <div style="font-size:11px;font-weight:700;color:var(--brand);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">Items</div>
            ${rows}
            <div style="display:flex;justify-content:space-between;padding-top:9px;font-size:15px;font-weight:800;color:var(--brand);">
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
            ${gcashRef?`<div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;"><span style="color:var(--text-muted);font-weight:600;">GCash Ref</span><span style="font-weight:700;">${escH(gcashRef)}</span></div>`:''}
            ${change!==null?`<div style="display:flex;justify-content:space-between;font-size:15px;margin-top:4px;"><span style="color:var(--text-muted);font-weight:600;">Change</span><span style="font-weight:800;color:#065F46;">₱${change.toFixed(2)}</span></div>`:''}
        </div>`;

    const modal = document.getElementById('awiReceiptModal');
    modal.style.display = 'flex';

    // Update local stock so grid refreshes immediately
    awiCart.forEach(c => {
        const p = awiProducts.find(x => x.id == c.pid);
        if (p) p.stock = Math.max(0, parseInt(p.stock) - c.qty);
    });
    awiProducts = awiProducts.filter(p => parseInt(p.stock) > 0);
}

function awiCloseReceipt() {
    document.getElementById('awiReceiptModal').style.display = 'none';
}

function awiNewTx() {
    awiCloseReceipt();
    awiCart = [];
    document.getElementById('awiCustName').value = '';
    document.getElementById('awiGcashRef').value = '';
    document.getElementById('awiTendered').value = '';
    document.getElementById('awiChangeRow').style.display = 'none';
    awiSetPay('cash');
    awiRenderCart();
    awiRenderGrid(awiProducts);
    document.getElementById('awiSearch').value = '';
}

function awiPrintReceipt() {
    if (!awiLastReceipt) { awiToast('No receipt data to print.', 'warn'); return; }
    const r = awiLastReceipt;

    const itemLines = r.items.map(i => {
        const label = i.name + ' x' + i.qty;
        const price = 'P' + (i.price * i.qty).toFixed(2);
        const pad   = Math.max(1, 24 - label.length - price.length);
        return escH(label) + '&nbsp;'.repeat(pad) + price;
    }).join('<br>');

    const methodLabel = r.method === 'gcash' ? 'GCash' : 'Cash';
    const refLine     = r.gcashRef ? '<div>Ref#: <b>' + escH(r.gcashRef) + '</b></div>' : '';
    const tendLine    = (r.method === 'cash' && r.tendered > 0) ? '<div>Tendered: P' + r.tendered.toFixed(2) + '</div>' : '';
    const changeLine  = r.change !== null ? '<div><b>Change:   P' + r.change.toFixed(2) + '</b></div>' : '';

    const receiptHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Receipt #' + r.orderId + '</title>'
        + '<style>'
        + '@page{size:48mm auto;margin:1.5mm}'
        + '*{margin:0;padding:0;box-sizing:border-box}'
        + 'body{font-family:"Courier New",Courier,monospace;font-size:7.5pt;width:45mm;color:#000;background:#fff}'
        + '.c{text-align:center}.b{font-weight:bold}'
        + '.div{border-top:1px dashed #000;margin:3px 0}'
        + '.tr{display:flex;justify-content:space-between;font-size:9pt;font-weight:bold;border-top:1px solid #000;padding-top:3px;margin-top:3px}'
        + '.status{text-align:center;background:#000;color:#fff;padding:2px 0;font-size:8pt;font-weight:bold;margin:4px 0}'
        + '.ft{text-align:center;margin-top:6px;font-size:6.5pt}'
        + '</style></head><body>'
        + '<div class="c b" style="font-size:11pt;letter-spacing:.05em">Majoy\'s</div>'
        + '<div class="c" style="font-size:7pt">Homemade Sweets</div>'
        + '<div class="div"></div>'
        + '<div class="c" style="font-size:6.5pt">Order #' + r.orderId + '</div>'
        + '<div class="c" style="font-size:6.5pt">' + escH(r.now) + '</div>'
        + '<div class="div"></div>'
        + '<div style="font-size:7pt">Customer: <b>' + escH(r.custName) + '</b></div>'
        + '<div class="div"></div>'
        + '<div style="font-size:6.5pt;font-weight:bold;margin-bottom:2px">ITEM&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;AMOUNT</div>'
        + '<div style="font-size:7pt;line-height:1.55">' + itemLines + '</div>'
        + '<div class="div"></div>'
        + '<div class="tr"><span>TOTAL</span><span>P' + r.total.toFixed(2) + '</span></div>'
        + '<div style="margin-top:4px;font-size:7pt">'
        + '<div>Payment: <b>' + methodLabel + '</b></div>'
        + refLine + tendLine + changeLine
        + '</div>'
        + '<div class="status">PAID ✓</div>'
        + '<div class="ft">Thank you for your order!<br>Visit us again soon &#127856;<br><br><br></div>'
        + '<scr' + 'ipt>window.onload=function(){window.print();setTimeout(function(){window.close();},1200);}<\/scr' + 'ipt>'
        + '</body></html>';

    const w = window.open('', '_blank', 'width=220,height=620,toolbar=0,menubar=0,scrollbars=1');
    if (!w) { awiToast('Pop-up blocked — please allow pop-ups for this page.', 'warn'); return; }
    w.document.write(receiptHtml);
    w.document.close();
}

function awiToast(msg, type) {
    const styles = {
        ok:   'background:#d1fae5;color:#065F46;border:1.5px solid #34d399;',
        warn: 'background:#fef3c7;color:#78350F;border:1.5px solid #fbbf24;',
        err:  'background:#fee2e2;color:#b91c1c;border:1.5px solid #f87171;',
    };
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;${styles[type]||styles.ok}
        padding:12px 20px;border-radius:10px;font-size:14px;font-weight:600;
        box-shadow:0 4px 20px rgba(0,0,0,.15);max-width:320px;`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2600);
}
</script>


<div id="toastContainer"></div>
<div id="confirmOverlay" class="confirm-overlay" style="display:none;">
    <div class="confirm-box">
        <h3 id="confirmTitle">Confirm</h3>
        <p id="confirmMsg"></p>
        <div class="confirm-box-btns">
            <button class="cbtn-no"  onclick="_confirmResolve(false)">Cancel</button>
            <button class="cbtn-yes" id="confirmYes" onclick="_confirmResolve(true)">Confirm</button>
        </div>
    </div>
</div>

<script>
function mjCycleTheme() {
    var themes = ['light', 'dark'];
    var icons  = { light: '☀️', dark: '🌙' }; // shows CURRENT theme's icon
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