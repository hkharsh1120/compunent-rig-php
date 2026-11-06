
<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart-functions.php';

requireLogin();

$items = getCartItems($pdo);

if (empty($items)) {
    header('Location: /compunent-rig/cart.php');
    exit;
}

$subtotal = cartSubtotal($items);
$shippingFee = 0.00;
$discountAmount = 0.00;
$totalAmount = $subtotal + $shippingFee - $discountAmount;

// Get customer's saved details.
$stmt = $pdo->prepare("
    SELECT name, phone
    FROM users
    WHERE id = ?
      AND status = 'active'
    LIMIT 1
");

$stmt->execute([currentUserId()]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: /compunent-rig/auth/logout.php');
    exit;
}

$pageTitle = 'Checkout';

require_once __DIR__ . '/includes/header.php';

?>

<section class="section premium-checkout-section">
    <div class="container">

        <!-- Checkout Heading -->
        <div class="checkout-page-heading">
            <div>
                <span class="checkout-eyebrow">ALMOST THERE</span>
                <h1>Checkout</h1>
                <p>Complete your shipping details to place your order.</p>
            </div>

            <a href="cart.php" class="checkout-back-link">
                ← Back to Cart
            </a>
        </div>

        <!-- Checkout Progress -->
        <div class="checkout-progress">
            <div class="checkout-progress-step completed">
                <span>1</span>
                <strong>Shopping Cart</strong>
            </div>

            <div class="checkout-progress-line active"></div>

            <div class="checkout-progress-step current">
                <span>2</span>
                <strong>Checkout</strong>
            </div>

            <div class="checkout-progress-line"></div>

            <div class="checkout-progress-step">
                <span>3</span>
                <strong>Confirmation</strong>
            </div>
        </div>

        <div class="premium-checkout-layout">

            <!-- Shipping Form -->
            <section class="checkout-form-panel">

                <form
                    method="POST"
                    action="actions/checkout-place.php"
                    class="premium-checkout-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrfToken()) ?>"
                    >

                    <!-- Contact Information -->
                    <div class="checkout-form-section">

                        <div class="checkout-section-heading">
                            <span class="checkout-section-number">01</span>

                            <div>
                                <h2>Contact Information</h2>
                                <p>Enter your delivery contact details.</p>
                            </div>
                        </div>

                        <div class="checkout-fields-grid">

                            <div class="checkout-field full-width">
                                <label for="shipping_name">Full Name</label>

                                <input
                                    type="text"
                                    id="shipping_name"
                                    name="shipping_name"
                                    value="<?= e($customer['name']) ?>"
                                    maxlength="100"
                                    autocomplete="name"
                                    placeholder="Enter your full name"
                                    required
                                >
                            </div>

                            <div class="checkout-field full-width">
                                <label for="shipping_phone">Phone Number</label>

                                <input
                                    type="tel"
                                    id="shipping_phone"
                                    name="shipping_phone"
                                    value="<?= e($customer['phone'] ?? '') ?>"
                                    maxlength="20"
                                    autocomplete="tel"
                                    placeholder="Enter your contact number"
                                    required
                                >
                            </div>

                        </div>

                    </div>

                    <!-- Shipping Address -->
                    <div class="checkout-form-section">

                        <div class="checkout-section-heading">
                            <span class="checkout-section-number">02</span>

                            <div>
                                <h2>Shipping Address</h2>
                                <p>Where should we deliver your order?</p>
                            </div>
                        </div>

                        <div class="checkout-fields-grid">

                            <div class="checkout-field full-width">
                                <label for="address_line1">Address Line 1</label>

                                <input
                                    type="text"
                                    id="address_line1"
                                    name="shipping_address_line1"
                                    maxlength="255"
                                    autocomplete="address-line1"
                                    placeholder="House number, street, area"
                                    required
                                >
                            </div>

                            <div class="checkout-field full-width">
                                <label for="address_line2">
                                    Address Line 2
                                    <span>(Optional)</span>
                                </label>

                                <input
                                    type="text"
                                    id="address_line2"
                                    name="shipping_address_line2"
                                    maxlength="255"
                                    autocomplete="address-line2"
                                    placeholder="Landmark, apartment, etc."
                                >
                            </div>

                            <div class="checkout-field">
                                <label for="shipping_city">City</label>

                                <input
                                    type="text"
                                    id="shipping_city"
                                    name="shipping_city"
                                    maxlength="100"
                                    autocomplete="address-level2"
                                    placeholder="Your city"
                                    required
                                >
                            </div>

                            <div class="checkout-field">
                                <label for="shipping_state">State</label>

                                <input
                                    type="text"
                                    id="shipping_state"
                                    name="shipping_state"
                                    maxlength="100"
                                    autocomplete="address-level1"
                                    placeholder="Your state"
                                    required
                                >
                            </div>

                            <div class="checkout-field">
                                <label for="postal_code">Postal Code</label>

                                <input
                                    type="text"
                                    id="postal_code"
                                    name="shipping_postal_code"
                                    maxlength="20"
                                    autocomplete="postal-code"
                                    placeholder="PIN code"
                                    required
                                >
                            </div>

                            <div class="checkout-field">
                                <label for="shipping_country">Country</label>

                                <input
                                    type="text"
                                    id="shipping_country"
                                    name="shipping_country"
                                    value="India"
                                    maxlength="100"
                                    autocomplete="country-name"
                                    required
                                >
                            </div>

                        </div>

                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-form-section">

                        <div class="checkout-section-heading">
                            <span class="checkout-section-number">03</span>

                            <div>
                                <h2>Payment Method</h2>
                                <p>Choose how you want to pay.</p>
                            </div>
                        </div>

                        <div class="checkout-payment-card">

                            <div class="checkout-payment-radio">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="cod"
                                    checked
                                    disabled
                                >
                            </div>

                            <div class="checkout-payment-icon">
                                ₹
                            </div>

                            <div class="checkout-payment-details">
                                <strong>Cash on Delivery</strong>
                                <p>Pay when your order is delivered.</p>
                            </div>

                            <span class="checkout-payment-badge">
                                Available
                            </span>

                        </div>

                    </div>

                    <button type="submit" class="checkout-place-order-button">
                        Place Order — Cash on Delivery
                        <span>→</span>
                    </button>

                    <p class="checkout-secure-note">
                        ✓ Your order will be placed securely.
                    </p>

                </form>

            </section>

            <!-- Order Summary -->
            <aside class="premium-checkout-summary">

                <div class="checkout-summary-heading">
                    <h2>Your Order</h2>
                    <span><?= count($items) ?> item(s)</span>
                </div>

                <div class="checkout-summary-products">

                    <?php foreach ($items as $item): ?>

                        <div class="premium-checkout-item">

                            <div class="checkout-item-image">

                                <?php if (!empty($item['image_path'])): ?>

                                    <img
                                        src="<?= e($item['image_path']) ?>"
                                        alt="<?= e($item['name']) ?>"
                                    >

                                <?php else: ?>

                                    <span>No Image</span>

                                <?php endif; ?>

                                <span class="checkout-item-quantity">
                                    <?= (int) $item['quantity'] ?>
                                </span>

                            </div>

                            <div class="checkout-item-info">
                                <strong><?= e($item['name']) ?></strong>

                                <span>
                                    <?= e(formatPrice($item['price'])) ?> each
                                </span>
                            </div>

                            <strong class="checkout-item-total">
                                <?= e(formatPrice($item['line_total'])) ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="checkout-summary-calculations">

                    <div class="checkout-calculation-row">
                        <span>Subtotal</span>
                        <strong><?= e(formatPrice($subtotal)) ?></strong>
                    </div>

                    <div class="checkout-calculation-row">
                        <span>Shipping</span>

                        <strong>
                            <?= $shippingFee == 0
                                ? 'Free'
                                : e(formatPrice($shippingFee)) ?>
                        </strong>
                    </div>

                    <div class="checkout-calculation-row">
                        <span>Discount</span>

                        <strong>
                            <?= e(formatPrice($discountAmount)) ?>
                        </strong>
                    </div>

                    <div class="checkout-total-row">
                        <span>Total</span>
                        <strong><?= e(formatPrice($totalAmount)) ?></strong>
                    </div>

                </div>

                <div class="checkout-summary-payment">
                    <span>PAYMENT METHOD</span>
                    <strong>Cash on Delivery</strong>
                </div>

                <div class="checkout-summary-support">
                    <span>✓</span>
                    Review your delivery details before placing your order.
                </div>

            </aside>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
