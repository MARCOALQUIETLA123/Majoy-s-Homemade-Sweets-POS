<?php
/**
 * flutter_login.php — JSON login endpoint for the Flutter mobile app.
 * Same authentication as login_process.php but returns JSON and sets the PHP session cookie.
 */
session_start();
include 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$data     = read_json();
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if ($username === '' || $password === '') json_out(['success' => false, 'message' => 'Username and password are required']);

$user = db_one($conn, "SELECT * FROM user WHERE username = ? AND status = 'Active'", 's', [$username]);
if (!$user || !password_verify($password, $user['password_hash'])) {
    json_out(['success' => false, 'message' => 'Invalid credentials or account is inactive']);
}

session_regenerate_id(true);
$full = join_name($user['first_name'], $user['last_name']);
$_SESSION['username'] = $user['username'];
$_SESSION['fullname'] = $full;
$_SESSION['role']     = strtolower($user['user_type']);
$_SESSION['user_id']  = (int)$user['id'];

json_out([
    'success' => true,
    'user'    => [
        'id'           => (int)$user['id'],
        'username'     => $user['username'],
        'Fullname'     => $full,
        'first_name'   => $user['first_name'],
        'last_name'    => $user['last_name'],
        'role'         => strtolower($user['user_type']),
        'User_id'      => (string)$user['id'],
        'phone'        => $user['phone_number'],
        'address'      => default_address_text($conn, (int)$user['id']),
        'ActiveStatus' => $user['status'],
    ],
]);
