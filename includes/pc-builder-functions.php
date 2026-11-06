
<?php

/**
 * Get available PC Builder components.
 * Only active products with stock and compatibility data are included.
 */
function getPcBuilderProducts(PDO $pdo): array
{
    $sql = "
        SELECT
            p.id,
            p.name,
            p.sku,
            p.brand,
            p.description,
            p.price,
            p.stock_quantity,
            pc.component_type,
            pc.socket AS compat_socket,
            pc.ram_type,
            pc.capacity_gb,
            pc.wattage,
            pc.recommended_psu_wattage,
            pc.storage_interface,
            pc.supported_storage_interfaces,
            pc.max_ram_capacity_gb
        FROM products p
        INNER JOIN product_compatibility pc
            ON pc.product_id = p.id
        WHERE p.status = 'active'
          AND p.stock_quantity > 0
        ORDER BY p.name ASC
    ";

    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [
        'cpu' => [],
        'motherboard' => [],
        'ram' => [],
        'gpu' => [],
        'storage' => [],
        'psu' => [],
        'case' => [],
    ];

    foreach ($products as $product) {
        $type = $product['component_type'];

        if (isset($grouped[$type])) {
            $grouped[$type][] = $product;
        }
    }

    return $grouped;
}


/**
 * Check compatibility between selected PC components.
 *
 * Expected input:
 * [
 *     'cpu' => product array,
 *     'motherboard' => product array,
 *     'ram' => product array,
 *     ...
 * ]
 */
function checkPcBuildCompatibility(array $selectedProducts): array
{
    $errors = [];
    $warnings = [];

    $cpu = $selectedProducts['cpu'] ?? null;
    $motherboard = $selectedProducts['motherboard'] ?? null;
    $ram = $selectedProducts['ram'] ?? null;
    $gpu = $selectedProducts['gpu'] ?? null;
    $storage = $selectedProducts['storage'] ?? null;
    $psu = $selectedProducts['psu'] ?? null;

    // 1. CPU and motherboard socket compatibility.
    if ($cpu && $motherboard) {
        $cpuSocket = trim($cpu['compat_socket'] ?? '');
        $motherboardSocket = trim($motherboard['compat_socket'] ?? '');

        if ($cpuSocket === '' || $motherboardSocket === '') {
            $warnings[] = 'CPU or motherboard socket information is missing.';
        } elseif (strcasecmp($cpuSocket, $motherboardSocket) !== 0) {
            $errors[] = 'CPU socket does not match the motherboard socket.';
        }
    }

    // 2. Motherboard and RAM type compatibility.
    if ($motherboard && $ram) {
        $motherboardRamType = trim($motherboard['ram_type'] ?? '');
        $ramType = trim($ram['ram_type'] ?? '');

        if ($motherboardRamType === '' || $ramType === '') {
            $warnings[] = 'Motherboard or RAM type information is missing.';
        } elseif (strcasecmp($motherboardRamType, $ramType) !== 0) {
            $errors[] = 'RAM type is not supported by the motherboard.';
        }

        // Check maximum supported RAM capacity.
        $maxRam = (int) ($motherboard['max_ram_capacity_gb'] ?? 0);
        $ramCapacity = (int) ($ram['capacity_gb'] ?? 0);

        if ($maxRam <= 0 || $ramCapacity <= 0) {
            $warnings[] = 'RAM capacity or motherboard maximum RAM capacity is missing.';
        } elseif ($ramCapacity > $maxRam) {
            $errors[] = 'Selected RAM capacity exceeds the motherboard maximum supported capacity.';
        }
    }

    // 3. GPU and PSU wattage compatibility.
    if ($gpu && $psu) {
        $requiredWattage = (int) ($gpu['recommended_psu_wattage'] ?? 0);
        $psuWattage = (int) ($psu['wattage'] ?? 0);

        if ($requiredWattage <= 0 || $psuWattage <= 0) {
            $warnings[] = 'GPU recommended PSU wattage or PSU wattage information is missing.';
        } elseif ($psuWattage < $requiredWattage) {
            $errors[] = 'PSU wattage is below the GPU recommended PSU requirement.';
        }
    } elseif ($gpu && !$psu) {
        $warnings[] = 'Select a PSU to check GPU power requirements.';
    }

    // 4. Storage and motherboard interface compatibility.
    if ($storage && $motherboard) {
        $storageInterface = trim($storage['storage_interface'] ?? '');
        $supportedInterfaces = trim(
            $motherboard['supported_storage_interfaces'] ?? ''
        );

        if ($storageInterface === '' || $supportedInterfaces === '') {
            $warnings[] = 'Storage or motherboard supported storage interface information is missing.';
        } else {
            $supportedList = array_filter(
                array_map('trim', explode(',', $supportedInterfaces))
            );

            $isSupported = false;

            foreach ($supportedList as $interface) {
                if (strcasecmp($storageInterface, $interface) === 0) {
                    $isSupported = true;
                    break;
                }
            }

            if (!$isSupported) {
                $errors[] = 'Selected storage interface is not supported by the motherboard.';
            }
        }
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'warnings' => array_values(array_unique($warnings)),
        'is_compatible' => empty($errors),
    ];
}
