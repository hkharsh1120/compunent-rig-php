
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
    exit('Invalid form token.');
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

if (!$productId || $productId < 1 || !$quantity || $quantity < 1) {
    http_response_code(400);
    exit('Invalid product or quantity.');
}

if (!array_key_exists($productId, $_SESSION['cart'] ?? [])) {
    http_response_code(404);
    exit('Product not found in your cart.');
}

$stmt = $pdo->prepare("
    SELECT stock_quantity
    FROM products
    WHERE id = :id
      AND status = 'active'
    LIMIT 1
");

$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (
    !$product
    || $quantity > 100
    || $quantity > (int) $product['stock_quantity']
) {
    http_response_code(409);
    exit('Requested quantity is unavailable.');
}

$_SESSION['cart'][$productId] = $quantity;

header('Location: /compunent-rig/cart.php');
exit;
