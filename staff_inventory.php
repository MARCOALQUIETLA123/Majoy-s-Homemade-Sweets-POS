<?php
session_start();
// Only staff can access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: login.php");
    exit();
}

// ── API HANDLER ─────────────────────────────────────────────────────────────
if (isset($_GET['staff_inventory_api'])) {
    include 'db.php';
    header('Content-Type: application/json');
    $action = $_GET['staff_inventory_api'];
    $data   = read_json();

    try {
        if ($action === 'get_products') {
            $rows = products_admin($conn);
            usort($rows, fn($a, $b) => [$a['days_remaining'] ?? 999, $a['name']] <=> [$b['days_remaining'] ?? 999, $b['name']]);
            json_out($rows);
        }
        if ($action === 'get_discard_products') {
            $rows = products_in_stock($conn);
            usort($rows, fn($a, $b) => [$a['days_remaining'] ?? 9999, $a['name']] <=> [$b['days_remaining'] ?? 9999, $b['name']]);
            json_out($rows);
        }
        if ($action === 'get_stats') json_out(inventory_stats($conn));
        if ($action === 'discard_items') {
            json_out(product_discard($conn, (int)($data['product_id'] ?? 0), (int)($data['quantity'] ?? 0), (string)($data['reason'] ?? '')));
        }
        if ($action === 'get_history') json_out(discard_history($conn, isset($_GET['limit']) ? (int)$_GET['limit'] : 50));
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
<title>Staff Inventory - Majoy's Homemade Sweets</title>
<link rel="stylesheet" href="majoys.css">
<style>

/* ══════════════════════════════════════════════
   3-WAY THEME SYSTEM
   ══════════════════════════════════════════════ */

/* LIGHT (default) */
:root, [data-theme="light"] {
    --bg:          #F4F0F8;
    --surface:     #FFFFFF;
    --surface2:    #f0e8f8;
    --plum:        #1e1040;
    --plum-mid:    #2e1a5a;
    --plum-light:  #4a2878;
    --plum-pale:   #e0d0ee;
    --gold:        #6b2048;
    --gold-light:  #7d2d55;
    --accent:      #3d2268;
    --text:        #100828;
    --text-muted:  #6a4a8a;
    --border:      var(--border);
    --border-soft: #ecddf5;
    --shadow:      0 2px 12px rgba(30,16,64,0.08);
    --shadow-md:   0 4px 24px rgba(30,16,64,0.12);
    --shadow-lg:   0 8px 40px rgba(30,16,64,0.16);
    --header-bg:   #1e1040;
    --header-accent: #d4a0b8;
    --header-sub:  rgba(255,255,255,0.55);
}

/* DARK */
[data-theme="dark"] {
    --bg:          #0d0b18;
    --surface:     #161228;
    --surface2:    #1e1835;
    --plum:        #c4b5fd;
    --plum-mid:    #a78bfa;
    --plum-light:  #7c3aed;
    --plum-pale:   #2e1a5a;
    --gold:        #f9a8d4;
    --gold-light:  #f472b6;
    --accent:      #a78bfa;
    --text:        #ede9fe;
    --text-muted:  #a78bfa;
    --border:      rgba(167,139,250,0.2);
    --border-soft: rgba(255,255,255,0.06);
    --shadow:      0 2px 12px rgba(0,0,0,0.4);
    --shadow-md:   0 4px 24px rgba(0,0,0,0.5);
    --shadow-lg:   0 8px 40px rgba(0,0,0,0.6);
    --header-bg:   #0d0b18;
    --header-accent: #a78bfa;
    --header-sub:  rgba(255,255,255,0.4);
    --green:       #34d399;
    --green-bg:    rgba(52,211,153,0.12);
    --red:         #f87171;
    --red-bg:      rgba(248,113,113,0.12);
    --orange:      #fbbf24;
    --orange-bg:   rgba(251,191,36,0.12);
    --blue:        #60a5fa;
    --blue-bg:     rgba(96,165,250,0.12);
}

/* LOGO PURPLE (#9B59B6 / #A855F7 / #C084FC) */
[data-theme="logo"] {
    --bg:          #f5f0ff;
    --surface:     #ffffff;
    --surface2:    #ede5ff;
    --plum:        #6b21a8;
    --plum-mid:    #9B59B6;
    --plum-light:  #A855F7;
    --plum-pale:   #e9d5ff;
    --gold:        #C084FC;
    --gold-light:  #d8b4fe;
    --accent:      #9B59B6;
    --text:        #3b0764;
    --text-muted:  #9B59B6;
    --border:      #e9d5ff;
    --border-soft: #f3e8ff;
    --shadow:      0 2px 12px rgba(107,33,168,0.1);
    --shadow-md:   0 4px 24px rgba(107,33,168,0.14);
    --shadow-lg:   0 8px 40px rgba(107,33,168,0.18);
    --header-bg:   #6b21a8;
    --header-accent: #e9d5ff;
    --header-sub:  rgba(255,255,255,0.6);
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


:root{--bg:#080510;--bg2:#0b0716;--surface:#0f081a;--surface2:#110920;--surface3:#140a24;--glass:rgba(255,255,255,.04);--glass-border:rgba(255,255,255,.08);--plum:        #1e1040;--plum-mid:    #2e1a5a;--plum-light:  #4a2878;--gold:        #6b2048;--gold-light:  #7d2d55;--gold-pale:   #d4a0b8;--gold-dim:rgba(107,32,72,.15);--gold-glow:0 0 20px rgba(107,32,72,.3);--cream:#E8DFF0;--cream-dim:rgba(232,223,240,.6);--cream-muted:rgba(232,223,240,.35);--green:#3DB87A;--green-bg:rgba(61,184,122,.12);--red:#E05555;--red-bg:rgba(224,85,85,.12);--orange:#E08C3A;--orange-bg:rgba(224,140,58,.12);--blue:#4A90D4;--blue-bg:rgba(74,144,212,.12);--purple:#3d2268;--text:#E8DFF0;--text-muted:rgba(232,223,240,.45);--text-dim:rgba(232,223,240,.25);--border:rgba(107,32,72,.12);--border-soft:rgba(255,255,255,.06);--shadow-sm:0 2px 8px rgba(0,0,0,.35);--shadow:0 4px 20px rgba(0,0,0,.4);--shadow-md:0 8px 32px rgba(0,0,0,.45);--shadow-lg:0 16px 56px rgba(0,0,0,.55);--r:12px;--r-sm:8px;--r-lg:18px;--sidebar-w:252px;--header-h:62px;--t:all .22s cubic-bezier(.4,0,.2,1)}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%;font-family:'Plus Jakarta Sans','DM Sans',sans-serif;background:var(--bg);color:var(--text);-webkit-font-smoothing:antialiased}
::-webkit-scrollbar{width:5px;height:5px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:rgba(107,32,72,.25);border-radius:3px}::-webkit-scrollbar-thumb:hover{background:rgba(107,32,72,.45)}
.top-header{height:var(--header-h);background:rgba(13,5,32,.92);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 24px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:200;box-shadow:0 1px 0 rgba(107,32,72,.08),0 4px 24px rgba(0,0,0,.4)}
.top-header::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(107,32,72,.6) 30%,rgba(125,45,85,.9) 50%,rgba(107,32,72,.6) 70%,transparent)}
.top-header h1{font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;font-style:italic;color:var(--cream);display:flex;align-items:center;gap:10px;letter-spacing:.01em}
.top-header h1 img{height:36px;width:36px;object-fit:cover;border-radius:50%;border:1.5px solid rgba(107,32,72,.4);box-shadow:0 0 12px rgba(107,32,72,.2)}
.header-right{display:flex;align-items:center;gap:14px}
.header-right .user-chip{text-align:right;line-height:1.4}
.header-right .user-chip strong{display:block;color:var(--cream);font-size:13px;font-weight:600}
.header-right .user-chip span{font-size:11px;color:var(--gold);letter-spacing:.06em;font-weight:500;text-transform:uppercase}
.logout-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:rgba(107,32,72,.1);color:var(--gold-light);text-decoration:none;border-radius:var(--r-sm);font-weight:600;font-size:12px;border:1px solid rgba(107,32,72,.2);transition:var(--t);letter-spacing:.04em}
.logout-btn:hover{background:rgba(107,32,72,.2);border-color:rgba(107,32,72,.4);color:var(--gold-pale);box-shadow:var(--gold-glow)}
.dashboard-layout{display:grid;grid-template-columns:var(--sidebar-w) 1fr;min-height:calc(100vh - var(--header-h))}
.sidebar{background:var(--surface);border-right:1px solid var(--border-soft);padding:16px 10px 24px;overflow-y:auto;position:sticky;top:var(--header-h);height:calc(100vh - var(--header-h))}
.sidebar-section-label{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--text-dim);padding:14px 12px 5px}
.nav-item{padding:10px 13px;margin-bottom:2px;border-radius:var(--r-sm);cursor:pointer;color:var(--text-muted);font-weight:500;font-size:13.5px;display:flex;align-items:center;gap:9px;transition:var(--t);border:1px solid transparent;background:none;width:100%;text-align:left;text-decoration:none;position:relative;letter-spacing:.01em}
.nav-item:hover{background:var(--glass);color:var(--cream);border-color:var(--glass-border)}
.nav-item.active{background:linear-gradient(135deg,rgba(107,32,72,.18),rgba(107,32,72,.08));color:var(--gold-pale);border-color:rgba(107,32,72,.2);font-weight:700;box-shadow:0 2px 12px rgba(107,32,72,.12)}
.nav-item.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:2.5px;background:linear-gradient(to bottom,var(--gold-light),var(--gold));border-radius:0 2px 2px 0}
.main-content{padding:28px 32px;overflow-y:auto;background:var(--bg2)}
.section{display:none}.section.active{display:block;animation:sectionFadeIn .3s ease}
.section-title{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;font-style:italic;color:var(--cream);margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--border-soft);display:flex;align-items:center;gap:10px;letter-spacing:.01em}
.stat-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px;margin-bottom:22px}
.stat-card{background:var(--surface);border-radius:var(--r);border:1px solid var(--border-soft);padding:18px 20px;text-align:center;box-shadow:var(--shadow-sm);transition:var(--t)}
.stat-card:hover{border-color:rgba(107,32,72,.15);transform:translateY(-3px);box-shadow:var(--shadow)}
.stat-card h3{color:var(--text-dim);font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:10px}
.stat-card .value{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:700;color:var(--gold-light)}
.stat-card .value.green{color:var(--green)}.stat-card .value.orange{color:var(--orange)}.stat-card .value.red{color:var(--red)}
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px}
.product-card{background:var(--surface);border-radius:var(--r);overflow:hidden;border:1px solid var(--border-soft);transition:var(--t);box-shadow:var(--shadow-sm)}
.product-card:hover{transform:translateY(-5px);border-color:rgba(107,32,72,.2);box-shadow:var(--shadow-md),0 0 0 1px rgba(107,32,72,.1)}
.product-img{width:100%;height:175px;background:linear-gradient(135deg,var(--surface2),var(--surface3));display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:13px;overflow:hidden}
.product-img img{width:100%;height:100%;object-fit:cover}
.product-body{padding:16px}
.product-name{font-family:'Cormorant Garamond',serif;font-weight:600;font-size:17px;color:var(--cream);margin-bottom:6px}
.product-desc{color:var(--text-muted);font-size:12.5px;line-height:1.55;margin-bottom:12px}
.product-footer{display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--border-soft)}
.product-price{font-size:19px;font-weight:700;color:var(--gold-light)}
.product-stock{font-size:10.5px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:.06em}
.product-stock.low{color:var(--orange)}.product-stock.out{color:var(--red)}
.product-actions{display:flex;gap:7px;margin-top:11px}
.product-actions button{flex:1;padding:8px 7px;border:none;border-radius:var(--r-sm);font-size:12.5px;font-weight:600;cursor:pointer;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif}
.btn-edit{background:rgba(224,140,58,.15);color:var(--orange);border:1px solid rgba(224,140,58,.2)}.btn-edit:hover{background:rgba(224,140,58,.25)}
.btn-archive{background:var(--glass);color:var(--text-muted);border:1px solid var(--glass-border)}.btn-archive:hover{background:rgba(255,255,255,.08);color:var(--text)}
.btn-restore{background:var(--blue-bg);color:var(--blue);border:1px solid rgba(74,144,212,.2)}.btn-restore:hover{background:rgba(74,144,212,.2)}
.btn-delete{background:var(--red-bg);color:var(--red);border:1px solid rgba(224,85,85,.2)}.btn-delete:hover{background:rgba(224,85,85,.2)}
.shelf-badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.shelf-fresh{background:var(--green-bg);color:var(--green);border:1px solid rgba(61,184,122,.2)}.shelf-warning{background:var(--orange-bg);color:var(--orange);border:1px solid rgba(224,140,58,.2)}.shelf-critical{background:var(--red-bg);color:var(--red);border:1px solid rgba(224,85,85,.2)}.shelf-expired{background:var(--red);color:white}
.archive-badge{display:inline-flex;padding:2px 8px;background:rgba(160,122,181,.2);color:var(--purple);border-radius:8px;font-size:10px;font-weight:700;margin-left:6px;text-transform:uppercase;letter-spacing:.04em;vertical-align:middle;border:1px solid rgba(160,122,181,.25)}
.form-card{background:var(--surface);padding:26px;border-radius:var(--r);border:1px solid var(--border-soft);max-width:660px;box-shadow:var(--shadow)}
.form-group{margin-bottom:17px}
.form-group label{display:block;margin-bottom:7px;font-weight:600;color:var(--cream-dim);font-size:12.5px;letter-spacing:.03em;text-transform:uppercase}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:11px 14px;border:1px solid var(--border-soft);border-radius:var(--r-sm);font-size:14px;font-family:'Plus Jakarta Sans',sans-serif;background:var(--surface2);color:var(--text);transition:var(--t);outline:none}
.form-group input::placeholder,.form-group textarea::placeholder{color:var(--text-dim)}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:rgba(107,32,72,.4);background:var(--surface3);box-shadow:0 0 0 3px rgba(107,32,72,.08)}
.form-group select option{background:var(--surface2);color:var(--text)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.btn-primary{padding:12px 22px;background:linear-gradient(135deg,rgba(107,32,72,.25),rgba(107,32,72,.15));color:var(--gold-pale);border:1px solid rgba(107,32,72,.3);border-radius:var(--r-sm);font-size:14px;font-weight:700;cursor:pointer;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif;width:100%;letter-spacing:.04em;box-shadow:0 2px 12px rgba(107,32,72,.12)}
.btn-primary:hover{background:linear-gradient(135deg,rgba(107,32,72,.35),rgba(107,32,72,.22));border-color:rgba(107,32,72,.5);box-shadow:0 4px 20px rgba(107,32,72,.2);transform:translateY(-1px)}
.btn-success{padding:12px 22px;background:linear-gradient(135deg,rgba(61,184,122,.2),rgba(61,184,122,.12));color:var(--green);border:1px solid rgba(61,184,122,.25);border-radius:var(--r-sm);font-size:14px;font-weight:700;cursor:pointer;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif;width:100%}
.btn-success:hover{background:linear-gradient(135deg,rgba(61,184,122,.3),rgba(61,184,122,.2));transform:translateY(-1px)}
.btn-danger-solid{padding:10px 18px;background:linear-gradient(135deg,rgba(224,85,85,.2),rgba(224,85,85,.12));color:var(--red);border:1px solid rgba(224,85,85,.25);border-radius:var(--r-sm);font-size:13.5px;font-weight:700;cursor:pointer;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif}
.btn-danger-solid:hover{background:linear-gradient(135deg,rgba(224,85,85,.3),rgba(224,85,85,.2));transform:translateY(-1px)}
.btn-outline{padding:10px 18px;background:var(--glass);color:var(--text-muted);border:1px solid var(--glass-border);border-radius:var(--r-sm);font-size:13.5px;font-weight:600;cursor:pointer;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif}
.btn-outline:hover{background:rgba(255,255,255,.08);color:var(--text);border-color:rgba(255,255,255,.12)}
.image-preview{width:100%;height:155px;border:1.5px dashed rgba(107,32,72,.25);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;margin-top:10px;overflow:hidden;background:var(--surface2);color:var(--text-dim);font-size:13px;transition:var(--t)}
.image-preview:hover{border-color:rgba(107,32,72,.4)}
.image-preview img{max-width:100%;max-height:100%;object-fit:contain}
.success-msg{background:var(--green-bg);color:var(--green);padding:12px 16px;border-radius:var(--r-sm);margin-bottom:16px;border:1px solid rgba(61,184,122,.2);font-weight:600;display:none;font-size:13.5px}
.data-table{width:100%;border-collapse:separate;border-spacing:0;background:var(--surface);border-radius:var(--r);overflow:hidden;border:1px solid var(--border-soft);box-shadow:var(--shadow-sm)}
.data-table th,.data-table td{padding:12px 15px;text-align:left;border-bottom:1px solid var(--border-soft);font-size:13.5px}
.data-table th{background:var(--surface2);font-weight:700;color:var(--text-muted);font-size:10.5px;text-transform:uppercase;letter-spacing:.1em}
.data-table tr:last-child td{border-bottom:none}
.data-table tbody tr:hover td{background:var(--glass)}
.order-card{background:var(--surface);padding:20px 22px;border-radius:var(--r);margin-bottom:12px;border:1px solid var(--border-soft);transition:var(--t);box-shadow:var(--shadow-sm)}
.order-card:hover{border-color:rgba(107,32,72,.15);box-shadow:var(--shadow)}
.order-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px}
.order-number{font-family:'Cormorant Garamond',serif;font-weight:700;font-size:17px;color:var(--cream)}
.order-status{padding:4px 12px;border-radius:20px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
.status-pending{background:rgba(224,140,58,.15);color:var(--orange);border:1px solid rgba(224,140,58,.2)}
.status-approved{background:var(--blue-bg);color:var(--blue);border:1px solid rgba(74,144,212,.2)}
.status-processing{background:rgba(160,122,181,.15);color:var(--purple);border:1px solid rgba(160,122,181,.2)}
.status-out_for_delivery{background:rgba(32,178,170,.15);color:#20B2AA;border:1px solid rgba(32,178,170,.2)}
.status-completed{background:var(--green-bg);color:var(--green);border:1px solid rgba(61,184,122,.2)}
.status-cancelled,.status-rejected{background:var(--red-bg);color:var(--red);border:1px solid rgba(224,85,85,.2)}
.status-staff_approved{background:rgba(107,32,72,.12);color:var(--gold);border:1px solid rgba(107,32,72,.2)}
.order-filter-btn{padding:8px 15px;border:1px solid var(--border-soft);background:var(--glass);border-radius:20px;font-size:12.5px;font-weight:500;color:var(--text-muted);cursor:pointer;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif}
.order-filter-btn:hover{border-color:rgba(107,32,72,.25);color:var(--cream);background:rgba(107,32,72,.08)}
.order-filter-btn.active{background:linear-gradient(135deg,rgba(107,32,72,.22),rgba(107,32,72,.12));color:var(--gold-pale);border-color:rgba(107,32,72,.3);font-weight:700;box-shadow:0 2px 10px rgba(107,32,72,.15)}
.order-meta{color:var(--text-muted);font-size:12.5px;margin-bottom:10px}
.order-meta strong{color:var(--text)}
.order-item-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-soft);font-size:13.5px;color:var(--text)}
.order-item-row:last-child{border-bottom:none}
.order-footer{display:flex;justify-content:space-between;align-items:center;margin-top:13px;padding-top:13px;border-top:1px solid var(--border-soft)}
.order-total{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--gold-light)}
.order-actions{display:flex;gap:7px;flex-wrap:wrap}
.order-actions button{padding:8px 13px;border:none;border-radius:var(--r-sm);font-size:12.5px;font-weight:700;cursor:pointer;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif;letter-spacing:.02em}
.btn-process{background:var(--blue-bg);color:var(--blue);border:1px solid rgba(74,144,212,.25)}
.btn-delivery{background:rgba(32,178,170,.12);color:#20B2AA;border:1px solid rgba(32,178,170,.25)}
.btn-approve{background:var(--green-bg);color:var(--green);border:1px solid rgba(61,184,122,.25)}
.btn-reject,.btn-cancel{background:var(--red-bg);color:var(--red);border:1px solid rgba(224,85,85,.25)}
.btn-complete{background:var(--green-bg);color:var(--green);border:1px solid rgba(61,184,122,.25)}
.btn-del-order{background:var(--glass);color:var(--text-muted);border:1px solid var(--glass-border)}
.order-actions button:hover{transform:translateY(-2px);filter:brightness(1.1);box-shadow:0 3px 10px rgba(0,0,0,.3)}
.alert-box{padding:12px 16px;border-radius:var(--r-sm);margin-bottom:10px;border-left:3px solid;font-weight:600;font-size:13.5px}
.alert-danger{background:var(--red-bg);border-color:var(--red);color:#F8A0A0}
.alert-warning{background:var(--orange-bg);border-color:var(--orange);color:#F0C090}
.alert-success-box{background:var(--green-bg);border-left:3px solid var(--green);color:var(--green);padding:12px 16px;border-radius:var(--r-sm);margin-bottom:10px;font-weight:600}
.empty-state{text-align:center;padding:56px 20px;color:var(--text-dim)}
.empty-state .icon{font-size:44px;margin-bottom:12px;opacity:.5;display:block}
.empty-state p{font-size:14px;font-weight:500}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(13,5,32,.75);backdrop-filter:blur(8px);z-index:500;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal-box{background:var(--surface);border-radius:var(--r-lg);padding:30px;width:90%;max-width:540px;max-height:90vh;overflow-y:auto;border:1px solid rgba(107,32,72,.15);box-shadow:var(--shadow-lg),0 0 60px rgba(107,32,72,.06);animation:modalSlideUp .28s cubic-bezier(.34,1.56,.64,1)}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--border-soft)}
.modal-header h2{font-family:'Cormorant Garamond',serif;color:var(--cream);font-size:20px;font-weight:600;font-style:italic}
.modal-close{background:none;border:none;font-size:22px;color:var(--text-dim);cursor:pointer;padding:2px 8px;border-radius:6px;line-height:1;transition:var(--t)}
.modal-close:hover{background:var(--glass);color:var(--text)}
.confirm-overlay{position:fixed;inset:0;background:rgba(13,5,32,.8);z-index:10000;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(8px)}
.confirm-box{background:var(--surface);border-radius:var(--r);padding:28px;max-width:370px;width:100%;border:1px solid rgba(107,32,72,.15);box-shadow:var(--shadow-lg);animation:modalSlideUp .22s ease}
.confirm-box h3{font-family:'Cormorant Garamond',serif;color:var(--cream);margin-bottom:10px;font-size:19px;font-weight:600;font-style:italic}
.confirm-box p{color:var(--text-muted);font-size:13.5px;line-height:1.65;margin-bottom:22px;white-space:pre-line}
.confirm-box-btns{display:flex;gap:9px;justify-content:flex-end}
.confirm-box-btns button{padding:9px 18px;border-radius:var(--r-sm);font-weight:700;font-size:13.5px;cursor:pointer;border:none;font-family:'Plus Jakarta Sans',sans-serif;transition:var(--t)}
.cbtn-yes{background:linear-gradient(135deg,rgba(107,32,72,.3),rgba(107,32,72,.15));color:var(--gold-pale);border:1px solid rgba(107,32,72,.3)}.cbtn-yes:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(107,32,72,.2)}
.cbtn-yes.danger{background:linear-gradient(135deg,rgba(224,85,85,.3),rgba(224,85,85,.15));color:var(--red);border-color:rgba(224,85,85,.3)}
.cbtn-no{background:var(--glass);color:var(--text-muted);border:1px solid var(--glass-border)}.cbtn-no:hover{background:rgba(255,255,255,.08)}
.two-col-layout{display:grid;grid-template-columns:380px 1fr;gap:22px}
.panel-card{background:var(--surface);padding:22px;border-radius:var(--r);border:1px solid var(--border-soft);box-shadow:var(--shadow-sm)}
.staff-item{background:var(--surface2);padding:14px 16px;border-radius:var(--r-sm);border:1px solid var(--border-soft);margin-bottom:9px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;transition:var(--t)}
.staff-item:hover{border-color:rgba(107,32,72,.15);background:var(--surface3)}
.staff-name{font-weight:700;color:var(--cream);font-size:14.5px}
.staff-meta{font-size:11.5px;color:var(--text-muted);margin-top:3px}
.staff-btns{display:flex;gap:7px;flex-wrap:wrap}
.btn-sm{padding:6px 12px;border:1px solid;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif}
.btn-sm:hover{transform:translateY(-1px)}
.btn-warn{background:var(--orange-bg);color:var(--orange);border-color:rgba(224,140,58,.25)}.btn-act{background:var(--green-bg);color:var(--green);border-color:rgba(61,184,122,.25)}.btn-del{background:var(--red-bg);color:var(--red);border-color:rgba(224,85,85,.25)}
.strength-bar{height:3px;border-radius:2px;background:var(--border-soft);margin-top:6px;overflow:hidden}
.strength-fill{height:100%;transition:all .3s;border-radius:2px}
.sw{background:var(--red);width:33%}.sm{background:var(--orange);width:66%}.ss{background:var(--green);width:100%}
.discard-tabs{display:flex;gap:7px;margin-bottom:20px;flex-wrap:wrap}
.dtab{padding:8px 18px;border:1px solid var(--border-soft);border-radius:20px;font-size:13px;font-weight:500;cursor:pointer;transition:var(--t);background:var(--glass);color:var(--text-muted);font-family:'Plus Jakarta Sans',sans-serif}
.dtab.active{background:linear-gradient(135deg,rgba(107,32,72,.22),rgba(107,32,72,.1));color:var(--gold-pale);border-color:rgba(107,32,72,.3);font-weight:700;box-shadow:0 2px 10px rgba(107,32,72,.12)}
.dtab-content{display:none}.dtab-content.active{display:block;animation:sectionFadeIn .25s ease}
.product-item-d{background:var(--surface);padding:14px 16px;border:1px solid var(--border-soft);border-radius:var(--r-sm);margin-bottom:9px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;transition:var(--t)}
.product-item-d:hover{border-color:rgba(107,32,72,.15);box-shadow:var(--shadow-sm)}
.discard-form-box{background:var(--surface2);padding:20px;border-radius:var(--r-sm);border:1px solid rgba(107,32,72,.12);margin-top:16px}
.history-item-d{background:var(--surface);padding:15px;border-radius:var(--r-sm);border-left:3px solid var(--red);border-top:1px solid var(--border-soft);border-right:1px solid var(--border-soft);border-bottom:1px solid var(--border-soft);box-shadow:var(--shadow-sm);margin-bottom:9px}
.stat-grid-d{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:13px;margin-bottom:20px}
.stat-card-d{background:var(--surface);padding:18px;border-radius:var(--r-sm);border:1px solid var(--border-soft);text-align:center;box-shadow:var(--shadow-sm);transition:var(--t)}
.stat-card-d:hover{border-color:rgba(107,32,72,.15);transform:translateY(-2px)}
.stat-val{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:700;color:var(--gold-light);margin:8px 0}
.stat-lbl{font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--text-dim);letter-spacing:.1em}
.reason-tag{display:inline-block;padding:3px 11px;background:var(--red-bg);color:var(--red);border:1px solid rgba(224,85,85,.2);border-radius:8px;font-size:12px;font-weight:600;margin-top:7px}
.om-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:13px;margin-bottom:20px}
.om-stat{background:var(--surface);border:1px solid var(--border-soft);border-radius:var(--r-sm);padding:16px 18px;text-align:center;box-shadow:var(--shadow-sm);transition:var(--t)}
.om-stat:hover{border-color:rgba(107,32,72,.12);transform:translateY(-2px)}
.om-stat h4{color:var(--text-dim);font-size:11px;font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:.08em}
.om-stat-val{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--cream)}
.om-filters{display:flex;gap:10px;align-items:center;margin-bottom:16px;padding:14px 16px;background:var(--surface);border-radius:var(--r-sm);border:1px solid var(--border-soft);flex-wrap:wrap;box-shadow:var(--shadow-sm)}
.om-filters input,.om-filters select{padding:9px 13px;border:1px solid var(--border-soft);border-radius:var(--r-sm);font-size:13.5px;outline:none;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif;background:var(--surface2);color:var(--text)}
.om-filters input:focus,.om-filters select:focus{border-color:rgba(107,32,72,.35);background:var(--surface3)}
.om-filters input{flex:1;min-width:190px}
.om-filters select option{background:var(--surface2)}
.om-table-wrap{overflow-x:auto;border-radius:var(--r-sm);border:1px solid var(--border-soft);box-shadow:var(--shadow-sm)}
.om-table{width:100%;border-collapse:collapse}
.om-table thead{background:var(--surface2)}
.om-table th{padding:12px 15px;text-align:left;color:var(--text-muted);font-weight:700;font-size:10.5px;text-transform:uppercase;letter-spacing:.1em;border-bottom:1px solid var(--border-soft)}
.om-table tbody tr{border-bottom:1px solid var(--border-soft);transition:var(--t)}
.om-table tbody tr:hover{background:var(--glass)}
.om-table tbody tr:last-child{border-bottom:none}
.om-table td{padding:12px 15px;color:var(--text);font-size:13.5px}
.status-badge{display:inline-flex;padding:3px 10px;border-radius:20px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.sb-completed{background:var(--green-bg);color:var(--green);border:1px solid rgba(61,184,122,.2)}.sb-pending{background:var(--orange-bg);color:var(--orange);border:1px solid rgba(224,140,58,.2)}.sb-approved{background:var(--blue-bg);color:var(--blue);border:1px solid rgba(74,144,212,.2)}.sb-processing{background:rgba(160,122,181,.12);color:var(--purple);border:1px solid rgba(160,122,181,.2)}.sb-cancelled,.sb-rejected{background:var(--red-bg);color:var(--red);border:1px solid rgba(224,85,85,.2)}
.sale-period-btn{padding:8px 15px;border:1px solid var(--border-soft);background:var(--glass);border-radius:20px;font-size:12.5px;font-weight:500;color:var(--text-muted);cursor:pointer;transition:var(--t);font-family:'Plus Jakarta Sans',sans-serif}
.sale-period-btn:hover{border-color:rgba(61,184,122,.25);color:var(--green)}
.sale-period-btn.active{background:var(--green-bg);color:var(--green);border-color:rgba(61,184,122,.3);font-weight:700}
.welcome-banner{background:linear-gradient(135deg,var(--surface2),var(--surface3));border:1px solid var(--border);padding:22px 26px;border-radius:var(--r-lg);margin-bottom:24px;position:relative;overflow:hidden}
.welcome-banner h3{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;font-style:italic;color:var(--cream);margin-bottom:4px}
.welcome-banner p{color:var(--text-muted);font-size:13.5px}
#toastContainer{position:fixed;bottom:22px;right:22px;z-index:9999;display:flex;flex-direction:column;gap:9px;pointer-events:none}
.toast{min-width:250px;max-width:330px;padding:12px 15px;border-radius:var(--r-sm);font-size:13.5px;font-weight:600;box-shadow:var(--shadow-md);display:flex;align-items:flex-start;gap:9px;animation:toastIn .3s ease;pointer-events:all;line-height:1.45;font-family:'Plus Jakarta Sans',sans-serif;backdrop-filter:blur(12px)}
.toast.success{background:rgba(26,92,56,.92);color:white;border:1px solid rgba(61,184,122,.3)}
.toast.error{background:rgba(140,30,45,.92);color:white;border:1px solid rgba(224,85,85,.3)}
.toast.info{background:rgba(26,75,120,.92);color:white;border:1px solid rgba(74,144,212,.3)}
.toast.warning{background:rgba(139,69,19,.92);color:white;border:1px solid rgba(224,140,58,.3)}
.hamburger{display:none;flex-direction:column;justify-content:center;gap:5px;background:var(--glass);border:1px solid var(--glass-border);cursor:pointer;padding:8px 10px;border-radius:8px;transition:var(--t)}
.hamburger:hover{background:rgba(255,255,255,.08);border-color:rgba(107,32,72,.2)}
.hamburger span{display:block;width:20px;height:2px;background:var(--cream-dim);border-radius:2px;transition:all .3s}
.hamburger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.hamburger.open span:nth-child(2){opacity:0}
.hamburger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(13,5,32,.7);z-index:199;backdrop-filter:blur(3px)}
.sidebar-overlay.active{display:block}
@media(max-width:900px){.two-col-layout{grid-template-columns:1fr}.om-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.hamburger{display:flex}.user-chip span{display:none}.top-header{padding:0 14px}.top-header h1{font-size:16px}.top-header h1 img{height:28px;width:28px}.logout-btn{padding:7px 11px;font-size:12px}.dashboard-layout{grid-template-columns:1fr}.sidebar{position:fixed;top:0;left:0;width:264px;height:100vh;z-index:200;transform:translateX(-100%);transition:transform .3s ease;overflow-y:auto;padding-top:20px;box-shadow:4px 0 24px rgba(0,0,0,.5)}.sidebar.open{transform:translateX(0)}.main-content{padding:16px}.form-row{grid-template-columns:1fr}.om-stats{grid-template-columns:repeat(2,1fr)}.products-grid{grid-template-columns:repeat(auto-fill,minmax(155px,1fr))}.stat-grid-d{grid-template-columns:repeat(2,1fr)}.staff-item{flex-direction:column;align-items:flex-start}.discard-tabs{flex-wrap:wrap}}
@media(min-width:769px){.sidebar{transform:none!important;position:sticky;height:calc(100vh - var(--header-h))}}


/* ══ DARK THEME OVERRIDES ══ */
[data-theme="dark"] body { background: var(--bg); color: var(--text); }
[data-theme="dark"] .tabs-container { background: #161228; border-bottom-color: rgba(167,139,250,0.15); }
[data-theme="dark"] .tab-btn { color: rgba(167,139,250,0.6); }
[data-theme="dark"] .tab-btn:hover { color: #a78bfa; }
[data-theme="dark"] .tab-btn.active { color: #ede9fe; border-bottom-color: #a78bfa; }
[data-theme="dark"] .product-card { background: #161228; border-color: rgba(167,139,250,0.15); }
[data-theme="dark"] .product-card:hover { border-color: rgba(167,139,250,0.35); }
[data-theme="dark"] .product-name { color: #ede9fe; }
[data-theme="dark"] .product-desc { color: rgba(232,223,240,0.6); }
[data-theme="dark"] .product-body { background: #161228; }
[data-theme="dark"] .product-footer { border-top-color: rgba(167,139,250,0.12); }
[data-theme="dark"] .section-title { color: #c4b5fd; border-bottom-color: rgba(167,139,250,0.2); }
[data-theme="dark"] .cart-panel { background: #161228; border-color: rgba(167,139,250,0.15); }
[data-theme="dark"] .cart-panel h2 { color: #ede9fe; border-bottom-color: rgba(167,139,250,0.15); }
[data-theme="dark"] .cart-item { background: #1e1835; border-color: rgba(167,139,250,0.15); }
[data-theme="dark"] .cart-item-name { color: #ede9fe; }
[data-theme="dark"] .cart-total-row { border-top-color: rgba(167,139,250,0.2); }
[data-theme="dark"] .order-card { background: #161228; border-color: rgba(167,139,250,0.15); }
[data-theme="dark"] .order-header { border-bottom-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .order-number { color: #c4b5fd; }
[data-theme="dark"] .order-date { color: rgba(167,139,250,0.7); }
[data-theme="dark"] .order-item-row { border-bottom-color: rgba(167,139,250,0.1); color: rgba(232,223,240,0.7); }
[data-theme="dark"] .order-total-row { border-top-color: rgba(167,139,250,0.2); }
[data-theme="dark"] .modal-content, [data-theme="dark"] .modal-box { background: #1e1835; border: 1px solid rgba(167,139,250,0.2); }
[data-theme="dark"] .modal-header { border-bottom-color: rgba(167,139,250,0.15); }
[data-theme="dark"] .modal-title { color: #ede9fe; }
[data-theme="dark"] .modal-close { color: rgba(167,139,250,0.6); }
[data-theme="dark"] .form-group label { color: rgba(232,223,240,0.8); }
[data-theme="dark"] .form-group input,
[data-theme="dark"] .form-group select,
[data-theme="dark"] .form-group textarea { background: #0d0b18; border-color: rgba(167,139,250,0.2); color: #ede9fe; }
[data-theme="dark"] .form-group input:focus,
[data-theme="dark"] .form-group select:focus,
[data-theme="dark"] .form-group textarea:focus { border-color: #a78bfa; background: #161228; }
[data-theme="dark"] .nav-drawer { background: #161228; }
[data-theme="dark"] .nav-drawer-title { color: #ede9fe; }
[data-theme="dark"] .nav-drawer-btn { color: rgba(167,139,250,0.7); }
[data-theme="dark"] .nav-drawer-btn:hover { background: #1e1835; color: #ede9fe; }
[data-theme="dark"] .nav-drawer-btn.active { background: rgba(167,139,250,0.15); color: #c4b5fd; }
[data-theme="dark"] .nav-drawer-divider { background: rgba(167,139,250,0.1); }
[data-theme="dark"] .empty-state { color: rgba(167,139,250,0.5); }
[data-theme="dark"] .profile-card { background: #161228; border-color: rgba(167,139,250,0.15); }
[data-theme="dark"] .profile-form-group label { color: rgba(232,223,240,0.8); }
[data-theme="dark"] .profile-form-group input { background: #0d0b18; border-color: rgba(167,139,250,0.2); color: #ede9fe; }
[data-theme="dark"] .confirm-modal, [data-theme="dark"] .confirm-box { background: #1e1835; border: 1px solid rgba(167,139,250,0.2); }
[data-theme="dark"] .confirm-modal h3, [data-theme="dark"] .confirm-box h3 { color: #ede9fe; }
[data-theme="dark"] .confirm-modal p, [data-theme="dark"] .confirm-box p { color: rgba(232,223,240,0.65); }
[data-theme="dark"] .confirm-no, [data-theme="dark"] .cbtn-no { background: #161228; color: #a78bfa; border-color: rgba(167,139,250,0.2); }
[data-theme="dark"] .menu-search { background-color: #161228; border-color: rgba(167,139,250,0.2); color: #ede9fe; }
[data-theme="dark"] .sidebar { background: #161228; border-right-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .nav-item { color: rgba(167,139,250,0.65); }
[data-theme="dark"] .nav-item:hover { background: rgba(167,139,250,0.08); color: #ede9fe; }
[data-theme="dark"] .nav-item.active { background: linear-gradient(135deg,rgba(167,139,250,0.2),rgba(167,139,250,0.1)); color: #c4b5fd; }
[data-theme="dark"] .data-table { background: #161228; border-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .data-table th { background: #1e1835; color: rgba(167,139,250,0.7); border-bottom-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .data-table td { border-bottom-color: rgba(167,139,250,0.08); color: #ede9fe; }
[data-theme="dark"] .data-table tbody tr:hover td { background: rgba(167,139,250,0.05); }
[data-theme="dark"] .panel-card { background: #161228; border-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .staff-item { background: #1e1835; border-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .staff-name { color: #ede9fe; }
[data-theme="dark"] .customer-info { background: #1e1835; border-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .stock-check-section { background: #1e1835; border-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .stock-item { background: #161228; }
[data-theme="dark"] .om-filters { background: #161228; border-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .om-filters input, [data-theme="dark"] .om-filters select { background: #0d0b18; border-color: rgba(167,139,250,0.2); color: #ede9fe; }
[data-theme="dark"] .om-table thead { background: #1e1835; }
[data-theme="dark"] .om-table th { color: rgba(167,139,250,0.7); border-bottom-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .om-table td { color: #ede9fe; border-bottom-color: rgba(167,139,250,0.08); }
[data-theme="dark"] .om-table tbody tr:hover { background: rgba(167,139,250,0.05); }
[data-theme="dark"] .welcome-banner { background: linear-gradient(135deg, #1e1835, #2e1a5a); border-color: rgba(167,139,250,0.15); }
[data-theme="dark"] .welcome-banner h3 { color: #ede9fe; }
[data-theme="dark"] .welcome-banner p { color: rgba(167,139,250,0.7); }
[data-theme="dark"] .form-card { background: #161228; border-color: rgba(167,139,250,0.1); }
[data-theme="dark"] .alert, [data-theme="dark"] .alert-box { background: #1e1835; }

/* ══ LOGO THEME OVERRIDES ══ */
[data-theme="logo"] body { background: var(--bg); color: var(--text); }
[data-theme="logo"] .tabs-container { background: white; border-bottom-color: #e9d5ff; }
[data-theme="logo"] .tab-btn { color: #9B59B6; }
[data-theme="logo"] .tab-btn.active { color: #6b21a8; border-bottom-color: #A855F7; }
[data-theme="logo"] .product-card { background: white; border-color: #e9d5ff; }
[data-theme="logo"] .product-name { color: #3b0764; }
[data-theme="logo"] .section-title { color: #6b21a8; border-bottom-color: #A855F7; }
[data-theme="logo"] .cart-panel { background: white; border-color: #e9d5ff; }
[data-theme="logo"] .cart-panel h2 { color: #3b0764; }
[data-theme="logo"] .cart-item { background: #f5f0ff; border-color: #e9d5ff; }
[data-theme="logo"] .order-card { background: white; border-color: #e9d5ff; }
[data-theme="logo"] .order-number { color: #6b21a8; }
[data-theme="logo"] .modal-content, [data-theme="logo"] .modal-box { background: white; }
[data-theme="logo"] .nav-drawer { background: white; }
[data-theme="logo"] .nav-drawer-btn.active { background: linear-gradient(135deg,#f5f0ff,#e9d5ff); color: #6b21a8; }
[data-theme="logo"] .sidebar { background: white; }
[data-theme="logo"] .nav-item.active { background: linear-gradient(135deg,#6b21a8,#9B59B6); color: white; }
[data-theme="logo"] .form-card { background: white; }
[data-theme="logo"] .panel-card { background: white; }

</style>
</head>
<body>
<!-- MAJOY LOADER -->
<div id="mjLoader" style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;overflow:hidden;transition:opacity .6s,visibility .6s;">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 20% 10%,rgba(107,32,72,.22) 0%,transparent 60%),radial-gradient(ellipse 60% 80% at 80% 90%,rgba(107,32,72,.15) 0%,transparent 60%),linear-gradient(160deg,#100828 0%,#1e1040 50%,#18102c 100%);animation:mjLbg 4s ease-in-out infinite alternate;"></div>
  <div style="position:absolute;inset:0;pointer-events:none;">
    <span style="position:absolute;left:12%;top:20%;font-size:26px;opacity:0;animation:mjP 5s ease-in-out infinite 0s">🍰</span>
    <span style="position:absolute;left:82%;top:15%;font-size:18px;opacity:0;animation:mjP 5s ease-in-out infinite .4s">🧁</span>
    <span style="position:absolute;left:72%;top:70%;font-size:24px;opacity:0;animation:mjP 5s ease-in-out infinite .8s">🍓</span>
    <span style="position:absolute;left:18%;top:75%;font-size:20px;opacity:0;animation:mjP 5s ease-in-out infinite 1.2s">🍮</span>
    <span style="position:absolute;left:50%;top:10%;font-size:16px;opacity:0;animation:mjP 5s ease-in-out infinite .2s">✨</span>
    <span style="position:absolute;left:90%;top:50%;font-size:22px;opacity:0;animation:mjP 5s ease-in-out infinite .6s">🌸</span>
    <span style="position:absolute;left:5%;top:50%;font-size:19px;opacity:0;animation:mjP 5s ease-in-out infinite 1s">🍬</span>
    <span style="position:absolute;left:40%;top:85%;font-size:28px;opacity:0;animation:mjP 5s ease-in-out infinite 1.4s">🎂</span>
    <span style="position:absolute;left:65%;top:88%;font-size:17px;opacity:0;animation:mjP 5s ease-in-out infinite .3s">⭐</span>
    <span style="position:absolute;left:28%;top:8%;font-size:21px;opacity:0;animation:mjP 5s ease-in-out infinite .9s">🌷</span>
  </div>
  <div id="mjCard" style="position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;text-align:center;padding:0 24px;animation:mjCardIn .8s cubic-bezier(.34,1.56,.64,1) forwards;opacity:0;">
    <div style="width:130px;height:130px;position:relative;margin-bottom:-96px;animation:mjRing 2.8s linear infinite;">
      <svg viewBox="0 0 120 120" fill="none" style="width:100%;height:100%;">
        <circle cx="60" cy="60" r="54" stroke="url(#mjGrad)" stroke-width="2.5" stroke-dasharray="340" stroke-dashoffset="340" class="mjCirc" stroke-linecap="round"/>
        <defs><linearGradient id="mjGrad" x1="0" y1="0" x2="120" y2="120"><stop offset="0%" stop-color="#d4a0b8"/><stop offset="50%" stop-color="#6b2048"/><stop offset="100%" stop-color="#4a1030"/></linearGradient></defs>
      </svg>
    </div>
    <div style="width:84px;height:84px;border-radius:50%;overflow:hidden;border:2px solid rgba(107,32,72,.35);box-shadow:0 0 0 6px rgba(107,32,72,.08),0 8px 32px rgba(0,0,0,.4);animation:mjLogoPulse 2.5s ease-in-out infinite;">
      <img src="logo.png" alt="Majoy's" style="width:100%;height:100%;object-fit:cover;">
    </div>
    <h1 style="margin-top:18px;font-family:'Cormorant Garamond',Georgia,serif;font-size:38px;font-weight:700;font-style:italic;background:linear-gradient(135deg,#d4a0b8,#6b2048,#7d2d55);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;background-size:200% auto;animation:mjShimmer 2s linear infinite;">Majoy's</h1>
    <p style="margin-top:3px;font-size:11px;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.4);">Homemade Sweets</p>
    <div style="display:flex;gap:7px;margin-top:26px;">
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
  function hide(){
    var l=document.getElementById("mjLoader");
    if(!l)return;
    var waited=Date.now()-window._mjStart;
    setTimeout(function(){l.style.opacity="0";l.style.visibility="hidden";setTimeout(function(){if(l.parentNode)l.parentNode.removeChild(l);},650);},Math.max(0,1600-waited));
  }
  window._mjStart=Date.now();
  if(document.readyState==="complete")hide();
  else{window.addEventListener("load",hide);setTimeout(hide,4000);}
})();
</script>

<!-- ── HEADER ──────────────────────────────────────────────────────────── -->
<header class="top-header">
    <h1>📦 Staff Inventory</h1>
    <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
        <strong><?= htmlspecialchars($_SESSION['fullname']) ?></strong>
        <a href="user.php" class="back-btn">← Back to Dashboard</a>
    </div>
</header>

<!-- ── TABS ────────────────────────────────────────────────────────────── -->
<nav class="tabs-bar">
    <button class="tab-btn active" onclick="switchTab('inventory', this)">📋 View Inventory</button>
    <button class="tab-btn" onclick="switchTab('discard', this)">
        🗑️ Discard Items
        <span class="tab-badge" id="expiryBadge" style="display:none;">!</span>
    </button>
    <button class="tab-btn" onclick="switchTab('history', this)">📜 Discard History</button>
</nav>

<!-- ── CONTENT ──────────────────────────────────────────────────────────── -->
<div class="container">

    <!-- ══ TAB 1: INVENTORY VIEW (READ-ONLY) ════════════════════════════ -->
    <div id="pane-inventory" class="tab-pane active">

        <h2 class="section-title">📊 Inventory Overview</h2>

        <!-- Stats -->
        <div class="stat-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-label">Total Products</div>
                <div class="stat-value" id="statTotal">—</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Stock</div>
                <div class="stat-value" id="statStock">—</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Fresh Items</div>
                <div class="stat-value" id="statFresh" style="color:#38a169;">—</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Expiring Soon</div>
                <div class="stat-value" id="statExpiring" style="color:#dd6b20;">—</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Low Stock</div>
                <div class="stat-value" id="statLow" style="color:#e53e3e;">—</div>
            </div>
        </div>

        <!-- Search & Filters -->
        <input type="text" id="searchInput" class="search-box"
               placeholder="🔍 Search products by name or description...">

        <div class="filter-row">
            <button class="filter-btn active" onclick="setFilter('all', this)">All</button>
            <button class="filter-btn" onclick="setFilter('expiring', this)">⚠ Near Expiry</button>
            <button class="filter-btn" onclick="setFilter('expired', this)">❌ Expired</button>
            <button class="filter-btn" onclick="setFilter('low', this)">📉 Low Stock</button>
            <button class="filter-btn" onclick="setFilter('out', this)">🚫 Out of Stock</button>
        </div>

        <!-- Read-only notice -->
        <div style="background:#e9f7ef; border-left:4px solid #38a169; padding:12px 16px;
                    border-radius:8px; font-size:13px; color:#22543d; margin-bottom:20px;">
            ℹ️ <strong>View Only</strong> — You can view stock and expiry info here.
            To add, edit, or archive products, contact the Admin/Owner.
            To discard near-expiry items, use the <strong>Discard Items</strong> tab.
        </div>

        <div class="product-grid" id="productGrid">
            <div class="empty-state" style="grid-column:1/-1;">
                <div class="empty-icon">⏳</div><p>Loading inventory...</p>
            </div>
        </div>

    </div>

    <!-- ══ TAB 2: DISCARD ITEMS ══════════════════════════════════════════ -->
    <div id="pane-discard" class="tab-pane">

        <h2 class="section-title">🗑️ Discard Near-Expiry / Expired Items</h2>

        <div class="info-banner">
            📌 <strong>Staff Discard Process:</strong> Identify near-expiry or expired products below,
            select the item, enter quantity and reason, then submit. Stock will be deducted
            and the discard will be logged automatically.
        </div>

        <!-- Discard Form (hidden until product selected) -->
        <div id="discardFormCard" class="discard-form-card" style="display:none;">
            <h3 style="margin-bottom:16px; color:var(--text);">📋 Discard Details</h3>
            <div id="selectedProductInfo" class="selected-product-info"></div>

            <form id="discardForm" onsubmit="submitDiscard(event)">
                <div class="form-group">
                    <label class="form-label">Quantity to Discard *</label>
                    <input type="number" class="form-control" id="discardQty" min="1" required>
                    <small style="color:var(--plum-mid); font-size:12px; margin-top:4px; display:block;">
                        Available stock: <strong id="availableStock">0</strong>
                    </small>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for Discarding *</label>
                    <select class="form-select" id="reasonSelect" onchange="onReasonChange()">
                        <option value="">-- Select Reason --</option>
                        <option value="Expired">Expired — Past shelf life</option>
                        <option value="Damaged">Damaged — Physical damage</option>
                        <option value="Quality Issue">Quality Issue — Does not meet standards</option>
                        <option value="Contaminated">Contaminated — Food safety concern</option>
                        <option value="Overproduction">Overproduction — Excess inventory</option>
                        <option value="Customer Return">Customer Return</option>
                        <option value="Other">Other (specify below)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <textarea class="form-control" id="reasonNotes" rows="3"
                              placeholder="Add any extra details..."></textarea>
                </div>
                <div style="display:flex; gap:12px;">
                    <button type="submit" class="btn btn-danger" id="submitBtn" style="flex:1;">
                        🗑️ Confirm Discard
                    </button>
                    <button type="button" class="btn btn-outline" onclick="cancelDiscard()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Product list for discard -->
        <div class="product-list-card">
            <div class="product-list-header">Select a Product to Discard</div>
            <div id="discardProductList">
                <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading products...</p></div>
            </div>
        </div>

    </div>

    <!-- ══ TAB 3: DISCARD HISTORY ════════════════════════════════════════ -->
    <div id="pane-history" class="tab-pane">
        <h2 class="section-title">📜 Discard History</h2>
        <div id="historyList">
            <div class="empty-state"><div class="empty-icon">⏳</div><p>Loading history...</p></div>
        </div>
    </div>

</div><!-- /container -->

<script>
// ── STATE ───────────────────────────────────────────────────────────────────
let inventoryProducts = [];
let discardProducts   = [];
let selectedProduct   = null;
let currentFilter     = 'all';

// ── API ─────────────────────────────────────────────────────────────────────
async function api(action, data = null) {
    const url  = `?staff_inventory_api=${action}`;
    const opts = { method: data ? 'POST' : 'GET' };
    if (data) {
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body    = JSON.stringify(data);
    }
    const res = await fetch(url, opts);
    return res.json();
}

// ── TABS ─────────────────────────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('pane-' + name).classList.add('active');
    btn.classList.add('active');

    if (name === 'inventory') { loadInventory(); loadStats(); }
    if (name === 'discard')   { loadDiscardProducts(); }
    if (name === 'history')   { loadHistory(); }
}

// ── FILTER ───────────────────────────────────────────────────────────────────
function setFilter(f, btn) {
    currentFilter = f;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderInventory();
}

// ── INVENTORY TAB ─────────────────────────────────────────────────────────────
async function loadStats() {
    const s = await api('get_stats');
    document.getElementById('statTotal').textContent    = s.total_products;
    document.getElementById('statStock').textContent    = s.total_stock;
    document.getElementById('statFresh').textContent    = s.fresh_items;
    document.getElementById('statExpiring').textContent = s.expiring_items;
    document.getElementById('statLow').textContent      = s.low_stock;

    // Show expiry alert badge on discard tab
    const alertCount = (Number(s.expiring_items) || 0);
    const badge = document.getElementById('expiryBadge');
    badge.style.display = alertCount > 0 ? 'inline-block' : 'none';
    badge.textContent   = alertCount;
}

async function loadInventory() {
    inventoryProducts = await api('get_products');
    renderInventory();
}

function renderInventory() {
    const grid   = document.getElementById('productGrid');
    const search = document.getElementById('searchInput').value.toLowerCase();

    let list = inventoryProducts.filter(p => {
        const matchSearch = !search ||
            p.name.toLowerCase().includes(search) ||
            (p.description && p.description.toLowerCase().includes(search));

        const days  = p.days_remaining !== null ? parseInt(p.days_remaining) : null;
        let matchFilter = true;
        if (currentFilter === 'expiring') matchFilter = days !== null && days >= 0 && days <= 5;
        if (currentFilter === 'expired')  matchFilter = days !== null && days < 0;
        if (currentFilter === 'low')      matchFilter = parseInt(p.stock) > 0 && parseInt(p.stock) < 5;
        if (currentFilter === 'out')      matchFilter = parseInt(p.stock) === 0;

        return matchSearch && matchFilter;
    });

    if (!list.length) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;">
            <div class="empty-icon">🍰</div><p>No products match this filter.</p></div>`;
        return;
    }

    grid.innerHTML = list.map(p => {
        const stock = parseInt(p.stock);
        const days  = p.days_remaining !== null ? parseInt(p.days_remaining) : null;

        // Stock badge
        let sClass = 'stock-good', sText = `✓ ${stock} in stock`;
        if (stock === 0)      { sClass = 'stock-out';  sText = '✗ Out of stock'; }
        else if (stock < 5)   { sClass = 'stock-low';  sText = `⚠ ${stock} left`; }

        // Shelf life info
        let shelfHtml = '';
        if (days !== null) {
            let tClass = 'shelf-fresh', tText = '';
            if (days < 0)      { tClass = 'shelf-critical'; tText = '⚠ EXPIRED'; }
            else if (days === 0){ tClass = 'shelf-critical'; tText = '⚠ Expires TODAY'; }
            else if (days <= 2) { tClass = 'shelf-critical'; tText = `⚠ ${days}d left`; }
            else if (days <= 5) { tClass = 'shelf-warning';  tText = `⏰ ${days} days left`; }
            else               { tClass = 'shelf-fresh';    tText = `✓ Fresh (${days}d)`; }

            const expDate = new Date(p.expiry_date).toLocaleDateString();
            shelfHtml = `<div class="shelf-bar">
                <div style="color:var(--plum-mid); font-weight:600; margin-bottom:4px;">Shelf Life</div>
                <div style="color:var(--plum-mid); font-size:11px;">Expires: ${expDate}</div>
                <span class="shelf-tag ${tClass}">${tText}</span>
            </div>`;
        }

        // Image
        let imgHtml = '<span>No Image</span>';
        if (p.photo) {
            let url = p.photo;
            if (url.startsWith('0x')) url = hexToStr(url.slice(2));
            imgHtml = `<img src="${escH(url)}" alt="${escH(p.name)}">`;
        }

        const nearExpiry = days !== null && days <= 5;

        return `
        <div class="product-card ${nearExpiry ? 'near-expiry' : ''}">
            <div class="product-img">${imgHtml}</div>
            <div class="product-body">
                <div class="product-name">${escH(p.name)}</div>
                <div class="product-desc">${escH(p.description || 'No description')}</div>
                <div class="product-meta">
                    <span class="product-price">₱${Number(p.price).toFixed(2)}</span>
                    <span class="stock-badge ${sClass}">${sText}</span>
                </div>
                ${shelfHtml}
                ${nearExpiry ? `<div style="margin-top:10px; font-size:12px; color:#e53e3e; font-weight:600;">
                    ⚠ Near expiry — consider discarding via the Discard tab</div>` : ''}
            </div>
        </div>`;
    }).join('');
}

// ── DISCARD TAB ───────────────────────────────────────────────────────────────
async function loadDiscardProducts() {
    discardProducts = await api('get_discard_products');
    renderDiscardList();
}

function renderDiscardList() {
    const list = document.getElementById('discardProductList');

    if (!discardProducts.length) {
        list.innerHTML = `<div class="empty-state"><div class="empty-icon">📦</div>
            <p>No products available for discarding.</p></div>`;
        return;
    }

    list.innerHTML = discardProducts.map(p => {
        const days = p.days_remaining !== null ? parseInt(p.days_remaining) : null;
        let badge = '';
        let expText = '';

        if (days !== null) {
            if (days < 0)      { badge = `<span class="badge badge-danger">⚠ EXPIRED</span>`;
                                 expText = `Expired ${Math.abs(days)}d ago`; }
            else if (days <= 2){ badge = `<span class="badge badge-danger">⚠ CRITICAL</span>`;
                                 expText = `Expires in ${days}d`; }
            else if (days <= 5){ badge = `<span class="badge badge-warning">⏰ NEAR EXPIRY</span>`;
                                 expText = `${days} days left`; }
            else               { badge = `<span class="badge badge-success">✓ FRESH</span>`;
                                 expText = `${days} days remaining`; }
        }

        const isSelected = selectedProduct && selectedProduct.id === p.id;

        return `
        <div class="product-row ${isSelected ? 'selected' : ''}" id="prow-${p.id}">
            <div style="flex:1; min-width:160px;">
                <div class="prow-name">${escH(p.name)}</div>
                <div class="prow-meta">
                    Stock: ${p.stock} | ₱${Number(p.price).toFixed(2)}
                    ${expText ? ' | ' + expText : ''}
                </div>
            </div>
            <div class="prow-right">
                ${badge}
                <button class="btn btn-danger" onclick="selectForDiscard(${p.id})">
                    🗑️ Select
                </button>
            </div>
        </div>`;
    }).join('');
}

function selectForDiscard(productId) {
    selectedProduct = discardProducts.find(p => p.id == productId);
    if (!selectedProduct) return;

    // Highlight row
    document.querySelectorAll('.product-row').forEach(r => r.classList.remove('selected'));
    const row = document.getElementById('prow-' + productId);
    if (row) row.classList.add('selected');

    // Populate form
    const days = selectedProduct.days_remaining !== null ? parseInt(selectedProduct.days_remaining) : null;
    let expiryNote = '';
    if (days !== null) {
        if (days < 0)       expiryNote = `<span style="color:#e53e3e; font-weight:700;">⚠ EXPIRED ${Math.abs(days)} day${Math.abs(days)!==1?'s':''} ago</span>`;
        else if (days <= 2) expiryNote = `<span style="color:#e53e3e; font-weight:700;">⚠ Expires in ${days} day${days!==1?'s':''}</span>`;
        else                expiryNote = `<span style="color:#dd6b20;">⏰ ${days} days until expiry</span>`;
    }

    document.getElementById('selectedProductInfo').innerHTML = `
        <strong style="font-size:16px; color:var(--text);">${escH(selectedProduct.name)}</strong><br>
        <span style="font-size:13px; color:var(--plum-mid);">
            Stock available: <strong>${selectedProduct.stock}</strong> |
            Price: ₱${Number(selectedProduct.price).toFixed(2)}
        </span><br>
        ${expiryNote}
    `;

    document.getElementById('availableStock').textContent = selectedProduct.stock;
    document.getElementById('discardQty').max   = selectedProduct.stock;
    document.getElementById('discardQty').value = 1;
    document.getElementById('reasonSelect').value = days !== null && days <= 0 ? 'Expired' : '';
    document.getElementById('reasonNotes').value  = '';

    const formCard = document.getElementById('discardFormCard');
    formCard.style.display = 'block';
    formCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function cancelDiscard() {
    selectedProduct = null;
    document.getElementById('discardFormCard').style.display = 'none';
    document.getElementById('discardForm').reset();
    document.querySelectorAll('.product-row').forEach(r => r.classList.remove('selected'));
}

function onReasonChange() {
    const sel   = document.getElementById('reasonSelect');
    const notes = document.getElementById('reasonNotes');
    if (sel.value === 'Other') {
        notes.required    = true;
        notes.placeholder = 'Please specify the reason...';
    } else {
        notes.required    = false;
        notes.placeholder = 'Add any extra details...';
    }
}

async function submitDiscard(e) {
    e.preventDefault();
    if (!selectedProduct) { alert('Please select a product first.'); return; }

    const qty    = parseInt(document.getElementById('discardQty').value);
    const reason = document.getElementById('reasonSelect').value;
    const notes  = document.getElementById('reasonNotes').value.trim();

    if (!reason) { alert('Please select a discard reason.'); return; }
    if (reason === 'Other' && !notes) { alert('Please specify the reason in the notes field.'); return; }
    if (qty > selectedProduct.stock) {
        alert(`Cannot discard more than available stock (${selectedProduct.stock}).`); return;
    }

    const fullReason = notes ? `${reason}: ${notes}` : reason;

    if (!confirm(`Discard ${qty} unit(s) of "${selectedProduct.name}"?\n\nReason: ${fullReason}\n\nThis action cannot be undone!`)) return;

    const btn = document.getElementById('submitBtn');
    btn.disabled    = true;
    btn.textContent = '⏳ Processing...';

    const res = await api('discard_items', {
        product_id: selectedProduct.id,
        quantity:   qty,
        reason:     fullReason
    });

    btn.disabled    = false;
    btn.textContent = '🗑️ Confirm Discard';

    if (res.success) {
        alert(`✅ Successfully discarded ${qty} item(s) of "${selectedProduct.name}".\nRemaining stock: ${res.remaining_stock}`);
        cancelDiscard();
        loadDiscardProducts();
        loadStats();
    } else {
        alert('❌ Error: ' + (res.error || 'Failed to discard items.'));
    }
}

// ── HISTORY TAB ───────────────────────────────────────────────────────────────
async function loadHistory() {
    const history = await api('get_history');
    const list    = document.getElementById('historyList');

    if (!history.length) {
        list.innerHTML = `<div class="empty-state">
            <div class="empty-icon">📋</div><p>No discard history yet.</p></div>`;
        return;
    }

    list.innerHTML = history.map(item => {
        const date  = new Date(item.discard_date).toLocaleDateString();
        const value = (item.quantity_discarded * item.price).toFixed(2);
        return `
        <div class="history-item">
            <div class="history-header">
                <span class="history-product">${escH(item.cake_name)}</span>
                <span class="history-date">${date}</span>
            </div>
            <div class="history-detail">
                <strong>Qty:</strong> ${item.quantity_discarded} |
                <strong>Value Lost:</strong> ₱${value} |
                <strong>By:</strong> ${escH(item.discarded_by)}
            </div>
            <span class="reason-tag">${escH(item.reason)}</span>
        </div>`;
    }).join('');
}

// ── HELPERS ───────────────────────────────────────────────────────────────────
function escH(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function hexToStr(hex) {
    let s = '';
    for (let i = 0; i < hex.length; i += 2)
        s += String.fromCharCode(parseInt(hex.substr(i, 2), 16));
    return s;
}

// ── INIT ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadInventory();

    document.getElementById('searchInput').addEventListener('input', renderInventory);

    // Auto-refresh stats every 60s
    setInterval(() => { loadStats(); loadInventory(); }, 60000);
});
</script>


<script>
function mjCycleTheme() {
    var themes = ['light','dark','logo'];
    var icons  = {light:'🌙', dark:'🌸', logo:'☀️'};
    var cur    = document.documentElement.getAttribute('data-theme') || 'light';
    var next   = themes[(themes.indexOf(cur) + 1) % 3];
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('mj_theme', next);
    var btn = document.getElementById('mjThemeBtn');
    if (btn) btn.textContent = icons[next];
}
window.addEventListener('DOMContentLoaded', function(){
    var icons = {light:'🌙', dark:'🌸', logo:'☀️'};
    var cur   = localStorage.getItem('mj_theme') || 'light';
    var btn   = document.getElementById('mjThemeBtn');
    if (btn) btn.textContent = icons[cur];
});
</script>
</body>
</html>