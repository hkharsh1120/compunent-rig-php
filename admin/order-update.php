
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form token.');
}

$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$newStatus = $_POST['status'] ?? '';
$newPaymentStatus = $_POST['payment_status'] ?? '';

$allowedStatuses = [
    'pending',
    'processing',
    'shipped',
    'delivered',
    'cancelled'
];

if (
    !$orderId
    || $orderId < 1
    || !in_array($newStatus, $allowedStatuses, true)
    || !in_array($newPaymentStatus, ['pending', 'paid'], true)
) {
    http_response_code(400);
    exit('Invalid order information.');
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id, status, payment_status
        FROM orders
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new RuntimeException('Order not found.');
    }

    $oldStatus = $order['status'];
    $oldPaymentStatus = $order['payment_status'];

    if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
        throw new RuntimeException('Cancelled orders cannot be reopened.');
    }

    if ($oldPaymentStatus === 'paid' && $newStatus === 'cancelled') {
        throw new RuntimeException('A paid COD order cannot be cancelled here. Handle the refund separately.');
    }

    if ($newPaymentStatus === 'paid' && $newStatus !== 'delivered') {
        throw new RuntimeException('Mark COD payment as paid only after delivery.');
    }

    if ($oldStatus === 'cancelled' && $newPaymentStatus !== 'pending') {
        throw new RuntimeException('Cancelled orders cannot receive a payment update.');
    }

    // Restore inventory only on the first cancellation.
    if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') {

        $stmt = $pdo->prepare("
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = ?
            ORDER BY id
            FOR UPDATE
        ");

        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();

        $stockStmt = $pdo->prepare("
            UPDATE products
            SET stock_quantity = stock_quantity + ?
            WHERE id = ?
        ");

        $movementStmt = $pdo->prepare("
            INSERT INTO inventory_movements (
                product_id,
                order_id,
                quantity_change,
                reason
            )
            VALUES (?, ?, ?, 'order_cancelled')
        ");

        foreach ($items as $item) {

            // Product may have been deleted after the order.
            if ($item['product_id'] === null) {
                continue;
            }

            $quantity = (int) $item['quantity'];
            $productId = (int) $item['product_id'];

            $stockStmt->execute([$quantity, $productId]);

            $movementStmt->execute([
                $productId,
                $orderId,
                $quantity
            ]);
        }
    }

    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = ?, payment_status = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $newStatus,
        $newPaymentStatus,
        $orderId
    ]);

    $pdo->commit();

    header('Location: order-details.php?id=' . $orderId . '&updated=1');
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Order update error: ' . $e->getMessage());

    if ($e instanceof RuntimeException) {
        http_response_code(409);
        exit(e($e->getMessage()));
    }

    http_response_code(500);
    exit('Unable to update order. Please try again.');
}
