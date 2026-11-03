<?php
session_start();

// Only customers can access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'customer') {
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard – Majoy's Homemade Sweets</title>
<link rel="stylesheet" href="majoys.css">
<style>

/* ══════════════════════════════════════════════
   2-WAY THEME SYSTEM: light · dark
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
}

/* DARK */
[data-theme="dark"] {
    --bg:          #13111A;
    --surface:     #1E1A2E;
    --surface2:    #272340;
    --surface3:    #302B4F;
    --plum:        #A889CC;
    --plum-mid:    #C4A8E0;
    --plum-light:  #9B7FBF;
    --plum-pale:   #272340;
    --gold:        #D4BCE8;
    --gold-light:  #A889CC;
    --accent:      #A889CC;
    --text:        #EDE7F6;
    --text-muted:  #9B8AB8;
    --border:      rgba(168,137,204,0.30);
    --border-soft: rgba(168,137,204,0.15);
    --shadow:      0 2px 12px rgba(0,0,0,0.50);
    --shadow-md:   0 4px 24px rgba(0,0,0,0.60);
    --shadow-lg:   0 8px 40px rgba(0,0,0,0.70);
    --header-bg:   #1E1A2E;
    --header-accent: #EDE7F6;
    --header-sub:  rgba(237,231,246,0.65);
    --green:       #4ADE80;
    --green-bg:    rgba(74,222,128,0.18);
    --red:         #F87171;
    --red-bg:      rgba(248,113,113,0.18);
    --orange:      #FBBF24;
    --orange-bg:   rgba(251,191,36,0.18);
    --blue:        #60A5FA;
    --blue-bg:     rgba(96,165,250,0.18);
}

/* No third theme — 2 themes only */

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

/* ═══════ RESET & BASE ═══════ */
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--surface2);
    min-height: 100vh;
    color: var(--text);
}

/* ═══════ TOAST NOTIFICATIONS ═══════ */
#toastContainer {
    position: fixed; bottom: 24px; right: 24px;
    z-index: 9999; display: flex; flex-direction: column; gap: 10px;
    pointer-events: none;
}
.toast {
    min-width: 280px; max-width: 360px;
    padding: 14px 18px; border-radius: 12px;
    font-size: 14px; font-weight: 600;
    box-shadow: 0 6px 24px rgba(0,0,0,0.15);
    display: flex; align-items: flex-start; gap: 10px;
    animation: toastIn .3s ease; pointer-events: all;
    line-height: 1.4;
}
.toast.success { background: #1a7a4a; color: white; }
.toast.error   { background: #c53030; color: white; }
.toast.info    { background: #2b6cb0; color: white; }
.toast.warning { background: #c05621; color: white; }
/* ═══════ CONFIRM MODAL ═══════ */
.confirm-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.5);
    z-index: 10000; display: flex; align-items: center; justify-content: center;
    padding: 20px;
}
.confirm-modal {
    background: var(--surface); border-radius: 16px; padding: 28px;
    max-width: 400px; width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    animation: toastIn .2s ease;
}
.confirm-modal h3 { color: var(--text); margin-bottom: 10px; font-size: 18px; }
.confirm-modal p  { color: var(--plum-mid); font-size: 14px; line-height: 1.6; margin-bottom: 20px; white-space: pre-line; }
.confirm-modal-btns { display: flex; gap: 12px; justify-content: flex-end; }
.confirm-modal-btns button { padding: 10px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; border: none; }
.confirm-yes { background: linear-gradient(135deg,#3d2268,#4a2878); color: white; }
.confirm-yes.danger { background: linear-gradient(135deg,#f56565,#e53e3e); }
.confirm-no  { background: var(--surface2); color: var(--text); }

/* ═══════ ORDER CONFIRMED OVERLAY ═══════ */
.order-confirmed-overlay {
    position: fixed; inset: 0; background: rgba(22,14,48,.7);
    z-index: 10000; display: flex; align-items: center; justify-content: center;
    padding: 20px;
}
.order-confirmed-card {
    background: var(--surface); border-radius: 20px; padding: 36px 32px;
    max-width: 460px; width: 100%; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    animation: toastIn .3s ease;
}
.order-confirmed-icon { font-size: 64px; margin-bottom: 12px; }
.order-confirmed-title { font-size: 26px; font-weight: 800; color: var(--text); margin-bottom: 6px; }
.order-confirmed-sub { color: var(--plum-mid); font-size: 14px; margin-bottom: 20px; }
.order-confirmed-details {
    background: var(--surface2); border-radius: 12px; padding: 16px;
    text-align: left; font-size: 13px; line-height: 1.8;
    color: var(--text); margin-bottom: 22px;
}
.order-confirmed-details strong { color: var(--accent); }
.order-confirmed-btn {
    background: linear-gradient(135deg,#3d2268,#4a2878);
    color: white; border: none; border-radius: 10px;
    padding: 14px 32px; font-size: 16px; font-weight: 700;
    cursor: pointer; width: 100%;
}

/* ═══════ SEARCH BAR ═══════ */
.menu-search {
    width: 100%; padding: 13px 18px 13px 44px;
    font-size: 15px; border: 2px solid var(--border);
    border-radius: 10px; background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%238b7ba8' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 14px center;
    color: var(--text); margin-bottom: 16px;
    transition: border-color .2s;
}
.menu-search:focus { outline: none; border-color: var(--accent); }

/* ═══════ LAST UPDATED ═══════ */
.last-updated {
    font-size: 12px; color: var(--text-muted); text-align: right;
    margin-bottom: 10px; font-style: italic;
}

/* ═══════ CANCEL BUTTON ═══════ */
/* ═══════ PROFILE TAB ═══════ */
.profile-card {
    background: var(--surface); border-radius: 16px; padding: 28px;
    max-width: 520px; margin: 0 auto;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    border: 1px solid var(--border);
}
.profile-avatar {
    width: 72px; height: 72px; background: linear-gradient(135deg,#3d2268,#4a2878);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: white; margin: 0 auto 20px;
}
.profile-form-group { margin-bottom: 16px; }
.profile-form-group label { display: block; font-weight: 600; color: var(--text); margin-bottom: 6px; font-size: 14px; }
.profile-form-group input {
    width: 100%; padding: 11px 14px; border: 2px solid var(--border);
    border-radius: 8px; font-size: 14px; color: var(--text); background: var(--surface);
    transition: border-color .2s;
}
.profile-form-group input:focus { outline: none; border-color: var(--accent); }
.profile-form-group input:disabled { background: var(--surface2); color: var(--plum-mid); cursor: not-allowed; }
.profile-save-btn {
    width: 100%; padding: 13px; background: linear-gradient(135deg,#3d2268,#4a2878);
    color: white; border: none; border-radius: 10px;
    font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 8px;
    transition: opacity .2s;
}
.profile-save-btn:hover { opacity: .9; }

/* ═══════ TOP HEADER ═══════ */
.top-header {
    background: linear-gradient(135deg, #100828 0%, #18103a 55%, #100828 100%);
    padding: 0 32px;
    height: 64px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 24px rgba(26,10,40,0.45);
    border-bottom: 2px solid rgba(107,32,72,0.3);
    position: sticky;
    top: 0;
    z-index: 100;
}
.top-header h1 {
    font-size: 20px; color: #d4a0b8; display: flex; align-items: center; gap: 10px;
    font-style: italic; letter-spacing: .02em; text-shadow: 0 2px 8px rgba(0,0,0,.3);
}
.top-header h1 img { height: 42px; object-fit: contain; border-radius: 50%; border: 2px solid rgba(107,32,72,.4); }
.header-right { display: flex; align-items: center; gap: 14px; }
.header-right .user-chip { text-align: right; line-height: 1.3; display: flex; flex-direction: column; }
.header-right .user-chip strong { color: #d4a0b8; font-size: 15px; font-weight: 700; }
.header-right .user-chip span  { color: rgba(255,255,255,.5); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; }
.logout-btn {
    display: inline-block; padding: 8px 20px;
    background: rgba(255,255,255,.1); color: rgba(255,255,255,.9);
    text-decoration: none; border-radius: 8px;
    font-weight: 600; font-size: 13px; transition: all .25s;
    border: 1px solid rgba(255,255,255,.2);
}
.logout-btn:hover { background: rgba(255,255,255,.2); transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,.25); }

/* ═══════ TABS NAVIGATION ═══════ */
.tabs-container {
    background: var(--surface);
    padding: 0 32px;
    border-bottom: 2px solid var(--border);
    display: flex;
    gap: 8px;
}
.tab-btn {
    padding: 14px 24px;
    background: none;
    border: none;
    color: var(--plum-mid);
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all .25s;
}
.tab-btn:hover { color: var(--plum-mid); }
.tab-btn.active {
    color: var(--text);
    border-bottom-color: var(--accent);
}

/* ═══════ CONTENT SECTIONS ═══════ */
.tab-content {
    display: none;
    padding: 28px;
    max-width: 1400px;
    margin: 0 auto;
}
.tab-content.active { display: block; }

/* ═══════ PAGE LAYOUT FOR MENU ═══════ */
.page-layout {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 28px;
}

/* ═══════ SECTION TITLES ═══════ */
.section-title {
    font-size: 20px; font-weight: 700; color: var(--text);
    margin-bottom: 16px; padding-bottom: 8px; border-bottom: 3px solid #3d2268;
}

/* ═══════ PRODUCTS GRID ═══════ */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 18px;
}
.product-card {
    background: var(--surface); border-radius: 14px; overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.07); border: 1px solid var(--border);
    transition: transform .25s, box-shadow .25s;
}
.product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(22,14,48,.12); }
.product-card.disabled { opacity: .55; }

.product-img {
    width: 100%; height: 170px;
    background: linear-gradient(135deg, #e0d0ee, #6a3a90);
    display: flex; align-items: center; justify-content: center;
    color: var(--plum-mid); font-size: 14px; overflow: hidden;
}
.product-img img { width:100%; height:100%; object-fit:cover; }

.product-body { padding: 16px; }
.product-name { font-weight: 700; font-size: 15px; color: var(--text); margin-bottom: 5px; }
.product-desc { color: var(--plum-mid); font-size: 13px; line-height: 1.5; margin-bottom: 8px; }
.product-footer {
    display: flex; justify-content: space-between; align-items: center;
    padding-top: 8px; border-top: 1px solid #e0d0ee;
}
.product-price { font-size: 18px; font-weight: 700; color: var(--accent); }
.product-stock { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #48bb78; }
.product-stock.low  { color: #ed8936; }
.product-stock.out  { color: #f56565; }



/* ═══════ CART PANEL ═══════ */
.cart-panel {
    background: var(--surface); border-radius: 14px; padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08); border: 1px solid var(--border);
    position: sticky; top: 150px;
    max-height: calc(100vh - 170px);
    overflow-y: auto;
}
.cart-panel h2 { font-size: 18px; color: var(--text); margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid var(--border); }

.cart-item {
    background: var(--surface2); padding: 12px 14px; border-radius: 10px;
    margin-bottom: 10px; border: 1px solid var(--border);
}
.cart-item-top { display: flex; justify-content: space-between; align-items: flex-start; }
.cart-item-name { font-weight: 700; color: var(--text); font-size: 14px; }
.cart-item-price-each { color: var(--plum-mid); font-size: 12px; }
.cart-item-bottom { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
.qty-controls { display: flex; align-items: center; gap: 10px; }
.qty-num { min-width: 22px; text-align: center; font-weight: 700; color: var(--text); }
.cart-item-subtotal { font-weight: 700; color: var(--accent); font-size: 14px; }
.cart-total-row {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 16px; padding-top: 14px; border-top: 2px solid var(--border);
}
.cart-total-label { font-weight: 700; color: var(--text); font-size: 16px; }
.cart-total-value { font-size: 22px; font-weight: 700; color: var(--accent); }

/* ═══════ CUSTOM CAKE FORM ═══════ */
.custom-cake-form {
    background: var(--surface);
    padding: 28px;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    border: 1px solid var(--border);
    max-width: 800px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    color: var(--text);
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 8px;
}
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-size: 14px;
    color: var(--text);
    transition: all .25s;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(160,122,181,.2);
}
.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.submit-custom-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 13px; background: #7B5EA7; color: white; border: none;
    border-radius: 11px; font-size: 15px; font-weight: 700; cursor: pointer;
    transition: all 0.18s; box-shadow: 0 3px 12px rgba(123,94,167,0.32);
}
.submit-custom-btn:hover { background: #5A3F84; transform: translateY(-2px); box-shadow: 0 5px 18px rgba(123,94,167,0.42); }

/* ═══════ ORDER HISTORY ═══════ */
.order-card {
    background: var(--surface); padding: 20px; border-radius: 12px;
    margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06);
    border: 1px solid var(--border);
}
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.order-number { font-weight: 700; font-size: 16px; color: var(--text); }
.order-status { padding: 5px 14px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.status-pending    { background:#feebc8; color:#744210; }
.status-approved   { background:#bee3f8; color:#2c5282; }
.status-processing { background:#c3dafe; color:#3730a3; }
.status-rejected   { background:#fed7d7; color:#742a2a; }
.status-cancelled  { background:#e2e8f0; color:#4a5568; }
.status-completed  { background:#c6f6d5; color:#22543d; }
.status-cancelled  { background:#fed7d7; color:#742a2a; }

.order-date { color: var(--plum-mid); font-size: 13px; margin-bottom: 10px; }
.order-item-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #ecddf5; font-size: 13px; color: var(--plum-mid); }
.order-item-row:last-child { border-bottom: none; }
.order-total-row { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; margin-top: 10px; border-top: 2px solid var(--border); }
.order-total-val { font-size: 18px; font-weight: 700; color: var(--accent); }

/* ═══════ EMPTY STATES ═══════ */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--plum-mid);
}
.empty-state .icon { font-size: 64px; margin-bottom: 16px; }
.empty-state p { font-size: 16px; }

/* ═══════ MODAL ═══════ */
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(22,14,48,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal.active { display: flex; }
.modal-content {
    background: var(--surface);
    padding: 32px;
    border-radius: 16px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,.2);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--border);
}
.modal-title { font-size: 20px; font-weight: 700; color: var(--text); }
.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: var(--plum-mid);
    cursor: pointer;
    line-height: 1;
}
.modal-close:hover { color: var(--plum-mid); }

.payment-method-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}
.payment-method-btn {
    padding: 16px;
    border: 2px solid var(--border);
    background: var(--surface);
    border-radius: 10px;
    cursor: pointer;
    transition: all .25s;
    text-align: center;
    font-weight: 600;
    color: var(--plum-mid);
}
.payment-method-btn:hover {
    border-color: var(--accent);
    background: var(--surface2);
}
.payment-method-btn.active {
    border-color: var(--accent);
    background: linear-gradient(135deg, #ecddf5, #e0d0ee);
    color: var(--text);
}
.payment-method-btn .icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.gcash-instructions {
    background: var(--surface2);
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 20px;
}
.gcash-instructions h4 {
    color: var(--text);
    margin-bottom: 10px;
    font-size: 15px;
}
.gcash-instructions p {
    color: var(--plum-mid);
    font-size: 13px;
    line-height: 1.6;
    margin-bottom: 8px;
}
.gcash-number {
    background: var(--surface2);
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    font-weight: 700;
    color: var(--accent);
    font-size: 18px;
    margin-top: 10px;
}

/* ═══════ HAMBURGER & NAV DRAWER ═══════ */
.hamburger {
    display: none;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
}
.hamburger span {
    display: block;
    width: 24px;
    height: 3px;
    background: var(--text);
    border-radius: 3px;
    transition: all .3s;
}
.hamburger.open span:nth-child(1) { transform: translateY(8px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; }
.hamburger.open span:nth-child(3) { transform: translateY(-8px) rotate(-45deg); }

.nav-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(22,14,48,.45);
    z-index: 298;
}
.nav-overlay.active { display: block; }

.nav-drawer {
    position: fixed;
    top: 0; left: -280px;
    width: 260px;
    height: 100vh;
    background: var(--surface);
    z-index: 299;
    padding: 24px 16px;
    box-shadow: 4px 0 20px rgba(22,14,48,.15);
    transition: left .3s ease;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.nav-drawer.open { left: 0; }

.nav-drawer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 2px solid var(--border);
}
.nav-drawer-title { font-weight: 700; color: var(--text); font-size: 16px; }
.nav-drawer-close {
    background: none; border: none;
    font-size: 22px; color: var(--plum-mid);
    cursor: pointer; padding: 2px 6px;
    border-radius: 6px;
}
.nav-drawer-close:hover { background: var(--surface2); }

.nav-drawer-btn {
    width: 100%;
    padding: 14px 16px;
    background: none;
    border: none;
    border-radius: 10px;
    text-align: left;
    font-size: 15px;
    font-weight: 600;
    color: var(--plum-mid);
    cursor: pointer;
    transition: background .2s, color .2s;
}
.nav-drawer-btn:hover { background: var(--surface2); color: var(--text); }
.nav-drawer-btn.active { background: linear-gradient(135deg, #ecddf5, #e0d0ee); color: var(--text); }

.nav-drawer-divider {
    height: 1px;
    background: var(--border);
    margin: 8px 0;
}

@media (max-width: 768px) {
    .hamburger { display: flex; }
    .tabs-container { display: none; }
}
@media (min-width: 769px) {
    .nav-drawer { display: none !important; }
    .nav-overlay { display: none !important; }
}


.float-cart-count {
    background: #f56565;
    border-radius: 50%;
    font-size: 12px;
    padding: 2px 6px;
    margin-left: 6px;
}

/* ═══════ CART OVERLAY (mobile) ═══════ */
.cart-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(22,14,48,.45);
    z-index: 299;
}
.cart-overlay.active { display: block; }
.cart-drawer {
    display: none;
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: var(--surface);
    border-radius: 20px 20px 0 0;
    padding: 20px;
    z-index: 300;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 -6px 30px rgba(22,14,48,.2);
}
.cart-drawer.active { display: block; }
.cart-drawer-handle {
    width: 40px; height: 4px;
    background: var(--border);
    border-radius: 2px;
    margin: 0 auto 16px;
}

/* ═══════ RESPONSIVE ═══════ */
@media (max-width: 900px) {
    .page-layout { grid-template-columns: 1fr; }
    .cart-panel { display: none; } /* hidden on mobile – use drawer instead */
    .form-row { grid-template-columns: 1fr; }
    .float-cart-btn { display: inline-flex; align-items: center; }
}

@media (max-width: 768px) {
    .top-header { padding: 10px 14px; }
    .top-header h1 { font-size: 15px; gap: 6px; }
    .top-header h1 img { height: 30px; }
    .user-chip span { display: none; }
    .logout-btn { padding: 7px 12px; font-size: 13px; }

    .tabs-container {
        padding: 0 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        gap: 0;
        flex-wrap: nowrap;
    }
    .tabs-container::-webkit-scrollbar { display: none; }
    .tab-btn { padding: 10px 13px; font-size: 13px; white-space: nowrap; flex-shrink: 0; }

    .tab-content { padding: 14px; }
    .section-title { font-size: 17px; }

    .products-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .product-img { height: 130px; }
    .product-body { padding: 12px; }
    .product-name { font-size: 13px; }
    .product-price { font-size: 15px; }

    .custom-cake-form { padding: 16px; }
    .form-group input, .form-group select, .form-group textarea { padding: 10px 12px; font-size: 14px; }
    .submit-custom-btn { padding: 12px; font-size: 15px; }

    .order-card { padding: 14px; }
    .order-header { flex-wrap: wrap; gap: 6px; }
    .order-status { font-size: 10px; padding: 4px 10px; }
    .order-total-val { font-size: 16px; }

    .modal-content { padding: 18px; border-radius: 16px; width: 95%; }
    .modal-title { font-size: 17px; }
    .payment-method-selector { grid-template-columns: 1fr 1fr; gap: 8px; }
    .payment-method-btn { padding: 12px 8px; font-size: 13px; }
    .payment-method-btn .icon { font-size: 24px; }
    .confirm-order-btn { padding: 13px; font-size: 15px; }
}

@media (max-width: 480px) {
    .products-grid { grid-template-columns: repeat(2, 1fr); }
    .top-header h1 span { display: none; }
}

/* ══ DARK THEME OVERRIDES ══ */
[data-theme="dark"] body { background: var(--bg); color: var(--text); }

/* ── Header & nav tabs ── */
[data-theme="dark"] .top-header { background: var(--header-bg) !important; border-bottom: 1px solid var(--border-soft); }
[data-theme="dark"] .tabs-container { background: var(--surface); border-bottom-color: var(--border-soft); }
[data-theme="dark"] .tab-btn { color: var(--text-muted); }
[data-theme="dark"] .tab-btn:hover { color: var(--plum-mid); }
[data-theme="dark"] .tab-btn.active { color: var(--text); border-bottom-color: var(--accent); }

/* ── Product cards ── */
[data-theme="dark"] .product-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .product-card:hover { border-color: var(--border); }
[data-theme="dark"] .product-name { color: var(--text); }
[data-theme="dark"] .product-desc { color: var(--text-muted); }
[data-theme="dark"] .product-body { background: var(--surface); }
[data-theme="dark"] .product-footer { border-top-color: var(--border-soft); }
[data-theme="dark"] .product-price { color: var(--accent); }
[data-theme="dark"] .section-title { color: var(--plum-mid); border-bottom-color: var(--border-soft); }

/* ── Cart panel ── */
[data-theme="dark"] .cart-panel { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .cart-panel h2 { color: var(--text); border-bottom-color: var(--border-soft); }
[data-theme="dark"] .cart-item { background: var(--surface2); border-color: var(--border-soft); }
[data-theme="dark"] .cart-item-name { color: var(--text); }
[data-theme="dark"] .cart-item-price-each { color: var(--text-muted); }
[data-theme="dark"] .qty-num { color: var(--text); }
[data-theme="dark"] .cart-item-subtotal { color: var(--accent); }
[data-theme="dark"] .cart-total-row { border-top-color: var(--border-soft); }
[data-theme="dark"] .cart-total-label { color: var(--text); }
[data-theme="dark"] .cart-total-value { color: var(--accent); }

/* ── Custom cake form card ── */
[data-theme="dark"] .custom-cake-form { background: var(--surface); border-color: var(--border-soft); }

/* ── ALL form inputs, selects, textareas ── */
[data-theme="dark"] .form-group label { color: var(--plum-mid); }
[data-theme="dark"] .form-group input,
[data-theme="dark"] .form-group select,
[data-theme="dark"] .form-group textarea {
    background: var(--surface2);
    border-color: var(--border);
    color: var(--text);
}
[data-theme="dark"] .form-group input::placeholder,
[data-theme="dark"] .form-group select::placeholder,
[data-theme="dark"] .form-group textarea::placeholder { color: var(--text-muted); }
[data-theme="dark"] .form-group input:focus,
[data-theme="dark"] .form-group select:focus,
[data-theme="dark"] .form-group textarea:focus {
    border-color: var(--accent);
    background: var(--surface3);
    box-shadow: 0 0 0 3px rgba(168,137,204,0.20);
    outline: none;
}

/* ── Profile card ── */
[data-theme="dark"] .profile-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .profile-form-group label { color: var(--plum-mid); }
[data-theme="dark"] .profile-form-group input {
    background: var(--surface2);
    border-color: var(--border);
    color: var(--text);
}
[data-theme="dark"] .profile-form-group input:focus {
    border-color: var(--accent);
    background: var(--surface3);
    outline: none;
}
[data-theme="dark"] .profile-form-group input:disabled { background: var(--surface2); color: var(--text-muted); }

/* ── Order cards ── */
[data-theme="dark"] .order-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .order-header { border-bottom-color: var(--border-soft); }
[data-theme="dark"] .order-number { color: var(--plum-mid); }
[data-theme="dark"] .order-date { color: var(--text-muted); }
[data-theme="dark"] .order-item-row { border-bottom-color: var(--border-soft); color: var(--text-muted); }
[data-theme="dark"] .order-total-row { border-top-color: var(--border-soft); }
[data-theme="dark"] .order-total-val { color: var(--accent); }

/* ── Order status badges ── */
[data-theme="dark"] .status-pending    { background: rgba(251,191,36,0.20);  color: #FCD34D; }
[data-theme="dark"] .status-approved   { background: rgba(96,165,250,0.20);  color: #93C5FD; }
[data-theme="dark"] .status-processing { background: rgba(167,139,250,0.22); color: #C4B5FD; }
[data-theme="dark"] .status-completed  { background: rgba(74,222,128,0.20);  color: #86EFAC; }
[data-theme="dark"] .status-rejected   { background: rgba(248,113,113,0.20); color: #FCA5A5; }
[data-theme="dark"] .status-cancelled  { background: var(--surface2); color: var(--text-muted); }

/* ── Modal & confirm ── */
[data-theme="dark"] .modal-content,
[data-theme="dark"] .modal-box { background: var(--surface); border: 1px solid var(--border); }
[data-theme="dark"] .modal-header { border-bottom-color: var(--border-soft); }
[data-theme="dark"] .modal-title { color: var(--text); }
[data-theme="dark"] .modal-close { color: var(--text-muted); }
[data-theme="dark"] .modal-close:hover { color: var(--plum-mid); background: var(--surface2); }
[data-theme="dark"] .confirm-modal,
[data-theme="dark"] .confirm-box { background: var(--surface); border: 1px solid var(--border); }
[data-theme="dark"] .confirm-modal h3,
[data-theme="dark"] .confirm-box h3 { color: var(--text); }
[data-theme="dark"] .confirm-modal p,
[data-theme="dark"] .confirm-box p { color: var(--text-muted); }
[data-theme="dark"] .confirm-no,
[data-theme="dark"] .cbtn-no { background: var(--surface2); color: var(--plum-mid); border-color: var(--border); }

/* ── Payment method selector ── */
[data-theme="dark"] .payment-method-selector > div { border-color: var(--border); background: var(--surface2); color: var(--text); }
[data-theme="dark"] .payment-method-selector > div.selected { border-color: var(--accent); background: var(--surface3); color: var(--text); }

/* ── Search bar ── */
[data-theme="dark"] .menu-search { background-color: var(--surface2); border-color: var(--border); color: var(--text); }
[data-theme="dark"] .menu-search:focus { border-color: var(--accent); background: var(--surface3); }

/* ── Mobile nav drawer ── */
[data-theme="dark"] .nav-drawer { background: var(--surface); }
[data-theme="dark"] .nav-drawer-title { color: var(--text); }
[data-theme="dark"] .nav-drawer-btn { color: var(--text-muted); }
[data-theme="dark"] .nav-drawer-btn:hover { background: var(--surface2); color: var(--text); }
[data-theme="dark"] .nav-drawer-btn.active { background: var(--surface2); color: var(--plum-mid); border-left: 3px solid var(--accent); }
[data-theme="dark"] .nav-drawer-divider { background: var(--border-soft); }

/* ── Empty state ── */
[data-theme="dark"] .empty-state { color: var(--text-muted); }

/* ── Misc shared components ── */
[data-theme="dark"] .form-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .panel-card { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .alert,
[data-theme="dark"] .alert-box { background: var(--surface2); }
[data-theme="dark"] .welcome-banner { background: linear-gradient(135deg, #2D1F4E, #3D2A60); }
[data-theme="dark"] .welcome-banner h3 { color: #EDE7F6; }
[data-theme="dark"] .welcome-banner p { color: rgba(237,231,246,0.70); }

/* ── Inspiration / reference image upload area ── */
[data-theme="dark"] .reference-upload-area,
[data-theme="dark"] [class*="upload"] { background: var(--surface2); border-color: var(--border); color: var(--text-muted); }

/* ── Data tables ── */
[data-theme="dark"] .data-table { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .data-table th { background: #2D1F4E; color: #EDE7F6; border-bottom-color: var(--border-soft); }
[data-theme="dark"] .data-table td { border-bottom-color: var(--border-soft); color: var(--text); }
[data-theme="dark"] .data-table tbody tr:hover td { background: var(--surface2); }
[data-theme="dark"] .staff-item { background: var(--surface2); border-color: var(--border-soft); }
[data-theme="dark"] .staff-name { color: var(--text); }
[data-theme="dark"] .om-filters { background: var(--surface); border-color: var(--border-soft); }
[data-theme="dark"] .om-filters input,
[data-theme="dark"] .om-filters select { background: var(--surface2); border-color: var(--border); color: var(--text); }
[data-theme="dark"] .om-table thead { background: #2D1F4E; }
[data-theme="dark"] .om-table th { color: #EDE7F6; border-bottom-color: var(--border-soft); }
[data-theme="dark"] .om-table td { color: var(--text); border-bottom-color: var(--border-soft); }
[data-theme="dark"] .om-table tbody tr:hover { background: var(--surface2); }

/* ── Order confirmed card ── */
[data-theme="dark"] .order-confirmed-card { background: var(--surface); border: 1px solid var(--border); color: var(--text); }
[data-theme="dark"] .order-confirmed-title { color: var(--text); }
[data-theme="dark"] .order-confirmed-sub { color: var(--text-muted); }
[data-theme="dark"] .order-confirmed-details { background: var(--surface2); color: var(--text); }

/* ── Payment method buttons ── */
[data-theme="dark"] .payment-method-btn { background: var(--surface2); border-color: var(--border); color: var(--text-muted); }
[data-theme="dark"] .payment-method-btn:hover { background: var(--surface3); border-color: var(--brand); color: var(--text); }
[data-theme="dark"] .payment-method-btn.active { background: var(--surface3); border-color: var(--brand); color: var(--text); }

/* ── GCash number display ── */
[data-theme="dark"] .gcash-number { background: var(--surface3); color: var(--brand-dark); }

/* ── Profile form inputs ── */
[data-theme="dark"] .profile-form-group input { background: var(--surface2); border-color: var(--border); color: var(--text); }

/* ── Product body ── */
[data-theme="dark"] .product-body { background: var(--surface); }

/* ── Cart item border ── */
[data-theme="dark"] .cart-item { border-color: var(--border-soft); }
[data-theme="dark"] .cart-panel h2 { border-bottom-color: var(--border-soft); }
[data-theme="dark"] .cart-total-row { border-top-color: var(--border-soft); }

/* ── Custom cake form ── */
[data-theme="dark"] .custom-cake-form { background: var(--surface); border-color: var(--border-soft); }

/* End of theme overrides */


/* ── ENHANCED SHELF BADGES ── */
.shelf-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em;
    border: 1.5px solid transparent;
}
.shelf-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.shelf-fresh    { background: #E8F5E9; color: #1B5E20; border-color: rgba(76,175,80,0.25); }
.shelf-fresh::before    { background: #4CAF50; }
.shelf-warning  { background: #FFF3E0; color: #E65100; border-color: rgba(255,152,0,0.28); }
.shelf-warning::before  { background: #FF9800; }
.shelf-critical { background: #FFEBEE; color: #B71C1C; border-color: rgba(244,67,54,0.25); }
.shelf-critical::before { background: #F44336; }
.shelf-expired  { background: #F44336; color: white; border-color: transparent; }
.shelf-expired::before  { background: rgba(255,255,255,0.75); }

/* ── ENHANCED ADD TO CART BUTTON ── */
.add-btn {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; margin-top: 12px; padding: 10px 14px;
    background: #7B5EA7; color: white; border: none; border-radius: 10px;
    font-size: 13px; font-weight: 700; cursor: pointer;
    transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
    letter-spacing: 0.03em; box-shadow: 0 2px 10px rgba(123,94,167,0.28);
}
.add-btn:hover { background: #5A3F84; transform: translateY(-2px); box-shadow: 0 4px 16px rgba(123,94,167,0.38); }
.add-btn:active { transform: scale(0.97); }
.add-btn:disabled { background: #C4A8E0; cursor: not-allowed; transform: none; box-shadow: none; opacity: 0.75; }

/* ── ENHANCED QTY BUTTONS ── */
.qty-btn {
    width: 30px; height: 30px; border: 1.5px solid rgba(123,94,167,0.25);
    background: #EDE7F6; color: #7B5EA7; border-radius: 7px; cursor: pointer;
    font-weight: 700; font-size: 16px; transition: all 0.16s; display: flex;
    align-items: center; justify-content: center;
}
.qty-btn:hover { background: #7B5EA7; color: white; border-color: transparent; box-shadow: 0 2px 8px rgba(123,94,167,0.30); transform: scale(1.08); }
.qty-btn:active { transform: scale(0.94); }

/* ── ENHANCED CHECKOUT / CONFIRM BUTTONS ── */
.checkout-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 13px 20px;
    background: #7B5EA7; color: white; border: none; border-radius: 11px;
    font-size: 15px; font-weight: 700; cursor: pointer;
    transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
    letter-spacing: 0.02em; box-shadow: 0 3px 14px rgba(123,94,167,0.32);
}
.checkout-btn:hover { background: #5A3F84; transform: translateY(-2px); box-shadow: 0 5px 18px rgba(123,94,167,0.42); }
.checkout-btn:active { transform: scale(0.97); }

.confirm-order-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 13px 20px;
    background: #4CAF50; color: white; border: none; border-radius: 11px;
    font-size: 15px; font-weight: 700; cursor: pointer;
    transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
    box-shadow: 0 3px 12px rgba(76,175,80,0.30);
}
.confirm-order-btn:hover { background: #388E3C; transform: translateY(-2px); box-shadow: 0 5px 16px rgba(76,175,80,0.40); }
.confirm-order-btn:active { transform: scale(0.97); }

/* ── ENHANCED REMOVE / CANCEL BUTTONS ── */
.remove-btn {
    background: none; border: none; color: #F44336; font-size: 12.5px;
    cursor: pointer; font-weight: 700; margin-top: 4px; padding: 2px 6px;
    border-radius: 5px; transition: all 0.15s;
}
.remove-btn:hover { background: #FFEBEE; }

.cancel-order-btn {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; padding: 10px 18px; background: transparent;
    color: #F44336; border: 1.5px solid rgba(244,67,54,0.35);
    border-radius: 10px; font-size: 14px; font-weight: 600;
    cursor: pointer; transition: all 0.18s;
}
.cancel-order-btn:hover { background: #FFEBEE; border-color: #F44336; }

/* ── ENHANCED FLOAT CART BUTTON ── */
.float-cart-btn {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: none; /* shown via JS when cart has items */
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    background: #7B5EA7;
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s;
    box-shadow: 0 4px 20px rgba(123,94,167,0.5);
    z-index: 290;
}
.float-cart-btn:hover { background: #5A3F84; transform: translateY(-3px); box-shadow: 0 8px 28px rgba(123,94,167,0.52); }
.float-cart-btn:active { transform: scale(0.97); }
/* On desktop where cart panel is visible, hide the float button always */
@media (min-width: 901px) {
    .float-cart-btn { display: none !important; }
}


/* ═══ ORDER TABS INSIDE customer.php ═══ */
.ord-tab-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:9px 18px; border-radius:22px;
    border:2px solid var(--border); background:var(--surface);
    color:var(--text-muted); font-size:13px; font-weight:600;
    cursor:pointer; transition:all .2s; font-family:inherit;
}
.ord-tab-btn:hover { border-color:var(--plum); color:var(--plum); background:var(--surface2); }
.ord-tab-btn.active { background:var(--plum); color:white; border-color:var(--plum); box-shadow:0 3px 12px rgba(123,94,167,0.30); }
.ord-tab-count {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:20px; height:20px; border-radius:10px; font-size:11px;
    font-weight:700; padding:0 5px; background:rgba(255,255,255,0.25);
}
.ord-tab-btn:not(.active) .ord-tab-count { background:rgba(123,94,167,0.12); color:var(--plum); }
.ord-tool-btn {
    display:inline-flex; align-items:center; gap:5px;
    padding:7px 13px; border-radius:18px;
    border:1.5px solid var(--border); background:var(--surface);
    color:var(--text-muted); font-size:12px; font-weight:600;
    cursor:pointer; transition:all .2s; font-family:inherit;
}
.ord-tool-btn:hover { border-color:var(--plum); color:var(--plum); }
.ord-tool-btn.has-filter { background:var(--plum); color:white; border-color:var(--plum); }
.ord-filter-chip-active {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 10px; background:rgba(123,94,167,0.12);
    border:1.5px solid var(--plum-light,#C4A8E0); color:var(--plum);
    border-radius:18px; font-size:12px; font-weight:600;
}
.ord-filter-chip-active button { background:none; border:none; cursor:pointer; color:inherit; font-size:12px; line-height:1; padding:0; }
.ord-sort-opt {
    display:flex; align-items:center; justify-content:space-between;
    padding:12px 14px; border-radius:9px; cursor:pointer;
    font-size:14px; font-weight:500; color:var(--text);
    border:1.5px solid transparent; margin-bottom:6px; transition:all .15s;
}
.ord-sort-opt:hover { background:var(--surface2); border-color:var(--border); }
.ord-sort-opt.active { background:var(--surface2); border-color:var(--plum); color:var(--plum); font-weight:700; }
.ord-fchip {
    padding:7px 14px; border-radius:18px; border:2px solid var(--border);
    background:var(--surface2); color:var(--text-muted);
    font-size:12px; font-weight:600; cursor:pointer; transition:all .15s; font-family:inherit;
}
.ord-fchip:hover { border-color:var(--plum); color:var(--plum); }
.ord-fchip.active { background:var(--plum); color:white; border-color:var(--plum); }
.ord-archived-label {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 8px; background:#F5F5F5; color:#757575;
    border-radius:8px; font-size:10px; font-weight:700; text-transform:uppercase;
}
[data-theme="dark"] .ord-archived-label { background:rgba(255,255,255,0.08); color:#9E9E9E; }
.ord-history-banner {
    padding:9px 13px; border-radius:8px; font-size:12px; font-weight:600;
    margin-bottom:10px; display:flex; align-items:center; gap:7px;
}
.ord-banner-completed { background:#E8F5E9; border-left:4px solid #4CAF50; color:#1B5E20; }
.ord-banner-cancelled { background:#F5F5F5; border-left:4px solid #9E9E9E; color:#616161; }
.ord-banner-rejected  { background:#FFEBEE; border-left:4px solid #F44336; color:#B71C1C; }
[data-theme="dark"] .ord-banner-completed { background:rgba(76,175,80,0.15); color:#A5D6A7; }
[data-theme="dark"] .ord-banner-cancelled { background:rgba(255,255,255,0.06); color:#9E9E9E; }
[data-theme="dark"] .ord-banner-rejected  { background:rgba(244,67,54,0.15); color:#EF9A9A; }
.ord-archive-btn {
    display:inline-flex; align-items:center; gap:5px;
    padding:7px 14px; border-radius:8px; font-size:12px; font-weight:700;
    cursor:pointer; font-family:inherit; transition:all .2s; border:none;
}
.ord-archive-btn.do-archive { background:#F5F5F5; color:#616161; border:1.5px solid rgba(158,158,158,0.25); }
.ord-archive-btn.do-archive:hover { background:#EEEEEE; color:#333; }
.ord-archive-btn.do-unarchive { background:rgba(123,94,167,0.10); color:var(--plum); border:1.5px solid var(--border); }
.ord-archive-btn.do-unarchive:hover { background:var(--plum); color:white; }
[data-theme="dark"] .ord-archive-btn.do-archive { background:rgba(255,255,255,0.07); color:#9E9E9E; }
@keyframes slideUpSheet { from{transform:translateY(100%)} to{transform:translateY(0)} }
.ord-see-all-btn { background:none; border:none; color:var(--plum); font-size:12px; font-weight:700; cursor:pointer; font-family:inherit; padding:5px 0 0; display:inline-flex; align-items:center; gap:3px; }
.ord-see-all-btn:hover { color:var(--plum-dark,#5A3F84); }
</style>
</head>
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
            <span>Majoy's Homemade Sweets</span>
        </h1>
    </div>
    <div class="header-right">
    <button class="theme-toggle-btn" id="mjThemeBtn" onclick="mjCycleTheme()" title="Switch theme">🌙</button>
        <div class="user-chip">
            <strong><?= htmlspecialchars($_SESSION['fullname']) ?></strong>
            <span>Customer</span>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</header>

<!-- TABS -->
<div class="tabs-container">
    <button class="tab-btn active" onclick="switchTab('menu')">🍰 Menu</button>
    <button class="tab-btn" onclick="switchTab('custom')">✨ Custom</button>
    <button class="tab-btn" onclick="switchTab('customrequests')">🎨 Requests</button>
    <button class="tab-btn" onclick="switchTab('orders')">📋 Orders</button>
    <button class="tab-btn" onclick="switchTab('profile')">👤 Profile</button>
</div>

<!-- Mobile Nav Drawer -->
<div class="nav-overlay" id="navOverlay" onclick="closeNavDrawer()"></div>
<div class="nav-drawer" id="navDrawer">
    <div class="nav-drawer-header">
        <span class="nav-drawer-title">📋 Navigation</span>
        <button class="nav-drawer-close" onclick="closeNavDrawer()">✕</button>
    </div>
    <button class="nav-drawer-btn active" id="drawerMenu" onclick="switchTab('menu'); closeNavDrawer()">🍰 Menu</button>
    <button class="nav-drawer-btn" id="drawerCustom" onclick="switchTab('custom'); closeNavDrawer()">✨ Custom Cake</button>
    <button class="nav-drawer-btn" id="drawerRequests" onclick="switchTab('customrequests'); closeNavDrawer()">🎨 My Requests</button>
    <button class="nav-drawer-btn" id="drawerOrders" onclick="switchTab('orders'); closeNavDrawer()">📋 My Orders</button>
    <button class="nav-drawer-btn" id="drawerProfile" onclick="switchTab('profile'); closeNavDrawer()">👤 Profile</button>
    <div class="nav-drawer-divider"></div>
    <a href="logout.php" style="display:block; padding:14px 16px; color:#f56565; font-weight:600; text-decoration:none; border-radius:10px; font-size:15px;">🚪 Logout</a>
</div>

<!-- Mobile cart overlay & drawer -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCartDrawer()"></div>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-drawer-handle"></div>
    <h2 style="font-size:17px;color:var(--text);margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #e0d0ee;">🛒 Your Cart</h2>
    <div id="cartItemsMobile"></div>
    <div class="cart-total-row">
        <span class="cart-total-label">Total:</span>
        <span class="cart-total-value">₱<span id="cartTotalMobile">0.00</span></span>
    </div>
    <button class="checkout-btn" onclick="closeCartDrawer(); openCheckoutModal();" style="margin-top:14px;">Proceed to Checkout</button>
</div>

<!-- Floating cart button (mobile only) -->
<button class="float-cart-btn" id="floatCartBtn" onclick="openCartDrawer()">
    🛒 Cart <span class="float-cart-count" id="floatCartCount">0</span>
</button>

<!-- MENU TAB -->
<div id="menuTab" class="tab-content active">
    <!-- Cutoff time notice -->
    <div id="cutoffNotice" style="display:none; background:#fed7d7; border-left:4px solid #f56565; border-radius:8px; padding:14px 18px; margin-bottom:18px; color:#742a2a; font-weight:600; font-size:14px;"></div>
    <div class="page-layout">
        <div>
            <h2 class="section-title">Our Products</h2>
            <input type="text" class="menu-search" id="menuSearch" placeholder="Search cakes..." oninput="filterProducts()">
            <div id="productsGrid" class="products-grid">
                <div class="empty-state"><div class="icon">⏳</div><p>Loading products...</p></div>
            </div>
        </div>
        
        <div class="cart-panel">
            <h2>🛒 Your Cart</h2>
            <div id="cartItems">
                <div class="empty-state"><div class="icon">🛒</div><p>Cart is empty</p></div>
            </div>
            <div class="cart-total-row">
                <span class="cart-total-label">Total:</span>
                <span class="cart-total-value">₱<span id="cartTotal">0.00</span></span>
            </div>
            <button class="checkout-btn" onclick="openCheckoutModal()">Proceed to Checkout</button>
        </div>
    </div>
</div>

<!-- CUSTOM CAKE TAB -->
<div id="customTab" class="tab-content">
    <h2 class="section-title">Order Custom Cake</h2>

    <!-- INSPIRATION GALLERY -->
    <div id="inspirationSection" style="margin-bottom:28px;">
        <h3 style="font-size:16px;font-weight:700;color:var(--text);margin-bottom:4px;">🎂 Custom Cake Inspirations</h3>
        <p style="font-size:13px;color:var(--plum-mid);margin-bottom:14px;">Browse our cake designs for inspiration before ordering.</p>
        <div id="inspirationGallery" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;"></div>
    </div>

    <form class="custom-cake-form" onsubmit="submitCustomCake(event)">

        <!-- CAKE SHAPE -->
        <div class="form-group">
            <label>Cake Shape *</label>
            <div style="display:flex;gap:12px;margin-top:6px;">
                <button type="button" id="shapeRound" onclick="selectCakeShape('round')"
                    style="flex:1;padding:14px 10px;border:2px solid var(--plum);background:var(--plum);color:white;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer;transition:all .2s;display:flex;flex-direction:column;align-items:center;gap:6px;">
                    <span style="font-size:26px;">⭕</span>
                    <span>Round</span>
                </button>
                <button type="button" id="shapeSquare" onclick="selectCakeShape('square')"
                    style="flex:1;padding:14px 10px;border:2px solid var(--border);background:var(--surface2);color:var(--text);border-radius:12px;font-weight:600;font-size:14px;cursor:pointer;transition:all .2s;display:flex;flex-direction:column;align-items:center;gap:6px;">
                    <span style="font-size:26px;">⬜</span>
                    <span>Square</span>
                </button>
            </div>
            <input type="hidden" id="cakeShape" value="round">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="cakeSize">Cake Size *</label>
                <select id="cakeSize" required>
                    <option value="">Select size</option>
                    <option value="6 inch">6 inches (Serves 8-10)</option>
                    <option value="8 inch">8 inches (Serves 12-16)</option>
                    <option value="10 inch">10 inches (Serves 20-25)</option>
                    <option value="12 inch">12 inches (Serves 30-40)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="cakeFlavor">Flavor * <small style="color:var(--plum-mid); font-weight:400;">(Based on available products)</small></label>
                <select id="cakeFlavor" required>
                    <option value="">Loading flavors...</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="cakeLayers">Number of Layers *</label>
                <select id="cakeLayers" required>
                    <option value="1">1 Layer</option>
                    <option value="2">2 Layers</option>
                    <option value="3">3 Layers</option>
                    <option value="4">4 Layers</option>
                </select>
            </div>
            <div class="form-group">
                <label for="cakeFrosting">Frosting Type *</label>
                <select id="cakeFrosting" required>
                    <option value="">Select frosting</option>
                    <option value="Buttercream">Buttercream</option>
                    <option value="Cream Cheese">Cream Cheese</option>
                    <option value="Whipped Cream">Whipped Cream</option>
                    <option value="Fondant">Fondant</option>
                    <option value="Ganache">Ganache</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="cakeColor">Cake Color/Theme</label>
            <input type="text" id="cakeColor" placeholder="e.g., Pink and Gold, Rainbow, Unicorn">
        </div>

        <div class="form-group">
            <label for="cakeMessage">Message on Cake</label>
            <input type="text" id="cakeMessage" placeholder="e.g., Happy Birthday Sarah!">
        </div>

        <div class="form-group">
            <label for="cakeDecorations">Special Decorations/Requests</label>
            <textarea id="cakeDecorations" placeholder="Describe any special decorations, toppings, or design preferences..."></textarea>
        </div>

        <div class="form-group">
            <label for="cakeReferenceImage">📸 Reference Image / Design Inspiration <small style="color:var(--plum-mid); font-weight:400;">(Optional — upload a photo of the design you want)</small></label>
            <div style="border:2px dashed #e0d0ee; border-radius:10px; padding:16px; text-align:center; background:var(--surface2); transition:border-color .2s;" id="refImageDropzone">
                <input type="file" id="cakeReferenceImage" accept="image/*" style="display:none;" onchange="previewReferenceImage(this)">
                <div id="refImagePlaceholder">
                    <div style="font-size:32px; margin-bottom:8px;">🖼️</div>
                    <div style="color:var(--plum-mid); font-size:14px; margin-bottom:10px;">Drag & drop or click to upload a reference photo</div>
                    <button type="button" onclick="document.getElementById('cakeReferenceImage').click()" style="padding:8px 20px; background:linear-gradient(135deg,#3d2268,#4a2878); color:white; border:none; border-radius:8px; cursor:pointer; font-size:13px; font-weight:600;">Choose Image</button>
                </div>
                <div id="refImagePreview" style="display:none;">
                    <img id="refImageThumb" style="max-width:200px; max-height:200px; border-radius:10px; border:2px solid #e0d0ee; margin-bottom:8px;" alt="Reference image preview">
                    <br>
                    <button type="button" onclick="clearReferenceImage()" style="padding:6px 14px; background:#fed7d7; color:#742a2a; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; margin-top:4px;">✕ Remove Image</button>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="customCakeDate">Needed By Date *</label>
                <input type="date" id="customCakeDate" required min="<?= date('Y-m-d', strtotime('+3 days')) ?>" onchange="filterCustomCakeTimeSlots()">
            </div>
            <div class="form-group">
                <label for="customCakeTime">Preferred Time</label>
                <select id="customCakeTime">
                    <option value="">Select time (optional)</option>
                    <option value="08:00">8:00 AM</option>
                    <option value="09:00">9:00 AM</option>
                    <option value="10:00">10:00 AM</option>
                    <option value="11:00">11:00 AM</option>
                    <option value="12:00">12:00 PM</option>
                    <option value="13:00">1:00 PM</option>
                    <option value="14:00">2:00 PM</option>
                    <option value="15:00">3:00 PM</option>
                    <option value="16:00">4:00 PM</option>
                    <option value="17:00">5:00 PM</option>
                </select>
                <div id="customCakeTimeWarning" style="display:none; margin-top:6px; padding:8px 12px; background:var(--amber-bg); color:var(--amber-text); border-radius:8px; font-size:13px;">
                    ⚠️ No time slots left for today. Please select a future date.
                </div>
            </div>
        </div>

        <!-- FULFILLMENT METHOD -->
        <div class="form-group">
            <label>Fulfillment Method *</label>
            <div style="display:flex; gap:10px; margin-top:6px;">
                <button type="button" id="customFulfillDelivery" onclick="selectCustomFulfillment('delivery')"
                    style="flex:1; padding:12px; border:2px solid var(--plum); border-radius:10px; background:var(--plum); color:white; font-weight:700; cursor:pointer; font-size:14px; transition:all .2s;">
                    🚚 Delivery
                </button>
                <button type="button" id="customFulfillPickup" onclick="selectCustomFulfillment('pickup')"
                    style="flex:1; padding:12px; border:2px solid var(--border); border-radius:10px; background:var(--surface2); color:var(--text); font-weight:600; cursor:pointer; font-size:14px; transition:all .2s;">
                    🏪 Pickup
                </button>
            </div>
        </div>

        <!-- AUTO-FILL BANNER -->
        <div id="customCakeAutofillBanner" style="display:none; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:10px 14px; margin-bottom:4px; font-size:13px; color:#1d4ed8; display:flex; align-items:center; gap:8px;">
            ℹ️ Contact and address fields have been pre-filled from your profile. You can edit them below.
        </div>

        <div class="form-group">
            <label for="contactNumber">Contact Number *</label>
            <input type="tel" id="contactNumber" placeholder="09171234567" required maxlength="11" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>

        <div class="form-group" id="customCakeAddressGroup">
            <label for="deliveryAddress">Delivery Address *</label>
            <textarea id="deliveryAddress" placeholder="e.g. Blk 3 Lot 5 Sampaguita St., Brgy. Kauswagan, Cagayan de Oro City, Misamis Oriental" required></textarea>
            <small style="color:var(--plum-mid);font-size:11px;margin-top:4px;display:block;">📍 Include: House/Unit No., Street, Barangay, City/Municipality, Province</small>
        </div>

        <div style="background:var(--surface2);border:1px solid var(--border-soft);border-radius:10px;padding:14px 16px;margin-bottom:18px;font-size:13px;color:var(--text-muted);line-height:1.6;">
            💡 <strong style="color:var(--text);">How it works:</strong><br>
            Submit your request → Admin reviews &amp; sends you a price quote → You accept or decline the quote → If accepted, you choose your payment method (COD or GCash).
        </div>

        <button type="submit" class="submit-custom-btn">📤 Submit Custom Cake Request</button>
        <p style="text-align:center; color:var(--plum-mid); font-size:13px; margin-top:12px;">
            We'll review your request and send you a price quote. You can view it in the <strong>Requests</strong> tab.
        </p>
    </form>
</div>

<!-- MY ORDERS TAB -->
<div id="ordersTab" class="tab-content">
    <h2 class="section-title">My Orders</h2>
    <div style="font-size:12px;color:var(--text-muted);font-style:italic;margin-bottom:14px;" id="ordersLastUpdated"></div>

    <!-- ORDER TABS -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;" id="orderTabsBar">
        <button class="ord-tab-btn active" id="ordTabActive" onclick="switchOrderTab('active')">
            ⚡ Active <span class="ord-tab-count" id="ordCountActive">0</span>
        </button>
        <button class="ord-tab-btn" id="ordTabHistory" onclick="switchOrderTab('history')">
            📋 History <span class="ord-tab-count" id="ordCountHistory">0</span>
        </button>
        <button class="ord-tab-btn" id="ordTabArchived" onclick="switchOrderTab('archived')">
            🗄️ Archived <span class="ord-tab-count" id="ordCountArchived">0</span>
        </button>
    </div>

    <!-- SORT + FILTER TOOLBAR -->
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px;" id="ordToolbar">
        <button class="ord-tool-btn" id="ordSortBtn" onclick="openOrdSortSheet()">⇅ Sort: <span id="ordSortLabel">Newest</span></button>
        <button class="ord-tool-btn" id="ordFilterBtn" onclick="openOrdFilterSheet()">🔽 Filter</button>
        <div id="ordActiveChips" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;"></div>
    </div>

    <div id="ordersList">
        <div class="empty-state"><div class="icon">⏳</div><p>Loading orders...</p></div>
    </div>
</div>

<!-- ORDER SORT SHEET -->
<div id="ordSortOverlay" style="display:none;position:fixed;inset:0;background:rgba(26,20,38,0.55);z-index:800;backdrop-filter:blur(3px);align-items:flex-end;justify-content:center;" onclick="closeOrdSortSheet(event)">
    <div style="background:var(--surface);border-radius:20px 20px 0 0;padding:20px 24px 32px;width:100%;max-width:540px;box-shadow:0 -8px 40px rgba(0,0,0,0.25);animation:slideUpSheet .25s ease;border:1px solid var(--border-soft);border-bottom:none;">
        <div style="width:36px;height:4px;background:var(--border);border-radius:2px;margin:0 auto 18px;"></div>
        <div style="font-size:16px;font-weight:700;color:var(--plum);margin-bottom:14px;">Sort Orders</div>
        <div id="ordSortOptions"></div>
    </div>
</div>

<!-- ORDER FILTER SHEET -->
<div id="ordFilterOverlay" style="display:none;position:fixed;inset:0;background:rgba(26,20,38,0.55);z-index:800;backdrop-filter:blur(3px);align-items:flex-end;justify-content:center;" onclick="closeOrdFilterSheet(event)">
    <div style="background:var(--surface);border-radius:20px 20px 0 0;padding:20px 24px 32px;width:100%;max-width:540px;box-shadow:0 -8px 40px rgba(0,0,0,0.25);animation:slideUpSheet .25s ease;border:1px solid var(--border-soft);border-bottom:none;">
        <div style="width:36px;height:4px;background:var(--border);border-radius:2px;margin:0 auto 18px;"></div>
        <div style="font-size:16px;font-weight:700;color:var(--plum);margin-bottom:14px;">Filter by Status</div>
        <div id="ordFilterChips" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px;"></div>
        <button onclick="applyOrdFilter()" style="width:100%;padding:13px;background:var(--plum);color:white;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;">Apply Filters</button>
    </div>
</div>

<!-- PROFILE TAB -->
<div id="profileTab" class="tab-content">
    <h2 class="section-title">👤 My Profile</h2>
    <div class="profile-card">
        <div class="profile-avatar">👤</div>
        <div class="profile-form-group">
            <label>Full Name</label>
            <input type="text" id="profileFullname" placeholder="Your full name">
        </div>
        <div class="profile-form-group">
            <label>Username</label>
            <input type="text" id="profileUsername" disabled>
        </div>
        <div class="profile-form-group">
            <label>Saved Phone Number</label>
            <input type="text" id="profilePhone" placeholder="e.g. 09171234567">
        </div>
        <div class="profile-form-group">
            <label>Saved Delivery Address</label>
            <input type="text" id="profileAddress" placeholder="Your default delivery address">
        </div>
        <button class="profile-save-btn" onclick="saveProfile()">💾 Save Changes</button>

        <!-- CHANGE PASSWORD -->
        <div style="margin-top:24px;padding-top:20px;border-top:2px solid #e0d0ee;">
            <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:14px;">🔒 Change Password</div>
            <div class="profile-form-group">
                <label>Current Password</label>
                <input type="password" id="currentPassword" placeholder="Enter current password">
            </div>
            <div class="profile-form-group">
                <label>New Password</label>
                <input type="password" id="newPassword" placeholder="Enter new password (min. 6 characters)">
            </div>
            <div class="profile-form-group">
                <label>Confirm New Password</label>
                <input type="password" id="confirmPassword" placeholder="Re-enter new password">
            </div>
            <button class="profile-save-btn" onclick="changePassword()" style="background:linear-gradient(135deg,#2a1550,#18103a);">🔒 Update Password</button>
        </div>
    </div>
</div>

<!-- MY CUSTOM REQUESTS TAB -->
<div id="customrequestsTab" class="tab-content">
    <h2 class="section-title">🎨 My Custom Cake Requests</h2>
    <p style="color:var(--plum-mid); margin-bottom:20px; font-size:14px;">
        Track your requests. When admin sends a price quote, you can accept or decline here.
    </p>
    <div id="customRequestsList">
        <div class="empty-state"><div class="icon">⏳</div><p>Loading requests…</p></div>
    </div>
</div>

<!-- ══ ACCEPT QUOTE MODAL (payment collection) ══ -->
<div id="acceptQuoteModal" style="display:none;position:fixed;inset:0;background:rgba(22,14,48,.65);z-index:5000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--surface);border-radius:18px;padding:28px;max-width:480px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);color:var(--text);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:14px;border-bottom:2px solid var(--border-soft);">
            <h3 style="font-size:19px;font-weight:700;color:var(--text);">✅ Accept Quote</h3>
            <button onclick="closeAcceptQuoteModal()" style="background:none;border:none;font-size:24px;color:var(--plum-mid);cursor:pointer;line-height:1;">✕</button>
        </div>

        <!-- Quote summary -->
        <div id="aqmSummary" style="background:var(--surface2);border-radius:10px;padding:14px;margin-bottom:18px;font-size:13px;line-height:1.7;"></div>

        <!-- Quoted price display -->
        <div id="aqmPriceBlock" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-radius:12px;padding:16px;text-align:center;margin-bottom:20px;">
            <div style="color:#065f46;font-size:13px;font-weight:600;margin-bottom:4px;">💰 Quoted Price</div>
            <div id="aqmPrice" style="color:#064e3b;font-size:36px;font-weight:800;"></div>
        </div>

        <!-- Payment method selector -->
        <div style="margin-bottom:16px;">
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:10px;">Choose Payment Method *</div>
            <div style="display:flex;gap:10px;">
                <button type="button" id="aqmBtnCOD" onclick="selectAqmPayment('cod')"
                    style="flex:1;padding:13px;border:2px solid var(--plum);background:var(--plum);color:white;font-weight:700;border-radius:10px;cursor:pointer;font-size:14px;transition:all .2s;">
                    💵 Cash on Delivery
                </button>
                <button type="button" id="aqmBtnGcash" onclick="selectAqmPayment('gcash')"
                    style="flex:1;padding:13px;border:2px solid var(--border);background:var(--surface2);color:var(--text);font-weight:600;border-radius:10px;cursor:pointer;font-size:14px;transition:all .2s;">
                    📱 GCash
                </button>
            </div>
        </div>

        <!-- COD info -->
        <div id="aqmCodInfo" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:#1d4ed8;line-height:1.6;">
            💵 <strong>Cash on Delivery / Pickup</strong><br>
            Prepare the exact amount upon delivery/pickup. Our rider or staff will collect payment when your order arrives.
        </div>

        <!-- GCash section -->
        <div id="aqmGcashSection" style="display:none;">
            <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #86efac;border-radius:12px;padding:14px 16px;margin-bottom:14px;">
                <div style="font-weight:700;color:#166534;margin-bottom:6px;font-size:14px;">📱 GCash Payment Instructions</div>
                <div style="font-size:13px;color:#15803d;line-height:1.7;">
                    Send payment to: <strong>0926-402-0192</strong><br>
                    Account Name: <strong>Majoy's Homemade Sweets</strong><br>
                    After sending, enter your 13-digit reference number below.
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:700;color:var(--text);margin-bottom:6px;">
                    GCash Reference Number *
                    <span id="aqmRefCount" style="font-weight:400;color:var(--plum-mid);margin-left:6px;">0/13</span>
                </label>
                <input type="text" id="aqmGcashRef" placeholder="13-digit reference number"
                    maxlength="13" inputmode="numeric"
                    oninput="this.value=this.value.replace(/[^0-9]/g,'');document.getElementById('aqmRefCount').textContent=this.value.length+'/13';"
                    style="width:100%;padding:12px 14px;border:2px solid var(--border);border-radius:10px;font-size:18px;font-weight:700;letter-spacing:3px;font-family:monospace;color:var(--text);background:var(--surface2);outline:none;transition:border-color .2s;"
                    onfocus="this.style.borderColor='var(--plum)'" onblur="this.style.borderColor='var(--border)'">
            </div>
            <!-- GCash screenshot upload -->
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:13px;font-weight:700;color:var(--text);margin-bottom:6px;">
                    📸 GCash Screenshot <span style="font-weight:400;color:var(--plum-mid);">(Optional)</span>
                </label>
                <div id="aqmScreenshotDropzone" style="border:2px dashed #86efac;border-radius:10px;padding:16px;text-align:center;background:var(--surface2);cursor:pointer;"
                     onclick="document.getElementById('aqmScreenshotFile').click()">
                    <input type="file" id="aqmScreenshotFile" accept="image/*" style="display:none;" onchange="handleAqmScreenshot(this)">
                    <div id="aqmScreenshotPlaceholder">
                        <div style="font-size:28px;margin-bottom:6px;">📷</div>
                        <div style="color:var(--plum-mid);font-size:13px;margin-bottom:8px;">Tap to upload your GCash payment screenshot</div>
                        <span style="padding:7px 16px;background:linear-gradient(135deg,#3d2268,#4a2878);color:white;border-radius:8px;font-size:13px;font-weight:600;">Choose Screenshot</span>
                    </div>
                    <div id="aqmScreenshotPreview" style="display:none;">
                        <img id="aqmScreenshotThumb" style="max-width:180px;max-height:160px;border-radius:10px;border:2px solid #86efac;" alt="Screenshot">
                        <br>
                        <button type="button" onclick="event.stopPropagation();clearAqmScreenshot()"
                            style="margin-top:8px;padding:6px 14px;background:#fed7d7;color:#742a2a;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">
                            ✕ Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" id="aqmRequestId">
        <div style="display:flex;gap:10px;margin-top:4px;">
            <button onclick="confirmAcceptQuote()"
                style="flex:2;padding:14px;background:linear-gradient(135deg,#3d2268,#4a2878);color:white;border:none;border-radius:10px;font-weight:700;font-size:15px;cursor:pointer;">
                ✅ Confirm &amp; Accept
            </button>
            <button onclick="closeAcceptQuoteModal()"
                style="flex:1;padding:14px;background:var(--surface2);border:none;border-radius:10px;font-weight:700;color:var(--text);cursor:pointer;">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- CHECKOUT MODAL -->
<div id="checkoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Checkout</h3>
            <button type="button" class="modal-close" onclick="closeCheckoutModal()">&times;</button>
        </div>

        <h4 style="color:var(--text); margin-bottom:12px; font-size:16px;">Fulfillment Method</h4>
        <div class="payment-method-selector">
            <div class="payment-method-btn active" onclick="selectFulfillmentMethod('delivery')">
                <div class="icon">🚚</div>
                <div>Delivery</div>
            </div>
            <div class="payment-method-btn" onclick="selectFulfillmentMethod('pickup')">
                <div class="icon">🏪</div>
                <div>Pickup</div>
            </div>
        </div>

        <h4 style="color:var(--text); margin-bottom:12px; margin-top:20px; font-size:16px;">Payment Method</h4>
        <div class="payment-method-selector">
            <div class="payment-method-btn active" onclick="selectPaymentMethod('gcash')">
                <div class="icon">💳</div>
                <div>GCash</div>
            </div>
            <div class="payment-method-btn" onclick="selectPaymentMethod('cod')">
                <div class="icon">💵</div>
                <div>Cash <span id="paymentTypeLabel">on Delivery</span></div>
            </div>
        </div>

        <div id="gcashInstructions" class="gcash-instructions">
            <h4>📱 GCash Payment Instructions</h4>
            <p>1. Send payment to our GCash number:</p>
            <div class="gcash-number">0926-402-0192</div>
            <p style="margin-top:12px;">2. Use your <strong>Order Number</strong> as reference</p>
            <p>3. Take a screenshot of your payment confirmation</p>
            <p>4. Send the screenshot to our Facebook page or contact number</p>
        </div>

        <!-- GCash proof fields -->
        <div id="gcashProofFields" style="display:block;">
            <div class="form-group">
                <label for="gcashRefNumber">GCash Reference Number * <small style="color:var(--plum-mid);font-weight:400;">(13 digits)</small></label>
                <input type="text" id="gcashRefNumber" placeholder="e.g. 1234567890123" maxlength="13" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'');updateGcashRefHint();" style="letter-spacing:2px;font-size:16px;font-weight:700;">
                <small id="gcashRefHint" style="display:none;margin-top:4px;font-size:12px;"></small>
            </div>
            <div class="form-group">
                <label for="gcashScreenshotFile">GCash Screenshot <small style="color:var(--plum-mid);font-weight:400;">(Optional)</small></label>
                <input type="file" id="gcashScreenshotFile" accept="image/*" style="padding:8px;">
                <div id="gcashScreenshotPreview" style="display:none;margin-top:8px;">
                    <img id="gcashScreenshotThumb" style="max-width:120px;border-radius:8px;border:2px solid #e0d0ee;" alt="GCash screenshot preview">
                </div>
            </div>
        </div>

        <div id="codInstructions" class="gcash-instructions" style="display:none;">
            <h4 id="codTitle">💵 Cash on Delivery</h4>
            <p id="codText">Prepare exact amount upon delivery to avoid delays.</p>
            <p id="codText2">Our delivery rider will hand over your order once payment is received.</p>
        </div>

        <div id="pickupInstructions" class="gcash-instructions" style="display:none; background:#e6f7ff; border-left:4px solid #4a90e2;">
            <h4 style="color:#2c5282;">🏪 Pickup Information</h4>
            <p><strong>Pickup Location:</strong></p>
            <p style="margin-left:10px;">Blk13 Lot8 Phase2 Menzi Village<br>Damilag Manolo Fortich Bukidnon</p>
            <p style="margin-top:10px;"><strong>Store Hours:</strong></p>
            <p style="margin-left:10px;">Monday - Sunday: 8:00 AM - 6:00 PM</p>
            <p style="margin-top:10px; color:#d97706; font-weight:600;">⏰ Please wait for confirmation before picking up!</p>
        </div>

        <div class="form-group">
            <label for="deliveryName"><span id="nameLabel">Recipient</span> Name *</label>
            <input type="text" id="deliveryName" required>
        </div>

        <div class="form-group">
            <label for="deliveryPhone">Contact Number *</label>
            <input type="tel" id="deliveryPhone" placeholder="09171234567" required maxlength="11" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>

        <div class="form-group" id="addressGroup">
            <label for="deliveryAddressCheckout">Delivery Address *</label>
            <textarea id="deliveryAddressCheckout" placeholder="e.g. Blk 3 Lot 5 Sampaguita St., Brgy. Kauswagan, Cagayan de Oro City, Misamis Oriental" required></textarea>
            <small style="color:var(--plum-mid);font-size:11px;margin-top:4px;display:block;">📍 Include: House/Unit No., Street, Barangay, City/Municipality, Province</small>
        </div>

        <div class="form-group">
            <label for="preferredDate"><span id="dateLabel">Delivery</span> Date *</label>
            <input type="date" id="preferredDate" required onchange="validateTimeIfToday()">
            <small style="color:var(--plum-mid); font-size:12px; display:block; margin-top:4px;">
                <span id="dateHint">📅 Select when you want this delivered (Today or any future date)</span>
            </small>
        </div>

        <div class="form-group">
            <label for="preferredTime">Preferred Time (Optional)</label>
            <select id="preferredTime" onchange="validateTimeIfToday()">
                <option value="">-- Select a time --</option>
                <option value="06:00">6:00 AM</option>
                <option value="07:00">7:00 AM</option>
                <option value="08:00">8:00 AM</option>
                <option value="09:00">9:00 AM</option>
                <option value="10:00">10:00 AM</option>
                <option value="11:00">11:00 AM</option>
                <option value="12:00">12:00 PM</option>
                <option value="13:00">1:00 PM</option>
                <option value="14:00">2:00 PM</option>
                <option value="15:00">3:00 PM</option>
                <option value="16:00">4:00 PM</option>
                <option value="17:00">5:00 PM</option>
                <option value="18:00">6:00 PM</option>
                <option value="19:00">7:00 PM</option>
                <option value="20:00">8:00 PM</option>
                <option value="21:00">9:00 PM</option>
            </select>
            <small id="timeWarning" style="color:#e53e3e; font-size:12px; display:none; margin-top:4px;">⚠️ That time has already passed today. Please select a future time.</small>
        </div>

        <div class="form-group" id="notesGroup">
            <label for="deliveryNotes"><span id="notesLabel">Delivery</span> Notes (Optional)</label>
            <textarea id="deliveryNotes" placeholder="e.g., Gate code, landmarks, special instructions..."></textarea>
        </div>

        <button type="button" class="confirm-order-btn" onclick="confirmOrder()">Confirm Order</button>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════
//  GLOBALS
// ═══════════════════════════════════════════════════════════════
let productsCache = [];
let cart = [];
let selectedPaymentMethod = 'gcash';
let selectedFulfillmentMethod = 'delivery'; // 'delivery' or 'pickup'

// ═══════════════════════════════════════════════════════════════
//  API HELPER
// ═══════════════════════════════════════════════════════════════
async function api(action, data = null) {
    const opts = { method: data ? 'POST' : 'GET' };
    if (data) {
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(data);
    }
    const res = await fetch(`customer_api.php?action=${action}`, opts);
    return await res.json();
}

// ═══════════════════════════════════════════════════════════════
//  TAB SWITCHING
// ═══════════════════════════════════════════════════════════════
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.nav-drawer-btn').forEach(b => b.classList.remove('active'));

    const map    = { menu:[0,'drawerMenu'], custom:[1,'drawerCustom'], customrequests:[2,'drawerRequests'], orders:[3,'drawerOrders'], profile:[4,'drawerProfile'] };
    const tabIds = { menu:'menuTab', custom:'customTab', customrequests:'customrequestsTab', orders:'ordersTab', profile:'profileTab' };

    if (map[tab]) {
        document.getElementById(tabIds[tab]).classList.add('active');
        const allTabBtns = document.querySelectorAll('.tab-btn');
        if (allTabBtns[map[tab][0]]) allTabBtns[map[tab][0]].classList.add('active');
        const drawerBtn = document.getElementById(map[tab][1]);
        if (drawerBtn) drawerBtn.classList.add('active');
    }

    if (tab === 'custom')         { loadFlavorsFromInventory(); loadInspirationGallery(); prefillCustomCakeForm(); }
    if (tab === 'customrequests') loadMyCustomRequests();
    if (tab === 'orders')         loadMyOrders();
    if (tab === 'profile')        loadProfile();
}

// ═══════════════════════════════════════════════════════════════
//  REFERENCE IMAGE (custom cake)
// ═══════════════════════════════════════════════════════════════
let referenceImagePath = '';

function previewReferenceImage(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('refImageThumb').src = e.target.result;
        document.getElementById('refImagePlaceholder').style.display = 'none';
        document.getElementById('refImagePreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function clearReferenceImage() {
    document.getElementById('cakeReferenceImage').value = '';
    document.getElementById('refImageThumb').src = '';
    document.getElementById('refImagePlaceholder').style.display = 'block';
    document.getElementById('refImagePreview').style.display = 'none';
    referenceImagePath = '';
}

// ═══════════════════════════════════════════════════════════════
//  CUTOFF TIME CHECK
// ═══════════════════════════════════════════════════════════════
let orderingEnabled = true;
let cutoffTime = '20:00';

async function checkCutoffTime() {
    // Cutoff feature disabled — ordering is always open
    return;
    try {
        const res = await api('get_cutoff');
        cutoffTime = res.cutoff || '20:00';
        const [ch, cm] = cutoffTime.split(':').map(Number);
        const now = new Date();
        const nowMins = now.getHours() * 60 + now.getMinutes();
        const cutoffMins = ch * 60 + cm;

        if (nowMins >= cutoffMins) {
            orderingEnabled = false;
            const notice = document.getElementById('cutoffNotice');
            notice.style.display = 'block';
            notice.innerHTML = `🚫 <strong>Ordering is closed for today.</strong> Our ordering cutoff is ${formatCutoff(cutoffTime)}. You can still browse our menu, but orders will open again tomorrow morning.`;
            // Disable all add-to-cart buttons
            document.querySelectorAll('.add-btn').forEach(btn => {
                btn.disabled = true;
                btn.textContent = '⏰ Orders Closed';
                btn.style.background = '#cbd5e0';
            });
            // Disable checkout
            document.querySelectorAll('.checkout-btn').forEach(btn => btn.disabled = true);
        } else {
            orderingEnabled = true;
            const minsLeft = cutoffMins - nowMins;
            if (minsLeft <= 60) {
                const notice = document.getElementById('cutoffNotice');
                notice.style.display = 'block';
                notice.style.background = '#fef3c7';
                notice.style.borderLeftColor = '#d97706';
                notice.style.color = '#92400e';
                notice.innerHTML = `⏰ <strong>Heads up!</strong> Ordering closes at ${formatCutoff(cutoffTime)} (${minsLeft} minute${minsLeft !== 1 ? 's' : ''} left). Place your order now!`;
            }
        }
    } catch(e) { /* cutoff check failed silently */ }
}

function formatCutoff(t) {
    const [h, m] = t.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${String(m).padStart(2,'0')} ${ampm}`;
}

// ═══════════════════════════════════════════════════════════════
//  LOAD PRODUCTS
// ═══════════════════════════════════════════════════════════════
async function loadProducts() {
    productsCache = await api('get_products');
    const query = document.getElementById('menuSearch') ? document.getElementById('menuSearch').value.toLowerCase().trim() : '';
    const list  = query ? productsCache.filter(p => p.name.toLowerCase().includes(query) || (p.description||'').toLowerCase().includes(query)) : productsCache;
    renderProductGrid(list);
}

function renderProductGrid(list) {
    const grid = document.getElementById('productsGrid');
    if (!list.length) {
        grid.innerHTML = '<div class="empty-state"><div class="icon">🍰</div><p>No products available right now</p></div>';
        return;
    }

    grid.innerHTML = list.map(p => {
        const days = calcDays(p);
        const expired = days !== null && days < 0;
        const noStock = p.stock <= 0;
        const disabled = expired || noStock;

        // Color-coded stock dot: green = above 15, orange = 1-15, red = 0
        const dotColor = noStock ? '#f56565' : p.stock <= 15 ? '#ed8936' : '#48bb78';
        const dotLabel = noStock ? 'Out of Stock' : p.stock <= 15 ? 'Low Stock' : 'In Stock';
        const stockDot = `<span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:${dotColor};"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${dotColor};flex-shrink:0;box-shadow:0 0 4px ${dotColor}80;"></span>${dotLabel}</span>`;

        // Low stock warning banner (orange range only)
        const lowStockBanner = (!noStock && p.stock <= 15 && !expired)
            ? `<div style="background:var(--amber-bg); border:1px solid var(--amber); border-radius:6px; padding:6px 10px; margin-bottom:8px; font-size:12px; color:var(--amber-text); font-weight:600; text-align:center;">
                   \u26a0\ufe0f Hurry! Only ${p.stock} left in stock
               </div>`
            : '';

        let shelfBadge = '';

        let btn = '';
        if (expired) btn = '<div style="color:#f56565;font-weight:600;font-size:13px;margin-top:8px;text-align:center;">⚠️ Expired</div>';
        else if (noStock) btn = '<div style="color:var(--text-muted);font-weight:600;font-size:13px;margin-top:8px;text-align:center;">Out of Stock</div>';
        else btn = `<button class="add-btn" onclick="addToCart(${p.id})">+ Add to Cart</button>`;

        return `
        <div class="product-card ${disabled?'disabled':''}">
            <div class="product-img">${p.photo ? '<img src="'+p.photo+'" alt="'+escH(p.name)+'">' : '<span>No Image</span>'}</div>
            <div class="product-body">
                <div class="product-name">${escH(p.name)}</div>
                <div class="product-desc">${escH(p.description||'')}</div>
                <div class="product-footer">
                    <span class="product-price">₱${Number(p.price).toFixed(2)}</span>
                    ${stockDot}
                </div>
                ${lowStockBanner}
                ${shelfBadge}
                ${btn}
            </div>
        </div>`;
    }).join('');
}

// ═══════════════════════════════════════════════════════════════
//  LOAD FLAVORS FROM INVENTORY
// ═══════════════════════════════════════════════════════════════
async function loadFlavorsFromInventory() {
    const flavorSelect = document.getElementById('cakeFlavor');
    
    // If products not loaded yet, load them
    if (!productsCache || productsCache.length === 0) {
        productsCache = await api('get_products');
    }
    
    console.log('Products loaded:', productsCache); // Debug
    
    // Only show products that have stock
    // Filter out Custom orders if they exist, otherwise show all products with stock
    const availableProducts = productsCache.filter(p => {
        // Must have stock
        if (!p.stock || p.stock <= 0) return false;
        
        // If product has 'custom' property, exclude Custom orders
        if (p.custom && p.custom === 'Custom') return false;
        
        // If product has 'archived' property, exclude archived products
        if (p.archived && p.archived == 1) return false;
        
        return true;
    });
    
    console.log('Available products for flavors:', availableProducts); // Debug
    
    // Extract product names as flavor options
    const flavors = new Set();
    
    availableProducts.forEach(product => {
        // Add the product name directly as a flavor option
        flavors.add(product.name);
    });
    
    // Convert Set to sorted array
    const sortedFlavors = Array.from(flavors).sort();
    
    console.log('Flavors:', sortedFlavors); // Debug
    
    // Populate dropdown
    if (sortedFlavors.length > 0) {
        flavorSelect.innerHTML = '<option value="">Select flavor (Based on available products)</option>';
        
        sortedFlavors.forEach(flavor => {
            const option = document.createElement('option');
            option.value = flavor;
            option.textContent = flavor;
            flavorSelect.appendChild(option);
        });
        
        // Add "Custom/Other" option at the end
        const customOption = document.createElement('option');
        customOption.value = 'Custom';
        customOption.textContent = 'Custom/Other (specify in decorations)';
        flavorSelect.appendChild(customOption);
        
    } else {
        // Fallback if no products found
        flavorSelect.innerHTML = `
            <option value="">No products available</option>
            <option value="Custom">Custom (specify flavor in decorations)</option>
        `;
    }
}

// ═══════════════════════════════════════════════════════════════
//  NAV DRAWER (mobile hamburger)
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
//  MOBILE CART DRAWER
// ═══════════════════════════════════════════════════════════════
function openCartDrawer() {
    document.getElementById('cartDrawer').classList.add('active');
    document.getElementById('cartOverlay').classList.add('active');
}
function closeCartDrawer() {
    document.getElementById('cartDrawer').classList.remove('active');
    document.getElementById('cartOverlay').classList.remove('active');
}

// ═══════════════════════════════════════════════════════════════
//  CART LOGIC
// ═══════════════════════════════════════════════════════════════
function addToCart(productId) {
    if (!orderingEnabled) {
        showToast(`🚫 Ordering is closed. Our cutoff time is ${formatCutoff(cutoffTime)}.`, 'error');
        return;
    }
    const p = productsCache.find(x => x.id == productId);
    if (!p || p.stock <= 0) { showToast('This item is out of stock.', 'error'); return; }

    const existing = cart.find(c => c.id == productId);
    if (existing) {
        if (existing.quantity >= p.stock) { 
            showToast(`Only ${p.stock} in stock — you already have the max in your cart.`, 'warning'); 
            return; 
        }
        existing.quantity++;
    } else {
        cart.push({ 
            id: p.id, 
            name: p.name, 
            price: Number(p.price), 
            quantity: 1, 
            maxStock: p.stock 
        });
    }
    renderCart();
}

function changeQty(idx, delta) {
    const item = cart[idx];
    const newQ = item.quantity + delta;
    if (newQ <= 0) { 
        cart.splice(idx, 1); 
        renderCart(); 
        return; 
    }
    if (newQ > item.maxStock) { 
        showToast('Max available: ' + item.maxStock, 'warning');
        return; 
    }
    item.quantity = newQ;
    renderCart();
}

function removeItem(idx) { 
    cart.splice(idx, 1); 
    renderCart(); 
}

function renderCart() {
    const el = document.getElementById('cartItems');
    const elM = document.getElementById('cartItemsMobile');
    const totalItems = cart.reduce((s, i) => s + i.quantity, 0);

    // Update floating button count
    const floatBtn = document.getElementById('floatCartBtn');
    const floatCount = document.getElementById('floatCartCount');
    floatCount.textContent = totalItems;
    floatBtn.style.display = totalItems > 0 ? 'inline-flex' : 'none';

    if (!cart.length) {
        const empty = '<div class="empty-state"><div class="icon">🛒</div><p>Cart is empty</p></div>';
        el.innerHTML = empty;
        elM.innerHTML = empty;
        document.getElementById('cartTotal').textContent = '0.00';
        document.getElementById('cartTotalMobile').textContent = '0.00';
        return;
    }

    let total = 0;
    const html = cart.map((item, i) => {
        total += item.price * item.quantity;
        return `
        <div class="cart-item">
            <div class="cart-item-top">
                <div>
                    <div class="cart-item-name">${escH(item.name)}</div>
                    <div class="cart-item-price-each">₱${item.price.toFixed(2)} each</div>
                </div>
            </div>
            <div class="cart-item-bottom">
                <div class="qty-controls">
                    <button class="qty-btn" onclick="changeQty(${i},-1)">−</button>
                    <span class="qty-num">${item.quantity}</span>
                    <button class="qty-btn" onclick="changeQty(${i},1)">+</button>
                </div>
                <span class="cart-item-subtotal">₱${(item.price * item.quantity).toFixed(2)}</span>
            </div>
            <button class="remove-btn" onclick="removeItem(${i})">Remove</button>
        </div>`;
    }).join('');

    el.innerHTML = html;
    elM.innerHTML = html;
    document.getElementById('cartTotal').textContent = total.toFixed(2);
    document.getElementById('cartTotalMobile').textContent = total.toFixed(2);
}

// ═══════════════════════════════════════════════════════════════
//  CHECKOUT MODAL
// ═══════════════════════════════════════════════════════════════
function openCheckoutModal() {
    if (!cart.length) {
        showToast('Your cart is empty. Add some items first!', 'warning');
        return;
    }
    // Auto-fill from saved profile
    const savedPhone   = localStorage.getItem('profilePhone') || '';
    const savedAddress = localStorage.getItem('profileAddress') || '';
    const savedName    = localStorage.getItem('profileFullname') || '';
    if (savedName)    document.getElementById('deliveryName').value = savedName;
    if (savedPhone)   document.getElementById('deliveryPhone').value = savedPhone;
    if (savedAddress) document.getElementById('deliveryAddressCheckout').value = savedAddress;
    document.getElementById('checkoutModal').classList.add('active');
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.remove('active');
}

function selectFulfillmentMethod(method) {
    selectedFulfillmentMethod = method;
    
    // Update button states for fulfillment
    const fulfillmentBtns = document.querySelectorAll('.payment-method-selector')[0].querySelectorAll('.payment-method-btn');
    fulfillmentBtns.forEach(btn => btn.classList.remove('active'));
    event.target.closest('.payment-method-btn').classList.add('active');
    
    // Update UI based on fulfillment method
    if (method === 'delivery') {
        // Show delivery-related fields
        document.getElementById('addressGroup').style.display = 'block';
        document.getElementById('deliveryAddressCheckout').required = true;
        document.getElementById('nameLabel').textContent = 'Recipient';
        document.getElementById('notesLabel').textContent = 'Delivery';
        document.getElementById('dateLabel').textContent = 'Delivery';
        document.getElementById('dateHint').textContent = '📅 Select when you want this delivered (Today or any future date)';
        document.getElementById('deliveryNotes').placeholder = 'e.g., Gate code, landmarks, special instructions...';
        
        // Update payment label
        document.getElementById('paymentTypeLabel').textContent = 'on Delivery';
        
        // Update COD instructions
        document.getElementById('codTitle').textContent = '💵 Cash on Delivery';
        document.getElementById('codText').textContent = 'Prepare exact amount upon delivery to avoid delays.';
        document.getElementById('codText2').textContent = 'Our delivery rider will hand over your order once payment is received.';
        document.getElementById('codText2').style.display = 'block';
        
        // Hide pickup instructions
        document.getElementById('pickupInstructions').style.display = 'none';
        
    } else { // pickup
        // Hide/modify delivery-related fields
        document.getElementById('addressGroup').style.display = 'none';
        document.getElementById('deliveryAddressCheckout').required = false;
        document.getElementById('deliveryAddressCheckout').value = 'PICKUP - Blk13 Lot8 Phase2 Menzi Village Damilag Manolo Fortich Bukidnon';
        document.getElementById('nameLabel').textContent = 'Your';
        document.getElementById('notesLabel').textContent = 'Pickup';
        document.getElementById('dateLabel').textContent = 'Pickup';
        document.getElementById('dateHint').textContent = '📅 Select when you want to pick this up (Today or any future date)';
        document.getElementById('deliveryNotes').placeholder = 'e.g., Preferred pickup time, special requests...';
        
        // Update payment label
        document.getElementById('paymentTypeLabel').textContent = 'on Pickup';
        
        // Update COD instructions
        document.getElementById('codTitle').textContent = '💵 Cash on Pickup';
        document.getElementById('codText').textContent = 'Prepare exact amount when picking up your order.';
        document.getElementById('codText2').style.display = 'none';
        
        // Show pickup instructions if GCash is selected
        if (selectedPaymentMethod === 'gcash' || document.getElementById('gcashInstructions').style.display !== 'none') {
            document.getElementById('pickupInstructions').style.display = 'block';
        }
    }
}

// Live time validation when user changes the time dropdown
function validateTimeIfToday() {
    const dateVal = document.getElementById('preferredDate').value;
    const timeVal = document.getElementById('preferredTime').value;
    const warning = document.getElementById('timeWarning');
    if (!dateVal || !timeVal) { warning.style.display = 'none'; return; }
    const today = new Date(); today.setHours(0,0,0,0);
    const selDate = new Date(dateVal + 'T00:00:00'); selDate.setHours(0,0,0,0);
    if (selDate.getTime() !== today.getTime()) { warning.style.display = 'none'; return; }
    const [selHour] = timeVal.split(':').map(Number);
    const now = new Date();
    const selMinutes = selHour * 60;
    const nowMinutes = now.getHours() * 60 + now.getMinutes();
    if (selMinutes <= nowMinutes) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

function selectPaymentMethod(method) {
    selectedPaymentMethod = method;
    
    // Update button states for payment
    const paymentBtns = document.querySelectorAll('.payment-method-selector')[1].querySelectorAll('.payment-method-btn');
    paymentBtns.forEach(btn => btn.classList.remove('active'));
    event.target.closest('.payment-method-btn').classList.add('active');
    
    // Show/hide instructions based on payment method
    if (method === 'gcash') {
        document.getElementById('gcashInstructions').style.display = 'block';
        document.getElementById('codInstructions').style.display = 'none';
        document.getElementById('gcashProofFields').style.display = 'block';
        
        // Show pickup info if pickup is selected
        if (selectedFulfillmentMethod === 'pickup') {
            document.getElementById('pickupInstructions').style.display = 'block';
        }
    } else {
        document.getElementById('gcashInstructions').style.display = 'none';
        document.getElementById('codInstructions').style.display = 'block';
        document.getElementById('pickupInstructions').style.display = 'none';
        document.getElementById('gcashProofFields').style.display = 'none';
    }
}

async function confirmOrder() {
    const name = document.getElementById('deliveryName').value.trim();
    const phone = document.getElementById('deliveryPhone').value.trim();
    const address = document.getElementById('deliveryAddressCheckout').value.trim();
    const notes = document.getElementById('deliveryNotes').value.trim();
    const preferredDate = document.getElementById('preferredDate').value;
    const preferredTime = document.getElementById('preferredTime').value;
    
    // Validation based on fulfillment method
    if (selectedFulfillmentMethod === 'delivery') {
        if (!name || !phone || !address || !preferredDate) {
            showToast('Please fill in all required fields.', 'error');
            return;
        }
    } else { // pickup
        if (!name || !phone || !preferredDate) {
            showToast('Please fill in your name, contact number, and preferred date.', 'error');
            return;
        }
    }

    // Phone number must be exactly 11 digits starting with 09
    const phoneDigits = phone.replace(/\D/g, '');
    if (phoneDigits.length !== 11 || !/^09/.test(phone.replace(/\s/g,''))) {
        showToast('Contact number must be exactly 11 digits and start with 09 (e.g. 09171234567).', 'error');
        document.getElementById('deliveryPhone').focus();
        return;
    }

    // GCash validation
    let gcashRef = '';
    let gcashScreenshot = '';
    if (selectedPaymentMethod === 'gcash') {
        gcashRef = document.getElementById('gcashRefNumber').value.trim();
        if (!gcashRef || !/^\d{13}$/.test(gcashRef)) {
            showToast('GCash reference number must be exactly 13 digits.', 'error');
            document.getElementById('gcashRefNumber').focus();
            return;
        }
        // Upload screenshot if provided
        const ssFile = document.getElementById('gcashScreenshotFile').files[0];
        if (ssFile) {
            const fd = new FormData();
            fd.append('file', ssFile);
            try {
                const r = await fetch('customer_api.php?action=upload_gcash_screenshot', { method:'POST', body:fd });
                const d = await r.json();
                if (d.path) gcashScreenshot = d.path;
            } catch(e) { /* screenshot upload failed, continue anyway */ }
        }
    }

    // Check if this is an advance order
    const selectedDateObj = new Date(preferredDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selectedDateObj.setHours(0, 0, 0, 0);
    
    const daysInAdvance = Math.ceil((selectedDateObj - today) / (1000 * 60 * 60 * 24));
    const isAdvanceOrder = daysInAdvance > 0;

    // Block past dates
    if (daysInAdvance < 0) {
        showToast('❌ Please select today or a future date. Past dates are not allowed.', 'error');
        document.getElementById('preferredDate').focus();
        return;
    }

    // Block past times for today's orders
    if (daysInAdvance === 0 && preferredTime) {
        const [selHour, selMin] = preferredTime.split(':').map(Number);
        const now = new Date();
        const selMinutes = selHour * 60 + selMin;
        const nowMinutes = now.getHours() * 60 + now.getMinutes();
        if (selMinutes <= nowMinutes) {
            const h = selHour % 12 || 12;
            const ampm = selHour < 12 ? 'AM' : 'PM';
            showToast('❌ ' + h + ':00 ' + ampm + ' has already passed today. Please choose a later time or a future date.', 'error');
            document.getElementById('preferredTime').focus();
            return;
        }
    }

    const orderData = {
        items: cart.map(c => ({ 
            id: c.id, 
            name: c.name, 
            quantity: c.quantity 
        })),
        payment_method: selectedPaymentMethod,
        fulfillment_method: selectedFulfillmentMethod,
        delivery_name: name,
        delivery_phone: phone,
        delivery_address: selectedFulfillmentMethod === 'pickup'
            ? 'PICKUP - Blk13 Lot8 Phase2 Menzi Village Damilag Manolo Fortich Bukidnon'
            : (address || ''),
        delivery_notes: notes,
        preferred_date: preferredDate,
        preferred_time: preferredTime,
        is_advance_order: isAdvanceOrder,
        gcash_ref_number: gcashRef,
        gcash_screenshot: gcashScreenshot
    };

    const res = await api('place_order', orderData);

    if (res.success) {
        const dateOptions  = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = selectedDateObj.toLocaleDateString('en-US', dateOptions);
        function formatTime(t) { if (!t) return ''; if (t.includes(':')) { const h = parseInt(t); const ampm = h < 12 ? 'AM' : 'PM'; const hr = h % 12 || 12; return '🕐 ' + hr + ':00 ' + ampm; } const lb = {'morning':'🌅 Morning','afternoon':'☀️ Afternoon','evening':'🌆 Evening'}; return lb[t] || t; }
        const timeLabels = {};

        let details = `<strong>Order #${res.order_id}</strong><br>`;
        details += isAdvanceOrder
            ? `📅 Scheduled: ${formattedDate}${preferredTime ? ' · ' + formatTime(preferredTime) : ''}<br>`
            : `📅 For: Today${preferredTime ? ' · ' + formatTime(preferredTime) : ''}<br>`;

        if (selectedFulfillmentMethod === 'pickup') {
            details += `📍 Pickup: Blk13 Lot8 Phase2 Menzi Village, Damilag<br>`;
        } else {
            details += `🚚 Delivery to: ${escH(address)}<br>`;
        }

        if (selectedPaymentMethod === 'gcash') {
            details += `<br>💳 <strong>GCash:</strong> Send payment to <strong>0926-402-0192</strong><br>Reference: Order #${res.order_id}`;
        } else {
            details += `💵 Pay in cash ${selectedFulfillmentMethod === 'pickup' ? 'at pickup' : 'upon delivery'}.`;
        }

        cart = [];
        renderCart();
        closeCheckoutModal();
        await loadProducts();
        showOrderConfirmed(res.order_id, details);
    } else {
        showToast('Error: ' + (res.error || 'Could not place order.'), 'error');
    }
}

// ═══════════════════════════════════════════════════════════════
//  INSPIRATION GALLERY
// ═══════════════════════════════════════════════════════════════
async function loadInspirationGallery() {
    const gallery = document.getElementById('inspirationGallery');
    const section = document.getElementById('inspirationSection');
    if (!gallery) return;
    try {
        const res = await api('get_inspirations');
        if (!res || res.error || !res.length) {
            section.style.display = 'block';
            gallery.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--text-muted);font-size:13px;background:#f9f4ff;border-radius:12px;border:2px dashed #e0d0ee;">🎂 No inspiration photos posted yet. Check back soon!</div>';
            return;
        }
        section.style.display = 'block';
        gallery.innerHTML = res.map(img => `
            <div style="border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);border:1px solid var(--border);background:var(--surface);">
                <div style="height:160px;overflow:hidden;">
                    <img src="${escH(img.photo)}" alt="${escH(img.name)}" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <div style="padding:10px 12px;">
                    <div style="font-size:13px;font-weight:700;color:var(--text);">${escH(img.name)}</div>
                    ${img.description ? `<div style="font-size:12px;color:var(--plum-mid);margin-top:3px;line-height:1.4;">${escH(img.description)}</div>` : ''}
                </div>
            </div>`
        ).join('');
    } catch(e) {
        section.style.display = 'none';
    }
}

// ═══════════════════════════════════════════════════════════════
//  CUSTOM CAKE ORDER
// ═══════════════════════════════════════════════════════════════
// ─── Custom cake: state vars ───────────────────────────────────
let customCakeFulfillment = 'delivery';

function selectCakeShape(shape) {
    document.getElementById('cakeShape').value = shape;
    var roundBtn  = document.getElementById('shapeRound');
    var squareBtn = document.getElementById('shapeSquare');
    if (shape === 'round') {
        roundBtn.style.background    = 'var(--plum)';
        roundBtn.style.color         = 'white';
        roundBtn.style.borderColor   = 'var(--plum)';
        squareBtn.style.background   = 'var(--surface2)';
        squareBtn.style.color        = 'var(--text)';
        squareBtn.style.borderColor  = 'var(--border)';
    } else {
        squareBtn.style.background   = 'var(--plum)';
        squareBtn.style.color        = 'white';
        squareBtn.style.borderColor  = 'var(--plum)';
        roundBtn.style.background    = 'var(--surface2)';
        roundBtn.style.color         = 'var(--text)';
        roundBtn.style.borderColor   = 'var(--border)';
    }
}
let aqmPaymentMethod = 'cod';       // used in Accept Quote Modal
let aqmScreenshotPath = '';         // used in Accept Quote Modal

function prefillCustomCakeForm() {
    const savedPhone   = localStorage.getItem('profilePhone') || '';
    const savedAddress = localStorage.getItem('profileAddress') || '';
    const contactEl    = document.getElementById('contactNumber');
    const addressEl    = document.getElementById('deliveryAddress');
    const banner       = document.getElementById('customCakeAutofillBanner');
    let prefilled = false;
    if (savedPhone && contactEl && !contactEl.value) {
        contactEl.value = savedPhone;
        prefilled = true;
    }
    if (savedAddress && addressEl && !addressEl.value) {
        addressEl.value = savedAddress;
        prefilled = true;
    }
    if (banner) banner.style.display = prefilled ? 'flex' : 'none';
    // Refresh time slots in case date is today
    filterCustomCakeTimeSlots();
}

function selectCustomFulfillment(method) {
    customCakeFulfillment = method;
    const dBtn = document.getElementById('customFulfillDelivery');
    const pBtn = document.getElementById('customFulfillPickup');
    const addrGroup = document.getElementById('customCakeAddressGroup');
    const addrArea  = document.getElementById('deliveryAddress');
    if (method === 'delivery') {
        dBtn.style.background = 'var(--plum)'; dBtn.style.color = 'white'; dBtn.style.borderColor = 'var(--plum)';
        pBtn.style.background = 'var(--surface2)'; pBtn.style.color = 'var(--text)'; pBtn.style.borderColor = 'var(--border)';
        addrGroup.style.display = 'block';
        addrArea.required = true;
    } else {
        pBtn.style.background = 'var(--plum)'; pBtn.style.color = 'white'; pBtn.style.borderColor = 'var(--plum)';
        dBtn.style.background = 'var(--surface2)'; dBtn.style.color = 'var(--text)'; dBtn.style.borderColor = 'var(--border)';
        addrGroup.style.display = 'none';
        addrArea.required = false;
    }
}

// selectCustomPayment / GCash helpers removed — payment now happens at quote acceptance

function filterCustomCakeTimeSlots() {
    const dateEl    = document.getElementById('customCakeDate');
    const timeEl    = document.getElementById('customCakeTime');
    const warningEl = document.getElementById('customCakeTimeWarning');
    if (!dateEl || !timeEl) return;
    const today = new Date();
    const selected = dateEl.value;
    const todayStr = today.toISOString().split('T')[0];
    const nowMins  = today.getHours() * 60 + today.getMinutes();
    let anyVisible = false;
    Array.from(timeEl.options).forEach(opt => {
        if (!opt.value) { anyVisible = true; return; }
        const [h, m] = opt.value.split(':').map(Number);
        const optMins = h * 60 + (m || 0);
        if (selected === todayStr && optMins <= nowMins) {
            opt.disabled = true; opt.style.display = 'none';
        } else {
            opt.disabled = false; opt.style.display = '';
            anyVisible = true;
        }
    });
    if (warningEl) warningEl.style.display = (!anyVisible && selected === todayStr) ? 'block' : 'none';
}

async function submitCustomCake(e) {
    e.preventDefault();

    // Validate contact
    const contactVal    = document.getElementById('contactNumber').value.trim();
    const contactDigits = contactVal.replace(/\D/g, '');
    if (contactDigits.length !== 11 || !/^09/.test(contactVal.replace(/\s/g,''))) {
        showToast('Contact number must be 11 digits and start with 09.', 'error');
        document.getElementById('contactNumber').focus();
        return;
    }

    // Validate address for delivery
    if (customCakeFulfillment === 'delivery') {
        const addr = document.getElementById('deliveryAddress').value.trim();
        if (!addr) {
            showToast('Please enter your delivery address.', 'error');
            document.getElementById('deliveryAddress').focus();
            return;
        }
    }

    // Upload reference image if provided
    let refImagePath = '';
    const refFile = document.getElementById('cakeReferenceImage').files[0];
    if (refFile) {
        try {
            const fd = new FormData();
            fd.append('file', refFile);
            const r  = await fetch('customer_api.php?action=upload_reference_image', { method:'POST', body:fd });
            const d  = await r.json();
            if (d.path) refImagePath = d.path;
        } catch(err) { /* continue without image */ }
    }

    const formData = {
        size:               document.getElementById('cakeSize').value,
        shape:              document.getElementById('cakeShape').value,
        flavor:             document.getElementById('cakeFlavor').value,
        layers:             document.getElementById('cakeLayers').value,
        frosting:           document.getElementById('cakeFrosting').value,
        color:              document.getElementById('cakeColor').value,
        message:            document.getElementById('cakeMessage').value,
        decorations:        document.getElementById('cakeDecorations').value,
        delivery_date:      document.getElementById('customCakeDate').value,
        delivery_time:      document.getElementById('customCakeTime').value,
        contact_number:     contactVal,
        delivery_address:   customCakeFulfillment === 'pickup'
                              ? 'PICKUP - Blk13 Lot8 Phase2 Menzi Village Damilag Manolo Fortich Bukidnon'
                              : document.getElementById('deliveryAddress').value.trim(),
        fulfillment_method: customCakeFulfillment,
        reference_image:    refImagePath
    };

    const res = await api('submit_custom_order', formData);

    if (res.success) {
        showToast(`Request #${res.request_id} submitted! We'll review it and send you a price quote.`, 'success', 6000);
        e.target.reset();
        customCakeFulfillment = 'delivery';
        selectCustomFulfillment('delivery');
        selectCakeShape('round');
        switchTab('customrequests');
    } else {
        showToast('Error: ' + (res.error || 'Failed to submit request'), 'error');
    }
}

// ═══════════════════════════════════════════════════════════════
//  MY ORDERS — TABBED VERSION
// ═══════════════════════════════════════════════════════════════
let _allMyOrders    = [];
let _ordTab         = 'active';
let _ordSort        = 'newest';
let _ordFilters     = [];
let _ordTempFilters = [];
let _ordExpanded    = {};
let _ordArchivedIds = new Set(
    (JSON.parse(localStorage.getItem('mj_archived_orders') || '[]'))
        .map(Number)
        .filter(id => Number.isInteger(id) && id > 0)
);

const ORD_SORT_OPTS = [
    { key:'newest',  label:'📅 Newest First'   },
    { key:'oldest',  label:'📅 Oldest First'   },
    { key:'highest', label:'💰 Highest Amount' },
    { key:'lowest',  label:'💰 Lowest Amount'  },
];
const ORD_FILTER_CFG = {
    active:   ['pending','approved','staff_approved','processing'],
    history:  ['completed','cancelled','rejected'],
    archived: [],
};
const ORD_STATUS_LABELS = {
    pending:'⏳ Pending', approved:'✅ Approved', staff_approved:'👤 Staff Approved',
    processing:'👨‍🍳 Processing', completed:'🎉 Completed', cancelled:'🚫 Cancelled', rejected:'❌ Rejected',
};

function _ordSaveArchived(){ localStorage.setItem('mj_archived_orders', JSON.stringify([..._ordArchivedIds])); }
function _isOrdActive(o){ return ['pending','approved','staff_approved','processing'].includes(o.status) && !_ordArchivedIds.has(o.id); }
function _isOrdHistory(o){ return ['completed','cancelled','rejected'].includes(o.status) && !_ordArchivedIds.has(o.id); }
function _isOrdArchived(o){ return _ordArchivedIds.has(o.id); }
function _canArchiveOrd(o){ return ['completed','cancelled','rejected'].includes(o.status); }

function switchOrderTab(tab){
    _ordTab = tab;
    _ordFilters = [];
    document.querySelectorAll('.ord-tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('ordTab'+tab.charAt(0).toUpperCase()+tab.slice(1)).classList.add('active');
    document.getElementById('ordToolbar').style.display = tab==='archived' ? 'none' : 'flex';
    _renderMyOrders();
    _renderOrdChips();
}

function _updateOrdCounts(){
    document.getElementById('ordCountActive').textContent   = _allMyOrders.filter(_isOrdActive).length;
    document.getElementById('ordCountHistory').textContent  = _allMyOrders.filter(_isOrdHistory).length;
    document.getElementById('ordCountArchived').textContent = _allMyOrders.filter(_isOrdArchived).length;
}

// Sort sheet
function openOrdSortSheet(){
    document.getElementById('ordSortOptions').innerHTML = ORD_SORT_OPTS.map(s=>`
        <div class="ord-sort-opt ${_ordSort===s.key?'active':''}" onclick="selectOrdSort('${s.key}')">
            ${s.label} ${_ordSort===s.key?'<span style="color:var(--plum);font-size:18px;font-weight:700;">✓</span>':''}
        </div>`).join('');
    document.getElementById('ordSortOverlay').style.display='flex';
}
function closeOrdSortSheet(e){ if(e.target===document.getElementById('ordSortOverlay')) document.getElementById('ordSortOverlay').style.display='none'; }
function selectOrdSort(key){
    _ordSort=key;
    document.getElementById('ordSortLabel').textContent={newest:'Newest',oldest:'Oldest',highest:'Highest ₱',lowest:'Lowest ₱'}[key];
    document.getElementById('ordSortOverlay').style.display='none';
    _renderMyOrders();
}

// Filter sheet
function openOrdFilterSheet(){
    _ordTempFilters=[..._ordFilters];
    const avail = ORD_FILTER_CFG[_ordTab]||[];
    document.getElementById('ordFilterChips').innerHTML = avail.map(f=>`
        <button class="ord-fchip ${_ordTempFilters.includes(f)?'active':''}" onclick="toggleOrdTempFilter('${f}',this)">
            ${ORD_STATUS_LABELS[f]||f}
        </button>`).join('');
    document.getElementById('ordFilterOverlay').style.display='flex';
}
function closeOrdFilterSheet(e){ if(e.target===document.getElementById('ordFilterOverlay')) document.getElementById('ordFilterOverlay').style.display='none'; }
function toggleOrdTempFilter(f,btn){
    btn.classList.toggle('active');
    if(_ordTempFilters.includes(f)) _ordTempFilters=_ordTempFilters.filter(x=>x!==f);
    else _ordTempFilters.push(f);
}
function applyOrdFilter(){
    _ordFilters=[..._ordTempFilters];
    document.getElementById('ordFilterOverlay').style.display='none';
    const btn=document.getElementById('ordFilterBtn');
    if(_ordFilters.length){ btn.classList.add('has-filter'); btn.innerHTML=`🔽 Filter <span style="background:rgba(255,255,255,0.25);border-radius:10px;padding:1px 6px;font-size:10px;font-weight:700;">${_ordFilters.length}</span>`; }
    else { btn.classList.remove('has-filter'); btn.innerHTML='🔽 Filter'; }
    _renderMyOrders();
    _renderOrdChips();
}
function removeOrdFilter(f){
    _ordFilters=_ordFilters.filter(x=>x!==f);
    applyOrdFilter();
}
function clearAllOrdFilters(){
    _ordFilters=[];
    applyOrdFilter();
}
function _renderOrdChips(){
    const el=document.getElementById('ordActiveChips');
    if(!_ordFilters.length){ el.innerHTML=''; return; }
    el.innerHTML=_ordFilters.map(f=>`
        <div class="ord-filter-chip-active">${ORD_STATUS_LABELS[f]||f}
            <button onclick="removeOrdFilter('${f}')">✕</button>
        </div>`).join('')
        +`<button onclick="clearAllOrdFilters()" style="background:none;border:none;cursor:pointer;color:#F44336;font-size:12px;font-weight:700;font-family:inherit;padding:3px 7px;border-radius:7px;">Clear All</button>`;
}

// Archive / Unarchive
function archiveOrd(id){ const n = Number(id); if (!n || n <= 0) return; _ordArchivedIds.add(n); _ordSaveArchived(); _updateOrdCounts(); _renderMyOrders(); }
function unarchiveOrd(id){ _ordArchivedIds.delete(Number(id)); _ordSaveArchived(); _updateOrdCounts(); _renderMyOrders(); }

// Items collapsible
function toggleOrdItems(id){ _ordExpanded[id]=!_ordExpanded[id]; _renderMyOrders(); }

function _renderOrdItems(o){
    const items=o.items||[];
    if(!items.length) return '';
    const expanded=!!_ordExpanded[o.id];
    const show=expanded?items:items.slice(0,2);
    let html=show.map(i=>`
        <div class="order-item-row"><span>${escH(i.name)} ×${i.quantity}</span><span>₱${(Number(i.price)*i.quantity).toFixed(2)}</span></div>`).join('');
    if(items.length>2){
        html+=expanded
            ?`<button class="ord-see-all-btn" onclick="toggleOrdItems(${o.id})">▴ Show less</button>`
            :`<button class="ord-see-all-btn" onclick="toggleOrdItems(${o.id})">▾ See all ${items.length} items</button>`;
    }
    return html;
}

function _renderOrdCard(o, tab){
    const archived=_ordArchivedIds.has(o.id);
    const payBadge=o.payment_method
        ?`<span style="padding:2px 8px;border-radius:8px;font-size:11px;font-weight:600;background:${o.payment_method==='gcash'?'#E3F2FD':'#E8F5E9'};color:${o.payment_method==='gcash'?'#0D47A1':'#1B5E20'};">${o.payment_method==='gcash'?'💳 GCash':'💵 Cash'}</span>`:'';

    // History/archived banner
    let banner='';
    if(tab!=='active'){
        if(o.status==='completed') banner=`<div class="ord-history-banner ord-banner-completed">🎉 Order completed — Thank you for your purchase!</div>`;
        else if(o.status==='cancelled') banner=`<div class="ord-history-banner ord-banner-cancelled">🚫 This order was cancelled.</div>`;
        else if(o.status==='rejected') banner=`<div class="ord-history-banner ord-banner-rejected">❌ Order rejected${o.review_notes?' — '+escH(o.review_notes):''}</div>`;
    }

    // Schedule tag
    let schedTag='';
    if(o.preferred_date){
        const d=new Date(o.preferred_date+'T00:00:00');
        const today=new Date();today.setHours(0,0,0,0);
        const days=Math.ceil((d-today)/86400000);
        function fmtT(t){if(!t)return'';if(t.includes(':')){const h=parseInt(t);return'🕐 '+(h%12||12)+':00 '+(h<12?'AM':'PM');}const lb={'morning':'🌅 Morning','afternoon':'☀️ Afternoon','evening':'🌆 Evening'};return lb[t]||t;}
        schedTag=`<div style="background:${days>0?'#FFF3E0':'#E8F5E9'};color:${days>0?'#E65100':'#1B5E20'};padding:5px 10px;border-radius:7px;font-size:12px;font-weight:600;margin-bottom:9px;">
            ${days>0?'📅 Scheduled: '+d.toLocaleDateString('en-US',{weekday:'short',month:'short',day:'numeric',year:'numeric'}):'📅 For Today'}
            ${o.preferred_time?' | '+fmtT(o.preferred_time):''}
        </div>`;
    }

    // Delivery info
    let dInfo='';
    if(o.delivery_name){
        const isPickup=o.fulfillment_method==='pickup'||(o.delivery_address&&o.delivery_address.toUpperCase().startsWith('PICKUP'));
        dInfo=`<div style="background:var(--surface2);border:1.5px solid ${isPickup?'rgba(74,144,226,.35)':'var(--border)'};padding:10px;border-radius:8px;margin:10px 0;font-size:13px;">
            <strong style="color:${isPickup?'#4a90e2':'var(--plum-mid)'};">${isPickup?'🏪 Pickup Details:':'🚚 Delivery Details:'}</strong><br>
            <span style="color:var(--text);">${escH(o.delivery_name)} | ${escH(o.delivery_phone)}<br>
            ${isPickup?'<strong style="color:var(--text);">Store Pickup</strong>':escH(o.delivery_address)}
            ${o.delivery_notes&&!o.delivery_notes.startsWith('QUOTED_')?'<br><span style="color:var(--text-muted);">Notes: '+escH(o.delivery_notes)+'</span>':''}
            </span></div>`;
    }

    // Stepper — only on active tab
    const stepper = (tab==='active') ? renderDeliveryStepper(o) : '';

    // Archive/unarchive button
    let archBtn='';
    if(tab==='history' && _canArchiveOrd(o)){
        archBtn=`<div style="padding:9px 16px 13px;border-top:1px solid var(--border-soft);display:flex;justify-content:flex-end;">
            <button class="ord-archive-btn do-archive" onclick="archiveOrd(${o.id})">🗄️ Archive</button>
        </div>`;
    } else if(tab==='archived'){
        archBtn=`<div style="padding:9px 16px 13px;border-top:1px solid var(--border-soft);display:flex;justify-content:flex-end;">
            <button class="ord-archive-btn do-unarchive" onclick="unarchiveOrd(${o.id})">↩ Unarchive</button>
        </div>`;
    }

    // Delivered line
    const deliveredLine=o.delivery_confirmed_at
        ?`<div style="color:#48bb78;font-size:12px;margin-bottom:8px;">✓ Delivered: ${new Date(o.delivery_confirmed_at).toLocaleString()}</div>`:'';

    // Rejection reason
    const rejNote=(o.status==='rejected'&&o.review_notes&&tab!=='active')
        ?`<div style="background:#fed7d7;border-left:4px solid #f56565;border-radius:6px;padding:10px 14px;margin-top:10px;font-size:13px;color:#742a2a;"><strong>❌ Rejection Reason:</strong><br><span>${escH(o.review_notes)}</span></div>`:'' ;

    // Cancel button only on pending active orders
    const cancelBtn=(o.status==='pending'&&tab==='active')
        ?`<button class="cancel-order-btn" onclick="cancelOrder(${o.id})">✕ Cancel Order</button>`:'';

    const cardStyle=tab==='archived'?'opacity:0.72;':'';

    return `<div class="order-card" style="${cardStyle}">
        <div style="padding:18px 18px 14px;">
            <div class="order-header">
                <span class="order-number">Order #${o.id}
                    ${tab==='archived'?'<span class="ord-archived-label">🗄️ Archived</span>':''}
                    ${payBadge}
                </span>
                <span class="order-status status-${o.status}">${ORD_STATUS_LABELS[o.status]||o.status}</span>
            </div>
            <div class="order-date">${new Date(o.order_date).toLocaleString()}</div>
            ${deliveredLine}${banner}${schedTag}${stepper}${dInfo}
            <div style="margin:10px 0;">${_renderOrdItems(o)}</div>
            <div class="order-total-row">
                <span style="color:var(--plum-mid);font-size:13px;">Items: ${(o.items||[]).reduce((s,i)=>s+i.quantity,0)}</span>
                <span class="order-total-val">₱${Number(o.total).toFixed(2)}</span>
            </div>
            ${cancelBtn}${rejNote}
        </div>
        ${archBtn}
    </div>`;
}

function _renderMyOrders(){
    let list;
    if(_ordTab==='active')   list=_allMyOrders.filter(_isOrdActive);
    if(_ordTab==='history')  list=_allMyOrders.filter(_isOrdHistory);
    if(_ordTab==='archived') list=_allMyOrders.filter(_isOrdArchived);

    if(_ordTab!=='archived'&&_ordFilters.length) list=list.filter(o=>_ordFilters.includes(o.status));

    list=[...list].sort((a,b)=>{
        if(_ordSort==='newest')  return new Date(b.order_date)-new Date(a.order_date);
        if(_ordSort==='oldest')  return new Date(a.order_date)-new Date(b.order_date);
        if(_ordSort==='highest') return Number(b.total)-Number(a.total);
        if(_ordSort==='lowest')  return Number(a.total)-Number(b.total);
        return 0;
    });

    const el=document.getElementById('ordersList');
    if(!el) return;
    if(!list.length){
        const msgs={active:{icon:'⚡',text:'No active orders.'},history:{icon:'📋',text:'No order history yet.'},archived:{icon:'🗄️',text:'No archived orders.'}};
        const m=msgs[_ordTab];
        el.innerHTML=`<div class="empty-state"><div class="icon">${m.icon}</div><p>${_ordFilters.length?'No orders match the selected filters.':m.text}</p></div>`;
        return;
    }
    el.innerHTML=list.map(o=>_renderOrdCard(o,_ordTab)).join('');
}

async function loadMyOrders() {
    const orders = await api('get_my_orders');
    const tsEl   = document.getElementById('ordersLastUpdated');
    if (tsEl) tsEl.textContent = 'Last updated: ' + new Date().toLocaleTimeString();
    _allMyOrders = (!orders || orders.error) ? [] : orders;

    // Purge any archived IDs that don't exist in the current server response.
    // This prevents newly-placed orders from accidentally appearing in Archived
    // because a stale/corrupted ID in localStorage matched the new order's ID.
    const validIds = new Set(_allMyOrders.map(o => Number(o.id)));
    let changed = false;
    _ordArchivedIds.forEach(id => {
        if (!validIds.has(id)) { _ordArchivedIds.delete(id); changed = true; }
    });
    if (changed) _ordSaveArchived();

    _updateOrdCounts();
    _renderMyOrders();
}

//  MY CUSTOM REQUESTS
// ═══════════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════
//  MY CUSTOM REQUESTS
// ═══════════════════════════════════════════════════════════════
async function loadMyCustomRequests() {
    const el = document.getElementById('customRequestsList');
    el.innerHTML = '<div class="empty-state"><div class="icon">⏳</div><p>Loading…</p></div>';
    const requests = await api('get_my_custom_orders');

    if (!requests || requests.error || !requests.length) {
        el.innerHTML = '<div class="empty-state"><div class="icon">🎨</div><p>No custom requests yet. Submit one from the Custom tab!</p></div>';
        return;
    }

    const statusCfg = {
        pending_quote: { bg:'var(--amber-bg)',  color:'var(--amber-text)',  icon:'⏳', label:'Awaiting Quote' },
        quoted:        { bg:'#dbeafe',          color:'#1e40af',            icon:'💰', label:'Quote Received' },
        accepted:      { bg:'var(--green-bg)',  color:'var(--green-text)',  icon:'✅', label:'Accepted'       },
        declined:      { bg:'var(--red-bg)',    color:'var(--red-text)',    icon:'❌', label:'Declined'       },
        cancelled:     { bg:'var(--surface2)',  color:'var(--text-muted)',  icon:'🚫', label:'Cancelled'      },
    };

    el.innerHTML = requests.map(r => {
        const qs = r.quote_status || (r.status === 'approved' ? 'accepted' : (r.status === 'pending' ? 'pending_quote' : r.status));
        const sc = statusCfg[qs] || statusCfg['pending_quote'];

        // Specs come from r.specs object (parsed from cake_specs JSON or review_notes fallback)
        const sp = r.specs || {};
        const specLines = [
            sp.size         && `<b>Size:</b> ${escH(sp.size)}`,
            sp.flavor       && `<b>Flavor:</b> ${escH(sp.flavor)}`,
            sp.layers       && `<b>Layers:</b> ${escH(sp.layers)}`,
            sp.frosting     && `<b>Frosting:</b> ${escH(sp.frosting)}`,
            sp.color_theme  && `<b>Color/Theme:</b> ${escH(sp.color_theme)}`,
            sp.message      && `<b>Message:</b> ${escH(sp.message)}`,
            sp.decorations  && `<b>Decorations:</b> ${escH(sp.decorations)}`,
        ].filter(Boolean).join('<br>') || '<span style="color:var(--text-muted);font-style:italic;">No details available</span>';

        const schedule = r.preferred_date
            ? `<div style="background:var(--amber-bg);color:var(--amber-text);padding:8px 12px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:10px;">
                 📅 Needed by: ${new Date(r.preferred_date+'T00:00:00').toLocaleDateString('en-US',{weekday:'short',month:'long',day:'numeric',year:'numeric'})}
                 ${r.preferred_time ? ' · '+escH(r.preferred_time) : ''}
                 · ${r.fulfillment_method === 'pickup' ? '🏪 Pickup' : '🚚 Delivery'}
               </div>` : '';

        const refImg = sp.reference_image
            ? `<div style="margin:10px 0;">
                 <div style="font-size:12px;color:var(--text-muted);font-weight:600;margin-bottom:4px;">📷 Reference Image:</div>
                 <img src="${escH(sp.reference_image)}" style="max-width:180px;max-height:140px;object-fit:cover;border-radius:8px;border:2px solid var(--border);">
               </div>` : '';

        // Quote block — shown when admin has set a price
        const quoteBlock = (qs === 'quoted' && qs !== 'accepted')
            ? `<div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:12px;padding:16px;margin:12px 0;">
                 <div style="font-size:13px;color:#1e40af;font-weight:600;margin-bottom:4px;">💰 Price Quote from Admin</div>
                 <div style="font-size:34px;font-weight:800;color:#1e3a8a;">₱${Number(r.quoted_price).toFixed(2)}</div>
                 ${r.quote_notes ? `<div style="font-size:13px;color:#1e40af;margin-top:6px;line-height:1.5;">${escH(r.quote_notes)}</div>` : ''}
                 <div style="font-size:11px;color:#3b82f6;margin-top:6px;">Quoted by ${escH(r.quoted_by)} · ${new Date(r.quoted_at).toLocaleString()}</div>
               </div>
               <div style="background:var(--amber-bg);border-left:3px solid var(--amber);border-radius:6px;padding:10px 12px;font-size:13px;color:var(--amber-text);margin-bottom:12px;">
                 ⚠️ Please review the quote. You can <strong>accept</strong> (choose payment method) or <strong>decline</strong>.
               </div>` : '';
        // Hide quoteBlock entirely if already accepted
        const quoteBlockFinal = (qs === 'accepted') ? '' : quoteBlock;

        // Accepted block
        const acceptedBlock = (qs === 'accepted' || r.status === 'approved')
            ? `<div style="background:var(--green-bg);border:1px solid var(--green);border-radius:10px;padding:14px;margin:10px 0;">
                 <div style="font-size:13px;font-weight:700;color:var(--green-text);">✅ You accepted the quote — Order #${r.order_id||'—'} has been created!</div>
                 <div style="font-size:24px;font-weight:800;color:var(--green-text);margin:6px 0;">₱${Number(r.quoted_price||0).toFixed(2)}</div>
                 <div style="font-size:13px;color:var(--green-text);">
                   Payment: ${r.payment_method === 'gcash' ? '💳 GCash' : '💵 Cash'}
                   ${r.gcash_ref_number ? ` · Ref: <strong>${escH(r.gcash_ref_number)}</strong>` : ''}
                 </div>
                 ${r.order_id ? `<button onclick="switchTab('orders')" style="margin-top:10px;padding:8px 16px;background:var(--green);color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">📋 View My Order</button>` : ''}
               </div>` : '';

        const declinedBlock = (qs === 'declined')
            ? `<div style="background:var(--red-bg);border-left:3px solid var(--red);border-radius:6px;padding:10px 12px;font-size:13px;color:var(--red-text);margin:10px 0;">
                 ❌ You declined the quote of ₱${Number(r.quoted_price||0).toFixed(2)}. You may submit a new request if you wish.
               </div>` : '';

        const pendingBlock = (qs === 'pending_quote' && !r.quoted_price)
            ? `<div style="background:var(--amber-bg);border-left:3px solid var(--amber);border-radius:6px;padding:10px 12px;font-size:13px;color:var(--amber-text);margin:10px 0;">
                 ⏳ Waiting for admin to review your request and send a price quote…
               </div>` : '';

        // Action buttons
        let actions = '';
        if (qs === 'quoted') {
            actions = `
                <button onclick="openAcceptQuoteModal(${r.id}, ${r.quoted_price}, '${escH((r.specs&&r.specs.flavor)||'Custom')} Cake (${escH((r.specs&&r.specs.size)||'?')})')"
                    style="flex:2;padding:13px;background:linear-gradient(135deg,#3d2268,#4a2878);color:white;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;">
                    ✅ Accept &amp; Pay
                </button>
                <button onclick="declineCustomQuote(${r.id})"
                    style="flex:1;padding:13px;background:var(--red-bg);color:var(--red-text);border:1px solid var(--red);border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;">
                    ❌ Decline
                </button>`;
        } else if (qs === 'pending_quote') {
            actions = `
                <button onclick="cancelCustomRequest(${r.id})"
                    style="padding:10px 18px;background:var(--surface2);color:var(--text-muted);border:1px solid var(--border);border-radius:8px;font-weight:600;font-size:13px;cursor:pointer;">
                    🚫 Cancel Request
                </button>`;
        }

        return `
        <div class="order-card" style="margin-bottom:16px;">
            <div class="order-header">
                <div>
                    <div class="order-number">🎨 Custom Request #${r.id}</div>
                    <div class="order-meta">Submitted: ${new Date(r.order_date).toLocaleString()}</div>
                </div>
                <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:${sc.bg};color:${sc.color};">
                    ${sc.icon} ${sc.label}
                </span>
            </div>
            ${schedule}
            <div style="background:var(--surface2);padding:14px;border-radius:8px;font-size:13px;line-height:1.7;margin-bottom:10px;">${specLines}</div>
            ${refImg}${pendingBlock}${quoteBlockFinal}${acceptedBlock}${declinedBlock}
            ${actions ? `<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid var(--border-soft);">${actions}</div>` : ''}
        </div>`;
    }).join('');
}

// ── Accept Quote Modal ────────────────────────────────────────
let _aqmPayment = 'cod';
let _aqmScreenshotPath = '';

function openAcceptQuoteModal(requestId, price, name) {
    _aqmPayment = 'cod';
    _aqmScreenshotPath = '';
    document.getElementById('aqmRequestId').value = requestId;
    document.getElementById('aqmPrice').textContent = '₱' + Number(price).toFixed(2);
    document.getElementById('aqmSummary').innerHTML =
        `<strong>${escH(name)}</strong><br>
         <span style="color:var(--text-muted);font-size:12px;">Request #${requestId} · Review the price and choose how you'll pay.</span>`;
    document.getElementById('aqmGcashRef').value = '';
    document.getElementById('aqmRefCount').textContent = '0/13';
    document.getElementById('aqmScreenshotFile').value = '';
    document.getElementById('aqmScreenshotPreview').style.display = 'none';
    document.getElementById('aqmScreenshotPlaceholder').style.display = 'block';
    selectAqmPayment('cod');
    document.getElementById('acceptQuoteModal').style.display = 'flex';
}

function closeAcceptQuoteModal() {
    document.getElementById('acceptQuoteModal').style.display = 'none';
}

function selectAqmPayment(method) {
    _aqmPayment = method;
    const codBtn   = document.getElementById('aqmBtnCOD');
    const gcashBtn = document.getElementById('aqmBtnGcash');
    const gcashSec = document.getElementById('aqmGcashSection');
    const codInfo  = document.getElementById('aqmCodInfo');
    if (method === 'gcash') {
        gcashBtn.style.background = 'var(--plum)'; gcashBtn.style.color = 'white'; gcashBtn.style.borderColor = 'var(--plum)';
        codBtn.style.background = 'var(--surface2)'; codBtn.style.color = 'var(--text)'; codBtn.style.borderColor = 'var(--border)';
        gcashSec.style.display = 'block';
        codInfo.style.display = 'none';
    } else {
        codBtn.style.background = 'var(--plum)'; codBtn.style.color = 'white'; codBtn.style.borderColor = 'var(--plum)';
        gcashBtn.style.background = 'var(--surface2)'; gcashBtn.style.color = 'var(--text)'; gcashBtn.style.borderColor = 'var(--border)';
        gcashSec.style.display = 'none';
        codInfo.style.display = 'block';
    }
}

function handleAqmScreenshot(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('aqmScreenshotThumb').src = e.target.result;
        document.getElementById('aqmScreenshotPlaceholder').style.display = 'none';
        document.getElementById('aqmScreenshotPreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function clearAqmScreenshot() {
    document.getElementById('aqmScreenshotFile').value = '';
    document.getElementById('aqmScreenshotThumb').src = '';
    document.getElementById('aqmScreenshotPreview').style.display = 'none';
    document.getElementById('aqmScreenshotPlaceholder').style.display = 'block';
    _aqmScreenshotPath = '';
}

async function confirmAcceptQuote() {
    const requestId = parseInt(document.getElementById('aqmRequestId').value);
    const gcashRef  = document.getElementById('aqmGcashRef').value.trim();

    if (_aqmPayment === 'gcash' && gcashRef.length !== 13) {
        showToast('Please enter a valid 13-digit GCash reference number.', 'error');
        document.getElementById('aqmGcashRef').focus();
        return;
    }

    // Upload GCash screenshot if provided
    _aqmScreenshotPath = '';
    const shotFile = document.getElementById('aqmScreenshotFile').files[0];
    if (shotFile) {
        try {
            const fd = new FormData();
            fd.append('file', shotFile);
            const r = await fetch('customer_api.php?action=upload_gcash_screenshot', { method:'POST', body:fd });
            const d = await r.json();
            if (d.path) _aqmScreenshotPath = d.path;
        } catch(err) { /* continue without screenshot */ }
    }

    const res = await api('accept_custom_quote', {
        request_id:       requestId,
        payment_method:   _aqmPayment,
        gcash_ref_number: _aqmPayment === 'gcash' ? gcashRef : '',
        gcash_screenshot: _aqmPayment === 'gcash' ? _aqmScreenshotPath : '',
    });

    if (res.success) {
        closeAcceptQuoteModal();
        showToast(`Quote accepted! Order #${res.order_id} has been created. We'll start preparing your cake!`, 'success', 6000);
        loadMyCustomRequests();
    } else {
        showToast('Error: ' + (res.error || 'Failed to accept quote'), 'error');
    }
}

async function declineCustomQuote(requestId) {
    const ok = await showConfirm('Decline Quote', 'Are you sure you want to decline this price quote?\nThe request will be marked as declined.', true);
    if (!ok) return;
    const res = await api('decline_custom_quote', { request_id: requestId });
    if (res.success) {
        showToast('Quote declined.', 'info');
        loadMyCustomRequests();
    } else {
        showToast('Error: ' + (res.error || 'Failed to decline'), 'error');
    }
}

async function cancelCustomRequest(requestId) {
    const ok = await showConfirm('Cancel Request', 'Cancel this custom cake request?\nThis can only be done before admin has sent a quote.', true);
    if (!ok) return;
    const res = await api('cancel_custom_order', { request_id: requestId });
    if (res.success) {
        showToast('Request cancelled.', 'info');
        loadMyCustomRequests();
    } else {
        showToast('Error: ' + (res.error || 'Cannot cancel at this stage'), 'error');
    }
}

// ═══════════════════════════════════════════════════════════════
//  DELIVERY / PICKUP STEPPER
// ═══════════════════════════════════════════════════════════════
function renderDeliveryStepper(order) {
    // Don't show for rejected/cancelled orders
    if (order.status === 'rejected' || order.status === 'cancelled') return '';

    // Detect pickup: use fulfillment_method first, then fall back to address prefix
    const isPickup = order.fulfillment_method === 'pickup'
        || (order.delivery_address && order.delivery_address.toUpperCase().startsWith('PICKUP'));

    // Two distinct step sets depending on fulfillment type
    const deliverySteps = [
        { key: 'order_placed', label: 'Order Placed',      icon: '📝', desc: 'We received your order' },
        { key: 'confirmed',    label: 'Order Confirmed',   icon: '✅', desc: 'Staff confirmed your order' },
        { key: 'preparing',    label: 'Preparing Order',   icon: '👨‍🍳', desc: 'Your order is being made' },
        { key: 'out_for_delivery', label: 'Out for Delivery', icon: '🚚', desc: 'On the way to you!' },
        { key: 'delivered',    label: 'Delivered',         icon: '🎉', desc: 'Order delivered!' },
    ];

    const pickupSteps = [
        { key: 'order_placed',     label: 'Order Placed',       icon: '📝', desc: 'We received your order' },
        { key: 'confirmed',        label: 'Order Confirmed',     icon: '✅', desc: 'Staff confirmed your order' },
        { key: 'preparing',        label: 'Preparing Order',     icon: '👨‍🍳', desc: 'Your order is being made' },
        { key: 'ready_for_pickup', label: 'Ready for Pickup',    icon: '🏪', desc: 'Come pick up your order!' },
        { key: 'delivered',        label: 'Picked Up',           icon: '🎉', desc: 'Order picked up!' },
    ];

    const steps = isPickup ? pickupSteps : deliverySteps;

    // Map order status / delivery_status → step key
    let deliveryStatus = order.delivery_status || '';
    if (!deliveryStatus || deliveryStatus === 'placed') {
        if      (order.status === 'pending')    deliveryStatus = 'order_placed';
        else if (order.status === 'approved')   deliveryStatus = 'confirmed';
        else if (order.status === 'processing') deliveryStatus = 'preparing';
        else if (order.status === 'completed')  deliveryStatus = 'delivered';
        else                                    deliveryStatus = 'order_placed';
    }

    // Normalise legacy 'placed' value
    if (deliveryStatus === 'placed') deliveryStatus = 'order_placed';

    // For pickup orders map out_for_delivery → ready_for_pickup so old data still renders correctly
    if (isPickup && deliveryStatus === 'out_for_delivery') deliveryStatus = 'ready_for_pickup';

    const currentIdx = steps.findIndex(s => s.key === deliveryStatus);
    const isCompleted = deliveryStatus === 'delivered';

    // Completion banner text differs by type
    const completionBanner = isPickup
        ? { emoji: '🎉', title: 'Order Picked Up!', sub: "Thank you for choosing Majoy's!" }
        : { emoji: '🎉', title: 'Order Delivered!',  sub: "Thank you for ordering from Majoy's!" };

    let html = `<div style="background: var(--surface2); border-radius:12px; padding:16px; margin:14px 0;">`;

    if (isCompleted) {
        html += `<div style="background:linear-gradient(135deg,#c6f6d5,#9ae6b4); border-radius:10px; padding:14px; text-align:center; margin-bottom:12px;">
            <div style="font-size:28px; margin-bottom:4px;">${completionBanner.emoji}</div>
            <div style="color:#22543d; font-weight:700; font-size:15px;">${completionBanner.title}</div>
            <div style="color:#276749; font-size:12px; margin-top:2px;">${completionBanner.sub}</div>
        </div>`;
    }

    html += `<div style="position:relative;">`;

    steps.forEach((step, idx) => {
        const isDone    = idx < currentIdx || isCompleted;
        const isCurrent = idx === currentIdx && !isCompleted;

        const circleStyle = isDone
            ? 'background:#48bb78; color:white;'
            : isCurrent
                ? 'background:#4299e1; color:white; animation:stepPulse 1.8s infinite;'
                : 'background:var(--border); color:var(--text-muted);';

        const labelColor = isDone ? '#22543d' : isCurrent ? '#2c5282' : '#6a4a8a';
        const isLast = idx === steps.length - 1;

        html += `
        <div style="display:flex; align-items:flex-start; gap:12px; padding-bottom:${isLast ? '0' : '12px'}; position:relative;">
            ${!isLast ? `<div style="position:absolute; left:11px; top:24px; width:2px; height:calc(100% - 8px); background:${isDone ? '#48bb78' : '#e0d0ee'};"></div>` : ''}
            <div style="width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; flex-shrink:0; z-index:1; ${circleStyle}">
                ${isDone ? '✓' : step.icon}
            </div>
            <div style="flex:1; padding-top:2px;">
                <div style="font-weight:700; font-size:13px; color:${labelColor};">${step.label}</div>
                <div style="font-size:11px; color:var(--text-muted); margin-top:1px;">${isCurrent ? '← ' : ''}${step.desc}</div>
            </div>
        </div>`;
    });

    html += `</div></div>`;
    return html;
}

// ═══════════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════════
function escH(s) { 
    const d = document.createElement('div'); 
    d.textContent = s; 
    return d.innerHTML; 
}

function calcDays(p) {
    if (!p.production_date || !p.shelf_life_days) return null;
    const expiry = new Date(new Date(p.production_date).getTime() + p.shelf_life_days * 86400000);
    return Math.ceil((expiry - new Date()) / 86400000);
}

function shelfStatus(days) {
    if (days === null) return { cls:'', txt:'N/A' };
    if (days < 0) return { cls:'shelf-critical', txt:'EXPIRED' };
    if (days === 0) return { cls:'shelf-critical', txt:'Expires Today' };
    if (days <= 2) return { cls:'shelf-critical', txt: days+'d left' };
    if (days <= 5) return { cls:'shelf-warning', txt: days+'d left' };
    return { cls:'shelf-fresh', txt: days+'d left' };
}

// ═══════════════════════════════════════════════════════════════
//  TOAST NOTIFICATIONS
// ═══════════════════════════════════════════════════════════════
function showToast(message, type = 'success', duration = 4000) {
    const icons = { success:'✅', error:'❌', info:'ℹ️', warning:'⚠️' };
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${icons[type]||'📢'}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'toastOut .3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ═══════════════════════════════════════════════════════════════
//  STYLED CONFIRM MODAL (replaces browser confirm())
// ═══════════════════════════════════════════════════════════════
let confirmModalResolve = null;
function showConfirm(title, message, danger = false) {
    return new Promise(resolve => {
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalMsg').textContent   = message;
        const yesBtn = document.getElementById('confirmYesBtn');
        yesBtn.className = danger ? 'confirm-yes danger' : 'confirm-yes';
        yesBtn.textContent = danger ? 'Yes, proceed' : 'Confirm';
        document.getElementById('confirmModalOverlay').style.display = 'flex';
        confirmModalResolve = (val) => {
            document.getElementById('confirmModalOverlay').style.display = 'none';
            resolve(val);
        };
    });
}

// ═══════════════════════════════════════════════════════════════
//  ORDER CONFIRMED SCREEN
// ═══════════════════════════════════════════════════════════════
function showOrderConfirmed(orderId, details) {
    document.getElementById('orderConfirmedDetails').innerHTML = details;
    document.getElementById('orderConfirmedOverlay').style.display = 'flex';
}
function closeOrderConfirmed() {
    document.getElementById('orderConfirmedOverlay').style.display = 'none';
    switchTab('orders');
}

// ═══════════════════════════════════════════════════════════════
//  SEARCH / FILTER PRODUCTS
// ═══════════════════════════════════════════════════════════════
function filterProducts() {
    const query = document.getElementById('menuSearch').value.toLowerCase().trim();
    const grid  = document.getElementById('productsGrid');
    if (!productsCache || !productsCache.length) return;

    const filtered = query
        ? productsCache.filter(p => p.name.toLowerCase().includes(query) || (p.description||'').toLowerCase().includes(query))
        : productsCache;

    if (!filtered.length) {
        grid.innerHTML = `<div class="empty-state"><div class="icon">🔍</div><p>No products match "<strong>${escH(query)}</strong>"</p></div>`;
        return;
    }
    renderProductGrid(filtered);
}

// ═══════════════════════════════════════════════════════════════
//  CANCEL ORDER
// ═══════════════════════════════════════════════════════════════
async function cancelOrder(orderId) {
    const ok = await showConfirm('Cancel Order', `Cancel Order #${orderId}?\n\nThis cannot be undone. Your order will be marked as cancelled.`, true);
    if (!ok) return;
    const res = await api('cancel_order', { order_id: orderId });
    if (res.success) {
        showToast(`Order #${orderId} has been cancelled.`, 'info');
        loadMyOrders();
    } else {
        showToast('Could not cancel: ' + (res.error || 'Unknown error'), 'error');
    }
}

// ═══════════════════════════════════════════════════════════════
//  PROFILE
// ═══════════════════════════════════════════════════════════════
async function loadProfile() {
    try {
        const data = await api('get_profile');
        if (data && !data.error) {
            document.getElementById('profileFullname').value = data.Fullname || '<?= htmlspecialchars($_SESSION["fullname"]) ?>';
            document.getElementById('profileUsername').value = data.username || '<?= htmlspecialchars($_SESSION["username"]) ?>';
            document.getElementById('profilePhone').value    = data.phone    || '';
            document.getElementById('profileAddress').value  = data.address  || '';
            // Keep localStorage in sync for checkout autofill
            if (data.phone)    localStorage.setItem('profilePhone',    data.phone);
            if (data.address)  localStorage.setItem('profileAddress',  data.address);
            if (data.Fullname) localStorage.setItem('profileFullname', data.Fullname);
            return;
        }
    } catch(e) { /* fallback below */ }
    // Fallback to localStorage if API fails
    document.getElementById('profileFullname').value = localStorage.getItem('profileFullname') || '<?= htmlspecialchars($_SESSION["fullname"]) ?>';
    document.getElementById('profileUsername').value = '<?= htmlspecialchars($_SESSION["username"]) ?>';
    document.getElementById('profilePhone').value    = localStorage.getItem('profilePhone') || '';
    document.getElementById('profileAddress').value  = localStorage.getItem('profileAddress') || '';
}
async function saveProfile() {
    const fullname = document.getElementById('profileFullname').value.trim();
    const phone    = document.getElementById('profilePhone').value.trim();
    const address  = document.getElementById('profileAddress').value.trim();
    if (!fullname) { showToast('Name cannot be empty.', 'error'); return; }

    const res = await api('save_profile', { fullname, phone, address });
    if (res.success) {
        // Keep localStorage in sync for checkout autofill
        localStorage.setItem('profileFullname', fullname);
        localStorage.setItem('profilePhone',    phone);
        localStorage.setItem('profileAddress',  address);
        showToast('Profile saved successfully! Details will auto-fill at checkout.', 'success');
    } else {
        showToast('Error: ' + (res.error || 'Failed to save profile.'), 'error');
    }
}

async function changePassword() {
    const current = document.getElementById('currentPassword').value;
    const newPass  = document.getElementById('newPassword').value;
    const confirm  = document.getElementById('confirmPassword').value;
    if (!current || !newPass || !confirm) { showToast('Please fill in all password fields.', 'error'); return; }
    if (newPass.length < 6) { showToast('New password must be at least 6 characters.', 'error'); return; }
    if (newPass !== confirm) { showToast('New passwords do not match.', 'error'); return; }
    const res = await api('change_password', { current_password: current, new_password: newPass });
    if (res.success) {
        showToast('Password updated successfully!', 'success');
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
    } else {
        showToast(res.error || 'Failed to update password.', 'error');
    }
}

// ═══════════════════════════════════════════════════════════════
//  INIT
// ═══════════════════════════════════════════════════════════════
// GCash ref hint updater
function updateGcashRefHint() {
    const input = document.getElementById('gcashRefNumber');
    const hint  = document.getElementById('gcashRefHint');
    const len   = input.value.length;
    if (len === 0) {
        hint.style.display = 'none';
    } else if (len < 13) {
        hint.textContent   = `⚠ ${13 - len} more digit${13 - len > 1 ? 's' : ''} needed`;
        hint.style.color   = '#ed8936';
        hint.style.display = 'block';
    } else {
        hint.textContent   = '✓ 13 digits — good to go!';
        hint.style.color   = '#48bb78';
        hint.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    loadInspirationGallery();
    checkCutoffTime();
    // Re-check cutoff every 5 minutes
    setInterval(checkCutoffTime, 5 * 60 * 1000);

    // GCash screenshot preview
    document.getElementById('gcashScreenshotFile').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('gcashScreenshotThumb').src = e.target.result;
                document.getElementById('gcashScreenshotPreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Set min date to today using client's local date (prevents picking past dates)
    const todayStr = new Date().toLocaleDateString('en-CA'); // yields YYYY-MM-DD
    const dateInput = document.getElementById('preferredDate');
    if (dateInput) {
        dateInput.min = todayStr;
        // If a past date somehow gets set, clear it
        dateInput.addEventListener('change', () => {
            if (dateInput.value < todayStr) {
                dateInput.value = '';
                showToast('❌ Past dates are not allowed. Please select today or a future date.', 'error');
            }
        });
    }
});

// Close modal when clicking outside
document.getElementById('checkoutModal').addEventListener('click', (e) => {
    if (e.target.id === 'checkoutModal') {
        closeCheckoutModal();
    }
});
</script>

<!-- TOAST CONTAINER -->
<div id="toastContainer"></div>

<!-- ORDER CONFIRMED OVERLAY -->
<div id="orderConfirmedOverlay" class="order-confirmed-overlay" style="display:none;">
    <div class="order-confirmed-card">
        <div class="order-confirmed-icon">🎉</div>
        <div class="order-confirmed-title">Order Placed!</div>
        <div class="order-confirmed-sub">Thank you for your order 🍰</div>
        <div class="order-confirmed-details" id="orderConfirmedDetails"></div>
        <button class="order-confirmed-btn" onclick="closeOrderConfirmed()">View My Orders</button>
    </div>
</div>

<!-- CONFIRM MODAL -->
<div id="confirmModalOverlay" class="confirm-modal-overlay" style="display:none;">
    <div class="confirm-modal">
        <h3 id="confirmModalTitle">Confirm</h3>
        <p id="confirmModalMsg"></p>
        <div class="confirm-modal-btns">
            <button class="confirm-no" onclick="confirmModalResolve(false)">Cancel</button>
            <button class="confirm-yes" id="confirmYesBtn" onclick="confirmModalResolve(true)">Confirm</button>
        </div>
    </div>
</div>


<script>
function mjCycleTheme() {
    var themes = ['light', 'dark'];
    var icons  = { light: '☀️', dark: '🌙' }; // shows CURRENT theme's icon
    var cur    = document.documentElement.getAttribute('data-theme') || 'light';
    // If somehow 'logo' is stored, reset to light
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