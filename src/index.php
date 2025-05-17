<?php
// Load config
$config = json_decode(file_get_contents('config.json'), true);

// API request to fetch vending machines
$url = "https://api.vendingweb.eu/api/external/machines";
$headers = [
    "x-api-key: {$config['apiKey']}",
    "Accept: application/json"
];

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ":" . $config['password']);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); // false voor lokaal testen indien nodig

$response = curl_exec($curl);
if (curl_errno($curl)) {
    die("Error contacting API: " . curl_error($curl));
}
$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpStatus !== 200) {
    die("API returned status $httpStatus: " . htmlspecialchars($response));
}

$machines = json_decode($response, true);

// Sorteer op ID
usort($machines, fn($a, $b) => $a['Id'] <=> $b['Id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select a vending machine</title>
    <script>
        function submitFormIfValid(select) {
            if (select.value !== "") {
                document.getElementById('machineForm').submit();
            }
        }
    </script>
</head>
<body>
<h1>Select a vending machine</h1>
<form id="machineForm" method="GET" action="stock.php">
    <label for="vendingmachine">Location:</label>
    <select name="vendingmachine" id="vendingmachine" onchange="submitFormIfValid(this)">
        <option value="">Select a location</option>
        <?php foreach ($machines as $machine): ?>
            <option value="<?= htmlspecialchars($machine['Id']) ?>">
                <?= htmlspecialchars($machine['Id']) ?> - <?= htmlspecialchars($machine['Name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>
</body>
</html>
