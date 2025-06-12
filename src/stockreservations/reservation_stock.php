<?php
$config = json_decode(file_get_contents('../conf/config.json'), true);

$machineId = intval($_GET['vendingmachine']);
$orderNr = $_GET['ticket'] ?? 'UNKNOWN';

$apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
$url = "{$apiBaseUrl}/api/external/machines/stock/{$machineId}";
$headers = [
    "x-api-key: {$config['apiKey']}",
    "Accept: application/json"
];

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ":" . $config['password']);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($curl);
$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$products = [];
if ($httpStatus === 200) {
    $data = json_decode($response, true);
    $products = $data['ProductStock'] ?? [];
    // Filter producten met ProductId 0
    $products = array_filter($products, function($product) {
        return isset($product['ProductId']) && $product['ProductId'] !== 0;
    });

    // Sort products - items with stock first, then items without stock
    usort($products, function($a, $b) {
        $aHasStock = ($a['AvailableCountExReservations'] > 0);
        $bHasStock = ($b['AvailableCountExReservations'] > 0);

        if ($aHasStock === $bHasStock) {
            return 0; // Keep original order if both have stock or both don't have stock
        }

        return $aHasStock ? -1 : 1; // Items with stock come first
    });
}

// Store API call information for debug console
$apiDebugInfo = [
    'url' => $url,
    'headers' => $headers,
    'status' => $httpStatus,
    'response' => $response
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Set default expiration date to 2 days from now
            const expirationDateInput = document.getElementById('expirationDate');
            if (expirationDateInput) {
                const now = new Date();
                const twoDaysFromNow = new Date(now.getTime() + (2 * 24 * 60 * 60 * 1000));

                // Format date to YYYY-MM-DDThh:mm
                const year = twoDaysFromNow.getFullYear();
                const month = String(twoDaysFromNow.getMonth() + 1).padStart(2, '0');
                const day = String(twoDaysFromNow.getDate()).padStart(2, '0');
                const hours = String(twoDaysFromNow.getHours()).padStart(2, '0');
                const minutes = String(twoDaysFromNow.getMinutes()).padStart(2, '0');

                expirationDateInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
            }

            // Handle image errors
            document.querySelectorAll("img").forEach(img => {
                img.onerror = () => {
                    img.src = "fallback.png";
                };
            });

            // Make product cards clickable to increase quantity (only for products with stock)
            document.querySelectorAll(".product-card:not(.no-stock)").forEach(card => {
                card.addEventListener("click", function(e) {
                    // Don't increment if clicking directly on the input field
                    if (e.target.tagName !== 'INPUT') {
                        const input = this.querySelector('input[type="number"]');
                        const max = parseInt(input.getAttribute('max'), 10);
                        const currentValue = parseInt(input.value, 10);

                        // Increment if not at max
                        if (currentValue < max) {
                            input.value = currentValue + 1;
                        }
                    }
                });
            });

            // Handle toggle switch for Prepaid
            const toggleSwitch = document.querySelector('.toggle-switch');
            const toggleInput = toggleSwitch.querySelector('input[type="checkbox"]');
            const toggleSlider = toggleSwitch.querySelector('.toggle-slider span');

            toggleSwitch.addEventListener('click', function() {
                toggleInput.checked = !toggleInput.checked;
                if (toggleInput.checked) {
                    toggleSlider.style.transform = 'translateX(26px)';
                    toggleSwitch.querySelector('.toggle-slider').style.backgroundColor = '#4CAF50';
                } else {
                    toggleSlider.style.transform = 'translateX(0)';
                    toggleSwitch.querySelector('.toggle-slider').style.backgroundColor = '#ccc';
                }
            });
        });
    </script>
    <meta charset="UTF-8">
    <title>Select a product</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f9f9f9;
            padding: 20px;
            overflow-x: hidden;
        }

        .debug-panel {
            position: fixed;
            top: 0;
            right: 0;
            width: 20%; /* 1/5 of screen width */
            height: 100vh;
            background-color: #f8f9fa;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 1000;
            padding: 15px;
            box-sizing: border-box;
            font-family: monospace;
            font-size: 12px;
            text-align: left;
        }

        /* Main content area adjusted to not be hidden by debug panel */
        body {
            padding-right: 22%; /* Slightly more than debug panel width */
        }
        h1 {
            color: #444;
        }
        .product-card {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px;
            width: 250px;
            display: inline-block;
            vertical-align: top;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        .product-card.no-stock {
            background-color: #f0f0f0;
            cursor: default;
            opacity: 0.8;
        }
        .product-card.no-stock:hover {
            transform: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-color: #ddd;
        }
        .no-stock-label {
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .product-card img {
            max-width: 100%;
            height: 120px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
<h1>OrderNr: <?= htmlspecialchars($orderNr) ?></h1>
<a href="../index.php" style="text-decoration: none; display: inline-block; margin-right: 10px;">
    <button type="button" style="padding: 10px 20px; font-size: 16px; margin-bottom: 20px;">Home</button>
</a>
<form id="reserveForm" method="POST" action="reservation_create.php" style="display: inline-block;">
    <input type="hidden" name="machineId" value="<?= $machineId ?>">
    <input type="hidden" name="ticket" value="<?= htmlspecialchars($orderNr) ?>">
    <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <button type="submit" form="reserveForm" style="padding: 10px 20px; font-size: 16px; margin-right: 15px;">Reserve Selected Items</button>
        <label style="display: inline-flex; align-items: center; margin-right: 15px;">
            <span style="margin-right: 10px; font-weight: bold;">Expire on:</span>
            <input type="datetime-local" name="expirationDate" id="expirationDate" form="reserveForm" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
        </label>
        <label class="toggle-switch" style="display: inline-flex; align-items: center; cursor: pointer;">
            <span style="margin-right: 10px; font-weight: bold;">Prepaid?</span>
            <input type="checkbox" name="isPaid" value="1" style="position: absolute; opacity: 0; width: 0; height: 0;">
            <span class="toggle-slider" style="position: relative; display: inline-block; width: 60px; height: 34px; background-color: #ccc; border-radius: 34px; transition: .4s;">
                <span style="position: absolute; content: ''; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; border-radius: 50%; transition: .4s;"></span>
            </span>
        </label>
    </div>
</form>

<div style="clear: both; width: 100%;"></div>

<?php foreach ($products as $product):
    $available = $product['AvailableCountExReservations'];
    $name = $product['ProductName'] ?? 'Unnamed';
    $sku = $product['ProductSKU'] ?? '';
    $productId = $product['ProductId'];
    $imageUrl = "image.php?product={$productId}";
    $hasStock = ($available > 0);
    ?>
    <div class="product-card <?= $hasStock ? '' : 'no-stock' ?>">
        <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($name) ?>">
        <h3><?= htmlspecialchars($name) ?></h3>
        <?php if ($hasStock): ?>
            <p>Available: <?= $available ?></p>
            <label>
                Quantity: <input type="number" name="quantity[<?= htmlspecialchars($sku) ?>]" value="0" min="0" max="<?= $available ?>" form="reserveForm" style="width: 60px;">
            </label>
        <?php else: ?>
            <p>Available: 0</p>
            <div class="no-stock-label">No Stock</div>
            <input type="hidden" name="quantity[<?= htmlspecialchars($sku) ?>]" value="0" form="reserveForm">
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if (isset($config['debug']) && $config['debug'] === true): ?>
<div id="debugConsole" class="debug-panel">
    <div style="margin-bottom: 10px;">
        <h3 style="margin: 0;">API Request/Response Log</h3>
    </div>
    <div id="debugConsoleContent">
        <p><strong>API URL:</strong> <?= htmlspecialchars($apiDebugInfo['url']) ?></p>
        <p><strong>Request Headers:</strong><br>
        <?php foreach ($apiDebugInfo['headers'] as $header): ?>
            <?= htmlspecialchars($header) ?><br>
        <?php endforeach; ?>
        </p>
        <p><strong>Response Status:</strong> <?= htmlspecialchars($apiDebugInfo['status']) ?></p>
        <p><strong>Response Body:</strong><br><?= htmlspecialchars($apiDebugInfo['response']) ?></p>
    </div>
</div>

<!-- Debug panel is now permanently visible -->
<?php endif; ?>
</body>
</html>
