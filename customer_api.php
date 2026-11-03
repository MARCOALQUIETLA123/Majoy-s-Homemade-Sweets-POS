<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// ── Only allow logged-in customers ───────────────────────────
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$action     = $_GET['action'] ?? '';
$customerId = me_id();
$data       = ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_FILES)) ? read_json() : [];

// ── image upload helper ──────────────────────────────────────
function save_upload(string $subdir, string $prefix, int $maxBytes) {
    if (!isset($_FILES['file'])) json_err('No file uploaded');
    $file = $_FILES['file'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) json_err('Invalid file type');
    if ($file['size'] > $maxBytes) json_err('File too large (max ' . ($maxBytes / 1048576) . 'MB)');
    $dir = __DIR__ . '/uploads/' . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = $prefix . uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $name)) json_out(['path' => 'uploads/' . $subdir . '/' . $name]);
    json_err('Upload failed');
}

try {

if ($action === 'get_products') json_out(products_for_customer($conn));

if ($action === 'upload_gcash_screenshot') save_upload('gcash', 'gcash_', 5 * 1048576);
if ($action === 'upload_reference_image')  save_upload('references', 'ref_', 10 * 1048576);

if ($action === 'get_cutoff') {
    json_out(['cutoff' => '22:00', 'server_time' => date('H:i'), 'server_date' => date('Y-m-d')]);
}

// ══════════════════════════════════════════════════════════════
//  PLACE ORDER → loads the cart tables, then CALL sp_place_order
// ══════════════════════════════════════════════════════════════
if ($action === 'place_order') {
    $items       = $data['items'] ?? [];
    $payKey      = ($data['payment_method'] ?? 'cod') === 'gcash' ? 'gcash' : 'cod';
    $fulfillment = ($data['fulfillment_method'] ?? 'delivery') === 'pickup' ? 'pickup' : 'delivery';
    $dName       = trim($data['delivery_name'] ?? '');
    $dPhone      = trim($data['delivery_phone'] ?? '');
    $dAddr       = trim($data['delivery_address'] ?? '');
    $prefDate    = $data['preferred_date'] ?? null;
    $prefTime    = $data['preferred_time'] ?? null;
    $gcashRef    = trim($data['gcash_ref_number'] ?? '');

    if (empty($items))                    json_err('No items');
    if (!$dName || !$dPhone)              json_err('Missing required details');
    if ($fulfillment === 'delivery' && !$dAddr) json_err('Delivery address is required');
    if (!$prefDate)                       json_err('Please select a preferred date');
    if ($payKey === 'gcash' && !preg_match('/^\d{13}$/', $gcashRef)) json_err('GCash reference number must be exactly 13 digits');

    // validate products against fresh stock
    $fresh = [];
    foreach (products_for_customer($conn) as $p) $fresh[(int)$p['id']] = $p;
    $cart = [];
    foreach ($items as $it) {
        $pid = (int)($it['id'] ?? 0); $qty = (int)($it['quantity'] ?? 0);
        if (!isset($fresh[$pid]))        json_err('One of the items is no longer available');
        if ($qty <= 0 || $qty > (int)$fresh[$pid]['stock']) json_err('Insufficient stock for "' . $fresh[$pid]['name'] . '" (only ' . $fresh[$pid]['stock'] . ' left)');
        $cart[$pid] = ($cart[$pid] ?? 0) + $qty;
    }

    // the phone typed at checkout is kept on the customer record
    if ($dPhone !== '') db_exec($conn, "UPDATE user SET phone_number = ? WHERE id = ?", 'si', [mb_substr($dPhone, 0, 20), $customerId]);

    $conn->begin_transaction();
    try {
        $sc = db_one($conn, "SELECT id FROM shopping_cart WHERE customer_id = ?", 'i', [$customerId]);
        if (!$sc) { db_exec($conn, "INSERT INTO shopping_cart (customer_id) VALUES (?)", 'i', [$customerId]); $cartId = (int)$conn->insert_id; }
        else $cartId = (int)$sc['id'];
        db_exec($conn, "DELETE FROM cart_items WHERE cart_id = ?", 'i', [$cartId]);
        foreach ($cart as $pid => $qty) db_exec($conn, "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?,?,?)", 'iii', [$cartId, $pid, $qty]);
        $conn->commit();
    } catch (Throwable $e) { $conn->rollback(); throw $e; }

    $pm      = payment_method_id($conn, $payKey);
    [$fn, $ln] = split_name($dName);
    $addrId  = $fulfillment === 'delivery' ? find_or_create_address($conn, $customerId, $dAddr) : null;
    $pTime   = time_or_default($prefTime);
    $stmt = $conn->prepare("CALL sp_place_order(?,?,?,?,?,?,?,?,?,@oid)");
    $ref  = $payKey === 'gcash' ? $gcashRef : null;
    $ful  = $fulfillment === 'delivery' ? 'Delivery' : 'Pick-up';
    $stmt->bind_param('iisssisss', $customerId, $pm, $prefDate, $ref, $ful, $addrId, $fn, $ln, $pTime);
    $stmt->execute(); $stmt->close();
    $oid = db_one($conn, "SELECT @oid AS id");
    json_out(['success' => true, 'order_id' => (int)$oid['id']]);
}

if ($action === 'get_my_orders') {
    json_out(load_orders($conn, 'o.customer_id = ?', 'i', [$customerId]));
}

if ($action === 'cancel_order') {
    $orderId = (int)($data['order_id'] ?? 0);
    $n = db_exec($conn, "UPDATE orders SET order_status = 'Cancelled' WHERE id = ? AND customer_id = ? AND order_status = 'Pending'", 'ii', [$orderId, $customerId]);
    json_out($n > 0 ? ['success' => true] : ['error' => 'Order not found, already processed, or not yours to cancel.']);
}

// ══════════════════════════════════════════════════════════════
//  CUSTOM CAKE REQUESTS  (CUSTOM_ORDERS)
// ══════════════════════════════════════════════════════════════
if ($action === 'submit_custom_order') {
    $sizeRaw = trim($data['size'] ?? '');
    $shape   = ucfirst(trim($data['shape'] ?? 'round'));
    $flavor  = trim($data['flavor'] ?? '');
    if ($sizeRaw === '' || $flavor === '') json_err('Cake size and flavor are required');
    $fulfil  = in_array($data['fulfillment_method'] ?? '', ['delivery', 'pickup'], true) ? $data['fulfillment_method'] : 'delivery';
    $needed  = !empty($data['delivery_date']) ? $data['delivery_date'] : date('Y-m-d', strtotime('+7 days'));
    $notes = [];
    foreach (['layers' => 'Layers', 'message' => 'Message', 'decorations' => 'Decorations'] as $k => $label) {
        $v = trim($data[$k] ?? ''); if ($v !== '') $notes[] = "$label: $v";
    }
    // fulfilment details are kept with the request until the customer accepts the quote
    $notes[] = 'Fulfillment: ' . $fulfil;
    if (!empty($data['delivery_address'])) $notes[] = 'Address: ' . trim($data['delivery_address']);
    if (!empty($data['contact_number']))   $notes[] = 'Contact: ' . trim($data['contact_number']);
    if (!empty($data['delivery_time']))    $notes[] = 'Time: ' . trim($data['delivery_time']);
    $notes[] = 'Recipient: ' . ($_SESSION['fullname'] ?? '');
    db_exec($conn, "INSERT INTO custom_orders (customer_id, cake_flavor, cake_size, cake_theme, preferred_frosting, reference_photo_path, design_notes, preferred_date)
                    VALUES (?,?,?,?,?,?,?,?)",
        'isssssss', [$customerId, mb_substr($flavor, 0, 50), mb_substr($shape . ' ' . $sizeRaw, 0, 30),
                     mb_substr(trim($data['color'] ?? '') ?: 'Custom design', 0, 100),
                     mb_substr(trim($data['frosting'] ?? ''), 0, 50) ?: null,
                     trim($data['reference_image'] ?? '') ?: null, implode("\n", $notes), $needed]);
    json_out(['success' => true, 'request_id' => (int)$conn->insert_id]);
}

if ($action === 'get_my_custom_orders') json_out(custom_requests($conn, $customerId));

if ($action === 'accept_custom_quote') {
    $cid     = (int)($data['request_id'] ?? 0);
    $payKey  = ($data['payment_method'] ?? 'cod') === 'gcash' ? 'gcash' : 'cod';
    $gcashRef = trim($data['gcash_ref_number'] ?? '');
    if ($payKey === 'gcash' && !preg_match('/^\d{13}$/', $gcashRef)) json_err('GCash reference number must be exactly 13 digits');
    $co = db_one($conn, "SELECT * FROM custom_orders WHERE id = ? AND customer_id = ? AND quote_status = 'Quoted'", 'ii', [$cid, $customerId]);
    if (!$co) json_err('Request not found or not ready to accept');
    if ((float)$co['quoted_price'] <= 0) json_err('No valid quote price found');
    $n = parse_custom_notes($co['design_notes']);
    [$fn, $ln] = split_name($n['recipient'] ?: ($_SESSION['fullname'] ?? ''));
    $addrId = $n['fulfillment'] === 'delivery' && $n['address'] !== '' ? find_or_create_address($conn, $customerId, $n['address']) : null;
    if ($n['fulfillment'] === 'delivery' && !$addrId) json_err('This request has no delivery address — please submit a new request.');
    $pm  = payment_method_id($conn, $payKey);
    $ful = $n['fulfillment'] === 'delivery' ? 'Delivery' : 'Pick-up';
    $pt  = time_or_default($n['time']);
    $ref = $payKey === 'gcash' ? $gcashRef : null;
    $stmt = $conn->prepare("CALL sp_accept_custom_quote(?,?,?,?,?,?,?,?,@oid)");
    $stmt->bind_param('iississs', $cid, $pm, $ref, $ful, $addrId, $fn, $ln, $pt);
    $stmt->execute(); $stmt->close();
    $oid = db_one($conn, "SELECT @oid AS id");
    json_out(['success' => true, 'order_id' => (int)$oid['id']]);
}

if ($action === 'decline_custom_quote') {
    $n = db_exec($conn, "UPDATE custom_orders SET quote_status = 'Rejected' WHERE id = ? AND customer_id = ? AND quote_status = 'Quoted'",
        'ii', [(int)($data['request_id'] ?? 0), $customerId]);
    json_out($n > 0 ? ['success' => true] : ['error' => 'Request not found or cannot be declined now']);
}

if ($action === 'cancel_custom_order') {
    $n = db_exec($conn, "UPDATE custom_orders SET quote_status = 'Rejected' WHERE id = ? AND customer_id = ? AND quote_status = 'Pending'",
        'ii', [(int)($data['request_id'] ?? 0), $customerId]);
    json_out($n > 0 ? ['success' => true] : ['error' => 'Request cannot be cancelled at this stage']);
}

if ($action === 'get_inspirations') json_out([]);   // gallery removed from the redesigned ERD

// ══════════════════════════════════════════════════════════════
//  ACCOUNT
// ══════════════════════════════════════════════════════════════
if ($action === 'change_password') {
    $cur = $data['current_password'] ?? ''; $new = $data['new_password'] ?? '';
    if (!$cur || !$new) json_err('Missing fields');
    if (strlen($new) < 6) json_err('Password must be at least 6 characters');
    $u = db_one($conn, "SELECT password_hash FROM user WHERE id = ?", 'i', [$customerId]);
    if (!$u) json_err('User not found');
    if (!password_verify($cur, $u['password_hash'])) json_err('Current password is incorrect');
    db_exec($conn, "UPDATE user SET password_hash = ? WHERE id = ?", 'si', [password_hash($new, PASSWORD_DEFAULT), $customerId]);
    json_out(['success' => true]);
}

if ($action === 'get_profile') {
    $u = db_one($conn, "SELECT first_name, last_name, username, phone_number FROM user WHERE id = ?", 'i', [$customerId]);
    if (!$u) json_err('User not found');
    json_out(['Fullname' => join_name($u['first_name'], $u['last_name']), 'first_name' => $u['first_name'], 'last_name' => $u['last_name'],
              'username' => $u['username'], 'phone' => $u['phone_number'], 'address' => default_address_text($conn, $customerId)]);
}

if ($action === 'save_profile') {
    $full = trim($data['fullname'] ?? ''); $phone = trim($data['phone'] ?? ''); $addr = trim($data['address'] ?? '');
    if ($full === '') json_err('Name cannot be empty');
    [$fn, $ln] = isset($data['first_name']) ? [trim($data['first_name']), trim($data['last_name'] ?? '-')] : split_name($full);
    db_exec($conn, "UPDATE user SET first_name = ?, last_name = ?, phone_number = ? WHERE id = ?", 'sssi', [$fn, $ln ?: '-', $phone !== '' ? $phone : null, $customerId]);
    if ($addr !== '') find_or_create_address($conn, $customerId, $addr, true);
    $_SESSION['fullname'] = join_name($fn, $ln);
    json_out(['success' => true]);
}

json_err('Unknown action');

} catch (Throwable $e) {
    json_err($e->getMessage());
}
