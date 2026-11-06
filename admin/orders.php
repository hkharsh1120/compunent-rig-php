
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin($pdo);

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$allowedStatuses = [
    'pending',
    'processing',
    'shipped',
    'delivered',
    'cancelled'
];

$sql = "
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.payment_status,
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email
    FROM orders o
    INNER JOIN users u ON u.id = o.user_id
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            o.order_number LIKE ?
            OR u.name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
}

if (in_array($status, $allowedStatuses, true)) {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY o.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$orders = $stmt->fetchAll();

$pageTitle = 'Order Management';

require_once __DIR__ . '/includes/header.php';

?>

<main class="admin-main">

    <h1>Order Management</h1>

    <form method="GET" class="admin-search-form">

        <input type="search" name="search"
               placeholder="Search order number or customer..."
               value="<?= e($search) ?>">

        <select name="status">
            <option value="">All Statuses</option>

            <?php foreach ($allowedStatuses as $item): ?>
                <option value="<?= e($item) ?>"
                    <?= $status === $item ? 'selected' : '' ?>>
                    <?= e(ucfirst($item)) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <button type="submit">Filter</button>

        <a href="orders.php">Reset</a>

    </form>

    <section class="admin-section">

        <h2>Orders (<?= count($orders) ?>)</h2>

        <div class="admin-table-wrapper">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Order Status</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($orders as $order): ?>

                        <tr>
                            <td><?= e($order['order_number']) ?></td>

                            <td>
                                <?= e($order['customer_name']) ?>
                                <br>
                                <small><?= e($order['customer_email']) ?></small>
                            </td>

                            <td><?= formatPrice($order['total_amount']) ?></td>

                            <td><?= e(ucfirst($order['status'])) ?></td>

                            <td>
                                <?= e(strtoupper($order['payment_status'])) ?>
                            </td>

                            <td><?= e($order['created_at']) ?></td>

                            <td>
                                <a href="order-details.php?id=<?= (int) $order['id'] ?>">
                                    View Details
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$orders): ?>
                        <tr><td colspan="7">No orders found.</td></tr>
                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
