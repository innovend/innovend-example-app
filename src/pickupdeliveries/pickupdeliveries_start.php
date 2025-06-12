<?php
// Get ticket number from GET parameter or generate a new one
$ticketNumber = isset($_GET['ticket']) ? $_GET['ticket'] : "INC" . rand(100000, 999999);

// Load configuration
$config = json_decode(file_get_contents('../conf/config.json'), true);

// Get machine data
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

// Store API call information for debug console
$apiDebugInfo = [
    'url' => $url,
    'headers' => $headers,
    'status' => $httpStatus,
    'response' => $response
];

$machines = [];

if ($httpStatus === 200) {
    $machines = json_decode($response, true);
    usort($machines, fn($a, $b) => $a['Id'] <=> $b['Id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Click & Collect Order</title>
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

        .back-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }

        /* Debug Console Styles */
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
    </style>
</head>
<body>
<div class="container">
    <h2>Create Click & Collect Order</h2>
    <div class="ticket">Ticket number: <?= htmlspecialchars($ticketNumber) ?></div>

    <p>Start a new click & collect order by selecting the desired vending machine location.</p>
    <form method="POST" action="pickupdeliveries_create.php">
        <input type="hidden" name="ticket" value="<?= htmlspecialchars($ticketNumber) ?>">
        <label for="vendingmachine">Select a location:</label>
        <select name="vendingmachine" id="vendingmachine" required>
            <option value="">Vending Machine location</option>
            <?php foreach ($machines as $machine): ?>
                <option value="<?= htmlspecialchars($machine['Id']) ?>">
                    <?= htmlspecialchars($machine['Id']) ?> - <?= htmlspecialchars($machine['Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Continue</button>
    </form>
    <a href="../index.php" class="back-button">Back to main menu</a>
</div>

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
