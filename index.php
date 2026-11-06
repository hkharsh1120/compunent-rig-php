
<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Computer Components & Custom PC Builds';

$products = getFeaturedProducts($pdo, 8);

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO SECTION -->
<section class="home-hero">
    <div class="container home-hero-content">

        <div class="hero-text">
            <span class="eyebrow">WELCOME TO COMPUNENT RIG</span>

            <h1>Build Your Dream PC.<br>
                <span>Your Setup Starts Here.</span>
            </h1>

            <p>
                Discover quality computer components, upgrade your setup,
                and create a custom PC that matches your performance needs
                and budget.
            </p>

            <div class="hero-actions">
                <a class="button" href="products.php">
                    Explore Components
                </a>

                <a class="button button-outline"
                   href="pc-builder/">
                    Build Your PC
                </a>
            </div>

            <div class="hero-note">
                <span>✓</span> Explore components
                <span>✓</span> Compare specifications
                <span>✓</span> Build your setup
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-visual-card">
                <div class="visual-top">
                    <span class="visual-dot"></span>
                    <span class="visual-dot"></span>
                    <span class="visual-dot"></span>
                    <span class="visual-label">CUSTOM PC BUILDER</span>
                </div>

                <div class="visual-chip">
                    <div class="chip-inner">
                        <span>CR</span>
                    </div>
                </div>

                <div class="visual-bottom">
                    <strong>DESIGN YOUR BUILD</strong>
                    <span>Choose. Customize. Build.</span>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- BENEFITS SECTION -->
<section class="benefits-section">
    <div class="container benefits-grid">

        <div class="benefit-item">
            <span class="benefit-icon">⚙</span>
            <div>
                <strong>Quality Components</strong>
                <p>Explore hardware for your setup.</p>
            </div>
        </div>

        <div class="benefit-item">
            <span class="benefit-icon">⌘</span>
            <div>
                <strong>Custom PC Builder</strong>
                <p>Choose compatible PC components.</p>
            </div>
        </div>

        <div class="benefit-item">
            <span class="benefit-icon">↗</span>
            <div>
                <strong>Easy Shopping</strong>
                <p>Browse and order components easily.</p>
            </div>
        </div>

    </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="section home-products">
    <div class="container">

        <div class="section-heading">
            <div>
                <span class="section-kicker">OUR COLLECTION</span>
                <h2>Featured Products</h2>
                <p class="muted">
                    Explore computer hardware and components for your next build.
                </p>
            </div>

            <a class="section-link" href="products.php">
                View All Products →
            </a>
        </div>

        <?php if (empty($products)): ?>

            <div class="empty-state">
                <h3>No products available yet.</h3>
                <p class="muted">
                    Products will appear here when they are added to the store.
                </p>
                <a class="button" href="products.php">Browse Products</a>
            </div>

        <?php else: ?>

            <div class="product-grid">

                <?php foreach ($products as $product): ?>

                    <article class="product-card">

                        <a class="product-image"
                           href="product.php?id=<?= (int) $product['id'] ?>">

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

                            <?php if ((int) $product['stock_quantity'] > 0): ?>

                                <div class="stock in-stock">
                                    ✓ In Stock
                                </div>

                            <?php else: ?>

                                <div class="stock out-of-stock">
                                    Out of Stock
                                </div>

                            <?php endif; ?>

                            <a class="button"
                               href="product.php?id=<?= (int) $product['id'] ?>">
                                View Product
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>
</section>

<!-- PC BUILDER PROMOTION -->
<section class="builder-promo-section">
    <div class="container">

        <div class="builder-promo">

            <div class="builder-promo-content">
                <span class="eyebrow">YOUR BUILD. YOUR RULES.</span>

                <h2>Ready to Build Your Own PC?</h2>

                <p>
                    Select your processor, motherboard, RAM, graphics card
                    and other components. Check compatibility and calculate
                    your build price in one place.
                </p>

                <a class="button builder-promo-button"
                   href="pc-builder/">
                    Start Building →
                </a>
            </div>

            <div class="builder-promo-graphic">
                <div class="promo-circle promo-circle-one"></div>
                <div class="promo-circle promo-circle-two"></div>
                <div class="promo-center">
                    <span>CR</span>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- BOTTOM CTA -->
<section class="section home-bottom">
    <div class="container">

        <div class="home-bottom-content">
            <h2>Find the Right Components for Your Setup.</h2>

            <p class="muted">
                Explore our product collection and discover your next upgrade.
            </p>

            <a href="products.php" class="button">
                Explore All Products
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
