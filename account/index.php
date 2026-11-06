
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = currentUserId();

// Fetch current customer details
$userStmt = $pdo->prepare("
    SELECT id, name, email, phone, role, created_at
    FROM users
    WHERE id = ?
      AND status = 'active'
    LIMIT 1
");

$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if (!$user || $user['role'] !== 'customer') {
    http_response_code(403);
    exit('Customer account access denied.');
}

// Fetch order statistics
$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_spent
    FROM orders
    WHERE user_id = ?
");

$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

// Fetch recent orders
$orderStmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        status,
        payment_method,
        payment_status,
        total_amount,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");

$orderStmt->execute([$userId]);
$orders = $orderStmt->fetchAll();

$pageTitle = 'My Account';

require_once __DIR__ . '/../includes/header.php';

?>

<section class="section account-section">
    <div class="container">

        <div class="account-welcome">
            <div>
                <span class="account-eyebrow">CUSTOMER DASHBOARD</span>
                <h1>Welcome, <?= e($user['name']) ?>!</h1>
                <p>Manage your account and track your orders.</p>
            </div>

            <a href="<?= e('/compunent-rig/products.php') ?>" class="button">
                Continue Shopping
            </a>
        </div>

        <div class="account-stats">

            <div class="account-stat-card">
                <span>Total Orders</span>
                <strong><?= (int) $stats['total_orders'] ?></strong>
            </div>

            <div class="account-stat-card">
                <span>Total Order Value</span>
                <strong><?= e(formatPrice($stats['total_spent'])) ?></strong>
            </div>

            <div class="account-stat-card">
                <span>Account Type</span>
                <strong>Customer</strong>
            </div>

        </div>

        <div class="account-layout">

            <aside class="account-sidebar">

                <h3>MY ACCOUNT</h3>

                <a href="index.php" class="account-nav-link active">
                    Dashboard
                </a>

                <a href="orders.php" class="account-nav-link">
                    My Orders
                </a>

                <a href="profile.php" class="account-nav-link">
                    Profile Settings
                </a>

                <a href="change-password.php" class="account-nav-link">
                    Change Password
                </a>

                <a href="<?= e('/compunent-rig/products.php') ?>" class="account-nav-link">
                    Continue Shopping
                </a>

            </aside>

            <div class="account-main">

                <div class="account-panel">

                    <div class="account-panel-heading">
                        <div>
                            <h2>Recent Orders</h2>
                            <p>Your latest purchases.</p>
                        </div>

                        <a href="orders.php">View All</a>
                    </div>

                    <?php if (!empty($orders)): ?>

                        <div class="account-table-wrapper">

                            <table class="account-orders-table">

                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($orders as $order): ?>

                                        <tr>
                                            <td>
                                                <strong>
                                                    <?= e($order['order_number']) ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <?= e(date(
                                                    'd M Y',
                                                    strtotime($order['created_at'])
                                                )) ?>
                                            </td>

                                            <td>
                                                <?= e(formatPrice($order['total_amount'])) ?>
                                            </td>

                                            <td>
                                                <span class="account-order-status">
                                                    <?= e(ucwords(str_replace(
                                                        '_',
                                                        ' ',
                                                        $order['status']
                                                    ))) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <a
                                                    href="order-details.php?id=<?= (int) $order['id'] ?>"
                                                    class="account-view-link"
                                                >
                                                    View
                                                </a>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="account-empty-state">
                            <h3>No orders yet</h3>
                            <p>Your orders will appear here after your first purchase.</p>

                            <a href="<?= e('/compunent-rig/products.php') ?>" class="button">
                                Explore Products
                            </a>
                        </div>

                    <?php endif; ?>

                </div>

                <div class="account-panel account-profile-panel">

                    <div class="account-panel-heading">
                        <div>
                            <h2>Account Information</h2>
                            <p>Your registered customer details.</p>
                        </div>

                        <a href="profile.php">Edit Profile</a>
                    </div>

                    <div class="account-information-grid">

                        <div>
                            <span>Full Name</span>
                            <strong><?= e($user['name']) ?></strong>
                        </div>

                        <div>
                            <span>Email Address</span>
                            <strong><?= e($user['email']) ?></strong>
                        </div>

                        <div>
                            <span>Phone Number</span>
                            <strong><?= e($user['phone'] ?: 'Not provided') ?></strong>
                        </div>

                        <div>
                            <span>Member Since</span>
                            <strong>
                                <?= e(date('d M Y', strtotime($user['created_at']))) ?>
                            </strong>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
