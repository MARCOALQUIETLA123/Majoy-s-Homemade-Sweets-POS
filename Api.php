<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// ── Only allow logged-in staff / admin ───────────────────────
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner', 'staff'], true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$action  = $_GET['action'] ?? '';
$role    = $_SESSION['role'];                 // 'admin' | 'owner' | 'staff'
$isAdmin = me_is_admin();
$data    = read_json();

try {

// ══════════════════════════════════════════════════════════════
//  PRODUCTS  (PRODUCTS + INVENTORY_BATCHES)
// ══════════════════════════════════════════════════════════════
if ($action === 'get_products') {
    json_out($isAdmin ? products_admin($conn) : products_in_stock($conn));
}

if ($action === 'add_product' && $isAdmin) {
    json_out(['success' => true, 'id' => product_add($conn, $data)]);
}

if ($action === 'update_product' && $isAdmin) {
    json_out(['success' => product_update($conn, $data)]);
}

if ($action === 'archive_product' && $isAdmin) {
    $n = db_exec($conn, "UPDATE products SET is_archived = 1 WHERE id = ? AND is_archived = 0", 'i', [(int)($data['id'] ?? 0)]);
    json_out(['success' => $n > 0]);
}

if ($action === 'unarchive_product' && $isAdmin) {
    $n = db_exec($conn, "UPDATE products SET is_archived = 0 WHERE id = ? AND is_archived = 1", 'i', [(int)($data['id'] ?? 0)]);
    json_out(['success' => $n > 0]);
}

// CRUA policy: records are archived, never deleted
if ($action === 'delete_archived_product' && $isAdmin) {
    json_err('Records are archived, never deleted (CRUA policy). The product stays archived.');
}

if ($action === 'get_archived_products' && $isAdmin) {
    json_out(products_archived($conn));
}

if ($action === 'get_shelf_life' && $isAdmin) {
    $rows = products_admin($conn);
    usort($rows, fn($a, $b) => ($a['days_remaining'] ?? 9999) <=> ($b['days_remaining'] ?? 9999));
    json_out($rows);
}

// ══════════════════════════════════════════════════════════════
//  CUSTOM CAKE REQUESTS  (CUSTOM_ORDERS)
// ══════════════════════════════════════════════════════════════
if ($action === 'get_custom_orders_admin' && $isAdmin) {
    json_out(custom_requests($conn));
}

if (in_array($action, ['set_custom_quote', 'revise_custom_quote'], true) && ($isAdmin || me_is_staff())) {
    $id    = (int)($data['id'] ?? 0);
    $price = (float)($data['price'] ?? 0);
    $notes = trim($data['notes'] ?? '');
    if ($id <= 0 || $price <= 0) json_err('Valid ID and price are required');
    $row = db_one($conn, "SELECT design_notes, quote_status FROM custom_orders WHERE id = ?", 'i', [$id]);
    if (!$row) json_err('Request not found');
    $allowed = $action === 'set_custom_quote' ? ['Pending'] : ['Quoted', 'Rejected'];
    if (!in_array($row['quote_status'], $allowed, true)) json_err('Request not found or cannot be quoted now');
    $n = parse_custom_notes($row['design_notes']);
    // keep the quote note inside design_notes (CUSTOM_ORDERS has no separate notes column)
    $lines = array_filter(explode("\n", (string)$row['design_notes']), fn($l) => strpos($l, 'Quote note:') !== 0);
    if ($notes !== '') $lines[] = 'Quote note: ' . $notes;
    $newNotes = implode("\n", $lines);
    $staff = me_is_staff() ? me_id() : null;
    db_exec($conn, "UPDATE custom_orders SET quoted_price = ?, quote_status = 'Quoted', design_notes = ?, reviewed_by_staff_id = COALESCE(?, reviewed_by_staff_id) WHERE id = ?",
        'dsii', [$price, $newNotes, $staff, $id]);
    json_out(['success' => true]);
}

if ($action === 'cancel_custom_order_admin' && ($isAdmin || me_is_staff())) {
    $id = (int)($data['id'] ?? 0);
    $n = db_exec($conn, "UPDATE custom_orders SET quote_status = 'Rejected' WHERE id = ? AND quote_status IN ('Pending','Quoted')", 'i', [$id]);
    json_out(['success' => $n > 0]);
}

// ══════════════════════════════════════════════════════════════
//  ORDERS
// ══════════════════════════════════════════════════════════════
if ($action === 'place_order') {
    json_err('Staff orders are recorded through the Walk-in POS (Orders → POS). Customers place orders from their own account.');
}

if ($action === 'get_orders' && $isAdmin)          json_out(load_orders($conn));
if ($action === 'get_all_orders_admin' && $isAdmin) json_out(load_orders($conn));
if ($action === 'get_all_orders_staff')             json_out(load_orders_staff($conn));
if ($action === 'get_my_orders') {
    // orders confirmed/recorded by this staff member
    json_out(load_orders($conn, "EXISTS (SELECT 1 FROM sales s WHERE s.order_id = o.id AND s.recorded_by_staff_id = ?)", 'i', [me_id()]));
}
if ($action === 'get_orders_statistics' && $isAdmin) json_out(orders_statistics($conn));

if ($action === 'update_order_status' && $isAdmin) {
    $orderId = (int)($data['id'] ?? 0);
    $act = status_to_action($data['status'] ?? '');
    if ($act === '') json_err('Invalid status');
    order_do($conn, $orderId, $act);
    json_out(['success' => true]);
}

// Orders are archived, never deleted (CRUA) — "delete" cancels a Pending order
if ($action === 'delete_order' && $isAdmin) {
    order_do($conn, (int)($data['id'] ?? 0), 'cancel');
    json_out(['success' => true]);
}

// Staff confirm (this also deducts stock and records the sale)
if ($action === 'staff_approve_order' && $role === 'staff') {
    order_do($conn, (int)($data['order_id'] ?? 0), 'approve');
    json_out(['success' => true]);
}
if ($action === 'staff_reject_order' && $role === 'staff') {
    order_do($conn, (int)($data['order_id'] ?? 0), 'cancel');
    json_out(['success' => true]);
}
// Two-step approval no longer exists: a staff confirmation is final.
if ($action === 'admin_approve_order' && $isAdmin) {
    $o = order_row($conn, (int)($data['order_id'] ?? 0));
    json_out($o && $o['order_status'] !== 'Pending' ? ['success' => true] : ['error' => 'Order must be confirmed by staff first']);
}
if ($action === 'admin_reject_order' && $isAdmin) {
    order_do($conn, (int)($data['order_id'] ?? 0), 'cancel');
    json_out(['success' => true]);
}

// ══════════════════════════════════════════════════════════════
//  STAFF ACCOUNTS  (USER + STAFF)
// ══════════════════════════════════════════════════════════════
if ($action === 'get_staff_list' && $isAdmin) {
    json_out(db_all($conn, "SELECT u.id, CAST(u.id AS CHAR) AS User_id, u.username, CONCAT(u.first_name,' ',IF(u.last_name='-','',u.last_name)) AS Fullname,
                                   u.first_name, u.last_name, u.phone_number AS phone, u.status AS ActiveStatus, 'Staff' AS role, s.job_title
                            FROM user u JOIN staff s ON s.user_id = u.id ORDER BY u.id"));
}
if ($action === 'delete_staff' && $isAdmin) {   // archive instead of delete
    $n = db_exec($conn, "UPDATE user SET status = 'Archived' WHERE id = ? AND user_type = 'staff'", 'i', [(int)($data['user_id'] ?? 0)]);
    json_out(['success' => $n >= 0]);
}

// ══════════════════════════════════════════════════════════════
//  INSPIRATIONS — removed from the redesigned ERD
// ══════════════════════════════════════════════════════════════
if ($action === 'get_inspirations') json_out([]);
if (in_array($action, ['add_inspiration', 'delete_inspiration'], true)) {
    json_err('The inspirations gallery is not part of the redesigned database.');
}

json_err('Unknown action');

} catch (Throwable $e) {
    json_err($e->getMessage());
}
