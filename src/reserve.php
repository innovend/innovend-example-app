<?php
$config = json_decode(file_get_contents('config.json'), true);

$machineId = intval($_POST['machineId']);
$sku = $_POST['productSku'];
$orderNr = $_POST['ticket'];

// timestamps
$now = (new DateTime())->format('c');
$expiration = (new DateTime('+24 hours'))->format('c');

// genereer unieke unlockcode
function generateUnlockCode() {
    return str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
}

// functie om reservering te maken
function createReservation($config, $machineId, $orderNr, $sku, $now, $expiration) {
    $unlockCode = generateUnlockCode();

    $payload = json_encode([
        [
            "MachineId" => $machineId,
            "CreatedOn" => $now,
            "DeliveryDate" => $now,
            "ExpirationDate" => $expiration,
            "UnlockCode" => $unlockCode,
            "OrderNr" => $orderNr,
            "IsPaid" => true,
            "Products" => [
                [
                    "SkuCode" => $sku,
                    "Qty" => 1
                ]
            ]
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

    return [$status, $response, $unlockCode];
}

// probeer reservering aan te maken (max 5 pogingen bij code conflict)
$maxAttempts = 5;
for ($i = 0; $i < $maxAttempts; $i++) {
    [$status, $response, $code] = createReservation($config, $machineId, $orderNr, $sku, $now, $expiration);
    if ($status === 200) {
        echo "<h1>Reservation successful!</h1>";
        echo "<p>Your unlock code: <strong>$code</strong></p>";
        echo '<br><form action="index.php" method="get"><button type="submit">Return Home</button></form>';
        exit;

    }
}
echo "<h1>Reservation failed</h1><pre>$response</pre>";
