<?php
session_start();
include 'db.php';

// ── Only allow Admin and Owner ────────────────────────────────
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'];
$fullname = $_SESSION['fullname'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Majoy's Homemade Sweets</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #b89cc8 0%, #a07ab5 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #4a3d5c;
            font-size: 32px;
        }
        
        .header .user-info {
            text-align: right;
        }
        
        .header .user-info strong {
            color: #6b5b7f;
            font-size: 16px;
        }
        
        .header .user-info span {
            color: #8b7ba8;
            font-size: 14px;
        }
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border: 2px solid #e8d5f2;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            color: #6b5b7f;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #4a3d5c;
        }
        
        /* Filters */
        .filters {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background: #f7f5fa;
            border-radius: 12px;
        }
        
        .filters input,
        .filters select {
            padding: 12px 16px;
            border: 2px solid #e8d5f2;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }
        
        .filters input:focus,
        .filters select:focus {
            border-color: #a07ab5;
        }
        
        .filters input[type="text"] {
            flex: 1;
            max-width: 300px;
        }
        
        .filters select {
            min-width: 150px;
        }
        
        .filters .spacer {
            flex: 1;
        }
        
        .filters button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #a07ab5, #8b6ba0);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(160, 122, 181, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #6b5b7f;
            border: 2px solid #e8d5f2;
        }
        
        .btn-secondary:hover {
            background: #f7f5fa;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 2px solid #e8d5f2;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f0e5f8;
        }
        
        thead th {
            padding: 16px;
            text-align: left;
            color: #4a3d5c;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid #e8d5f2;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #faf7fd;
        }
        
        tbody td {
            padding: 16px;
            color: #4a3d5c;
            font-size: 14px;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #22543d;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-staff_approved {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-approved {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .status-processing {
            background: #fce7f3;
            color: #831843;
        }
        
        .status-cancelled,
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Print Receipt Button */
        .btn-print-receipt {
            padding: 6px 12px;
            background: linear-gradient(135deg, #5a3f84, #7B5EA7);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .btn-print-receipt:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(90, 63, 132, 0.4);
        }
        
        .btn-print-receipt:active {
            transform: translateY(0);
        }
        
        /* Back button */
        .back-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #a07ab5, #8b6ba0);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(160, 122, 181, 0.3);
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(160, 122, 181, 0.4);
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8b7ba8;
        }
        
        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-state p {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Orders Management</h1>
            <div class="user-info">
                <strong><?php echo htmlspecialchars($fullname); ?></strong><br>
                <span><?php echo ucfirst($role); ?></span>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Orders Today</h3>
                <div class="value" id="totalToday">-</div>
            </div>
            <div class="stat-card">
                <h3>Completed Orders</h3>
                <div class="value" id="completedToday">-</div>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <div class="value" id="pendingToday">-</div>
            </div>
            <div class="stat-card">
                <h3>Revenue Today</h3>
                <div class="value" id="revenueToday">₱0</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <input type="text" id="searchBox" placeholder="🔍 Search by Order ID or Customer...">
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="staff_approved">Staff Approved</option>
                <option value="approved">Approved</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="rejected">Rejected</option>
            </select>
            <div class="spacer"></div>
            <button class="btn-secondary" onclick="exportOrders()">📤 Export</button>
            <button class="btn-secondary" onclick="printOrders()">🖨️ Print</button>
            <button class="btn-primary" onclick="createNewOrder()">➕ New Order</button>
        </div>
        
        <!-- Orders Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ORDER ID</th>
                        <th>DATE & TIME</th>
                        <th>CUSTOMER</th>
                        <th>ITEMS</th>
                        <th>TOTAL AMOUNT</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="ordersTable">
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="icon">⏳</div>
                                <p>Loading orders...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <button class="back-btn" onclick="goBack()">← BACK</button>
    
    <script>
        // ═══════════════════════════════════════════════════════════════
        //  API HELPER
        // ═══════════════════════════════════════════════════════════════
        async function api(action, data = null) {
            const opts = { method: data ? 'POST' : 'GET' };
            if (data) {
                opts.headers = { 'Content-Type': 'application/json' };
                opts.body = JSON.stringify(data);
            }
            const res = await fetch(`Api.php?action=${action}`, opts);
            return await res.json();
        }
        
        // ═══════════════════════════════════════════════════════════════
        //  LOAD ORDERS
        // ═══════════════════════════════════════════════════════════════
        async function loadOrders() {
            try {
                const orders = await api('get_all_orders_admin');
                const tbody = document.getElementById('ordersTable');
                
                if (!orders || orders.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="icon">📦</div>
                                    <p>No orders found</p>
                                </div>
                            </td>
                        </tr>`;
                    return;
                }
                
                // Apply filters
                const searchText = document.getElementById('searchBox').value.toLowerCase();
                const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
                
                let filtered = orders.filter(order => {
                    const matchesSearch = !searchText || 
                        order.id.toString().includes(searchText) ||
                        (order.staff_name && order.staff_name.toLowerCase().includes(searchText));
                    
                    const matchesStatus = !statusFilter || 
                        (order.status && order.status.toLowerCase() === statusFilter);
                    
                    return matchesSearch && matchesStatus;
                });
                
                if (filtered.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="icon">🔍</div>
                                    <p>No orders match your filters</p>
                                </div>
                            </td>
                        </tr>`;
                    return;
                }
                
                tbody.innerHTML = filtered.map(order => {
                    const orderId = 'ORD-' + String(order.id).padStart(5, '0');
                    const date = new Date(order.order_date);
                    const dateStr = date.toLocaleDateString('en-US', { 
                        month: 'short', day: 'numeric', year: 'numeric' 
                    });
                    const timeStr = date.toLocaleTimeString('en-US', { 
                        hour: 'numeric', minute: '2-digit', hour12: true 
                    });
                    const dateTime = `${dateStr} • ${timeStr}`;
                    
                    const items = (order.items || []).reduce((sum, item) => sum + item.quantity, 0);
                    const itemsText = `${items} item${items !== 1 ? 's' : ''}`;
                    
                    const total = `₱${parseFloat(order.total).toFixed(2)}`;
                    
                    const status = (order.status || 'pending').toLowerCase().replace(/ /g, '_');
                    const statusText = order.status ? order.status.toUpperCase().replace(/_/g, ' ') : 'PENDING';
                    
                    return `
                        <tr>
                            <td><strong>${orderId}</strong></td>
                            <td>${dateTime}</td>
                            <td>${escapeHtml(order.staff_name || 'N/A')}</td>
                            <td>${itemsText}</td>
                            <td><strong>${total}</strong></td>
                            <td><span class="status-badge status-${status}">${statusText}</span></td>
                            <td>
                                <button class="btn-print-receipt" onclick="printReceipt(${order.id})" title="Print 48mm Receipt">
                                    🖨️ Receipt
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
                
                // Store orders data globally for receipt printing
                window._ordersData = orders;
                
            } catch (error) {
                console.error('Error loading orders:', error);
                document.getElementById('ordersTable').innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="icon">❌</div>
                                <p>Error loading orders</p>
                            </div>
                        </td>
                    </tr>`;
            }
        }
        
        // ═══════════════════════════════════════════════════════════════
        //  LOAD STATISTICS
        // ═══════════════════════════════════════════════════════════════
        async function loadStatistics() {
            try {
                const stats = await api('get_orders_statistics');
                
                if (stats) {
                    document.getElementById('totalToday').textContent = stats.total_today || '0';
                    document.getElementById('completedToday').textContent = stats.completed_today || '0';
                    document.getElementById('pendingToday').textContent = stats.pending_today || '0';
                    document.getElementById('revenueToday').textContent = 
                        '₱' + parseFloat(stats.revenue_today || 0).toFixed(2);
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }
        
        // ═══════════════════════════════════════════════════════════════
        //  HELPER FUNCTIONS
        // ═══════════════════════════════════════════════════════════════
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function exportOrders() {
            alert('Export functionality coming soon!\nThis will export orders to CSV/Excel format.');
        }
        
        function printOrders() {
            window.print();
        }
        
        function createNewOrder() {
            window.location.href = 'admin.php#pos';
        }
        
        function goBack() {
            <?php if ($role === 'owner'): ?>
                window.location.href = 'owner.php';
            <?php else: ?>
                window.location.href = 'admin.php';
            <?php endif; ?>
        }
        
        // ═══════════════════════════════════════════════════════════════
        //  EVENT LISTENERS
        // ═══════════════════════════════════════════════════════════════
        document.getElementById('searchBox').addEventListener('input', loadOrders);
        document.getElementById('statusFilter').addEventListener('change', loadOrders);
        
        // ═══════════════════════════════════════════════════════════════
        //  PRINT RECEIPT — GOOJPRT PT210 @ 48 mm
        // ═══════════════════════════════════════════════════════════════
        function printReceipt(orderId) {
            const orders = window._ordersData || [];
            const order  = orders.find(o => o.id == orderId);
            if (!order) { alert('Order data not available. Please wait for the page to load.'); return; }

            const ordNum  = 'ORD-' + String(order.id).padStart(5, '0');
            const date    = new Date(order.order_date);
            const dateStr = date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
            const timeStr = date.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true });
            const items   = order.items || [];
            const total   = parseFloat(order.total || 0);
            const status  = (order.status || 'pending').toUpperCase().replace(/_/g, ' ');

            // Build items rows — truncate name to fit 48mm monospace
            function padL(s, n) { return String(s).padStart(n, ' '); }
            function padR(s, n) { return String(s).padEnd(n, ' '); }

            let itemsHtml = '';
            items.forEach(item => {
                const name  = (item.product_name || item.name || 'Item').substring(0, 18);
                const qty   = item.quantity || 1;
                const price = parseFloat(item.price || 0);
                const sub   = (qty * price).toFixed(2);
                itemsHtml += `
                    <tr>
                        <td class="item-name">${name}</td>
                        <td class="item-qty">${qty}</td>
                        <td class="item-price">&#8369;${parseFloat(price).toFixed(2)}</td>
                        <td class="item-sub">&#8369;${sub}</td>
                    </tr>`;
            });

            const receiptHtml = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt ${ordNum}</title>
<style>
  /* ── GOOJPRT PT210 — 48 mm paper ── */
  @page {
    size: 48mm auto;          /* exact roll width, auto height */
    margin: 1mm 1.5mm;        /* minimal margins for full print area */
  }
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    font-family: 'Courier New', Courier, monospace;
    font-size: 7pt;
    width: 45mm;              /* printable area inside 48mm */
    color: #000;
    background: #fff;
  }
  .receipt { width: 100%; }

  /* Header */
  .hdr { text-align: center; margin-bottom: 1mm; }
  .shop-name {
    font-size: 11pt;
    font-weight: bold;
    letter-spacing: 0.5pt;
    line-height: 1.3;
  }
  .shop-tag {
    font-size: 6.5pt;
    letter-spacing: 0.3pt;
  }
  .shop-address {
    font-size: 5.8pt;
    margin-top: 0.5mm;
    line-height: 1.4;
  }
  .shop-contact { font-size: 5.8pt; margin-top: 0.5mm; }

  /* Divider */
  .div { border-top: 1px dashed #000; margin: 1.5mm 0; }

  /* Meta rows */
  .meta { width: 100%; font-size: 6.5pt; border-collapse: collapse; }
  .meta td { padding: 0.3mm 0; vertical-align: top; }
  .meta .lbl { font-weight: bold; white-space: nowrap; padding-right: 1.5mm; }

  /* Items table */
  .items { width: 100%; font-size: 6pt; border-collapse: collapse; }
  .items thead tr { border-bottom: 1px solid #000; }
  .items th { font-size: 5.5pt; text-align: left; padding: 0.5mm 0; }
  .items th.r, .items td.r { text-align: right; }
  .items td { padding: 0.6mm 0; vertical-align: top; }
  .item-name { max-width: 18mm; word-break: break-word; }
  .item-qty  { text-align: center; width: 6mm; }
  .item-price{ text-align: right; width: 11mm; }
  .item-sub  { text-align: right; width: 11mm; }

  /* Total */
  .totals { width: 100%; font-size: 7pt; border-collapse: collapse; margin-top: 1mm; }
  .totals td { padding: 0.4mm 0; }
  .totals .bold { font-weight: bold; font-size: 8pt; }
  .totals .r { text-align: right; }

  /* Status pill */
  .status-row { text-align: center; margin: 1.5mm 0; }
  .status-pill {
    display: inline-block;
    font-size: 6pt;
    font-weight: bold;
    border: 1px solid #000;
    padding: 0.5mm 2mm;
    border-radius: 2mm;
    letter-spacing: 0.5pt;
  }

  /* Footer */
  .footer { text-align: center; font-size: 6pt; margin-top: 1.5mm; line-height: 1.5; }
  .footer .big { font-size: 7.5pt; font-weight: bold; }
</style>
</head>
<body>
<div class="receipt">

  <!-- HEADER -->
  <div class="hdr">
    <div class="shop-name">Majoy's</div>
    <div class="shop-tag">Homemade Sweets</div>
    <div class="shop-address">Blk13 Lot8 Phase2 Menzi Village<br>Damilag, Manolo Fortich, Bukidnon</div>
    <div class="shop-contact">0926-402-0192</div>
  </div>

  <div class="div"></div>

  <!-- ORDER INFO -->
  <table class="meta">
    <tr><td class="lbl">Order#</td><td>${ordNum}</td></tr>
    <tr><td class="lbl">Date</td><td>${dateStr}</td></tr>
    <tr><td class="lbl">Time</td><td>${timeStr}</td></tr>
    <tr><td class="lbl">Served by</td><td>${order.staff_name || 'N/A'}</td></tr>
  </table>

  <div class="div"></div>

  <!-- ITEMS -->
  <table class="items">
    <thead>
      <tr>
        <th>Item</th>
        <th style="text-align:center">Qty</th>
        <th style="text-align:right">Price</th>
        <th style="text-align:right">Sub</th>
      </tr>
    </thead>
    <tbody>
      ${itemsHtml}
    </tbody>
  </table>

  <div class="div"></div>

  <!-- TOTALS -->
  <table class="totals">
    <tr>
      <td class="bold">TOTAL</td>
      <td class="bold r">&#8369;${total.toFixed(2)}</td>
    </tr>
  </table>

  <div class="div"></div>

  <!-- STATUS -->
  <div class="status-row">
    <span class="status-pill">${status}</span>
  </div>

  <div class="div"></div>

  <!-- FOOTER -->
  <div class="footer">
    <div class="big">Thank you!</div>
    <div>Freshly Baked with Love &#10084;</div>
    <div style="margin-top:1mm">FB: majoys homemade sweets</div>
  </div>

  <!-- Feed space so cutter lands below text -->
  <div style="height:8mm"></div>

</div>
</body>
</html>`;

            const win = window.open('', '_blank', 'width=220,height=600,toolbar=0,menubar=0,scrollbars=1');
            win.document.write(receiptHtml);
            win.document.close();
            win.focus();
            // Short delay lets the page render before print dialog opens
            setTimeout(() => { win.print(); }, 350);
        }

        // ═══════════════════════════════════════════════════════════════
        //  INITIALIZE
        // ═══════════════════════════════════════════════════════════════
        loadOrders();
        loadStatistics();
        
        // Refresh every 30 seconds
        setInterval(() => {
            loadOrders();
            loadStatistics();
        }, 30000);
    </script>
</body>
</html>