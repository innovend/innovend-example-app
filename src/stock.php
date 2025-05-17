<?php
// Load config
$config = json_decode(file_get_contents('config.json'), true);

// Get machine ID from URL
$machineId = isset($_GET['vendingmachine']) ? intval($_GET['vendingmachine']) : 0;
if (!$machineId) {
    die("No vending machine selected.");
}

// Prepare API request
$url = "https://api.vendingweb.eu/api/external/machines/stock/{$machineId}";
$headers = [
    "x-api-key: {$config['apiKey']}",
    "Accept: application/json"
];

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ":" . $config['password']);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // false voor lokaal

$response = curl_exec($curl);
if (curl_errno($curl)) {
    die("Error contacting API: " . curl_error($curl));
}
$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpStatus !== 200) {
    die("API returned status $httpStatus: " . htmlspecialchars($response));
}

$data = json_decode($response, true);
$products = $data['ProductStock'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select a product</title>
</head>
<body>
<h1>Select a product from machine #<?= htmlspecialchars($machineId) ?></h1>
<form method="POST" action="reserve.php">
    <input type="hidden" name="machineId" value="<?= htmlspecialchars($machineId) ?>">
    <label for="product">Product:</label>
    <select name="productId" id="product">
        <?php foreach ($products as $product):
            $available = $product['AvailableCountExReservations'];
            $name = $product['ProductName'] ?? 'Unnamed product';
            $id = $product['ProductId'];
            echo "<option value=\"" . htmlspecialchars($id) . "\">($available) " . htmlspecialchars($name) . "</option>";
        endforeach; ?>
    </select>
    <br><br>
    <button type="submit">Reserve product</button>
</form>
</body>
</html>
