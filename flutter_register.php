<?php
/** flutter_register.php — JSON registration for the Flutter customer app */
session_start();
include 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$d       = read_json();
$first   = trim($d['first_name'] ?? '');
$last    = trim($d['last_name']  ?? '');
if ($first === '' && $last === '' && !empty($d['fullname'])) { [$first, $last] = split_name($d['fullname']); }
$username= trim($d['username']         ?? '');
$password= $d['password']              ?? '';
$confirm = $d['confirm_password']      ?? '';
$phone   = trim($d['phone']            ?? '');
$address = trim($d['address']          ?? '');

if ($first === '' || $last === '' || $username === '' || $password === '') json_out(['success' => false, 'message' => 'All fields are required']);
if ($password !== $confirm) json_out(['success' => false, 'message' => 'Passwords do not match']);
if (strlen($password) < 6)  json_out(['success' => false, 'message' => 'Password must be at least 6 characters']);
if (db_one($conn, "SELECT id FROM user WHERE username = ?", 's', [$username])) json_out(['success' => false, 'message' => 'Username already exists']);

$hashed = password_hash($password, PASSWORD_DEFAULT);
$conn->begin_transaction();
try {
    db_exec($conn, "INSERT INTO user (username, first_name, last_name, password_hash, phone_number, user_type, status) VALUES (?,?,?,?,?,'customer','Active')",
        'sssss', [$username, $first, $last, $hashed, $phone !== '' ? $phone : null]);
    $uid = (int)$conn->insert_id;
    db_exec($conn, "INSERT INTO customer (user_id) VALUES (?)", 'i', [$uid]);
    if ($address !== '') find_or_create_address($conn, $uid, $address, true);
    $conn->commit();
    json_out(['success' => true, 'message' => 'Account created!', 'user_id' => (string)$uid]);
} catch (Throwable $e) {
    $conn->rollback();
    json_out(['success' => false, 'message' => 'Registration failed. Try again.']);
}
