<?php
session_start();

// Only admin/owner can upload images
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'owner')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_FILES['photo'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file     = $_FILES['photo'];
$maxSize  = 5 * 1024 * 1024; // 5MB limit
$allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Validate size
if ($file['size'] > $maxSize) {
    echo json_encode(['error' => 'Image too large. Max 5MB.']);
    exit;
}

// Validate type
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowed)) {
    echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']);
    exit;
}

// Create uploads folder if it doesn't exist
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('product_', true) . '.' . strtolower($ext);
$destPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode([
        'success' => true,
        'path'    => 'uploads/' . $filename
    ]);
} else {
    echo json_encode(['error' => 'Failed to save image. Check folder permissions.']);
}
?>