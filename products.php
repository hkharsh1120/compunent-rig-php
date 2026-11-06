
<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Shop Computer Components';

$search = trim($_GET['q'] ?? $_GET['search'] ?? '');
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$minPriceInput = trim($_GET['min_price'] ?? '');
$maxPriceInput = trim($_GET['max_price'] ?? '');

$minPrice = null;
$maxPrice = null;
$errors = [];

if ($categoryId !== null && $categoryId !== false && $categoryId < 1) {
    $errors[] = 'Please select a valid category.';
    $categoryId = null;
}

if ($minPriceInput !== '') {
    if (!is_numeric($minPriceInput) || (float) $minPriceInput < 0) {
        $errors[] = 'Enter a valid minimum price.';
    } else {
        $minPrice = (float) $minPriceInput;
    }
}

if ($maxPriceInput !== '') {
    if (!is_numeric($maxPriceInput) || (float) $maxPriceInput < 0) {
        $errors[] = 'Enter a valid maximum price.';
    } else {
        $maxPrice = (float) $maxPriceInput;
    }
}

if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
    $errors[] = 'Minimum price cannot exceed maximum price.';
}

$sortOptions = [
    'newest' => 'p.id DESC',
    'price_low' => 'p.price ASC, p.id DESC',
    'price_high' => 'p.price DESC, p.id DESC',
    'name' => 'p.name ASC',
];

$sort = $_GET['sort'] ?? 'newest';

if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page !== false && $page !== null && $page > 0)
    ? $page
    : 1;

$perPage = 8;

$where = ["p.status = 'active'"];
$params = [];

if ($search !== '') {
    $where[] = "(
        p.name LIKE :search_name
        OR p.brand LIKE :search_brand
        OR p.sku LIKE :search_sku
    )";

    $searchTerm = '%' . $search . '%';

    $params['search_name'] = $searchTerm;
    $params['search_brand'] = $searchTerm;
    $params['search_sku'] = $searchTerm;
}

if ($categoryId !== null && $categoryId !== false) {
    $where[] = 'p.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

if ($minPrice !== null) {
    $where[] = 'p.price >= :min_price';
    $params['min_price'] = $minPrice;
}

if ($maxPrice !== null) {
    $where[] = 'p.price <= :max_price';
    $params['max_price'] = $maxPrice;
}

$whereSql = implode(' AND ', $where);

$categoryStmt = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name ASC'
);
$categories = $categoryStmt->fetchAll();

$totalProducts = 0;
$totalPages = 0;
$products = [];

if (empty($errors)) {
    $countSql = "
        SELECT COUNT(*)
        FROM products AS p
        WHERE $whereSql
    ";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);

    $totalProducts = (int) $countStmt->fetchColumn();
    $totalPages = (int) ceil($totalProducts / $perPage);

    // Keep page numbers within the available range.
    if ($totalPages > 0 && $page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.brand,
            p.price,
            p.stock_quantity,
            c.name AS category_name,
            (
                SELECT pi.image_path
                FROM product_images AS pi
                WHERE pi.product_id = p.id
                  AND pi.is_primary = 1
                ORDER BY pi.id ASC
                LIMIT 1
            ) AS image_path
        FROM products AS p
        INNER JOIN categories AS c
            ON c.id = p.category_id
        WHERE $whereSql
        ORDER BY {$sortOptions[$sort]}
        LIMIT $perPage OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';

?>

<section class="section catalogue-section">
    <div class="container">

    <div class="section-heading catalogue-heading">
            <div>
                <h1>Computer Components</h1>
                <p class="muted">
                    Find the right parts for your setup.
                </p>
            </div>
        </div>

        <form method="GET" action="products.php" class="filter-form catalogue-filters">

            <div class="filter-field">
                <label for="q">Search products</label>
                <input
                    type="search"
                    id="q"
                    name="q"
                    placeholder="Name, brand or SKU"
                    value="<?= e($search) ?>"
                >
            </div>

            <div class="filter-field">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>

                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (string) $categoryId === (string) $category['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field">
                <label for="min_price">Minimum price (₹)</label>
                <input
                    type="number"
                    id="min_price"
                    name="min_price"
                    min="0"
                    step="0.01"
                    value="<?= e($minPriceInput) ?>"
                >
            </div>

            <div class="filter-field">
                <label for="max_price">Maximum price (₹)</label>
                <input
                    type="number"
                    id="max_price"
                    name="max_price"
                    min="0"
                    step="0.01"
                    value="<?= e($maxPriceInput) ?>"
                >
            </div>

            <div class="filter-field">
                <label for="sort">Sort by</label>
                <select id="sort" name="sort">
                    <option value="newest"
                        <?= $sort === 'newest' ? 'selected' : '' ?>>
                        Newest
                    </option>

                    <option value="price_low"
                        <?= $sort === 'price_low' ? 'selected' : '' ?>>
                        Price: Low to High
                    </option>

                    <option value="price_high"
                        <?= $sort === 'price_high' ? 'selected' : '' ?>>
                        Price: High to Low
                    </option>

                    <option value="name"
                        <?= $sort === 'name' ? 'selected' : '' ?>>
                        Name: A to Z
                    </option>
                </select>
            </div>

            <div class="filter-actions">
                <button class="button" type="submit">
                    Apply Filters
                </button>

                <a class="button reset-button" href="products.php">
                    Reset
                </a>
            </div>

        </form>

        <?php if (!empty($errors)): ?>

            <div class="empty-state">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <p class="catalogue-results">
    Showing <?= count($products) ?> of <?= $totalProducts ?> products
</p>

            <?php if (empty($products)): ?>

                <div class="empty-state">
                    <h3>No products found.</h3>
                    <p>Try changing your search or filters.</p>
                </div>

            <?php else: ?>

                <div class="product-grid catalogue-grid">

                    <?php foreach ($products as $product): ?>

                        <article class="product-card catalogue-card">

                            <a
                                class="product-image"
                                href="product.php?id=<?= (int) $product['id'] ?>"
                            >
                                <?php if (!empty($product['image_path'])): ?>
                                    <img
                                        src="<?= e($product['image_path']) ?>"
                                        alt="<?= e($product['name']) ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        Product image coming soon
                                    </div>
                                <?php endif; ?>
                            </a>

                            <div class="product-info">

                                <span class="product-category">
                                    <?= e($product['category_name']) ?>
                                </span>

                                <h3>
                                    <a href="product.php?id=<?= (int) $product['id'] ?>">
                                        <?= e($product['name']) ?>
                                    </a>
                                </h3>

                                <div class="product-brand">
                                    <?= e($product['brand'] ?: 'Unbranded') ?>
                                </div>

                                <div class="product-price">
                                    <?= e(formatPrice($product['price'])) ?>
                                </div>

                                <div class="stock <?= (int) $product['stock_quantity'] > 0
                                    ? 'in-stock'
                                    : 'out-of-stock' ?>">
                                    <?= (int) $product['stock_quantity'] > 0
                                        ? 'In stock'
                                        : 'Out of stock' ?>
                                </div>

                                <a
                                    class="button"
                                    href="product.php?id=<?= (int) $product['id'] ?>"
                                >
                                    View Product
                                </a>

                            </div>
                        </article>

                    <?php endforeach; ?>

                </div>

                <?php if ($totalPages > 1): ?>

                    <nav class="pagination catalogue-pagination" aria-label="Product pages">

                        <?php
                        $queryParams = $_GET;
                        ?>

                        <?php if ($page > 1): ?>
                            <?php
                            $queryParams['page'] = $page - 1;
                            ?>
                            <a href="?<?= e(http_build_query($queryParams)) ?>">
                                &laquo; Previous
                            </a>
                        <?php endif; ?>

                        <span>
                            Page <?= $page ?> of <?= $totalPages ?>
                        </span>

                        <?php if ($page < $totalPages): ?>
                            <?php
                            $queryParams['page'] = $page + 1;
                            ?>
                            <a href="?<?= e(http_build_query($queryParams)) ?>">
                                Next &raquo;
                            </a>
                        <?php endif; ?>

                    </nav>

                <?php endif; ?>

            <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
