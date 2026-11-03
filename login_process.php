<?php
session_start();
include 'db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$user = db_one($conn, "SELECT * FROM user WHERE username = ? AND status = 'Active'", 's', [$username]);

if (!$user || !password_verify($password, $user['password_hash'])) {
    echo "<script>alert('Invalid login credentials or account is inactive!'); window.location.href='login.php';</script>";
    exit();
}

// Regenerate session ID on login to prevent session fixation attacks
session_regenerate_id(true);

$_SESSION['username'] = $user['username'];
$_SESSION['fullname'] = join_name($user['first_name'], $user['last_name']);
$_SESSION['role']     = strtolower($user['user_type']);   // admin | staff | customer
$_SESSION['user_id']  = (int)$user['id'];

// Keep the Admin / Owner / Staff / Customer role split from the RBAC design
switch ($_SESSION['role']) {
    case 'admin':    header("Location: admin.php");    break;
    case 'staff':    header("Location: user.php");     break;
    case 'customer': header("Location: customer.php"); break;
    default:
        session_destroy();
        echo "<script>alert('Invalid user role!'); window.location.href='login.php';</script>";
}
exit();
