<?php
// Generate a random 5-digit return code
$returnCode = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

// Get ticket number from GET parameter or generate a new one
$ticketNumber = isset($_GET['ticket']) ? $_GET['ticket'] : "INC" . rand(100000, 999999);

// Load configuration
$config = json_decode(file_get_contents('config.json'), true);

// Get machine data if needed
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

// Process form submission
$selectedMachine = '';
$codeGenerated = false;
$apiResponse = null;
$apiHttpStatus = null;
$payload = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vendingmachine'])) {
    $selectedMachine = $_POST['vendingmachine'];
    // Get ticket number from POST if available
    if (isset($_POST['ticket'])) {
        $ticketNumber = $_POST['ticket'];
    }

    // Call the API to create a pickup delivery
    $apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
    $apiUrl = "{$apiBaseUrl}/api/external/pickupdeliveries/create/true";
    $payload = json_encode([
        "MachineId" => $selectedMachine,
        "Returnable" => true,
        "OrderNr" => $ticketNumber,
        "UnlockCode" => $returnCode
    ]);

    $apiHeaders = [
        "x-api-key: {$config['apiKey']}",
        "Accept: application/json",
        "Content-Type: application/json",
        "Content-Length: " . strlen($payload)
    ];

    $apiCurl = curl_init($apiUrl);
    curl_setopt($apiCurl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($apiCurl, CURLOPT_HTTPHEADER, $apiHeaders);
    curl_setopt($apiCurl, CURLOPT_USERPWD, $config['username'] . ":" . $config['password']);
    curl_setopt($apiCurl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($apiCurl, CURLOPT_POST, true);
    curl_setopt($apiCurl, CURLOPT_POSTFIELDS, $payload);

    $apiResponse = curl_exec($apiCurl);
    $apiHttpStatus = curl_getinfo($apiCurl, CURLINFO_HTTP_CODE);
    curl_close($apiCurl);

    $codeGenerated = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Return to Locker Code</title>
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

        .return-code {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
            padding: 15px;
            background-color: #e9f7ef;
            border: 1px solid #28a745;
            border-radius: 5px;
            color: #28a745;
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
    </style>
</head>
<body>
<div class="container">
    <div class="ticket">Ticket number: <?= htmlspecialchars($ticketNumber) ?></div>

    <?php if ($codeGenerated): ?>
        <h2>Return Code Generated</h2>
        <p>Use the code below to return your asset to the locker:</p>
        <div class="return-code"><?= htmlspecialchars($returnCode) ?></div>
        <p>Selected location: 
            <?php 
            foreach ($machines as $machine) {
                if ($machine['Id'] == $selectedMachine) {
                    echo htmlspecialchars($machine['Id'] . ' - ' . $machine['Name']);
                    break;
                }
            }
            ?>
        </p>

        <?php if (isset($config['debug']) && $config['debug'] === true): ?>
        <div id="debugConsole" style="margin-top: 20px; text-align: left; background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; overflow-wrap: break-word; transition: height 0.3s ease-in-out;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin: 0;">API Request/Response Log</h3>
                <button id="toggleDebugConsole" style="background: #007bff; color: white; border: none; border-radius: 4px; padding: 5px 10px; cursor: pointer;">Minimize</button>
            </div>
            <div id="debugConsoleContent">
                <p><strong>API URL:</strong> <?= htmlspecialchars($apiUrl) ?></p>
                <p><strong>Request Payload:</strong><br><?= htmlspecialchars($payload ?? 'No payload data') ?></p>
                <p><strong>Response Status:</strong> <?= htmlspecialchars($apiHttpStatus ?? 'Unknown') ?></p>
                <p><strong>Response Body:</strong><br><?= htmlspecialchars($apiResponse ?? 'No response data') ?></p>
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

        <a href="index.php" class="back-button">Back to main menu</a>
    <?php else: ?>
        <p>Generate a return code to return your asset to an IT vending machine locker. First select the desired IT vending machine location.</p>
        <form method="POST" action="">
            <input type="hidden" name="ticket" value="<?= htmlspecialchars($ticketNumber) ?>">
            <label for="vendingmachine">Select a location:</label>
            <select name="vendingmachine" id="vendingmachine" required>
                <option value="">IT Vending Machine location</option>
                <?php foreach ($machines as $machine): ?>
                    <option value="<?= htmlspecialchars($machine['Id']) ?>">
                        <?= htmlspecialchars($machine['Id']) ?> - <?= htmlspecialchars($machine['Name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Generate Return Code</button>
        </form>
        <a href="index.php" class="back-button">Back to main menu</a>
    <?php endif; ?>
</div>
</body>
</html>
