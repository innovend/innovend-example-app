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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select a product</title>
    <style>
        .product-card {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 15px;
            width: 250px;
            display: inline-block;
            vertical-align: top;
            text-align: center;
        }
        .product-card img {
            max-width: 100%;
            height: 100px;
            object-fit: contain;
        }
    </style>
</head>
<body>
<h1>Ticket: <?= htmlspecialchars($ticket) ?></h1>

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
        <form method="POST" action="reserve.php">
            <input type="hidden" name="productSku" value="<?= htmlspecialchars($sku) ?>">
            <input type="hidden" name="machineId" value="<?= $machineId ?>">
            <input type="hidden" name="ticket" value="<?= htmlspecialchars($ticket) ?>">
            <button type="submit">Reserve</button>
        </form>
    </div>
<?php endforeach; ?>
</body>
</html>
