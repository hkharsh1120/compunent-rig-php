
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin($pdo);

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        p.id,
        p.name,
        p.sku,
        p.brand,
        p.price,
        p.stock_quantity,
        p.status,
        c.name AS category_name,
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
              AND pi.is_primary = 1
            ORDER BY pi.id ASC
            LIMIT 1
        ) AS image_path
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE p.name LIKE ?
           OR p.sku LIKE ?
           OR p.brand LIKE ?
    ";

    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll();

$pageTitle = 'Product Management';

require_once __DIR__ . '/includes/header.php';

?>

<main class="admin-main">

    <div class="admin-heading admin-page-heading">

        <div>
            <h1>Products</h1>
            <p>Manage your computer components and products.</p>
        </div>

        <a href="product-form.php" class="admin-primary-btn">
            + Add Product
        </a>

    </div>

    <?php if (isset($_GET['success'])): ?>
        <p class="admin-success">Product saved successfully.</p>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <p class="admin-success">Product deleted successfully.</p>
    <?php endif; ?>

    <form method="GET" class="admin-search-form">

        <input
            type="search"
            name="search"
            placeholder="Search by product, SKU or brand..."
            value="<?= e($search) ?>"
        >

        <button type="submit">Search</button>

        <a href="products.php">Reset</a>

    </form>

    <section class="admin-section">

        <h2>All Products (<?= count($products) ?>)</h2>

        <div class="admin-table-wrapper">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($products)): ?>

                        <tr>
                            <td colspan="8">No products found.</td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($products as $product): ?>

                            <tr>

                                <td>
                                    <?php if ($product['image_path']): ?>
                                        <img
                                            src="/compunent-rig/<?= e($product['image_path']) ?>"
                                            alt="<?= e($product['name']) ?>"
                                            class="admin-product-image"
                                        >
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong><?= e($product['name']) ?></strong>
                                    <br>
                                    <small><?= e($product['brand'] ?? '') ?></small>
                                </td>

                                <td><?= e($product['sku']) ?></td>

                                <td><?= e($product['category_name'] ?? 'Uncategorized') ?></td>

                                <td><?= formatPrice($product['price']) ?></td>

                                <td><?= (int) $product['stock_quantity'] ?></td>

                                <td><?= e(ucfirst($product['status'])) ?></td>

                                <td>
                                    <a href="product-form.php?id=<?= (int) $product['id'] ?>">
                                        Edit
                                    </a>

                                    <form
                                        action="product-delete.php"
                                        method="POST"
                                        class="admin-inline-form"
                                        onsubmit="return confirm('Delete this product?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrfToken()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int) $product['id'] ?>"
                                        >

                                        <button type="submit" class="admin-delete-btn">
                                            Delete
                                        </button>

                                    </form>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
