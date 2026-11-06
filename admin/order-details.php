
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin($pdo);

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    http_response_code(400);
    exit('Invalid order.');
}

$stmt = $pdo->prepare("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email
    FROM orders o
    INNER JOIN users u ON u.id = o.user_id
    WHERE o.id = ?
");

$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}

$stmt = $pdo->prepare("
    SELECT product_name, sku, quantity, unit_price, line_total
    FROM order_items
    WHERE order_id = ?
");

$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$pageTitle = 'Order Details';

require_once __DIR__ . '/includes/header.php';

?>

<main class="admin-main">

    <h1>Order Details</h1>

    <?php if (isset($_GET['updated'])): ?>
        <p class="admin-success">Order updated successfully.</p>
    <?php endif; ?>

    <section class="admin-section">

        <h2><?= e($order['order_number']) ?></h2>

        <p>
            Customer:
            <strong><?= e($order['customer_name']) ?></strong>
        </p>

        <p>Email: <?= e($order['customer_email']) ?></p>

        <p>Phone: <?= e($order['shipping_phone']) ?></p>

        <h3>Shipping Address</h3>

        <p>
            <?= e($order['shipping_name']) ?><br>
            <?= e($order['shipping_address_line1']) ?><br>

            <?php if ($order['shipping_address_line2']): ?>
                <?= e($order['shipping_address_line2']) ?><br>
            <?php endif; ?>

            <?= e($order['shipping_city']) ?>,
            <?= e($order['shipping_state']) ?> -
            <?= e($order['shipping_postal_code']) ?><br>

            <?= e($order['shipping_country']) ?>
        </p>

        <h3>Purchased Products</h3>

        <div class="admin-table-wrapper">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($items as $item): ?>

                        <tr>
                            <td><?= e($item['product_name']) ?></td>
                            <td><?= e($item['sku']) ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td><?= formatPrice($item['unit_price']) ?></td>
                            <td><?= formatPrice($item['line_total']) ?></td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <h3>Payment Summary</h3>

        <p>Subtotal: <?= formatPrice($order['subtotal']) ?></p>
        <p>Shipping: <?= formatPrice($order['shipping_fee']) ?></p>
        <p>Discount: <?= formatPrice($order['discount_amount']) ?></p>

        <h3>Grand Total: <?= formatPrice($order['total_amount']) ?></h3>

        <hr>

        <h3>Update Order</h3>

        <form method="POST" action="order-update.php"
              class="admin-product-form">

            <input type="hidden" name="csrf_token"
                   value="<?= e(csrfToken()) ?>">

            <input type="hidden" name="order_id"
                   value="<?= (int) $order['id'] ?>">

            <label>Order Status</label>

            <select name="status" required>
                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                    <option value="<?= e($status) ?>"
                        <?= $order['status'] === $status ? 'selected' : '' ?>>
                        <?= e(ucfirst($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>COD Payment Status</label>

            <select name="payment_status" required>
                <option value="pending"
                    <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>
                    Pending
                </option>

                <option value="paid"
                    <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>
                    Paid / Collected
                </option>
            </select>

            <button type="submit" class="admin-primary-btn">
                Update Order
            </button>

        </form>

        <p>
            <a href="orders.php">Back to Orders</a>
        </p>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
