
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin($pdo);

$customerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$customerId || $customerId < 1) {
    http_response_code(400);
    exit('Invalid customer.');
}

$stmt = $pdo->prepare("
    SELECT id, name, email, phone, status, created_at
    FROM users
    WHERE id = ?
      AND role = 'customer'
    LIMIT 1
");

$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    http_response_code(404);
    exit('Customer not found.');
}

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(
            SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END),
            0
        ) AS total_spent
    FROM orders
    WHERE user_id = ?
");

$stmt->execute([$customerId]);
$summary = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT id, order_number, total_amount, status,
           payment_status, created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$customerId]);
$orders = $stmt->fetchAll();

$pageTitle = 'Customer Details';

require_once __DIR__ . '/includes/header.php';

?>

<main class="admin-main">

    <h1>Customer Details</h1>

    <section class="admin-section">

        <h2><?= e($customer['name']) ?></h2>

        <p>Email: <?= e($customer['email']) ?></p>

        <p>
            Phone:
            <?= e($customer['phone'] ?? 'Not provided') ?>
        </p>

        <p>Status: <?= e(ucfirst($customer['status'])) ?></p>

        <p>Registered: <?= e($customer['created_at']) ?></p>

        <hr>

        <h3>Purchase Summary</h3>

        <p>Total Orders: <?= (int) $summary['total_orders'] ?></p>

        <p>
            Total Order Value:
            <strong><?= formatPrice($summary['total_spent']) ?></strong>
        </p>

    </section>

    <section class="admin-section" style="margin-top:25px;">

        <h2>Order History</h2>

        <div class="admin-table-wrapper">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($orders as $order): ?>

                        <tr>

                            <td><?= e($order['order_number']) ?></td>

                            <td><?= formatPrice($order['total_amount']) ?></td>

                            <td><?= e(ucfirst($order['status'])) ?></td>

                            <td><?= e(ucfirst($order['payment_status'])) ?></td>

                            <td><?= e($order['created_at']) ?></td>

                            <td>
                                <a href="order-details.php?id=<?= (int) $order['id'] ?>">
                                    View Order
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$orders): ?>
                        <tr>
                            <td colspan="6">This customer has no orders.</td>
                        </tr>
                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <p>
            <a href="customers.php">Back to Customers</a>
        </p>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
