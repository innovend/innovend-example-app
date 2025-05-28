<?php
// Genereer ticketnummer bij iedere pagina-refresh
$orderNumber = "INC" . rand(100000, 999999);

// Haal machinegegevens op
$config = json_decode(file_get_contents('../conf/config.json'), true);
$apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
$url = "{$apiBaseUrl}/api/external/machines";
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

$machines = [];

if ($httpStatus === 200) {
    $machines = json_decode($response, true);
    usort($machines, fn($a, $b) => $a['Id'] <=> $b['Id']);
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
    <meta charset="UTF-8">
    <title>Select a vending machine</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 400px;
        }

        select, button {
            padding: 10px;
            font-size: 16px;
            margin-top: 10px;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .ticket {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
    </style>
    <script>
        function submitFormIfValid(select) {
            if (select.value !== "") {
                document.getElementById('machineForm').submit();
            }
        }
    </script>
</head>
<body>
<div class="container">
    <div class="ticket">Order number: <?= htmlspecialchars($orderNumber) ?></div>
    <p>EXAMPLE 1: An employee made a request for an asset in your ITSM application. The request is approved. First select the
        desired vending machine location from the dropdown.</p>
    <p>EXAMPLE 2: You want to enable your webshop users to reserve products and collecting them at the vending machine to make sure they will have stock.</p>
    <form id="machineForm" method="GET" action="reservation_stock.php">
        <input type="hidden" name="ticket" value="<?= htmlspecialchars($orderNumber) ?>">
        <label for="vendingmachine">Select a location:</label>
        <select name="vendingmachine" id="vendingmachine" onchange="submitFormIfValid(this)">
            <option value="">Vending Machine location</option>
            <?php foreach ($machines as $machine): ?>
                <option value="<?= htmlspecialchars($machine['Id']) ?>">
                    <?= htmlspecialchars($machine['Id']) ?> - <?= htmlspecialchars($machine['Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

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
