
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Compunent Rig';
$baseUrl = '/compunent-rig';

// Calculate cart item count.
$cartCount = 0;

if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $quantity) {
        $cartCount += (int) $quantity;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Explore computer components and build your custom PC with Compunent Rig.">

    <title><?= e($pageTitle) ?> | Compunent Rig</title>

    <link rel="stylesheet"
    href="<?= $baseUrl ?>/assets/css/style.css?v=8"
</head>

<body>

<header class="site-header">

    <!-- TOP HEADER -->
    <div class="header-main">
        <div class="container header-main-inner">

            <a class="logo" href="<?= $baseUrl ?>/index.php">
                COMPUNENT<span>RIG</span>
            </a>

            <!-- PRODUCT SEARCH -->
            <form class="header-search"
                  method="GET"
                  action="<?= $baseUrl ?>/products.php">

                <input
                    type="search"
                    name="search"
                    placeholder="Search CPUs, GPUs, RAM, components..."
                    aria-label="Search products"
                >

                <button type="submit" aria-label="Search">
                    Search
                </button>

            </form>

            <!-- ACCOUNT AND CART -->
            <div class="header-actions">

                <?php if (!empty($_SESSION['user_id'])): ?>

                    <a class="header-account"
                       href="<?= $baseUrl ?>/account/index.php">

                        <span class="header-action-label">ACCOUNT</span>

                        <strong>
                            <?= e($_SESSION['user_name'] ?? 'Customer') ?>
                        </strong>

                    </a>

                    <form method="POST"
                          action="<?= $baseUrl ?>/auth/logout.php"
                          class="header-logout">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >

                        <button type="submit">Logout</button>

                    </form>

                <?php else: ?>

                    <a class="header-account"
                       href="<?= $baseUrl ?>/auth/login.php">

                        <span class="header-action-label">WELCOME</span>
                        <strong>Login / Register</strong>

                    </a>

                <?php endif; ?>

                <a class="header-cart"
                   href="<?= $baseUrl ?>/cart.php">

                    <span class="cart-icon">🛒</span>
                    <span>Cart</span>

                    <span class="cart-count">
                        <?= $cartCount ?>
                    </span>

                </a>

            </div>

        </div>
    </div>

    <!-- BOTTOM NAVIGATION -->
    <div class="header-navigation">
        <div class="container">

            <nav class="nav-links">

                <a href="<?= $baseUrl ?>/index.php">Home</a>

                <a href="<?= $baseUrl ?>/products.php">All Products</a>

                <a href="<?= $baseUrl ?>/pc-builder/">PC Builder</a>

            </nav>

        </div>
    </div>

</header>

<main>
