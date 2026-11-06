
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form token. Refresh the page and try again.');
}

$productId = filter_input(
    INPUT_POST,
    'product_id',
    FILTER_VALIDATE_INT
);

$quantity = filter_input(
    INPUT_POST,
    'quantity',
    FILTER_VALIDATE_INT
);

if (
    !$productId || $productId < 1
    || !$quantity || $quantity < 1
    || $quantity > 100
) {
    http_response_code(400);
    exit('Invalid product or quantity.');
}

$stmt = $pdo->prepare("
    SELECT id, stock_quantity
    FROM products
    WHERE id = :id
      AND status = 'active'
    LIMIT 1
");

$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product || (int) $product['stock_quantity'] < 1) {
    http_response_code(409);
    exit('This product is unavailable.');
}

$currentQuantity = $_SESSION['cart'][$productId] ?? 0;

if (!is_int($currentQuantity) || $currentQuantity < 0) {
    $currentQuantity = 0;
}

$newQuantity = $currentQuantity + $quantity;

if (
    $newQuantity > (int) $product['stock_quantity']
    || $newQuantity > 100
) {
    http_response_code(409);
    exit('Requested quantity exceeds available stock.');
}

$_SESSION['cart'][$productId] = $newQuantity;

header('Location: /compunent-rig/cart.php');
exit;
