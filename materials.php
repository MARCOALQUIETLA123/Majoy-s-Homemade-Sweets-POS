<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'owner', 'staff'], true)) {
    header("Location: login.php"); exit();
}

// ══════════════════════════════════════════════════════════════
//  INLINE API — raw materials, purchase orders, ledger, audit, reports
// ══════════════════════════════════════════════════════════════
if (isset($_GET['mat_api'])) {
    include 'db.php';
    header('Content-Type: application/json');
    $action = $_GET['mat_api'];
    $data   = read_json();
    $isAdmin = me_is_admin();
    try {
        $requireAdmin = function () use ($isAdmin) { if (!$isAdmin) json_err('Only the admin/owner can do this.'); };

        if ($action === 'overview') {
            json_out([
                'materials'  => db_one($conn, "SELECT COUNT(*) n FROM raw_materials WHERE is_archived=0")['n'],
                'low'        => db_all($conn, "SELECT * FROM v_low_stock_materials ORDER BY priority_level, material_name"),
                'priorities' => db_all($conn, "SELECT * FROM v_priority_containers ORDER BY priority_level, material_name"),
                'pending_po' => db_all($conn, "SELECT * FROM v_pending_purchase_orders ORDER BY purchase_order_id"),
                'expiring'   => db_all($conn, "SELECT * FROM v_batch_expiry WHERE freshness IN ('Expired','Expiring soon') AND stock_remaining>0 ORDER BY days_left"),
            ]);
        }

        if ($action === 'materials') {
            json_out(db_all($conn, "SELECT rm.id, rm.material_name, rm.unit_of_measure, rm.quantity_on_hand, rm.is_archived,
                                           rl.priority1_qty, rl.priority2_qty, rl.priority3_qty,
                                           CASE WHEN rm.is_archived=1 THEN NULL WHEN rm.quantity_on_hand<=rl.priority1_qty THEN 1
                                                WHEN rm.quantity_on_hand<=rl.priority2_qty THEN 2 WHEN rm.quantity_on_hand<=rl.priority3_qty THEN 3 END AS priority_level
                                    FROM raw_materials rm LEFT JOIN reorder_level rl ON rl.raw_material_id = rm.id
                                    ORDER BY rm.is_archived, rm.material_name"));
        }

        if ($action === 'add_material') {
            $requireAdmin();
            $name = trim($data['material_name'] ?? ''); $unit = trim($data['unit_of_measure'] ?? '');
            $p1 = (float)($data['priority1_qty'] ?? 0); $p2 = (float)($data['priority2_qty'] ?? 0); $p3 = (float)($data['priority3_qty'] ?? 0);
            if ($name === '' || $unit === '') json_err('Name and unit are required');
            if (!($p1 <= $p2 && $p2 <= $p3)) json_err('Reorder levels must satisfy Priority 1 ≤ Priority 2 ≤ Priority 3');
            $conn->begin_transaction();
            try {
                db_exec($conn, "INSERT INTO raw_materials (material_name, unit_of_measure) VALUES (?,?)", 'ss', [$name, mb_substr($unit, 0, 10)]);
                $id = (int)$conn->insert_id;
                db_exec($conn, "INSERT INTO reorder_level (raw_material_id, priority1_qty, priority2_qty, priority3_qty) VALUES (?,?,?,?)", 'iddd', [$id, $p1, $p2, $p3]);
                $conn->commit();
            } catch (Throwable $e) { $conn->rollback(); throw $e; }
            json_out(['success' => true, 'id' => $id]);
        }

        if ($action === 'update_material') {
            $requireAdmin();
            $id = (int)($data['id'] ?? 0);
            $p1 = (float)$data['priority1_qty']; $p2 = (float)$data['priority2_qty']; $p3 = (float)$data['priority3_qty'];
            if (!($p1 <= $p2 && $p2 <= $p3)) json_err('Reorder levels must satisfy Priority 1 ≤ Priority 2 ≤ Priority 3');
            $conn->begin_transaction();
            try {
                db_exec($conn, "UPDATE raw_materials SET material_name=?, unit_of_measure=? WHERE id=?", 'ssi', [trim($data['material_name']), mb_substr(trim($data['unit_of_measure']), 0, 10), $id]);
                if (db_one($conn, "SELECT id FROM reorder_level WHERE raw_material_id=?", 'i', [$id]))
                    db_exec($conn, "UPDATE reorder_level SET priority1_qty=?, priority2_qty=?, priority3_qty=? WHERE raw_material_id=?", 'dddi', [$p1, $p2, $p3, $id]);
                else
                    db_exec($conn, "INSERT INTO reorder_level (raw_material_id, priority1_qty, priority2_qty, priority3_qty) VALUES (?,?,?,?)", 'iddd', [$id, $p1, $p2, $p3]);
                $conn->commit();
            } catch (Throwable $e) { $conn->rollback(); throw $e; }
            json_out(['success' => true]);
        }

        if ($action === 'archive_material' || $action === 'restore_material') {
            $requireAdmin();
            db_exec($conn, "UPDATE raw_materials SET is_archived=? WHERE id=?", 'ii', [$action === 'archive_material' ? 1 : 0, (int)($data['id'] ?? 0)]);
            json_out(['success' => true]);
        }

        // Stock out (staff): production use, or discard / expiry of raw material
        if ($action === 'use_material') {
            require_staff_row();
            $qty = (float)($data['quantity'] ?? 0); $id = (int)($data['id'] ?? 0);
            if ($qty <= 0) json_err('Quantity must be above zero');
            $reason = $data['reason'] ?? 'Used';
            if ($reason === 'Used') {
                $conn->query("CALL sp_use_raw_material($id, $qty, " . me_id() . ")");
            } elseif (in_array($reason, ['Discarded', 'Expired'], true)) {
                db_exec($conn, "INSERT INTO stock_out (raw_material_id, quantity_out, stock_out_reason, stock_out_date, recorded_by_staff_id) VALUES (?,?,?,CURDATE(),?)",
                    'idsi', [$id, $qty, $reason, me_id()]);
            } else json_err('Invalid reason');
            json_out(['success' => true]);
        }

        // Purchase orders
        if ($action === 'po_list') {
            $pos = db_all($conn, "SELECT po.id, po.supplier_name, po.order_date, po.expected_date, po.purchase_order_status AS status, ty.type_name,
                                         CONCAT(u.first_name,' ',u.last_name) AS created_by
                                  FROM purchase_order po JOIN type ty ON ty.id=po.type_id JOIN user u ON u.id=po.created_by_admin_id
                                  ORDER BY FIELD(po.purchase_order_status,'Pending','Ordered','Received','Cancelled'), po.id DESC");
            foreach ($pos as &$p) {
                $p['items'] = db_all($conn, "SELECT poi.id, rm.material_name, rm.unit_of_measure, poi.quantity_ordered, poi.unit_cost
                                             FROM purchase_order_items poi JOIN raw_materials rm ON rm.id=poi.raw_material_id WHERE poi.purchase_order_id=?", 'i', [$p['id']]);
                $p['total'] = array_sum(array_map(fn($i) => $i['quantity_ordered'] * $i['unit_cost'], $p['items']));
            }
            json_out($pos);
        }
        if ($action === 'po_types') json_out(db_all($conn, "SELECT id, type_name FROM type ORDER BY id"));

        if ($action === 'po_create') {
            $requireAdmin();
            $supplier = trim($data['supplier_name'] ?? ''); $items = $data['items'] ?? [];
            if ($supplier === '' || empty($items)) json_err('Supplier and at least one item are required');
            $conn->begin_transaction();
            try {
                db_exec($conn, "INSERT INTO purchase_order (type_id, created_by_admin_id, supplier_name, order_date, expected_date) VALUES (?,?,?,CURDATE(),?)",
                    'iiss', [(int)$data['type_id'], me_id(), $supplier, !empty($data['expected_date']) ? $data['expected_date'] : null]);
                $poId = (int)$conn->insert_id;
                foreach ($items as $it) {
                    if ((float)$it['quantity_ordered'] <= 0) throw new Exception('Quantity must be above zero');
                    db_exec($conn, "INSERT INTO purchase_order_items (purchase_order_id, raw_material_id, quantity_ordered, unit_cost) VALUES (?,?,?,?)",
                        'iidd', [$poId, (int)$it['raw_material_id'], (float)$it['quantity_ordered'], (float)$it['unit_cost']]);
                }
                $conn->commit();
            } catch (Throwable $e) { $conn->rollback(); throw $e; }
            json_out(['success' => true, 'id' => $poId]);
        }
        if ($action === 'po_mark_ordered') {
            $requireAdmin();
            $id = (int)($data['id'] ?? 0);
            $n = db_exec($conn, "UPDATE purchase_order SET purchase_order_status='Ordered' WHERE id=? AND purchase_order_status='Pending'", 'i', [$id]);
            if ($n < 1) json_err('Only a Pending purchase order can be marked as Ordered');
            foreach (db_all($conn, "SELECT raw_material_id FROM purchase_order_items WHERE purchase_order_id=?", 'i', [$id]) as $r)
                $conn->query("CALL sp_mark_priority_ordered(" . (int)$r['raw_material_id'] . ")");
            json_out(['success' => true]);
        }
        if ($action === 'po_cancel') {
            $requireAdmin();
            $n = db_exec($conn, "UPDATE purchase_order SET purchase_order_status='Cancelled' WHERE id=? AND purchase_order_status IN ('Pending','Ordered')", 'i', [(int)($data['id'] ?? 0)]);
            if ($n < 1) json_err('Only a Pending or Ordered purchase order can be cancelled');
            json_out(['success' => true]);
        }
        if ($action === 'po_receive') {          // staff: STOCK_IN rows + quantity on hand + priority refresh
            require_staff_row();
            $conn->query("CALL sp_receive_purchase_order(" . (int)($data['id'] ?? 0) . ", " . me_id() . ")");
            json_out(['success' => true]);
        }

        // Ledger, batches, audit, reports (all read from the database views)
        if ($action === 'movements') json_out(db_all($conn, "SELECT * FROM v_stock_movements ORDER BY movement_date DESC LIMIT 150"));
        if ($action === 'batches')   json_out(db_all($conn, "SELECT * FROM v_batch_expiry ORDER BY (stock_remaining=0), days_left"));
        if ($action === 'audit') {
            $requireAdmin();
            json_out(db_all($conn, "SELECT * FROM v_audit_trail ORDER BY audit_id DESC LIMIT 200"));
        }
        if ($action === 'reports') {
            json_out([
                'daily'    => db_all($conn, "SELECT * FROM v_daily_sales ORDER BY sale_date DESC LIMIT 14"),
                'orders'   => db_all($conn, "SELECT * FROM v_order_summary ORDER BY order_id DESC LIMIT 25"),
                'delivery' => db_all($conn, "SELECT * FROM v_current_delivery_status ORDER BY delivery_id DESC LIMIT 15"),
                'custom'   => db_all($conn, "SELECT * FROM v_custom_order_queue ORDER BY custom_order_id DESC LIMIT 15"),
                'catalog'  => db_all($conn, "SELECT * FROM v_product_catalog ORDER BY product_id"),
            ]);
        }
        if ($action === 'order_details') {        // logs a RETRIEVE entry in the audit trail
            $oid = (int)($data['order_id'] ?? 0);
            $conn->query("CALL sp_log_retrieve(" . me_id() . ", 'ORDERS', $oid)");
            json_out(['summary' => db_one($conn, "SELECT * FROM v_order_summary WHERE order_id=?", 'i', [$oid]),
                      'items' => db_all($conn, "SELECT p.product_name, oi.quantity, oi.unit_price, oi.quantity*oi.unit_price AS line_total
                                                FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?", 'i', [$oid])]);
        }
        json_err('Unknown action');
    } catch (Throwable $e) { json_err($e->getMessage()); }
}
$isAdminPage = in_array($_SESSION['role'], ['admin', 'owner'], true);
$home = $isAdminPage ? 'admin.php' : 'user.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Raw Materials & Purchasing — Majoy's</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@1,700&display=swap" rel="stylesheet">
<style>
:root{--bg:#EDE7F6;--surface:#fff;--brand:#7B5EA7;--brand-d:#5A3F84;--text:#1A1A1A;--muted:#6b6280;--border:rgba(123,94,167,.18);--ok:#2f855a;--warn:#c05621;--bad:#c53030;--r:12px}
*{box-sizing:border-box}body{margin:0;font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text)}
header{background:linear-gradient(135deg,#3D2468,#7B5EA7);color:#fff;padding:14px 22px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
header h1{font-family:'Cormorant Garamond',serif;font-style:italic;margin:0;font-size:26px}
header .sp{flex:1}header a{color:#fff;text-decoration:none;background:rgba(255,255,255,.16);padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600}
nav{display:flex;gap:6px;padding:12px 22px;overflow-x:auto;background:var(--surface);border-bottom:1px solid var(--border)}
nav button{border:0;background:transparent;padding:9px 15px;border-radius:9px;font:600 13px 'Plus Jakarta Sans';color:var(--muted);cursor:pointer;white-space:nowrap}
nav button.active{background:var(--brand);color:#fff}
main{padding:20px 22px;max-width:1250px;margin:auto}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:18px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:16px}
.card .n{font-size:28px;font-weight:700;color:var(--brand-d)}.card .l{font-size:12px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em}
h2{font-size:16px;margin:22px 0 10px}.box{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:auto;margin-bottom:16px}
table{width:100%;border-collapse:collapse;font-size:13px}th{background:#f4effb;text-align:left;padding:10px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);white-space:nowrap}
td{padding:10px 12px;border-top:1px solid var(--border);vertical-align:top}
.pill{display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700}
.p1{background:#fed7d7;color:#9b2c2c}.p2{background:#feebc8;color:#9c4221}.p3{background:#fefcbf;color:#744210}.okp{background:#c6f6d5;color:#22543d}.gray{background:#e2e8f0;color:#4a5568}.blue{background:#bee3f8;color:#2a4365}
button.b{border:0;border-radius:8px;padding:7px 12px;font:600 12px 'Plus Jakarta Sans';cursor:pointer;background:var(--brand);color:#fff;margin:2px}
button.b.s{background:#ede7f6;color:var(--brand-d)}button.b.d{background:#fed7d7;color:#9b2c2c}
.toolbar{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;align-items:center}
input,select{padding:8px 10px;border:1px solid var(--border);border-radius:8px;font:13px 'Plus Jakarta Sans';background:#fff;min-width:0}
.modal{position:fixed;inset:0;background:rgba(26,20,38,.55);display:none;align-items:center;justify-content:center;z-index:50;padding:14px}.modal.on{display:flex}
.mb{background:#fff;border-radius:14px;padding:20px;width:min(560px,100%);max-height:90vh;overflow:auto}.mb h3{margin:0 0 12px}
.fr{display:grid;gap:8px;margin-bottom:10px}.fr label{font-size:12px;font-weight:600;color:var(--muted)}.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
#toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#2d2540;color:#fff;padding:11px 18px;border-radius:10px;font-size:13px;display:none;z-index:99;max-width:90vw}
.muted{color:var(--muted)}.empty{padding:26px;text-align:center;color:var(--muted)}
</style></head><body>
<header><h1>Majoy's</h1><div>Raw Materials &amp; Purchasing</div><div class="sp"></div>
  <a href="<?= $home ?>">← Back to dashboard</a><a href="logout.php">Logout</a></header>
<nav id="tabs"></nav>
<main id="view"><div class="empty">Loading…</div></main>
<div class="modal" id="modal"><div class="mb" id="mbody"></div></div><div id="toast"></div>

<script>
const IS_ADMIN = <?= $isAdminPage ? 'true' : 'false' ?>;
const TABS = [['overview','📊 Overview'],['materials','🧂 Raw Materials'],['po','🧾 Purchase Orders'],['movements','🔁 Stock Movements'],['batches','🎂 Batch Expiry'],['reports','📈 Reports']].concat(IS_ADMIN ? [['audit','🛡️ Audit Trail']] : []);
let cur = 'overview', MATS = [];
const $ = s => document.querySelector(s);
const esc = v => String(v ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const num = (v,d=2) => Number(v).toLocaleString('en-PH',{minimumFractionDigits:d,maximumFractionDigits:d});
const q3 = v => Number(v).toLocaleString('en-PH',{maximumFractionDigits:3});
function toast(m){const t=$('#toast');t.textContent=m;t.style.display='block';clearTimeout(t._h);t._h=setTimeout(()=>t.style.display='none',3500)}
async function api(a,body){const r=await fetch('materials.php?mat_api='+a,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body||{})});return r.json()}
async function act(a,body,msg){const r=await api(a,body);if(r.error){toast('⚠️ '+r.error);return false}toast('✅ '+(msg||'Done'));closeM();load(cur);return true}
function openM(h){$('#mbody').innerHTML=h;$('#modal').classList.add('on')}function closeM(){$('#modal').classList.remove('on')}
$('#modal').addEventListener('click',e=>{if(e.target.id==='modal')closeM()});
const prio = l => l?`<span class="pill p${l}">Priority ${l}</span>`:'<span class="pill okp">OK</span>';
const stat = s => `<span class="pill ${({Pending:'p3',Ordered:'blue',Received:'okp',Cancelled:'gray',Open:'p2',Fresh:'okp','Expiring soon':'p3',Expired:'p1','Sold out / discarded':'gray'})[s]||'gray'}">${esc(s)}</span>`;
function table(cols,rows,empty='No records'){if(!rows.length)return `<div class="box"><div class="empty">${empty}</div></div>`;
  return `<div class="box"><table><tr>${cols.map(c=>`<th>${c[0]}</th>`).join('')}</tr>${rows.map(r=>`<tr>${cols.map(c=>`<td>${c[1](r)}</td>`).join('')}</tr>`).join('')}</table></div>`}

function tabs(){$('#tabs').innerHTML=TABS.map(t=>`<button class="${t[0]===cur?'active':''}" onclick="load('${t[0]}')">${t[1]}</button>`).join('')}
async function load(t){cur=t;tabs();const v=$('#view');v.innerHTML='<div class="empty">Loading…</div>';
  const get=a=>api(a);const R={overview,materials,po,movements,batches,reports,audit}[t];v.innerHTML=await R()}

async function overview(){const d=await api('overview');
  return `<div class="cards"><div class="card"><div class="n">${d.materials}</div><div class="l">Active raw materials</div></div>
  <div class="card"><div class="n">${d.low.length}</div><div class="l">Below reorder level</div></div>
  <div class="card"><div class="n">${d.pending_po.length}</div><div class="l">Open purchase orders</div></div>
  <div class="card"><div class="n">${d.expiring.length}</div><div class="l">Batches expiring / expired</div></div></div>
  <h2>🚨 Priority containers (auto-flagged by the database)</h2>`+
  table([['Level',r=>prio(r.priority_level)],['Material',r=>esc(r.material_name)],['On hand',r=>q3(r.quantity_on_hand)],['Status',r=>stat(r.priority_status)],['Flagged',r=>esc(r.flagged_at)]],d.priorities,'Nothing needs reordering')+
  `<h2>🧾 Open purchase orders</h2>`+
  table([['PO #',r=>r.purchase_order_id],['Supplier',r=>esc(r.supplier_name)],['Type',r=>esc(r.type_name)],['Expected',r=>esc(r.expected_date||'—')],['Status',r=>stat(r.purchase_order_status)],['Items',r=>r.item_count],['Est. cost',r=>'₱'+num(r.estimated_cost)]],d.pending_po,'No open purchase orders')+
  `<h2>⏰ Product batches to watch</h2>`+
  table([['Batch',r=>'#'+r.batch_id],['Product',r=>esc(r.product_name)],['Baked',r=>esc(r.baked_date)],['Left',r=>r.stock_remaining],['Expiry',r=>esc(r.expiry_date)],['Freshness',r=>stat(r.freshness)]],d.expiring,'No batches near expiry')}

async function materials(){MATS=await api('materials');
  const rows=MATS;
  return `<div class="toolbar">${IS_ADMIN?'<button class="b" onclick="matForm()">＋ Add material</button>':''}<span class="muted">Priority levels use each material's reorder levels (P1 = most urgent).</span></div>`+
  table([['Material',r=>esc(r.material_name)+(r.is_archived==1?' <span class="pill gray">archived</span>':'')],['On hand',r=>`<b>${q3(r.quantity_on_hand)}</b> ${esc(r.unit_of_measure)}`],
    ['P1 / P2 / P3 levels',r=>`${q3(r.priority1_qty)} / ${q3(r.priority2_qty)} / ${q3(r.priority3_qty)}`],['Status',r=>r.is_archived==1?'—':prio(r.priority_level)],
    ['Actions',r=>r.is_archived==1?(IS_ADMIN?`<button class="b s" onclick="act('restore_material',{id:${r.id}},'Restored')">Restore</button>`:''):
      `${IS_ADMIN?`<button class="b s" onclick="matForm(${r.id})">Edit</button><button class="b d" onclick="if(confirm('Archive this material?'))act('archive_material',{id:${r.id}},'Archived')">Archive</button>`:''}
       ${!IS_ADMIN?`<button class="b" onclick="useForm(${r.id})">Use / discard</button>`:''}`]],rows)}
function matForm(id){const m=MATS.find(x=>x.id==id)||{};
  openM(`<h3>${id?'Edit':'Add'} raw material</h3><div class="fr"><label>Name</label><input id="f_n" value="${esc(m.material_name)}">
  <label>Unit (kg, L, pcs, …)</label><input id="f_u" value="${esc(m.unit_of_measure)}" maxlength="10"><label>Reorder levels — Priority 1 ≤ 2 ≤ 3</label>
  <div class="g3"><input id="f_1" type="number" step="0.001" placeholder="P1" value="${m.priority1_qty??''}"><input id="f_2" type="number" step="0.001" placeholder="P2" value="${m.priority2_qty??''}"><input id="f_3" type="number" step="0.001" placeholder="P3" value="${m.priority3_qty??''}"></div></div>
  <button class="b" onclick="act('${id?'update_material':'add_material'}',{id:${id||0},material_name:f_n.value,unit_of_measure:f_u.value,priority1_qty:f_1.value,priority2_qty:f_2.value,priority3_qty:f_3.value},'Saved')">Save</button><button class="b s" onclick="closeM()">Cancel</button>`)}
function useForm(id){const m=MATS.find(x=>x.id==id);
  openM(`<h3>Stock out — ${esc(m.material_name)}</h3><p class="muted">On hand: ${q3(m.quantity_on_hand)} ${esc(m.unit_of_measure)}</p><div class="fr"><label>Quantity (${esc(m.unit_of_measure)})</label><input id="f_q" type="number" step="0.001" min="0">
  <label>Reason</label><select id="f_r"><option>Used</option><option>Discarded</option><option>Expired</option></select></div>
  <button class="b" onclick="act('use_material',{id:${id},quantity:f_q.value,reason:f_r.value},'Stock out recorded')">Record</button><button class="b s" onclick="closeM()">Cancel</button>`)}

async function po(){const [list,mats,types]=await Promise.all([api('po_list'),api('materials'),api('po_types')]);MATS=mats;window._types=types;
  return `<div class="toolbar">${IS_ADMIN?'<button class="b" onclick="poForm()">＋ New purchase order</button>':''}<span class="muted">Pending → Ordered → Received (staff receive goods, which creates the STOCK_IN rows).</span></div>`+
  table([['PO #',r=>r.id],['Supplier',r=>esc(r.supplier_name)],['Type',r=>esc(r.type_name)],['Ordered',r=>esc(r.order_date)],['Expected',r=>esc(r.expected_date||'—')],
   ['Items',r=>r.items.map(i=>`${esc(i.material_name)} — ${q3(i.quantity_ordered)} ${esc(i.unit_of_measure)} @ ₱${num(i.unit_cost)}`).join('<br>')],['Total',r=>'₱'+num(r.total)],['Status',r=>stat(r.status)],
   ['Actions',r=>(r.status==='Pending'&&IS_ADMIN?`<button class="b" onclick="act('po_mark_ordered',{id:${r.id}},'Marked as Ordered')">Mark ordered</button>`:'')+
      (r.status==='Ordered'&&!IS_ADMIN?`<button class="b" onclick="if(confirm('Receive all items of PO #${r.id}? Stock on hand will increase.'))act('po_receive',{id:${r.id}},'Received — stock updated')">Receive</button>`:'')+
      (['Pending','Ordered'].includes(r.status)&&IS_ADMIN?`<button class="b d" onclick="if(confirm('Cancel PO #${r.id}?'))act('po_cancel',{id:${r.id}},'Cancelled')">Cancel</button>`:'')]],list)}
function poForm(){const opts=MATS.filter(m=>m.is_archived!=1).map(m=>`<option value="${m.id}">${esc(m.material_name)} (${esc(m.unit_of_measure)})</option>`).join('');
  openM(`<h3>New purchase order</h3><div class="fr"><label>Supplier</label><input id="p_s"><label>Type</label><select id="p_t">${_types.map(t=>`<option value="${t.id}">${esc(t.type_name)}</option>`).join('')}</select>
  <label>Expected date</label><input id="p_d" type="date"></div><div id="p_items"></div><button class="b s" onclick="addPoRow()">＋ Add item</button><br><br>
  <button class="b" onclick="savePo()">Create PO</button><button class="b s" onclick="closeM()">Cancel</button>`);window._opts=opts;addPoRow()}
function addPoRow(){const d=document.createElement('div');d.className='g3';d.style.marginBottom='6px';d.innerHTML=`<select>${_opts}</select><input type="number" step="0.001" min="0" placeholder="Qty"><input type="number" step="0.01" min="0" placeholder="Unit cost ₱">`;$('#p_items').appendChild(d)}
function savePo(){const items=[...document.querySelectorAll('#p_items .g3')].map(r=>({raw_material_id:r.children[0].value,quantity_ordered:r.children[1].value,unit_cost:r.children[2].value})).filter(i=>i.quantity_ordered);
  act('po_create',{supplier_name:p_s.value,type_id:p_t.value,expected_date:p_d.value,items},'Purchase order created')}

async function movements(){const d=await api('movements');
  return table([['Date',r=>esc(r.movement_date)],['Direction',r=>`<span class="pill ${r.direction==='IN'?'okp':'p2'}">${r.direction}</span>`],['Material',r=>esc(r.material_name)],['Qty',r=>q3(r.quantity)+' '+esc(r.unit_of_measure)],['Reason',r=>esc(r.reason)],['Recorded by',r=>esc(r.recorded_by)]],d)}
async function batches(){const d=await api('batches');
  return table([['Batch',r=>'#'+r.batch_id],['Product',r=>esc(r.product_name)],['Baked',r=>esc(r.baked_date)],['Added',r=>r.quantity_added],['Remaining',r=>r.stock_remaining],['Expiry',r=>esc(r.expiry_date)],['Days left',r=>r.days_left],['Freshness',r=>stat(r.freshness)]],d)}
async function audit(){const d=await api('audit');
  return `<p class="muted">Every create / update / archive / retrieve is written by the database triggers (latest 200).</p>`+table([['#',r=>r.audit_id],['When',r=>esc(r.performed_at)],['By',r=>esc(r.performed_by)],['Role',r=>esc(r.role)],['Action',r=>`<span class="pill blue">${esc(r.action_type)}</span>`],['Table',r=>esc(r.affected_table)],['Record',r=>r.record_id]],d)}
async function reports(){const d=await api('reports');
  return `<h2>💰 Daily sales (last 14 days)</h2>`+table([['Date',r=>esc(r.sale_date)],['Orders sold',r=>r.orders_sold],['Total sales',r=>'₱'+num(r.total_sales)]],d.daily)+
  `<h2>📋 Recent orders <span class="muted" style="font-weight:400">— click Details to retrieve (logged in the audit trail)</span></h2>`+
  table([['Order',r=>'#'+r.order_id],['Date',r=>esc(r.order_date)],['Customer',r=>esc(r.customer_name)],['Kind',r=>esc(r.order_kind)],['Payment',r=>esc(r.payment_method)],['Fulfillment',r=>esc((r.fulfillment_type||'')+' · '+(r.fulfillment_status||''))],['Status',r=>stat(r.order_status)],['Total',r=>'₱'+num(r.total_amount)],['',r=>`<button class="b s" onclick="orderDetails(${r.order_id})">Details</button>`]],d.orders)+
  `<h2>🚚 Latest delivery status</h2>`+table([['Delivery',r=>'#'+r.delivery_id],['Status',r=>stat(r.delivery_status)],['Updated',r=>esc(r.updated_at)]],d.delivery)+
  `<h2>🎨 Custom cake queue</h2>`+table([['Req',r=>'#'+r.custom_order_id],['Customer',r=>esc(r.customer_name)],['Flavor',r=>esc(r.cake_flavor)],['Size',r=>esc(r.cake_size)],['Theme',r=>esc(r.cake_theme)],['Needed',r=>esc(r.preferred_date)],['Quote',r=>stat(r.quote_status)],['Price',r=>r.quoted_price?'₱'+num(r.quoted_price):'—']],d.custom,'No custom requests')+
  `<h2>🍰 Product catalog</h2>`+table([['Product',r=>esc(r.product_name)],['Type',r=>esc(r.type_name)],['Price',r=>'₱'+num(r.product_price)],['Stock',r=>r.total_stock],['Baked',r=>esc(r.baked_date||'—')],['Best before',r=>esc(r.best_before_date||'—')],['Availability',r=>stat(r.availability)]],d.catalog)}
async function orderDetails(id){const d=await api('order_details',{order_id:id});if(d.error)return toast('⚠️ '+d.error);const s=d.summary||{};
  openM(`<h3>Order #${id}</h3><p class="muted">${esc(s.customer_name)} · ${esc(s.order_status)} · ${esc(s.payment_method)}</p>`+table([['Product',r=>esc(r.product_name)],['Qty',r=>r.quantity],['Unit',r=>'₱'+num(r.unit_price)],['Line',r=>'₱'+num(r.line_total)]],d.items,'Custom cake — no line items')+`<b>Total: ₱${num(s.total_amount||0)}</b><br><br><button class="b s" onclick="closeM()">Close</button>`)}
load('overview');
</script></body></html>
