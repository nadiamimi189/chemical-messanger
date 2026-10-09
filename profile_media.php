<?php
require_once __DIR__ . '/includes/init.php';
requireLogin();

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$photoType = $_GET['type'] ?? '';
$photoColumn = $photoType === 'avatar' ? 'avatar' : ($photoType === 'cover' ? 'cover_photo' : null);
if (!$userId || $photoColumn === null) {
    http_response_code(404);
    exit;
}

$photoStmt = $pdo->prepare("SELECT {$photoColumn} AS photo_path FROM users WHERE id = ?");
$photoStmt->execute([$userId]);
$photo = $photoStmt->fetch();
if (!$photo || empty($photo['photo_path'])) {
    http_response_code(404);
    exit;
}

sendUploadedMedia($photo['photo_path'], 'image', 'uploads/profile_photos');
