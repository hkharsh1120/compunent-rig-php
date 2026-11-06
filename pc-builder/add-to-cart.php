
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/pc-builder-functions.php';

function builderRedirectWithError(string $message): void
{
    header(
        'Location: /compunent-rig/pc-builder/index.php?error=' .
        urlencode($message)
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /compunent-rig/pc-builder/index.php');
    exit;
}

// CSRF protection
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    builderRedirectWithError('Invalid request. Please try again.');
}

$allowedTypes = [
    'cpu',
    'motherboard',
    'ram',
    'gpu',
    'storage',
    'psu',
    'case',
];

$submittedComponents = $_POST['components'] ?? [];

if (!is_array($submittedComponents)) {
    builderRedirectWithError('Invalid component selection.');
}

// Load current active, in-stock products from the database.
$availableProducts = getPcBuilderProducts($pdo);

$availableById = [];

foreach ($availableProducts as $type => $items) {
    foreach ($items as $item) {
        $availableById[(int) $item['id']] = $item;
    }
}

$selectedProducts = [];

foreach ($submittedComponents as $type => $productId) {
    if (!in_array($type, $allowedTypes, true)) {
        builderRedirectWithError('Invalid component type.');
    }

    if ($productId === '' || $productId === null) {
        continue;
    }

    $productId = filter_var($productId, FILTER_VALIDATE_INT);

    if ($productId === false || $productId < 1) {
        builderRedirectWithError('Invalid product selection.');
    }

    if (!isset($availableById[$productId])) {
        builderRedirectWithError(
            'A selected product is unavailable or out of stock. Please refresh the builder.'
        );
    }

    $product = $availableById[$productId];

    // Ensure the submitted product belongs to the submitted component type.
    if ($product['component_type'] !== $type) {
        builderRedirectWithError('Invalid component selection.');
    }

    $selectedProducts[$type] = $product;
}

if (empty($selectedProducts)) {
    builderRedirectWithError('Please select at least one component.');
}

// Server-side compatibility check.
$compatibility = checkPcBuildCompatibility($selectedProducts);

if (!$compatibility['is_compatible']) {
    builderRedirectWithError(
        'Your build has compatibility errors: ' .
        implode(' ', $compatibility['errors'])
    );
}

// Check all cart quantities before changing the session cart.
$currentCart = $_SESSION['cart'] ?? [];
$updatedCart = $currentCart;

foreach ($selectedProducts as $product) {
    $productId = (int) $product['id'];
    $currentQuantity = (int) ($updatedCart[$productId] ?? 0);
    $newQuantity = $currentQuantity + 1;
    $stock = (int) $product['stock_quantity'];

    if ($newQuantity > $stock || $newQuantity > 100) {
        builderRedirectWithError(
            'Insufficient stock for ' . $product['name'] . '.'
        );
    }

    $updatedCart[$productId] = $newQuantity;
}

// Apply changes only after every selected product passes validation.
$_SESSION['cart'] = $updatedCart;

header('Location: /compunent-rig/cart.php');
exit;
