<?php
$config = json_decode(file_get_contents('config.json'), true);

$machineId = intval($_GET['vendingmachine']);
$ticket = $_GET['ticket'] ?? 'UNKNOWN';

$url = "https://api.vendingweb.eu/api/external/machines/stock/{$machineId}";
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
<h1>Ticket: <?= htmlspecialchars($ticket) ?></h1>
<form id="reserveForm" method="POST" action="reserve.php">
    <input type="hidden" name="machineId" value="<?= $machineId ?>">
    <input type="hidden" name="ticket" value="<?= htmlspecialchars($ticket) ?>">
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

</body>
</html>
