
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

class CheckoutException extends RuntimeException {}

// Only POST requests are allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

// Customer must be logged in.
requireLogin();

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form token. Refresh checkout and try again.');
}

// Validate shipping information.
$shippingName = trim($_POST['shipping_name'] ?? '');
$shippingPhone = trim($_POST['shipping_phone'] ?? '');
$addressLine1 = trim($_POST['shipping_address_line1'] ?? '');
$addressLine2 = trim($_POST['shipping_address_line2'] ?? '');
$shippingCity = trim($_POST['shipping_city'] ?? '');
$shippingState = trim($_POST['shipping_state'] ?? '');
$postalCode = trim($_POST['shipping_postal_code'] ?? '');
$shippingCountry = trim($_POST['shipping_country'] ?? '');

if (
    $shippingName === ''
    || strlen($shippingName) > 100
    || $shippingPhone === ''
    || strlen($shippingPhone) > 20
    || !preg_match('/^[0-9+\-\s()]{7,20}$/', $shippingPhone)
    || $addressLine1 === ''
    || strlen($addressLine1) > 255
    || strlen($addressLine2) > 255
    || $shippingCity === ''
    || strlen($shippingCity) > 100
    || $shippingState === ''
    || strlen($shippingState) > 100
    || $postalCode === ''
    || strlen($postalCode) > 20
    || $shippingCountry === ''
    || strlen($shippingCountry) > 100
) {
    http_response_code(400);
    exit('Please provide valid shipping information.');
}

// Get and validate the session cart.
$cart = $_SESSION['cart'] ?? [];

if (!is_array($cart) || empty($cart)) {
    http_response_code(400);
    exit('Your cart is empty.');
}

$cartItems = [];

foreach ($cart as $id => $quantity) {
    $productId = filter_var($id, FILTER_VALIDATE_INT);
    $validQuantity = filter_var($quantity, FILTER_VALIDATE_INT);

    if (
        $productId === false
        || $productId < 1
        || $validQuantity === false
        || $validQuantity < 1
        || $validQuantity > 100
    ) {
        http_response_code(400);
        exit('Invalid cart information. Please review your cart.');
    }

    $cartItems[(int) $productId] = (int) $validQuantity;
}

if (empty($cartItems)) {
    http_response_code(400);
    exit('Your cart is empty.');
}

// Convert a DECIMAL database price to integer paise.
function priceToPaise($price): int
{
    $parts = explode('.', (string) $price, 2);

    $rupees = (int) $parts[0];
    $paise = isset($parts[1])
        ? (int) str_pad(substr($parts[1], 0, 2), 2, '0')
        : 0;

    return ($rupees * 100) + $paise;
}

// Convert integer paise to a DECIMAL database value.
function paiseToDecimal(int $paise): string
{
    return intdiv($paise, 100)
        . '.'
        . str_pad((string) ($paise % 100), 2, '0', STR_PAD_LEFT);
}

$userId = currentUserId();

try {

    $pdo->beginTransaction();

    // Lock all requested products while checking their stock.
    $productIds = array_keys($cartItems);

    $placeholders = implode(
        ',',
        array_fill(0, count($productIds), '?')
    );

    $stmt = $pdo->prepare("
        SELECT id, name, sku, price, stock_quantity, status
        FROM products
        WHERE id IN ($placeholders)
        ORDER BY id
        FOR UPDATE
    ");

    $stmt->execute($productIds);
    $products = $stmt->fetchAll();

    if (count($products) !== count($cartItems)) {
        throw new CheckoutException(
            'One or more products are no longer available.'
        );
    }

    $productsById = [];

    foreach ($products as $product) {
        $productsById[(int) $product['id']] = $product;
    }

    // Validate stock and calculate the total using integer paise.
    $subtotalPaise = 0;
    $orderLines = [];

    foreach ($cartItems as $productId => $quantity) {

        $product = $productsById[$productId];

        if ($product['status'] !== 'active') {
            throw new CheckoutException(
                $product['name'] . ' is no longer available.'
            );
        }

        if ((int) $product['stock_quantity'] < $quantity) {
            throw new CheckoutException(
                'Insufficient stock for ' . $product['name'] . '.'
            );
        }

        $unitPricePaise = priceToPaise($product['price']);
        $lineTotalPaise = $unitPricePaise * $quantity;

        $subtotalPaise += $lineTotalPaise;

        $orderLines[] = [
            'product_id' => $productId,
            'product_name' => $product['name'],
            'sku' => $product['sku'],
            'quantity' => $quantity,
            'unit_price' => $unitPricePaise,
            'line_total' => $lineTotalPaise,
        ];
    }

    // COD checkout: shipping and discount are initially zero.
    $shippingFeePaise = 0;
    $discountPaise = 0;
    $totalPaise = $subtotalPaise
        + $shippingFeePaise
        - $discountPaise;

    // Generate a unique order number.
    $orderNumber = 'CR'
        . date('Ymd')
        . strtoupper(bin2hex(random_bytes(4)));

    // Create the order.
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            order_number,
            status,
            subtotal,
            shipping_fee,
            discount_amount,
            total_amount,
            shipping_name,
            shipping_phone,
            shipping_address_line1,
            shipping_address_line2,
            shipping_city,
            shipping_state,
            shipping_postal_code,
            shipping_country,
            payment_method,
            payment_status
        ) VALUES (
            ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'cod', 'pending'
        )
    ");

    $stmt->execute([
        $userId,
        $orderNumber,
        paiseToDecimal($subtotalPaise),
        paiseToDecimal($shippingFeePaise),
        paiseToDecimal($discountPaise),
        paiseToDecimal($totalPaise),
        $shippingName,
        $shippingPhone,
        $addressLine1,
        $addressLine2 !== '' ? $addressLine2 : null,
        $shippingCity,
        $shippingState,
        $postalCode,
        $shippingCountry,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    // Prepare statements for order items and inventory.
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            sku,
            quantity,
            unit_price,
            line_total
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stockStmt = $pdo->prepare("
        UPDATE products
        SET stock_quantity = stock_quantity - ?
        WHERE id = ?
          AND stock_quantity >= ?
          AND status = 'active'
    ");

    $movementStmt = $pdo->prepare("
        INSERT INTO inventory_movements (
            product_id,
            order_id,
            quantity_change,
            reason
        ) VALUES (?, ?, ?, 'order')
    ");

    foreach ($orderLines as $line) {

        // Save the purchased product snapshot.
        $itemStmt->execute([
            $orderId,
            $line['product_id'],
            $line['product_name'],
            $line['sku'],
            $line['quantity'],
            paiseToDecimal($line['unit_price']),
            paiseToDecimal($line['line_total']),
        ]);

        // Reduce inventory.
        $stockStmt->execute([
            $line['quantity'],
            $line['product_id'],
            $line['quantity'],
        ]);

        if ($stockStmt->rowCount() !== 1) {
            throw new CheckoutException(
                'Stock could not be updated. Please try again.'
            );
        }

        // Record the inventory movement.
        $movementStmt->execute([
            $line['product_id'],
            $orderId,
            -$line['quantity'],
        ]);
    }

    // Commit all database changes together.
    $pdo->commit();

    // Clear cart only after successful commit.
    unset($_SESSION['cart']);

    // Remember the order number for the confirmation page.
    $_SESSION['last_order_number'] = $orderNumber;

    header(
        'Location: /compunent-rig/order-success.php?order='
        . urlencode($orderNumber)
    );
    exit;

} catch (CheckoutException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(409);
    exit(e($e->getMessage()));

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Checkout error: ' . $e->getMessage());

    http_response_code(500);
    exit('We could not place your order. Your cart has been preserved. Please try again.');
}
