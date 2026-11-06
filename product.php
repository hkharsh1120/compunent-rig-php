<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    http_response_code(404);
    exit('Product not found.');
}

// Fetch product
$sql = "
    SELECT
        p.id,
        p.name,
        p.slug,
        p.sku,
        p.brand,
        p.description,
        p.price,
        p.stock_quantity,
        p.product_type,
        c.name AS category_name
    FROM products AS p
    INNER JOIN categories AS c ON c.id = p.category_id
    WHERE p.id = :id
      AND p.status = 'active'
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

// Fetch product images
$imageStmt = $pdo->prepare("
    SELECT image_path, alt_text, is_primary
    FROM product_images
    WHERE product_id = :product_id
    ORDER BY is_primary DESC, id ASC
");

$imageStmt->execute(['product_id' => $productId]);
$images = $imageStmt->fetchAll();

// Fetch specifications
$specStmt = $pdo->prepare("
    SELECT spec_key, spec_value
    FROM product_specs
    WHERE product_id = :product_id
    ORDER BY spec_key ASC
");

$specStmt->execute(['product_id' => $productId]);
$specifications = $specStmt->fetchAll();

$pageTitle = $product['name'];

require_once __DIR__ . '/includes/header.php';

?>

<section class="section product-detail-section">
    <div class="container">

        <!-- Breadcrumb -->
        <nav class="product-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="products.php">Products</a>
            <span>/</span>
            <a href="products.php?category=<?= urlencode($product['category_name']) ?>">
                <?= e($product['category_name']) ?>
            </a>
            <span>/</span>
            <span class="breadcrumb-current"><?= e($product['name']) ?></span>
        </nav>

        <div class="product-detail-layout">

            <!-- Product Gallery -->
            <div class="product-gallery-panel">

                <?php if (!empty($images)): ?>

                    <div class="product-main-image">
                        <img
                            id="mainProductImage"
                            src="<?= e($images[0]['image_path']) ?>"
                            alt="<?= e($images[0]['alt_text'] ?: $product['name']) ?>"
                        >
                    </div>

                    <?php if (count($images) > 1): ?>
                        <div class="product-thumbnail-list">

                            <?php foreach ($images as $index => $image): ?>
                                <button
                                    type="button"
                                    class="product-thumbnail <?= $index === 0 ? 'active' : '' ?>"
                                    onclick="changeProductImage(this)"
                                    data-image="<?= e($image['image_path']) ?>"
                                    data-alt="<?= e($image['alt_text'] ?: $product['name']) ?>"
                                    aria-label="View product image <?= $index + 1 ?>"
                                >
                                    <img
                                        src="<?= e($image['image_path']) ?>"
                                        alt=""
                                    >
                                </button>
                            <?php endforeach; ?>

                        </div>
                    <?php endif; ?>

                <?php else: ?>

                    <div class="product-main-image product-image-placeholder">
                        <span>No product image available</span>
                    </div>

                <?php endif; ?>

            </div>

            <!-- Product Information -->
            <div class="product-detail-info">

                <div class="product-detail-category">
                    <?= e($product['category_name']) ?>
                </div>

                <h1><?= e($product['name']) ?></h1>

                <div class="product-detail-meta">
                    <span>
                        Brand: <strong><?= e($product['brand'] ?: 'Unbranded') ?></strong>
                    </span>

                    <span>
                        SKU: <strong><?= e($product['sku']) ?></strong>
                    </span>
                </div>

                <div class="product-detail-price">
                    <?= e(formatPrice($product['price'])) ?>
                </div>

                <?php if ((int) $product['stock_quantity'] > 0): ?>

                    <div class="product-stock-status available">
                        <span class="stock-indicator"></span>
                        In Stock
                        <span class="stock-quantity">
                            (<?= (int) $product['stock_quantity'] ?> available)
                        </span>
                    </div>

                <?php else: ?>

                    <div class="product-stock-status unavailable">
                        <span class="stock-indicator"></span>
                        Currently Out of Stock
                    </div>

                <?php endif; ?>

                <div class="product-detail-divider"></div>

                <!-- Description -->
                <div class="product-description-block">
                    <h2>Product Description</h2>

                    <p>
                        <?= nl2br(e(
                            $product['description'] ?: 'No description is available for this product yet.'
                        )) ?>
                    </p>
                </div>

                <!-- Add to Cart -->
                <?php if ((int) $product['stock_quantity'] > 0): ?>

                    <form
                        action="actions/cart-add.php"
                        method="POST"
                        class="product-add-cart-form"
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

                        <div class="product-quantity-control">
                            <label for="quantity">Quantity</label>

                            <input
                                type="number"
                                name="quantity"
                                id="quantity"
                                min="1"
                                max="<?= (int) $product['stock_quantity'] ?>"
                                value="1"
                                required
                            >
                        </div>

                        <button class="button product-add-button" type="submit">
                            <span>Add to Cart</span>
                        </button>

                    </form>

                <?php else: ?>

                    <button class="button product-add-button" type="button" disabled>
                        Out of Stock
                    </button>

                <?php endif; ?>

                <div class="product-detail-benefits">
                    <div>
                        <span class="benefit-icon">✓</span>
                        Quality Computer Components
                    </div>

                    <div>
                        <span class="benefit-icon">✓</span>
                        Secure Cash on Delivery
                    </div>

                    <div>
                        <span class="benefit-icon">✓</span>
                        Build Your Custom PC
                    </div>
                </div>

            </div>

        </div>

        <!-- Specifications -->
        <?php if (!empty($specifications)): ?>

            <section class="product-specifications-section">

                <div class="product-section-heading">
                    <span>TECHNICAL DETAILS</span>
                    <h2>Specifications</h2>
                    <p>Detailed technical information about this product.</p>
                </div>

                <div class="product-specifications-card">

                    <table class="product-specifications-table">
                        <tbody>

                            <?php foreach ($specifications as $spec): ?>

                                <tr>
                                    <th scope="row">
                                        <?= e($spec['spec_key']) ?>
                                    </th>

                                    <td>
                                        <?= e($spec['spec_value']) ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

            </section>

        <?php endif; ?>

        <div class="product-back-link">
            <a href="products.php">← Back to All Products</a>
        </div>

    </div>
</section>

<script>
function changeProductImage(button) {
    const mainImage = document.getElementById('mainProductImage');

    if (!mainImage) {
        return;
    }

    mainImage.src = button.dataset.image;
    mainImage.alt = button.dataset.alt;

    document.querySelectorAll('.product-thumbnail').forEach(function (thumbnail) {
        thumbnail.classList.remove('active');
    });

    button.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>