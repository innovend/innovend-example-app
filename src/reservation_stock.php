<?php
$config = json_decode(file_get_contents('config.json'), true);

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
            // Handle image errors
            document.querySelectorAll("img").forEach(img => {
                img.onerror = () => {
                    img.src = "fallback.png";
                };
            });

            // Make product cards clickable to increase quantity
            document.querySelectorAll(".product-card").forEach(card => {
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
        });
    </script>
    <meta charset="UTF-8">
    <title>Select a product</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f9f9f9;
            padding: 20px;
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
<a href="index.php" style="text-decoration: none; display: inline-block; margin-right: 10px;">
    <button type="button" style="padding: 10px 20px; font-size: 16px; margin-bottom: 20px;">Home</button>
</a>
<form id="reserveForm" method="POST" action="reservation_create.php" style="display: inline-block;">
    <input type="hidden" name="machineId" value="<?= $machineId ?>">
    <input type="hidden" name="ticket" value="<?= htmlspecialchars($orderNr) ?>">
    <button type="submit" form="reserveForm" style="padding: 10px 20px; font-size: 16px; margin-bottom: 20px;">Reserve Selected Items</button>
</form>

<?php foreach ($products as $product):
    $available = $product['AvailableCountExReservations'];
    $name = $product['ProductName'] ?? 'Unnamed';
    $sku = $product['ProductSKU'] ?? '';
    $productId = $product['ProductId'];
    $imageUrl = "image.php?product={$productId}";
    ?>
    <div class="product-card">
        <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($name) ?>">
        <h3><?= htmlspecialchars($name) ?></h3>
        <p>Available: <?= $available ?></p>
        <label>
            Quantity: <input type="number" name="quantity[<?= htmlspecialchars($sku) ?>]" value="0" min="0" max="<?= $available ?>" form="reserveForm" style="width: 60px;">
        </label>
    </div>
<?php endforeach; ?>

<?php if (isset($config['debug']) && $config['debug'] === true): ?>
<div id="debugConsole" style="margin-top: 20px; text-align: left; background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; overflow-wrap: break-word; position: fixed; bottom: 20px; right: 20px; max-width: 80%; max-height: 80%; overflow: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); transition: height 0.3s ease-in-out;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <h3 style="margin: 0;">API Request/Response Log</h3>
        <button id="toggleDebugConsole" style="background: #007bff; color: white; border: none; border-radius: 4px; padding: 5px 10px; cursor: pointer;">Minimize</button>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('toggleDebugConsole');
        const debugConsole = document.getElementById('debugConsole');
        const debugContent = document.getElementById('debugConsoleContent');

        toggleButton.addEventListener('click', function() {
            if (debugContent.style.display === 'none') {
                // Expand
                debugContent.style.display = 'block';
                toggleButton.textContent = 'Minimize';
                debugConsole.style.height = 'auto';
            } else {
                // Minimize
                debugContent.style.display = 'none';
                toggleButton.textContent = 'Expand';
                debugConsole.style.height = 'auto';
            }
        });
    });
</script>
<?php endif; ?>
</body>
</html>
