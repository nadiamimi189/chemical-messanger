<?php
require_once __DIR__ . '/includes/init.php';
requireLogin();

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$productId) {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare('SELECT media_path, media_type FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit;
}

sendUploadedMedia($product['media_path'], $product['media_type'], 'uploads/products');