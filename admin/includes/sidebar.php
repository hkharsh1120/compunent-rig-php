
<?php

$currentAdminPage = basename($_SERVER['PHP_SELF']);

$adminLinks = [
    [
        'label' => 'Dashboard',
        'url' => '/compunent-rig/admin/index.php',
        'page' => 'index.php',
        'icon' => '▦'
    ],
    [
        'label' => 'Products',
        'url' => '/compunent-rig/admin/products.php',
        'page' => 'products.php',
        'icon' => '▣'
    ],
    [
        'label' => 'Categories',
        'url' => '/compunent-rig/admin/categories.php',
        'page' => 'categories.php',
        'icon' => '▤'
    ],
    [
        'label' => 'Orders',
        'url' => '/compunent-rig/admin/orders.php',
        'page' => 'orders.php',
        'icon' => '▧'
    ],
    [
        'label' => 'Customers',
        'url' => '/compunent-rig/admin/customers.php',
        'page' => 'customers.php',
        'icon' => '♙'
    ],
];

?>

<aside class="admin-sidebar">

    <a href="/compunent-rig/admin/index.php" class="admin-brand">
        <span class="admin-brand-name">
            COMPUNENT<span>RIG</span>
        </span>

        <span class="admin-label">ADMIN CONTROL PANEL</span>
    </a>

    <div class="admin-nav-heading">MAIN MENU</div>

    <nav class="admin-nav">

        <?php foreach ($adminLinks as $link): ?>

            <a
                href="<?= e($link['url']) ?>"
                class="<?= $currentAdminPage === $link['page'] ? 'active' : '' ?>"
                <?= $currentAdminPage === $link['page'] ? 'aria-current="page"' : '' ?>
            >
                <span class="admin-nav-icon">
                    <?= e($link['icon']) ?>
                </span>

                <span><?= e($link['label']) ?></span>

                <?php if ($currentAdminPage === $link['page']): ?>
                    <span class="admin-active-indicator"></span>
                <?php endif; ?>

            </a>

        <?php endforeach; ?>

    </nav>

    <div class="admin-sidebar-bottom">

        <a href="/compunent-rig/" class="admin-website-link">
            <span class="admin-nav-icon">↗</span>
            Visit Website
        </a>

        <form action="/compunent-rig/auth/logout.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <button type="submit" class="admin-logout">
                <span>⇥</span>
                Logout
            </button>

        </form>

    </div>

</aside>
