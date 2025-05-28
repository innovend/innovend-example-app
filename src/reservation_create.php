<?php
$config = json_decode(file_get_contents('config.json'), true);

$machineId = intval($_POST['machineId']);
$quantities = $_POST['quantity'] ?? [];
$orderNr = $_POST['ticket'] ?? 'UNKNOWN';

// Convert quantities to individual product entries
$products = [];
foreach ($quantities as $sku => $qty) {
    $qty = intval($qty);
    if ($qty > 0) {
        // Add the product to the array multiple times based on quantity
        for ($i = 0; $i < $qty; $i++) {
            $products[] = $sku;
        }
    }
}

// timestamps
$now = (new DateTime())->format('c');
$expiration = (new DateTime('+24 hours'))->format('c');

// genereer unieke unlockcode
function generateUnlockCode() {
    return str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
}

// functie om reservering te maken
function createReservation($config, $machineId, $orderNr, $products, $now, $expiration) {
    $unlockCode = generateUnlockCode();

    // Create individual product entries
    $productEntries = [];
    foreach ($products as $sku) {
        $productEntries[] = [
            "SkuCode" => (string)$sku, // Ensure SKU is always a string
            "Qty" => 1
        ];
    }

    $payload = json_encode([
        [
            "MachineId" => $machineId,
            "CreatedOn" => $now,
            "DeliveryDate" => $now,
            "ExpirationDate" => $expiration,
            "UnlockCode" => $unlockCode,
            "OrderNr" => $orderNr,
            "IsPaid" => true,
            "Products" => $productEntries
        ]
    ]);

    $url = "https://api.vendingweb.eu/api/external/stockreservations/update/false/true";
    $headers = [
        "x-api-key: {$config['apiKey']}",
        "Accept: application/json",
        "Content-Type: application/json"
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ":" . $config['password']);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($curl, CURLOPT_POST, true);

    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [$status, $response, $unlockCode, $payload, $url];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 40px;
            text-align: center;
        }

        .message {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .message.success h1 {
            color: green;
        }

        .message.error h1 {
            color: red;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }

        button:hover {
            background-color: #0056b3;
        }

        pre {
            text-align: left;
            background: #eee;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>

<?php
// Check if any products were selected
if (empty($products)) {
    echo "
    <div class='message error'>
        <h1>No products selected</h1>
        <p>Please select at least one product to reserve.</p>
        <form action='reservation_stock.php' method='get'>
            <input type='hidden' name='vendingmachine' value='$machineId'>
            <input type='hidden' name='ticket' value='$orderNr'>
            <button type='submit'>Go Back</button>
        </form>
    </div>";
    exit;
}

// probeer reservering aan te maken (max 5 pogingen bij code-conflict)
$maxAttempts = 5;
$apiPayload = null;
$apiUrl = null;
for ($i = 0; $i < $maxAttempts; $i++) {
    [$status, $response, $code, $apiPayload, $apiUrl] = createReservation($config, $machineId, $orderNr, $products, $now, $expiration);
    if ($status === 200) {
        $productCount = count($products);
        echo "
        <div class='message success'>
            <h1>Reservation successful!</h1>
            <p>You have reserved $productCount item" . ($productCount > 1 ? "s" : "") . ".</p>
            <p>Your unlock code: <strong>$code</strong></p>";

        if (isset($config['debug']) && $config['debug'] === true) {
            echo "
            <div style='margin-top: 20px; text-align: left; background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; overflow-wrap: break-word;'>
                <h3 style='margin-top: 0;'>API Request/Response Log</h3>
                <p><strong>API URL:</strong> " . htmlspecialchars($apiUrl) . "</p>
                <p><strong>Request Payload:</strong><br>" . htmlspecialchars($apiPayload ?? 'No payload data') . "</p>
                <p><strong>Response Status:</strong> " . htmlspecialchars($status ?? 'Unknown') . "</p>
                <p><strong>Response Body:</strong><br>" . htmlspecialchars($response ?? 'No response data') . "</p>
            </div>";
        }

        echo "
            <form action='index.php' method='get'>
                <button type='submit'>Return Home</button>
            </form>
        </div>";
        exit;
    }
}

// als alle pogingen mislukken
echo "
    <div class='message error'>
        <h1>Reservation failed</h1>
        <pre>$response</pre>";

if (isset($config['debug']) && $config['debug'] === true) {
    echo "
    <div style='margin-top: 20px; text-align: left; background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; overflow-wrap: break-word;'>
        <h3 style='margin-top: 0;'>API Request/Response Log</h3>
        <p><strong>API URL:</strong> " . htmlspecialchars($apiUrl) . "</p>
        <p><strong>Request Payload:</strong><br>" . htmlspecialchars($apiPayload ?? 'No payload data') . "</p>
        <p><strong>Response Status:</strong> " . htmlspecialchars($status ?? 'Unknown') . "</p>
        <p><strong>Response Body:</strong><br>" . htmlspecialchars($response ?? 'No response data') . "</p>
    </div>";
}

echo "
        <form action='index.php' method='get'>
            <button type='submit'>Return Home</button>
        </form>
    </div>";
?>

</body>
</html>
