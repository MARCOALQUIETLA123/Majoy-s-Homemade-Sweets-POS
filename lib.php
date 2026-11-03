<?php
// ─────────────────────────────────────────────────────────────
//  lib.php — shared helpers on top of the majoys_bakery schema
// ─────────────────────────────────────────────────────────────

// ── fallback when the mbstring extension is off ──────────────
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $len = null) { return $len === null ? substr($s, $start) : substr($s, $start, $len); }
}

// ── tiny query helpers (prepared statements) ─────────────────
function db_all(mysqli $c, string $sql, string $types = '', array $p = []): array {
    $st = $c->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$p);
    $st->execute();
    $r = $st->get_result();
    $rows = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    $st->close();
    return $rows;
}
function db_one(mysqli $c, string $sql, string $types = '', array $p = []): ?array {
    $r = db_all($c, $sql, $types, $p);
    return $r[0] ?? null;
}
function db_exec(mysqli $c, string $sql, string $types = '', array $p = []): int {
    $st = $c->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$p);
    $st->execute();
    $n = $st->affected_rows;
    $st->close();
    return $n;
}

// ── JSON helpers ─────────────────────────────────────────────
function json_out($d) { echo json_encode($d); exit; }
function json_err($m) { echo json_encode(['error' => $m]); exit; }
function read_json(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }

// ── session helpers ──────────────────────────────────────────
function me_id(): int   { return (int)($_SESSION['user_id'] ?? 0); }
function me_role(): string { return $_SESSION['role'] ?? ''; }
function me_is_admin(): bool { return in_array(me_role(), ['admin', 'owner'], true); }
function me_is_staff(): bool { return me_role() === 'staff'; }
function require_staff_row() {
    // Stock/sales/discard/delivery ledgers reference STAFF(user_id) in the ERD.
    if (!me_is_staff()) json_err('This action is recorded against a staff account — please use a Staff login.');
}

// ── names ────────────────────────────────────────────────────
function split_name(string $full): array {
    $full = trim(preg_replace('/\s+/', ' ', $full));
    if ($full === '') return ['', ''];
    $pos = strrpos($full, ' ');
    if ($pos === false) return [$full, '-'];
    return [substr($full, 0, $pos), substr($full, $pos + 1)];
}
function join_name($f, $l): string { return trim($f . ' ' . ($l === '-' ? '' : $l)); }

// ── addresses (ADDRESSES table: street / barangay / city / province) ──
function parse_address(string $text): array {
    $parts = array_values(array_filter(array_map('trim', explode(',', $text)), 'strlen'));
    $n = count($parts);
    if ($n >= 4) {
        $province = array_pop($parts); $city = array_pop($parts); $barangay = array_pop($parts);
        $street = implode(', ', $parts);
    } elseif ($n === 3) {
        [$street, $barangay, $city] = $parts; $province = 'Bukidnon';
    } elseif ($n === 2) {
        [$street, $barangay] = $parts; $city = 'Manolo Fortich'; $province = 'Bukidnon';
    } else {
        $street = $parts[0] ?? $text; $barangay = 'N/A'; $city = 'Manolo Fortich'; $province = 'Bukidnon';
    }
    return [mb_substr($street, 0, 150), mb_substr($barangay, 0, 80), mb_substr($city, 0, 80), mb_substr($province, 0, 80)];
}
function address_to_text(?array $a): string {
    if (!$a) return '';
    $bits = [$a['street_address'], $a['barangay'] === 'N/A' ? '' : $a['barangay'], $a['city'], $a['province']];
    return implode(', ', array_filter($bits, 'strlen'));
}
function find_or_create_address(mysqli $c, int $customerId, string $text, bool $makeDefault = false): int {
    [$street, $brgy, $city, $prov] = parse_address($text);
    $row = db_one($c, "SELECT id FROM addresses WHERE customer_id=? AND street_address=? AND barangay=? AND city=? AND province=? LIMIT 1",
        'issss', [$customerId, $street, $brgy, $city, $prov]);
    if ($row) return (int)$row['id'];
    $has = db_one($c, "SELECT COUNT(*) n FROM addresses WHERE customer_id=?", 'i', [$customerId]);
    $def = ($makeDefault || (int)$has['n'] === 0) ? 1 : 0;
    if ($def) db_exec($c, "UPDATE addresses SET is_default=0 WHERE customer_id=?", 'i', [$customerId]);
    db_exec($c, "INSERT INTO addresses (customer_id, street_address, barangay, city, province, is_default) VALUES (?,?,?,?,?,?)",
        'issssi', [$customerId, $street, $brgy, $city, $prov, $def]);
    return (int)$c->insert_id;
}
function default_address_text(mysqli $c, int $customerId): string {
    $a = db_one($c, "SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC, id ASC LIMIT 1", 'i', [$customerId]);
    return address_to_text($a);
}

// ── lookup ids ───────────────────────────────────────────────
function payment_method_id(mysqli $c, string $key): int {
    $name = ($key === 'gcash') ? 'GCash' : 'Cash';
    $r = db_one($c, "SELECT id FROM payment_method WHERE method_name=? AND is_active=1", 's', [$name]);
    if (!$r) json_err("Payment method '$name' is not available");
    return (int)$r['id'];
}
function walkin_customer_id(mysqli $c): int {
    $r = db_one($c, "SELECT id FROM user WHERE username='walkin.customer'");
    if ($r) return (int)$r['id'];
    $hash = password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT);
    db_exec($c, "INSERT INTO user (username, first_name, last_name, password_hash, user_type, status) VALUES ('walkin.customer','Walk-in','Customer',?, 'customer','Inactive')", 's', [$hash]);
    $id = (int)$c->insert_id;
    db_exec($c, "INSERT INTO customer (user_id) VALUES (?)", 'i', [$id]);
    return $id;
}

// ═════════════════════════════════════════════════════════════
//  PRODUCTS  (PRODUCTS + INVENTORY_BATCHES + TYPE)
//  Output keeps the field names the web pages already use.
// ═════════════════════════════════════════════════════════════
function product_sql(string $where = '1=1', string $order = 'p.id DESC', bool $freshOnly = false): string {
    // oldest batch that still has stock decides the expiry shown (customers: oldest batch still within shelf life)
    $freshCond = $freshOnly ? " AND DATE_ADD(b.baked_date, INTERVAL p.shelf_life_days DAY) >= CURDATE()" : "";
    $oldest = "COALESCE((SELECT MIN(b.baked_date) FROM inventory_batches b WHERE b.product_id=p.id AND b.stock_remaining>0$freshCond), p.baked_date)";
    // stock that is still within shelf life (what customers may buy)
    $fresh  = "COALESCE((SELECT SUM(b.stock_remaining) FROM inventory_batches b WHERE b.product_id=p.id AND b.stock_remaining>0 AND DATE_ADD(b.baked_date, INTERVAL p.shelf_life_days DAY) >= CURDATE()),0)";
    return "SELECT p.id, p.product_name AS name, p.product_price AS price, p.total_stock AS stock,
                   $fresh AS fresh_stock, '' AS description, p.photo_path AS photo,
                   $oldest AS production_date, p.shelf_life_days, t.type_name, p.type_id,
                   'Regular' AS custom, p.is_archived AS archived, NULL AS archived_date, NULL AS archive_reason,
                   DATEDIFF(DATE_ADD($oldest, INTERVAL p.shelf_life_days DAY), CURDATE()) AS days_remaining,
                   DATE_ADD($oldest, INTERVAL p.shelf_life_days DAY) AS expiry_date
            FROM products p JOIN type t ON t.id = p.type_id
            WHERE $where ORDER BY $order";
}
function products_admin(mysqli $c): array   { return db_all($c, product_sql('p.is_archived=0')); }
function products_archived(mysqli $c): array{ return db_all($c, product_sql('p.is_archived=1')); }
function products_for_customer(mysqli $c): array {
    $rows = db_all($c, product_sql('p.is_archived=0', 'p.id DESC', true));
    $out = [];
    foreach ($rows as $r) {
        if ((int)$r['fresh_stock'] > 0) { $r['stock'] = (int)$r['fresh_stock']; $out[] = $r; }
    }
    return $out;
}
function products_in_stock(mysqli $c): array {
    return array_values(array_filter(products_admin($c), fn($r) => (int)$r['stock'] > 0));
}
function inventory_stats(mysqli $c): array {
    $rows = products_admin($c);
    $s = ['total_products' => count($rows), 'total_stock' => 0, 'fresh_items' => 0, 'expiring_items' => 0, 'low_stock' => 0];
    foreach ($rows as $r) {
        $st = (int)$r['stock']; $s['total_stock'] += $st;
        $d = $r['days_remaining'];
        if ($d !== null && $st > 0) {
            if ((int)$d > 2) $s['fresh_items'] += $st;
            elseif ((int)$d >= 0) $s['expiring_items'] += $st;
        }
        if ($st > 0 && $st < 5) $s['low_stock']++;
    }
    return $s;
}
function type_id_for(mysqli $c, ?string $name, ?int $id = null): int {
    if ($id) { $r = db_one($c, "SELECT id FROM type WHERE id=?", 'i', [$id]); if ($r) return (int)$r['id']; }
    if ($name) { $r = db_one($c, "SELECT id FROM type WHERE type_name=?", 's', [$name]); if ($r) return (int)$r['id']; }
    $r = db_one($c, "SELECT id FROM type ORDER BY id LIMIT 1");
    return (int)$r['id'];
}
function product_add(mysqli $c, array $d): int {
    $name  = trim($d['name'] ?? '');
    $price = (float)($d['price'] ?? 0);
    $stock = max(0, (int)($d['stock'] ?? 0));
    $shelf = max(1, (int)($d['shelf_life_days'] ?? 5));
    $baked = !empty($d['production_date']) ? $d['production_date'] : date('Y-m-d');
    $photo = trim($d['photo'] ?? '') ?: null;
    if ($name === '' || $price <= 0) throw new Exception('Product name and a price above zero are required');
    $tid = type_id_for($c, $d['type_name'] ?? null, isset($d['type_id']) ? (int)$d['type_id'] : null);
    $c->begin_transaction();
    try {
        db_exec($c, "INSERT INTO products (type_id, product_name, product_price, photo_path, shelf_life_days, total_stock, baked_date) VALUES (?,?,?,?,?,0,?)",
            'isdsis', [$tid, $name, $price, $photo, $shelf, $baked]);
        $pid = (int)$c->insert_id;
        if ($stock > 0) {
            db_exec($c, "INSERT INTO inventory_batches (product_id, quantity_added, stock_remaining, baked_date) VALUES (?,?,?,?)",
                'iiis', [$pid, $stock, $stock, $baked]);
        }
        $c->commit();
        return $pid;
    } catch (Throwable $e) { $c->rollback(); throw $e; }
}
function product_update(mysqli $c, array $d): bool {
    $id    = (int)($d['id'] ?? 0);
    $name  = trim($d['name'] ?? '');
    $price = (float)($d['price'] ?? 0);
    $shelf = max(1, (int)($d['shelf_life_days'] ?? 5));
    $photo = trim($d['photo'] ?? '') ?: null;
    $newStock = isset($d['stock']) ? max(0, (int)$d['stock']) : null;
    $baked = !empty($d['production_date']) ? $d['production_date'] : date('Y-m-d');
    if ($id <= 0 || $name === '' || $price <= 0) throw new Exception('Product name and a price above zero are required');
    $tid = type_id_for($c, $d['type_name'] ?? null, isset($d['type_id']) ? (int)$d['type_id'] : null);
    $c->begin_transaction();
    try {
        db_exec($c, "UPDATE products SET type_id=?, product_name=?, product_price=?, photo_path=?, shelf_life_days=? WHERE id=? AND is_archived=0",
            'isdsii', [$tid, $name, $price, $photo, $shelf, $id]);
        if ($newStock !== null) {
            $cur = db_one($c, "SELECT COALESCE(SUM(stock_remaining),0) s FROM inventory_batches WHERE product_id=?", 'i', [$id]);
            $delta = $newStock - (int)$cur['s'];
            if ($delta > 0) {            // stock added → a new batch (baked today / chosen date)
                db_exec($c, "INSERT INTO inventory_batches (product_id, quantity_added, stock_remaining, baked_date) VALUES (?,?,?,?)",
                    'iiis', [$id, $delta, $delta, $baked]);
            } elseif ($delta < 0) {      // stock reduced → take from newest batches (recount)
                $need = -$delta;
                foreach (db_all($c, "SELECT id, stock_remaining FROM inventory_batches WHERE product_id=? AND stock_remaining>0 ORDER BY baked_date DESC, id DESC", 'i', [$id]) as $b) {
                    if ($need <= 0) break;
                    $take = min($need, (int)$b['stock_remaining']);
                    db_exec($c, "UPDATE inventory_batches SET stock_remaining = stock_remaining - ? WHERE id=?", 'ii', [$take, $b['id']]);
                    $need -= $take;
                }
            }
        }
        $c->commit();
        return true;
    } catch (Throwable $e) { $c->rollback(); throw $e; }
}

// ── discards: product-level request spread over batches (oldest first) ──
function product_discard(mysqli $c, int $productId, int $qty, string $reason): array {
    require_staff_row();
    if ($productId <= 0 || $qty <= 0 || trim($reason) === '') throw new Exception('Invalid product, quantity or reason');
    $reason = mb_substr(trim($reason), 0, 20);
    $p = db_one($c, "SELECT id, total_stock FROM products WHERE id=? AND is_archived=0", 'i', [$productId]);
    if (!$p) throw new Exception('Product not found');
    if ((int)$p['total_stock'] < $qty) throw new Exception('Cannot discard more than available stock (' . $p['total_stock'] . ')');
    $need = $qty;
    foreach (db_all($c, "SELECT id, stock_remaining FROM inventory_batches WHERE product_id=? AND stock_remaining>0 ORDER BY baked_date ASC, id ASC", 'i', [$productId]) as $b) {
        if ($need <= 0) break;
        $take = min($need, (int)$b['stock_remaining']);
        $c->query("CALL sp_record_discard(" . (int)$b['id'] . ", $take, '" . $c->real_escape_string($reason) . "', " . me_id() . ", @did)");
        $need -= $take;
    }
    $left = db_one($c, "SELECT total_stock FROM products WHERE id=?", 'i', [$productId]);
    return ['success' => true, 'message' => 'Items discarded successfully', 'remaining_stock' => (int)$left['total_stock']];
}
function discard_history(mysqli $c, int $limit = 50): array {
    return db_all($c, "SELECT di.id, b.product_id AS cake_id, p.product_name AS cake_name, di.quantity_discarded,
                              p.product_price AS price, di.discard_reason AS reason, b.baked_date AS date_added, di.discard_date,
                              CONCAT(u.first_name,' ',IF(u.last_name='-','',u.last_name)) AS discarded_by,
                              CONCAT(di.discard_date,' 00:00:00') AS created_at
                       FROM discarded_items di
                       JOIN inventory_batches b ON b.id = di.batch_id
                       JOIN products p ON p.id = b.product_id
                       JOIN user u ON u.id = di.discarded_by_staff_id
                       ORDER BY di.discard_date DESC, di.id DESC LIMIT ?", 'i', [$limit]);
}
function discard_stats(mysqli $c): array {
    $t = db_one($c, "SELECT COALESCE(SUM(di.quantity_discarded),0) q, COALESCE(SUM(di.quantity_discarded*p.product_price),0) v
                     FROM discarded_items di JOIN inventory_batches b ON b.id=di.batch_id JOIN products p ON p.id=b.product_id");
    $m = db_one($c, "SELECT COALESCE(SUM(quantity_discarded),0) q FROM discarded_items WHERE MONTH(discard_date)=MONTH(CURDATE()) AND YEAR(discard_date)=YEAR(CURDATE())");
    $w = db_one($c, "SELECT COALESCE(SUM(quantity_discarded),0) q FROM discarded_items WHERE discard_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $r = db_one($c, "SELECT discard_reason r, COUNT(*) n FROM discarded_items GROUP BY discard_reason ORDER BY n DESC LIMIT 1");
    return ['total_discarded' => $t['q'], 'total_value_lost' => $t['v'], 'monthly_discarded' => $m['q'],
            'weekly_discarded' => $w['q'], 'common_reason' => $r ? $r['r'] : 'None'];
}

// ═════════════════════════════════════════════════════════════
//  ORDERS  (ORDERS + ORDER_ITEMS + DELIVERY/PICKUP + CUSTOM_ORDERS)
// ═════════════════════════════════════════════════════════════
function map_status(string $os, ?string $dstat): array {
    if ($os === 'Pending')   return ['pending', 'order_placed'];
    if ($os === 'Completed') return ['completed', 'delivered'];
    if ($os === 'Cancelled') return ['cancelled', 'order_placed'];
    if ($os === 'Ready')     return ['processing', 'out_for_delivery'];
    if ($dstat === 'Out for Delivery') return ['processing', 'out_for_delivery'];
    if ($os === 'Preparing' || $dstat === 'Preparing') return ['processing', 'preparing'];
    return ['approved', 'confirmed'];   // Confirmed
}
function pay_key(string $method, bool $walkin): string {
    if ($method === 'GCash') return 'gcash';
    return $walkin ? 'cash' : 'cod';
}
function parse_custom_notes(?string $notes): array {
    $o = ['layers' => '', 'message' => '', 'decorations' => '', 'fulfillment' => 'delivery', 'address' => '', 'contact' => '', 'time' => '', 'recipient' => '', 'quote_note' => '', 'extra' => ''];
    foreach (explode("\n", (string)$notes) as $line) {
        if (preg_match('/^(Layers|Message|Decorations|Fulfillment|Address|Contact|Time|Recipient|Quote note): ?(.*)$/', $line, $m)) {
            $k = strtolower(str_replace(' ', '_', $m[1]));
            $o[$k] = trim($m[2]);
        } elseif (trim($line) !== '') { $o['extra'] .= ($o['extra'] ? "\n" : '') . $line; }
    }
    return $o;
}
function order_base_sql(): string {
    return "SELECT o.id, o.customer_id, o.order_status, o.total_amount, o.gcash_ref_number, o.preferred_date, o.order_date,
                   pm.method_name, u.username AS cust_username, u.first_name AS cf, u.last_name AS cl, u.phone_number AS cphone,
                   d.id AS delivery_id, d.recipient_first_name AS rf, d.recipient_last_name AS rl,
                   a.street_address, a.barangay, a.city, a.province,
                   pk.id AS pickup_id, pk.pickup_time, pk.pickup_status,
                   (SELECT ds.delivery_status FROM delivery_status ds WHERE ds.delivery_id=d.id ORDER BY ds.updated_at DESC, ds.id DESC LIMIT 1) AS latest_ds,
                   co.id AS custom_id, co.cake_flavor, co.cake_size, co.cake_theme, co.preferred_frosting, co.reference_photo_path,
                   co.design_notes, co.quoted_price, co.quote_status AS co_quote_status,
                   (SELECT CONCAT(su.first_name,' ',IF(su.last_name='-','',su.last_name)) FROM user su WHERE su.id=co.reviewed_by_staff_id) AS quoted_by,
                   (SELECT CONCAT(ru.first_name,' ',IF(ru.last_name='-','',ru.last_name)) FROM sales s JOIN user ru ON ru.id=s.recorded_by_staff_id WHERE s.order_id=o.id LIMIT 1) AS reviewed_by_name
            FROM orders o
            JOIN payment_method pm ON pm.id = o.payment_method_id
            JOIN user u ON u.id = o.customer_id
            LEFT JOIN delivery d ON d.order_id = o.id
            LEFT JOIN addresses a ON a.id = d.address_id
            LEFT JOIN pickup pk ON pk.order_id = o.id
            LEFT JOIN custom_orders co ON co.order_id = o.id";
}
function shape_order(array $r, array $items): array {
    $walkin   = ($r['cust_username'] === 'walkin.customer');
    $custName = $walkin ? 'Walk-in Customer' : join_name($r['cf'], $r['cl']);
    $isPickup = !empty($r['pickup_id']);
    [$status, $dstat] = map_status($r['order_status'], $r['latest_ds']);
    if ($isPickup && $r['order_status'] === 'Ready') $dstat = 'out_for_delivery';
    $addr = $isPickup ? 'PICKUP - Blk13 Lot8 Phase2 Menzi Village Damilag Manolo Fortich Bukidnon'
                      : address_to_text(['street_address' => $r['street_address'] ?? '', 'barangay' => $r['barangay'] ?? '', 'city' => $r['city'] ?? '', 'province' => $r['province'] ?? '']);
    if ($walkin) $addr = 'PICKUP - Walk-in Customer';
    $recipient = $isPickup || !$r['rf'] ? $custName : join_name($r['rf'], $r['rl']);
    $isCustom = !empty($r['custom_id']);
    $o = [
        'id' => (int)$r['id'], 'order_id' => (int)$r['id'],
        'staff_user_id' => (string)$r['customer_id'], 'customer_id' => (int)$r['customer_id'],
        'staff_name' => $custName, 'customer_name' => $custName,
        'status' => $status, 'order_status' => $r['order_status'],
        'total' => (float)$r['total_amount'], 'sale_amount' => (float)$r['total_amount'],
        'fulfillment_method' => $isPickup ? 'pickup' : 'delivery',
        'payment_method' => pay_key($r['method_name'], $walkin),
        'delivery_name' => $recipient, 'delivery_phone' => $r['cphone'] ?? '', 'delivery_address' => $addr, 'delivery_notes' => '',
        'preferred_date' => $r['preferred_date'], 'preferred_time' => $isPickup ? substr((string)$r['pickup_time'], 0, 5) : '',
        'gcash_ref_number' => $r['gcash_ref_number'], 'gcash_screenshot' => '',
        'order_date' => $r['order_date'], 'delivery_status' => $dstat,
        'pickup_status' => $r['pickup_status'] ?? null,
        'reviewed_by' => $r['reviewed_by_name'] ?? null, 'reviewed_at' => null, 'review_notes' => $walkin ? 'Walk-in sale' : '',
        'is_custom' => $isCustom ? 1 : 0, 'items' => $items,
    ];
    if ($isCustom) {
        $n = parse_custom_notes($r['design_notes']);
        $o['custom_order_id'] = (int)$r['custom_id'];
        $o['quoted_price'] = $r['quoted_price'] !== null ? (float)$r['quoted_price'] : null;
        $o['quote_status'] = 'accepted';
        $o['items'] = [['id' => 0, 'order_id' => (int)$r['id'], 'product_id' => null,
                        'name' => '🎂 Custom ' . $r['cake_flavor'] . ' Cake (' . $r['cake_size'] . ') – Ref #C' . $r['custom_id'],
                        'price' => (float)$r['total_amount'], 'quantity' => 1]];
        $o['specs'] = custom_specs($r, $n);
    }
    return $o;
}
function custom_specs(array $r, array $n): array {
    return ['size' => $r['cake_size'], 'flavor' => $r['cake_flavor'], 'layers' => $n['layers'], 'frosting' => $r['preferred_frosting'],
            'color_theme' => $r['cake_theme'], 'message' => $n['message'], 'decorations' => $n['decorations'],
            'reference_image' => $r['reference_photo_path']];
}
function load_orders(mysqli $c, string $where = '1=1', string $types = '', array $params = [], string $order = 'o.order_date DESC, o.id DESC'): array {
    $rows = db_all($c, order_base_sql() . " WHERE $where ORDER BY $order", $types, $params);
    if (!$rows) return [];
    $ids = array_map(fn($r) => (int)$r['id'], $rows);
    $in  = implode(',', $ids);
    $items = [];
    foreach (db_all($c, "SELECT oi.id, oi.order_id, oi.product_id, p.product_name AS name, oi.unit_price AS price, oi.quantity
                         FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($in) ORDER BY oi.id") as $it) {
        $it['price'] = (float)$it['price']; $it['quantity'] = (int)$it['quantity'];
        $items[(int)$it['order_id']][] = $it;
    }
    return array_map(fn($r) => shape_order($r, $items[(int)$r['id']] ?? []), $rows);
}
// staff view: pending first, then confirmed, processing, completed
function load_orders_staff(mysqli $c): array {
    return load_orders($c, '1=1', '', [], "FIELD(o.order_status,'Pending','Confirmed','Preparing','Ready','Completed','Cancelled'), o.order_date DESC");
}

// ── order transitions (uses the stored procedures where they exist) ──
function order_row(mysqli $c, int $id): ?array {
    return db_one($c, "SELECT o.id, o.customer_id, o.order_status, d.id AS delivery_id, pk.id AS pickup_id
                       FROM orders o LEFT JOIN delivery d ON d.order_id=o.id LEFT JOIN pickup pk ON pk.order_id=o.id WHERE o.id=?", 'i', [$id]);
}
function order_do(mysqli $c, int $orderId, string $action, string $reason = ''): void {
    $o = order_row($c, $orderId);
    if (!$o) throw new Exception('Order not found');
    $s = $o['order_status'];
    switch ($action) {
        case 'approve':
            require_staff_row();
            if ($s !== 'Pending') throw new Exception('Only a Pending order can be confirmed');
            $c->query("CALL sp_confirm_order($orderId, " . me_id() . ")");   // deducts stock FIFO + records the sale
            break;
        case 'start_processing':
            if ($s !== 'Confirmed') throw new Exception('Only a Confirmed order can start processing');
            db_exec($c, "UPDATE orders SET order_status='Preparing' WHERE id=?", 'i', [$orderId]);
            break;
        case 'out_for_delivery':
            if (!in_array($s, ['Confirmed', 'Preparing', 'Ready'], true)) throw new Exception('Order must be confirmed first');
            if ($o['delivery_id']) {
                require_staff_row();
                $c->query("CALL sp_update_delivery_status(" . (int)$o['delivery_id'] . ", 'Out for Delivery', " . me_id() . ")");
            } else {
                $c->query("CALL sp_update_pickup_status($orderId, 'Ready', " . me_id() . ")");
                db_exec($c, "UPDATE orders SET order_status='Ready' WHERE id=?", 'i', [$orderId]);
            }
            break;
        case 'complete':
            if (!in_array($s, ['Confirmed', 'Preparing', 'Ready'], true)) throw new Exception('Order must be confirmed first');
            if ($o['delivery_id']) {
                require_staff_row();
                $c->query("CALL sp_update_delivery_status(" . (int)$o['delivery_id'] . ", 'Delivered', " . me_id() . ")");
            } else {
                $c->query("CALL sp_update_pickup_status($orderId, 'Picked Up', " . me_id() . ")");
            }
            break;
        case 'cancel':
            if ($s !== 'Pending') throw new Exception('Only a Pending order can be cancelled — it has already been confirmed');
            db_exec($c, "UPDATE orders SET order_status='Cancelled' WHERE id=?", 'i', [$orderId]);
            break;
        default: throw new Exception('Unknown order action');
    }
}
function status_to_action(string $newStatus): string {
    return ['approved' => 'approve', 'processing' => 'start_processing', 'out_for_delivery' => 'out_for_delivery',
            'completed' => 'complete', 'cancelled' => 'cancel', 'rejected' => 'cancel'][$newStatus] ?? '';
}

// ── walk-in sale (POS): Pending → Confirmed (sale recorded) → Completed ──
function walkin_checkout(mysqli $c, array $d): array {
    require_staff_row();
    $items = $d['items'] ?? [];
    $pay   = $d['payment_method'] ?? 'cash';
    $ref   = trim($d['gcash_ref'] ?? '');
    if (empty($items)) throw new Exception('Cart is empty');
    if (!in_array($pay, ['cash', 'gcash'], true)) throw new Exception('Invalid payment method');
    if ($pay === 'gcash' && $ref === '') throw new Exception('GCash reference number is required');
    if ($pay === 'gcash' && !preg_match('/^\d{13}$/', $ref)) throw new Exception('GCash reference number must be exactly 13 digits');
    $cid = walkin_customer_id($c);
    $pm  = payment_method_id($c, $pay);
    $c->begin_transaction();
    try {
        db_exec($c, "INSERT INTO orders (customer_id, payment_method_id, total_amount, gcash_ref_number, preferred_date) VALUES (?,?,0,?,CURDATE())",
            'iis', [$cid, $pm, $pay === 'gcash' ? $ref : null]);
        $oid = (int)$c->insert_id;
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0); $qty = (int)($it['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) throw new Exception('Invalid item in cart');
            $p = db_one($c, "SELECT product_name, product_price, total_stock FROM products WHERE id=? AND is_archived=0 FOR UPDATE", 'i', [$pid]);
            if (!$p) throw new Exception("Product not found (ID $pid)");
            if ((int)$p['total_stock'] < $qty) throw new Exception('Not enough stock for "' . $p['product_name'] . '" (have ' . $p['total_stock'] . ')');
            db_exec($c, "INSERT INTO order_items (order_id, product_id, unit_price, quantity) VALUES (?,?,?,?)", 'iidi', [$oid, $pid, $p['product_price'], $qty]);
        }
        db_exec($c, "INSERT INTO pickup (order_id, pickup_time, pickup_status) VALUES (?, CURTIME(), 'Picked Up')", 'i', [$oid]);
        $c->commit();
    } catch (Throwable $e) { $c->rollback(); throw $e; }
    $c->query("CALL sp_confirm_order($oid, " . me_id() . ")");
    db_exec($c, "UPDATE orders SET order_status='Completed' WHERE id=?", 'i', [$oid]);
    $t = db_one($c, "SELECT total_amount FROM orders WHERE id=?", 'i', [$oid]);
    return ['success' => true, 'order_id' => $oid, 'total' => (float)$t['total_amount']];
}

// ── sales (SALES joined to ORDERS) ──
function sales_list(mysqli $c, string $period): array {
    switch ($period) {
        case 'week':  $w = "WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"; break;
        case 'month': $w = "WHERE MONTH(s.sale_date)=MONTH(CURDATE()) AND YEAR(s.sale_date)=YEAR(CURDATE())"; break;
        case 'all':   $w = ""; break;
        default:      $w = "WHERE s.sale_date=CURDATE()";
    }
    $base = "FROM sales s JOIN orders o ON o.id=s.order_id JOIN payment_method pm ON pm.id=o.payment_method_id
             JOIN user cu ON cu.id=o.customer_id JOIN user su ON su.id=s.recorded_by_staff_id $w";
    $rows = db_all($c, "SELECT s.id, s.order_id, IF(cu.username='walkin.customer','walkin','order') AS sale_type,
                               CONCAT('Order #', o.id, ' — ', IF(cu.username='walkin.customer','Walk-in Customer',CONCAT(cu.first_name,' ',IF(cu.last_name='-','',cu.last_name)))) AS description,
                               o.total_amount AS amount, LOWER(pm.method_name) AS payment_method, s.sale_date,
                               CONCAT(su.first_name,' ',IF(su.last_name='-','',su.last_name)) AS recorded_by,
                               CONCAT(s.sale_date,' 00:00:00') AS created_at, o.gcash_ref_number
                        $base ORDER BY s.sale_date DESC, s.id DESC");
    $t = db_one($c, "SELECT COALESCE(SUM(o.total_amount),0) total,
                            COALESCE(SUM(CASE WHEN pm.method_name='Cash'  THEN o.total_amount ELSE 0 END),0) cash,
                            COALESCE(SUM(CASE WHEN pm.method_name='GCash' THEN o.total_amount ELSE 0 END),0) gcash, COUNT(*) count $base");
    return ['sales' => $rows, 'totals' => $t];
}
function orders_statistics(mysqli $c): array {
    $r = db_one($c, "SELECT COUNT(*) total_today,
                            COALESCE(SUM(order_status='Completed'),0) completed_today,
                            COALESCE(SUM(order_status='Pending'),0) pending_today,
                            COALESCE(SUM(CASE WHEN order_status='Completed' THEN total_amount ELSE 0 END),0) revenue_today
                     FROM orders WHERE DATE(order_date)=CURDATE()");
    return $r;
}

// ═════════════════════════════════════════════════════════════
//  CUSTOM ORDERS (CUSTOM_ORDERS table)
// ═════════════════════════════════════════════════════════════
function custom_old_status(string $qs): string {
    return ['Pending' => 'pending_quote', 'Quoted' => 'quoted', 'Accepted' => 'accepted', 'Rejected' => 'declined'][$qs] ?? 'pending_quote';
}
function custom_requests(mysqli $c, ?int $customerId = null): array {
    $where = $customerId ? 'WHERE co.customer_id = ?' : '';
    $rows = db_all($c, "SELECT co.*, u.first_name cf, u.last_name cl, u.phone_number cphone,
                               o.order_status, o.total_amount, o.id AS oid,
                               (SELECT CONCAT(su.first_name,' ',IF(su.last_name='-','',su.last_name)) FROM user su WHERE su.id=co.reviewed_by_staff_id) AS quoted_by
                        FROM custom_orders co JOIN user u ON u.id=co.customer_id LEFT JOIN orders o ON o.id=co.order_id
                        $where ORDER BY co.created_at DESC, co.id DESC", $customerId ? 'i' : '', $customerId ? [$customerId] : []);
    $out = [];
    foreach ($rows as $r) {
        $n = parse_custom_notes($r['design_notes']);
        $status = 'pending';
        if ($r['oid']) { [$status] = map_status($r['order_status'], null); }
        elseif ($r['quote_status'] === 'Rejected') $status = 'cancelled';
        $name = join_name($r['cf'], $r['cl']);
        $out[] = [
            'id' => (int)$r['id'], 'order_id' => $r['oid'] ? (int)$r['oid'] : null,
            'staff_user_id' => (string)$r['customer_id'], 'staff_name' => $name, 'customer_name' => $name,
            'status' => $status, 'quote_status' => custom_old_status($r['quote_status']),
            'total' => $r['oid'] ? (float)$r['total_amount'] : 0,
            'quoted_price' => $r['quoted_price'] !== null ? (float)$r['quoted_price'] : null,
            'quoted_by' => $r['quoted_by'] ?: ($r['quoted_price'] !== null ? 'Admin' : null), 'quote_notes' => $n['quote_note'], 'quoted_at' => $r['quoted_price'] !== null ? $r['created_at'] : null,
            'fulfillment_method' => $n['fulfillment'], 'delivery_name' => $n['recipient'] ?: $name,
            'delivery_phone' => $n['contact'] ?: ($r['cphone'] ?? ''), 'delivery_address' => $n['fulfillment'] === 'pickup' ? 'PICKUP - Blk13 Lot8 Phase2 Menzi Village Damilag Manolo Fortich Bukidnon' : $n['address'],
            'preferred_date' => $r['preferred_date'], 'preferred_time' => $n['time'], 'order_date' => $r['created_at'],
            'delivery_status' => $r['oid'] ? map_status($r['order_status'], null)[1] : 'order_placed',
            'is_custom' => 1, 'specs' => custom_specs($r, $n), 'items' => [], 'review_notes' => '', 'delivery_notes' => '',
        ];
    }
    return $out;
}
function time_or_default(?string $t, string $def = '10:00:00'): string {
    if (!$t) return $def;
    $ts = strtotime($t);
    return $ts ? date('H:i:s', $ts) : $def;
}
