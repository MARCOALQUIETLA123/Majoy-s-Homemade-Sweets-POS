# Majoy's — Demo Guide

## Setup (XAMPP)
1. Start Apache + MySQL. In phpMyAdmin create an empty database named exactly **`majoys_bakery`** (the dump does not create it), select it, then import `majoys_bakery.sql`.
2. *(Optional)* import `demo_setup.sql` → every account gets the password `majoys123`.
3. Copy this whole folder to `C:\xampp\htdocs\MAJOY\` and open `http://localhost/MAJOY/login.php`.

## Accounts
| Role | Username | Opens |
|---|---|---|
| Admin / Owner | `admin.majoy` | `admin.php` |
| Staff | `staff.rodel`, `staff.jenny`, `staff.marvin`, `staff.jerome`, `staff.aileen` | `user.php` |
| Customer | `angelica.ramos` (or register a new one) | `customer.php` |

## Feature walkthrough (what each ERD part shows)
| # | Feature | Where to show it | Database feature |
|---|---|---|---|
| 1 | Register a customer (first/last name, phone, address) | `register.php` | USER → CUSTOMER → ADDRESSES |
| 2 | Browse cakes, add to cart, order (pickup or delivery, Cash or GCash) | Customer → Shop / Checkout | `sp_place_order`, DELIVERY / PICKUP |
| 3 | Custom cake request → quote → accept | Customer → Custom Cake, Admin → Custom Orders | CUSTOM_ORDERS, `sp_accept_custom_quote` |
| 4 | Confirm order (stock deducted oldest-batch-first, sale recorded) | **Staff** → Incoming → Approve | `sp_confirm_order`, SALES |
| 5 | Preparing → Ready / Out for delivery → Completed | Staff → Approved tab | `sp_update_pickup_status`, `sp_update_delivery_status`, DELIVERY_STATUS |
| 6 | Walk-in sale (POS) | **Staff** → Walk-in Customer | walk-in order + SALES |
| 7 | Product add / edit / archive; each add creates a batch | Admin → POS/Products, Add New Stock, Archive | PRODUCTS, INVENTORY_BATCHES |
| 8 | Shelf life / batch expiry | Admin → Shelf Life; Materials → Batch Expiry | `v_batch_expiry` |
| 9 | Discard expired cakes | **Staff** → Inventory / Discard | `sp_record_discard`, DISCARDED_ITEMS |
| 10 | Staff accounts (create, deactivate, archive — logged as ARCHIVE) | Admin → Staff | USER + STAFF |
| 11 | Sales summary | Admin → Sales Summary | SALES, `v_daily_sales` |
| 12 | Raw materials, reorder levels, Priority 1/2/3 flags | `materials.php` → Overview / Raw Materials | RAW_MATERIALS, REORDER_LEVEL, PRIORITY_1-3, `sp_refresh_priority` |
| 13 | Purchase order: create → Ordered → Received | `materials.php` → Purchase Orders (admin creates, **staff** receives) | PURCHASE_ORDER(+ITEMS), `sp_receive_purchase_order`, STOCK_IN |
| 14 | Use / discard raw materials | `materials.php` as **staff** → Use / discard | `sp_use_raw_material`, STOCK_OUT |
| 15 | Stock movement ledger | `materials.php` → Stock Movements | `v_stock_movements` |
| 16 | Audit trail (CREATE / UPDATE / ARCHIVE / RETRIEVE) | `materials.php` as admin → Audit Trail; Reports → Details | AUDIT_LOGS, `sp_log_retrieve` |
| 17 | CRUA "never delete" rule | Try "delete" on a product / staff / order | delete triggers |

## Good to know
* Actions that the ERD ties to a staff member (confirm orders, walk-in POS, discards, delivery status, receiving stock) need a **Staff** login; the Admin login shows a clear message.
* Expired seed data: the sample batches were baked in early October, so many show "Expired" — a nice thing to discard live.
