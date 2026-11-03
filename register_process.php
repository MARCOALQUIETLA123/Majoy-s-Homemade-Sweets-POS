<?php
session_start();
include 'db.php';

$first   = trim($_POST['first_name'] ?? '');
$last    = trim($_POST['last_name']  ?? '');
if ($first === '' && $last === '' && !empty($_POST['fullname'])) { [$first, $last] = split_name($_POST['fullname']); }
$username= trim($_POST['username']         ?? '');
$password= $_POST['password']              ?? '';
$confirm = $_POST['confirm_password']      ?? '';
$phone   = trim($_POST['phone']            ?? '');
$address = trim($_POST['address']          ?? '');

function reg_fail($msg) {
    echo "<script>alert(" . json_encode($msg) . "); window.location.href='register.php';</script>";
    exit();
}

if ($first === '' || $last === '' || $username === '' || $password === '') reg_fail('All fields are required.');
if ($password !== $confirm)  reg_fail('Passwords do not match!');
if (strlen($password) < 6)   reg_fail('Password must be at least 6 characters.');
if ($phone !== '' && !preg_match('/^[0-9]{11}$/', $phone)) reg_fail('Phone number must be exactly 11 digits (numbers only).');

if (db_one($conn, "SELECT id FROM user WHERE username = ?", 's', [$username])) {
    reg_fail('Username already exists! Please choose a different one.');
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$conn->begin_transaction();
try {
    // USER (supertype) → CUSTOMER (subtype, authorization level 1) → ADDRESSES
    db_exec($conn, "INSERT INTO user (username, first_name, last_name, password_hash, phone_number, user_type, status) VALUES (?,?,?,?,?,'customer','Active')",
        'sssss', [$username, $first, $last, $hashed, $phone !== '' ? $phone : null]);
    $uid = (int)$conn->insert_id;
    db_exec($conn, "INSERT INTO customer (user_id) VALUES (?)", 'i', [$uid]);
    if ($address !== '') find_or_create_address($conn, $uid, $address, true);
    $conn->commit();
    echo "<script>alert('Account created successfully! You can now log in.'); window.location.href='login.php';</script>";
} catch (Throwable $e) {
    $conn->rollback();
    reg_fail('Registration failed. Please try again.');
}
