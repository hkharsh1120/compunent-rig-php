
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pc-builder-functions.php';

$pageTitle = 'PC Builder';

$products = getPcBuilderProducts($pdo);

$componentLabels = [
    'cpu' => 'Processor (CPU)',
    'motherboard' => 'Motherboard',
    'ram' => 'Memory (RAM)',
    'gpu' => 'Graphics Card (GPU)',
    'storage' => 'Storage',
    'psu' => 'Power Supply (PSU)',
    'case' => 'PC Case',
];

$builderProductsJson = json_encode(
    $products,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

require_once __DIR__ . '/../includes/header.php';
?>


<style>
/* =========================================
   PREMIUM PC BUILDER
========================================= */

.pc-builder {
    max-width: 1240px;
    margin: 42px auto 70px;
    padding: 0 22px;
    color: #f1f5f9;
}

.builder-heading {
    position: relative;
    overflow: hidden;
    padding: 38px;
    margin-bottom: 28px;
    border: 1px solid #293548;
    border-radius: 18px;
    background:
        radial-gradient(ellipse at 90% 10%, rgba(59,130,246,.19), transparent 45%),
        linear-gradient(135deg, #151e2d, #0d1420);
}

.builder-heading::after {
    content: "</>";
    position: absolute;
    right: 45px;
    top: 10px;
    font-size: 110px;
    font-weight: 900;
    color: rgba(96,165,250,.07);
    pointer-events: none;
}

.builder-eyebrow {
    display: inline-block;
    margin-bottom: 13px;
    padding: 6px 11px;
    border: 1px solid rgba(96,165,250,.25);
    border-radius: 30px;
    color: #93c5fd;
    background: rgba(59,130,246,.10);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.builder-heading h1 {
    margin: 0 0 12px;
    color: #f8fafc;
    font-size: clamp(28px, 4vw, 40px);
    font-weight: 800;
    letter-spacing: -1.3px;
}

.builder-heading p {
    max-width: 620px;
    margin: 0;
    color: #9caec3;
    font-size: 15px;
    line-height: 1.7;
}

.builder-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 350px;
    gap: 24px;
    align-items: start;
}

.builder-main {
    min-width: 0;
}

.builder-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin: 0 0 17px;
}

.builder-section-heading h2 {
    margin: 0;
    color: #f1f5f9;
    font-size: 19px;
    font-weight: 750;
}

.builder-section-heading span {
    color: #8493a8;
    font-size: 12px;
}

.builder-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.builder-part {
    min-width: 0;
    padding: 20px;
    border: 1px solid #293548;
    border-radius: 13px;
    background: #131d2b;
    transition: border-color .2s, transform .2s;
}

.builder-part:hover {
    border-color: #3b82f6;
    transform: translateY(-2px);
}

.builder-part-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.builder-part-label {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

.builder-part-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    border: 1px solid #34445d;
    border-radius: 10px;
    background: #1c2a3d;
    color: #93c5fd;
    font-size: 17px;
}

.builder-part label {
    display: block;
    margin: 0;
    color: #e2e8f0;
    font-size: 14px;
    font-weight: 700;
}

.builder-part small {
    color: #8292a8;
    font-size: 11px;
    white-space: nowrap;
}

.builder-part select {
    display: block;
    width: 100%;
    min-height: 46px;
    padding: 11px 36px 11px 12px;
    border: 1px solid #34445a;
    border-radius: 8px;
    outline: none;
    background: #0d1623;
    color: #e2e8f0;
    font-family: inherit;
    font-size: 13px;
    cursor: pointer;
    color-scheme: dark;
}

.builder-part select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.13);
}

.builder-part select option {
    background: #131d2b;
    color: #e2e8f0;
}

.builder-part-hint {
    display: block;
    margin-top: 10px;
    color: #718198;
    font-size: 11px;
}

/* Summary */

.builder-summary {
    position: sticky;
    top: 25px;
    padding: 23px;
    border: 1px solid #33445d;
    border-radius: 15px;
    background:
        radial-gradient(ellipse at top right, rgba(59,130,246,.10), transparent 55%),
        #131d2b;
}

.builder-summary h2 {
    margin: 0 0 5px;
    color: #f8fafc;
    font-size: 20px;
    font-weight: 800;
}

.builder-summary-subtitle {
    margin: 0 0 22px;
    color: #8493a8;
    font-size: 12px;
}

.builder-summary-label {
    margin-bottom: 10px;
    color: #a8b6c9;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.builder-selected-parts {
    min-height: 75px;
    padding: 13px;
    border: 1px solid #29384d;
    border-radius: 9px;
    background: rgba(8,15,25,.55);
}

.builder-selected-parts p {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin: 0;
    padding: 9px 0;
    border-bottom: 1px solid #253247;
    color: #cbd5e1;
    font-size: 12px;
    line-height: 1.5;
}

.builder-selected-parts p:last-child {
    border-bottom: 0;
}

.builder-empty {
    color: #75849a;
    font-size: 12px;
    line-height: 1.6;
}

.builder-total-wrap {
    margin: 19px 0;
    padding: 17px;
    border: 1px solid rgba(59,130,246,.25);
    border-radius: 10px;
    background: rgba(37,99,235,.09);
}

.builder-total-label {
    color: #93a4bb;
    font-size: 12px;
}

.builder-total {
    margin-top: 7px;
    color: #60a5fa;
    font-size: 29px;
    font-weight: 850;
    letter-spacing: -.8px;
    overflow-wrap: anywhere;
}

.builder-status {
    margin: 15px 0;
    padding: 13px 14px;
    border: 1px solid #34445a;
    border-radius: 9px;
    background: #1a2738;
    color: #cbd5e1;
    font-size: 12px;
    line-height: 1.6;
}

.builder-status strong {
    display: block;
    margin-bottom: 5px;
}

.builder-status.error {
    border-color: rgba(248,113,113,.35);
    background: rgba(127,29,29,.18);
    color: #fca5a5;
}

.builder-status.warning {
    border-color: rgba(251,191,36,.35);
    background: rgba(120,75,10,.17);
    color: #fcd34d;
}

.builder-status.success {
    border-color: rgba(74,222,128,.3);
    background: rgba(20,83,45,.18);
    color: #86efac;
}

.builder-status ul {
    margin: 7px 0 0;
    padding-left: 19px;
}

.builder-status li {
    margin: 4px 0;
}

.builder-submit {
    display: block;
    width: 100%;
    min-height: 48px;
    padding: 13px 16px;
    border: 0;
    border-radius: 9px;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: #fff;
    font-family: inherit;
    font-size: 14px;
    font-weight: 750;
    cursor: pointer;
    transition: opacity .2s, transform .2s;
}

.builder-submit:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(37,99,235,.22);
}

.builder-submit:disabled {
    opacity: .4;
    cursor: not-allowed;
}

.builder-error-message {
    margin-bottom: 20px;
}

/* Responsive */

@media (max-width: 950px) {
    .builder-layout {
        grid-template-columns: minmax(0, 1fr) 310px;
    }

    .builder-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .pc-builder {
        margin-top: 24px;
        padding: 0 15px;
    }

    .builder-heading {
        padding: 27px 22px;
    }

    .builder-heading::after {
        right: 15px;
        top: 20px;
        font-size: 65px;
    }

    .builder-layout {
        grid-template-columns: 1fr;
    }

    .builder-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .builder-summary {
        position: static;
    }
}

@media (max-width: 480px) {
    .builder-grid {
        grid-template-columns: 1fr;
    }

    .builder-part {
        padding: 16px;
    }

    .builder-summary {
        padding: 19px;
    }
}
</style>


<main class="pc-builder">

    <div class="builder-heading">
        <span class="builder-eyebrow">Custom PC Configuration</span>
        <h1>Build Your Dream PC</h1>
        <p>
            Choose your components, check compatibility in real time,
            and create a custom PC build that fits your requirements.
        </p>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="builder-status error builder-error-message">
            <?= e($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="/compunent-rig/pc-builder/add-to-cart.php"
          id="pcBuilderForm">

        <input type="hidden" name="csrf_token"
               value="<?= e(csrfToken()) ?>">

        <div class="builder-layout">

            <section class="builder-main">

                <div class="builder-section-heading">
                    <h2>Select Your Components</h2>
                    <span>7 component categories</span>
                </div>

                <div class="builder-grid">

                    <?php
                    $componentIcons = [
                        'cpu' => '⚡',
                        'motherboard' => '▦',
                        'ram' => '▤',
                        'gpu' => '▰',
                        'storage' => '▣',
                        'psu' => '🔌',
                        'case' => '▥'
                    ];
                    ?>

                    <?php foreach ($componentLabels as $type => $label): ?>

                        <div class="builder-part">

                            <div class="builder-part-top">

                                <div class="builder-part-label">
                                    <span class="builder-part-icon">
                                        <?= $componentIcons[$type] ?>
                                    </span>

                                    <label for="part-<?= e($type) ?>">
                                        <?= e($label) ?>
                                    </label>
                                </div>

                                <small>
                                    <?= count($products[$type]) ?> available
                                </small>

                            </div>

                            <select
                                id="part-<?= e($type) ?>"
                                name="components[<?= e($type) ?>]"
                                class="component-select"
                                data-type="<?= e($type) ?>"
                            >

                                <option value="">
                                    Select <?= e($label) ?>
                                </option>

                                <?php foreach ($products[$type] as $product): ?>

                                    <option
                                        value="<?= (int) $product['id'] ?>"
                                        data-price="<?= e($product['price']) ?>"
                                    >
                                        <?= e($product['name']) ?>
                                        — ₹<?= number_format((float) $product['price'], 2) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <span class="builder-part-hint">
                                Choose an available <?= e(strtolower($label)) ?>.
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>

            <aside class="builder-summary">

                <h2>Build Summary</h2>

                <p class="builder-summary-subtitle">
                    Your selected components and estimated total.
                </p>

                <div class="builder-summary-label">
                    Selected Components
                </div>

                <div id="selectedParts" class="builder-selected-parts">
                    <span class="builder-empty">
                        No components selected yet. Start by choosing your
                        components.
                    </span>
                </div>

                <div class="builder-total-wrap">
                    <div class="builder-total-label">
                        Estimated Build Total
                    </div>

                    <div class="builder-total">
                        ₹<span id="buildTotal">0.00</span>
                    </div>
                </div>

                <div class="builder-summary-label">
                    Compatibility Check
                </div>

                <div id="compatibilityStatus" class="builder-status">
                    Select components to check compatibility.
                </div>

                <button
                    type="submit"
                    id="addBuildButton"
                    class="builder-submit"
                    disabled
                >
                    Add Build to Cart →
                </button>

                <p class="builder-part-hint" style="text-align:center;margin-top:12px;">
                    Compatibility will be checked again before adding to cart.
                </p>

            </aside>

        </div>

    </form>

</main>


<script>
const builderProducts = <?= $builderProductsJson ?: '{}' ?>;

const selects = document.querySelectorAll('.component-select');
const totalElement = document.getElementById('buildTotal');
const partsElement = document.getElementById('selectedParts');
const statusElement = document.getElementById('compatibilityStatus');
const addButton = document.getElementById('addBuildButton');

function getSelectedProducts() {
    const selected = {};

    selects.forEach(select => {
        const id = select.value;

        if (!id) return;

        const type = select.dataset.type;

        selected[type] = (builderProducts[type] || []).find(
            product => String(product.id) === String(id)
        );
    });

    return selected;
}

function checkCompatibility(selected) {
    const errors = [];
    const warnings = [];

    const cpu = selected.cpu;
    const motherboard = selected.motherboard;
    const ram = selected.ram;
    const gpu = selected.gpu;
    const storage = selected.storage;
    const psu = selected.psu;

    // CPU and motherboard socket
    if (cpu && motherboard) {
        const cpuSocket = (cpu.compat_socket || '').trim();
        const boardSocket = (motherboard.compat_socket || '').trim();

        if (!cpuSocket || !boardSocket) {
            warnings.push('CPU or motherboard socket information is missing.');
        } else if (cpuSocket.toLowerCase() !== boardSocket.toLowerCase()) {
            errors.push('CPU socket does not match motherboard socket.');
        }
    }

    // Motherboard and RAM type/capacity
    if (motherboard && ram) {
        const boardRam = (motherboard.ram_type || '').trim();
        const ramType = (ram.ram_type || '').trim();

        if (!boardRam || !ramType) {
            warnings.push('Motherboard or RAM type information is missing.');
        } else if (boardRam.toLowerCase() !== ramType.toLowerCase()) {
            errors.push('RAM type is not supported by the motherboard.');
        }

        const maxRam = Number(motherboard.max_ram_capacity_gb || 0);
        const capacity = Number(ram.capacity_gb || 0);

        if (!maxRam || !capacity) {
            warnings.push('RAM capacity information is missing.');
        } else if (capacity > maxRam) {
            errors.push('RAM capacity exceeds motherboard maximum capacity.');
        }
    }

    // GPU and PSU wattage
    if (gpu && psu) {
        const required = Number(gpu.recommended_psu_wattage || 0);
        const available = Number(psu.wattage || 0);

        if (!required || !available) {
            warnings.push('GPU or PSU wattage information is missing.');
        } else if (available < required) {
            errors.push('PSU wattage is below the GPU recommended requirement.');
        }
    } else if (gpu && !psu) {
        warnings.push('Select a PSU to check GPU power requirements.');
    }

    // Storage and motherboard interface
    if (storage && motherboard) {
        const storageInterface = (storage.storage_interface || '').trim();
        const supported = (motherboard.supported_storage_interfaces || '')
            .split(',')
            .map(value => value.trim().toLowerCase())
            .filter(Boolean);

        if (!storageInterface || supported.length === 0) {
            warnings.push('Storage interface information is missing.');
        } else if (!supported.includes(storageInterface.toLowerCase())) {
            errors.push('Storage interface is not supported by the motherboard.');
        }
    }

    return { errors, warnings };
}

function updateBuilder() {
    const selected = getSelectedProducts();

    let total = 0;
    let selectedCount = 0;
    let partsHtml = '';

    Object.entries(selected).forEach(([type, product]) => {
        if (!product) return;

        total += Number(product.price);
        selectedCount++;

        partsHtml += `<p>${escapeHtml(product.name)} — ₹${Number(product.price).toFixed(2)}</p>`;
    });

    totalElement.textContent = total.toFixed(2);

    partsElement.innerHTML = partsHtml || 'No components selected yet.';

    const result = checkCompatibility(selected);

    statusElement.className = 'builder-status';

    if (result.errors.length > 0) {
        statusElement.classList.add('error');
        statusElement.innerHTML =
            '<strong>Compatibility errors:</strong>' +
            '<ul>' + result.errors.map(item => `<li>${escapeHtml(item)}</li>`).join('') + '</ul>';
    } else if (result.warnings.length > 0) {
        statusElement.classList.add('warning');
        statusElement.innerHTML =
            '<strong>Warnings:</strong>' +
            '<ul>' + result.warnings.map(item => `<li>${escapeHtml(item)}</li>`).join('') + '</ul>';
    } else if (selectedCount > 0) {
        statusElement.classList.add('success');
        statusElement.textContent = 'No known compatibility issues detected.';
    } else {
        statusElement.textContent = 'Select components to check compatibility.';
    }

    addButton.disabled = selectedCount === 0 || result.errors.length > 0;
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[character]);
}

selects.forEach(select => {
    select.addEventListener('change', updateBuilder);
});

updateBuilder();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
