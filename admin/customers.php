
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin($pdo);

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        u.phone,
        u.status,
        u.created_at,
        COUNT(o.id) AS total_orders
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'customer'
";

$params = [];

if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
}

$sql .= "
    GROUP BY u.id, u.name, u.email, u.phone, u.status, u.created_at
    ORDER BY u.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customer Management';

require_once __DIR__ . '/includes/header.php';

?>

<main class="admin-main">

    <h1>Customer Management</h1>
    <p>View registered customers and their purchase history.</p>

    <form method="GET" class="admin-search-form">

        <input
            type="search"
            name="search"
            placeholder="Search name, email or phone..."
            value="<?= e($search) ?>"
        >

        <button type="submit">Search</button>
        <a href="customers.php">Reset</a>

    </form>

    <section class="admin-section">

        <h2>Customers (<?= count($customers) ?>)</h2>

        <div class="admin-table-wrapper">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($customers as $customer): ?>

                        <tr>

                            <td><?= e($customer['name']) ?></td>
                            <td><?= e($customer['email']) ?></td>
                            <td><?= e($customer['phone'] ?? 'Not provided') ?></td>
                            <td><?= (int) $customer['total_orders'] ?></td>
                            <td><?= e(ucfirst($customer['status'])) ?></td>
                            <td><?= e($customer['created_at']) ?></td>

                            <td>
                                <a href="customer-details.php?id=<?= (int) $customer['id'] ?>">
                                    View Details
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$customers): ?>
                        <tr>
                            <td colspan="7">No customers found.</td>
                        </tr>
                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
