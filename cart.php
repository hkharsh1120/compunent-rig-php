
<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart-functions.php';

$items = getCartItems($pdo);
$subtotal = cartSubtotal($items);

$pageTitle = 'Shopping Cart';

require_once __DIR__ . '/includes/header.php';

?>

<section class="section shopping-cart-section">
    <div class="container">

        <div class="cart-page-heading">
            <div>
                <span class="cart-eyebrow">YOUR SELECTION</span>
                <h1>Shopping Cart</h1>
                <p>Review your selected components before checkout.</p>
            </div>

            <a href="products.php" class="cart-continue-link">
                ← Continue Shopping
            </a>
        </div>

        <?php if (empty($items)): ?>

            <div class="cart-empty-state">

                <div class="cart-empty-icon">🛒</div>

                <h2>Your cart is empty</h2>

                <p>
                    You haven't added any products yet.
                    Explore our computer components and find what you need.
                </p>

                <a href="products.php" class="button">
                    Explore Products
                </a>

            </div>

        <?php else: ?>

            <div class="cart-content-layout">

                <!-- Cart Items -->
                <div class="cart-items-panel">

                    <div class="cart-items-heading">
                        <h2>Your Items</h2>
                        <span><?= count($items) ?> product(s)</span>
                    </div>

                    <?php foreach ($items as $item): ?>

                        <article class="premium-cart-item">

                            <a
                                href="product.php?id=<?= (int) $item['id'] ?>"
                                class="premium-cart-image"
                            >

                                <?php if (!empty($item['image_path'])): ?>

                                    <img
                                        src="<?= e($item['image_path']) ?>"
                                        alt="<?= e($item['name']) ?>"
                                    >

                                <?php else: ?>

                                    <span>No Image</span>

                                <?php endif; ?>

                            </a>

                            <div class="premium-cart-details">

                                <div class="premium-cart-product-info">

                                    <a
                                        href="product.php?id=<?= (int) $item['id'] ?>"
                                        class="premium-cart-name"
                                    >
                                        <?= e($item['name']) ?>
                                    </a>

                                    <p class="premium-cart-unit-price">
                                        <?= e(formatPrice($item['price'])) ?> each
                                    </p>

                                    <?php if (
                                        $item['status'] !== 'active'
                                        || (int) $item['stock_quantity'] < 1
                                    ): ?>

                                        <span class="cart-stock-warning">
                                            Currently unavailable
                                        </span>

                                    <?php elseif (
                                        (int) $item['stock_quantity'] < (int) $item['quantity']
                                    ): ?>

                                        <span class="cart-stock-warning">
                                            Only <?= (int) $item['stock_quantity'] ?> in stock
                                        </span>

                                    <?php else: ?>

                                        <span class="cart-stock-available">
                                            In Stock
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="premium-cart-actions">

                                    <form
                                        action="actions/cart-update.php"
                                        method="POST"
                                        class="premium-cart-quantity-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrfToken()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int) $item['id'] ?>"
                                        >

                                        <label for="quantity-<?= (int) $item['id'] ?>">
                                            Qty
                                        </label>

                                        <input
                                            type="number"
                                            id="quantity-<?= (int) $item['id'] ?>"
                                            name="quantity"
                                            value="<?= (int) $item['quantity'] ?>"
                                            min="1"
                                            max="100"
                                            required
                                        >

                                        <button type="submit">
                                            Update
                                        </button>

                                    </form>

                                    <form
                                        action="actions/cart-remove.php"
                                        method="POST"
                                        class="premium-cart-remove-form"
                                        onsubmit="return confirm('Remove this product from your cart?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrfToken()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?= (int) $item['id'] ?>"
                                        >

                                        <button type="submit">
                                            Remove
                                        </button>

                                    </form>

                                </div>

                            </div>

                            <div class="premium-cart-line-total">
                                <span>Item Total</span>
                                <strong>
                                    <?= e(formatPrice($item['line_total'])) ?>
                                </strong>
                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <!-- Order Summary -->
                <aside class="premium-cart-summary">

                    <h2>Order Summary</h2>

                    <div class="cart-summary-row">
                        <span>Products</span>
                        <strong><?= count($items) ?></strong>
                    </div>

                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <strong><?= e(formatPrice($subtotal)) ?></strong>
                    </div>

                    <div class="cart-summary-row">
                        <span>Shipping</span>
                        <span class="cart-summary-note">
                            Calculated at checkout
                        </span>
                    </div>

                    <div class="cart-summary-divider"></div>

                    <div class="cart-summary-total">
                        <span>Estimated Total</span>
                        <strong><?= e(formatPrice($subtotal)) ?></strong>
                    </div>

                    <p class="cart-summary-disclaimer">
                        Final shipping charges and order total will be
                        confirmed during checkout.
                    </p>

                    <a href="checkout.php" class="button cart-checkout-button">
                        Proceed to Checkout →
                    </a>

                    <a href="products.php" class="cart-summary-continue">
                        Continue Shopping
                    </a>

                    <div class="cart-payment-info">
                        <span>✓</span>
                        Cash on Delivery Available
                    </div>

                </aside>

            </div>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
