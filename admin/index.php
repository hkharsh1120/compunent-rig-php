
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin($pdo);

// Dashboard statistics.
$stats = [];

$stats['products'] = (int) $pdo
    ->query("SELECT COUNT(*) FROM products")
    ->fetchColumn();

$stats['categories'] = (int) $pdo
    ->query("SELECT COUNT(*) FROM categories")
    ->fetchColumn();

$stats['customers'] = (int) $pdo
    ->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")
    ->fetchColumn();

$stats['orders'] = (int) $pdo
    ->query("SELECT COUNT(*) FROM orders")
    ->fetchColumn();

$stats['pending_orders'] = (int) $pdo
    ->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")
    ->fetchColumn();

$stats['revenue'] = (float) $pdo
    ->query("
        SELECT COALESCE(SUM(total_amount), 0)
        FROM orders
        WHERE status NOT IN ('cancelled')
    ")
    ->fetchColumn();

// Recent orders.
$stmt = $pdo->query("
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.payment_method,
        o.created_at,
        u.name AS customer_name
    FROM orders AS o
    INNER JOIN users AS u ON u.id = o.user_id
    ORDER BY o.id DESC
    LIMIT 5
");

$recentOrders = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';

require_once __DIR__ . '/includes/header.php';

?>

<main class="admin-main">

    <div class="admin-heading">
        <h1>Dashboard</h1>
        <p>Welcome to Compunent Rig administration.</p>
    </div>

    <div class="admin-stats">

        <div class="admin-stat-card">
            <h3>Total Products</h3>
            <strong><?= $stats['products'] ?></strong>
        </div>

        <div class="admin-stat-card">
            <h3>Categories</h3>
            <strong><?= $stats['categories'] ?></strong>
        </div>

        <div class="admin-stat-card">
            <h3>Customers</h3>
            <strong><?= $stats['customers'] ?></strong>
        </div>

        <div class="admin-stat-card">
            <h3>Total Orders</h3>
            <strong><?= $stats['orders'] ?></strong>
        </div>

        <div class="admin-stat-card">
            <h3>Pending Orders</h3>
            <strong><?= $stats['pending_orders'] ?></strong>
        </div>

        <div class="admin-stat-card">
            <h3>Order Revenue</h3>
            <strong><?= formatPrice($stats['revenue']) ?></strong>
        </div>

    </div>

    <section class="admin-section">

        <h2>Recent Orders</h2>

        <?php if (empty($recentOrders)): ?>

            <p>No orders found.</p>

        <?php else: ?>

            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($recentOrders as $order): ?>

                            <tr>
                                <td>
                                    <?= e($order['order_number']) ?>
                                </td>

                                <td>
                                    <?= e($order['customer_name']) ?>
                                </td>

                                <td>
                                    <?= formatPrice($order['total_amount']) ?>
                                </td>

                                <td>
                                    <?= e(strtoupper($order['payment_method'])) ?>
                                </td>

                                <td>
                                    <?= e(ucfirst($order['status'])) ?>
                                </td>

                                <td>
                                    <?= e($order['created_at']) ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
