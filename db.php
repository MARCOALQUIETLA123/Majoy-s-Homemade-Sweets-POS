<?php
// ─────────────────────────────────────────────────────────────
//  db.php — Database connection for Majoy's Homemade Sweets
//  Database: majoys_bakery (see majoys_bakery.sql)
// ─────────────────────────────────────────────────────────────
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Manila');

try {
    $conn = new mysqli("localhost", "root", "", "majoys_bakery");
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+08:00'");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die("Database connection failed: " . $e->getMessage());
}

require_once __DIR__ . '/lib.php';

// Every INSERT/UPDATE trigger writes to AUDIT_LOGS using @current_user_id (CRUA audit trail)
if (!empty($_SESSION['user_id'])) {
    $conn->query("SET @current_user_id = " . (int)$_SESSION['user_id']);
}
?>
