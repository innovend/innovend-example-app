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
</head>
<body>
<h1>Ticket: <?= htmlspecialchars($ticket) ?></h1>
<form method="POST" action="reserve.php">
    <input type="hidden" name="machineId" value="<?= $machineId ?>">
    <input type="hidden" name="ticket" value="<?= htmlspecialchars($ticket) ?>">
    <label for="productSku">Product:</label>
    <select name="productSku" id="productSku">
        <?php foreach ($products as $product):
            $available = $product['AvailableCountExReservations'];
            $name = $product['ProductName'] ?? 'Unnamed';
            $sku = $product['ProductSKU'] ?? '';
            echo "<option value=\"".htmlspecialchars($sku)."\">($available) ".htmlspecialchars($name)."</option>";
        endforeach; ?>
    </select>
    <br><br>
    <button type="submit">Reserve product</button>
</form>
</body>
</html>
