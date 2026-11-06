
<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$orderNumber = $_GET['order'] ?? '';
$lastOrderNumber = $_SESSION['last_order_number'] ?? '';

if (
    !is_string($orderNumber)
    || $orderNumber === ''
    || !is_string($lastOrderNumber)
    || !hash_equals($lastOrderNumber, $orderNumber)
) {
    http_response_code(404);
    exit('Order not found.');
}

$stmt = $pdo->prepare("
    SELECT id, order_number, status, total_amount,
           payment_method, payment_status, created_at
    FROM orders
    WHERE order_number = ?
      AND user_id = ?
    LIMIT 1
");

$stmt->execute([$orderNumber, currentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}

$pageTitle = 'Order Confirmed';

require_once __DIR__ . '/includes/header.php';

?>

<main class="container" style="padding: 40px 0;">

    <h1>Thank You for Your Order!</h1>

    <p>Your order has been successfully placed.</p>

    <h2>Order Details</h2>

    <p>
        Order Number:
        <strong><?= e($order['order_number']) ?></strong>
    </p>

    <p>
        Order Status:
        <strong><?= e($order['status']) ?></strong>
    </p>

    <p>
        Payment Method:
        <strong>Cash on Delivery</strong>
    </p>

    <p>
        Payment Status:
        <strong><?= e($order['payment_status']) ?></strong>
    </p>

    <p>
        Order Total:
        <strong><?= formatPrice($order['total_amount']) ?></strong>
    </p>

    <p>
        Order Date:
        <?= e($order['created_at']) ?>
    </p>

    <a href="/compunent-rig/products.php" class="btn">
        Continue Shopping
    </a>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
