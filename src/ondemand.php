<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbFile = '/var/www/html/ondemand.db';
$logFile = '/var/www/html/hook_debug.log';

// Verwerk POST verzoeken (hooks)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log de binnenkomende data
        $input = file_get_contents('php://input');
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Received data: " . $input . "\n", FILE_APPEND);

        // Decodeer de JSON data
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON: " . json_last_error_msg());
        }

        // Check if SQLite3 extension is available
        if (!class_exists('SQLite3')) {
            throw new Exception("SQLite3 extension is not enabled. Please see README.md for instructions on enabling SQLite3.");
        }

        // Database verbinding
        $db = new SQLite3($dbFile);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Database connected\n", FILE_APPEND);

        // Check if transactions table exists, if not create it
        $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='transactions'");
        if (!$tableCheck->fetchArray()) {
            // Create the transactions table
            $createTableQuery = "CREATE TABLE IF NOT EXISTS transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                machineId TEXT NOT NULL,
                machineName TEXT NOT NULL,
                firstName TEXT NOT NULL,
                middleName TEXT,
                lastName TEXT NOT NULL,
                badgeCode TEXT NOT NULL,
                products TEXT NOT NULL,
                received_at DATETIME NOT NULL
            )";
            $db->exec($createTableQuery);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Created transactions table\n", FILE_APPEND);
        }

        // Voorbereid de INSERT query (let op de syntax hier)
        $query = "INSERT INTO transactions (machineId, machineName, firstName, middleName, lastName, badgeCode, products, received_at) 
                 VALUES (:machineId, :machineName, :firstName, :middleName, :lastName, :badgeCode, :products, :received_at)";

        $stmt = $db->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare error: " . $db->lastErrorMsg());
        }

        // Bind parameters
        $stmt->bindValue(':machineId', $data['machineId'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':machineName', $data['machineName'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':firstName', $data['firstName'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':middleName', $data['middleName'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':lastName', $data['lastName'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':badgeCode', $data['badgeCode'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':products', json_encode($data['products'] ?? []), SQLITE3_TEXT);
        $stmt->bindValue(':received_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);

        // Voer de query uit
        $result = $stmt->execute();

        if (!$result) {
            throw new Exception("Execute error: " . $db->lastErrorMsg());
        }

        // Log success
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Transaction saved successfully\n", FILE_APPEND);

        // Stuur succesvolle response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;

    } catch (Exception $e) {
        // Log de error
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);

        // Stuur error response
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Voor GET verzoeken, toon de transacties
try {
    // Check if SQLite3 extension is available
    if (!class_exists('SQLite3')) {
        throw new Exception("SQLite3 extension is not enabled. Please see README.md for instructions on enabling SQLite3.");
    }

    $db = new SQLite3($dbFile);

    // Check if transactions table exists, if not create it
    $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='transactions'");
    if (!$tableCheck->fetchArray()) {
        // Create the transactions table
        $createTableQuery = "CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            machineId TEXT NOT NULL,
            machineName TEXT NOT NULL,
            firstName TEXT NOT NULL,
            middleName TEXT,
            lastName TEXT NOT NULL,
            badgeCode TEXT NOT NULL,
            products TEXT NOT NULL,
            received_at DATETIME NOT NULL
        )";
        $db->exec($createTableQuery);
    }

    $results = $db->query('SELECT * FROM transactions ORDER BY received_at DESC');
    $transactions = [];

    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $row['products'] = json_decode($row['products'], true);
        $transactions[] = $row;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// De rest van je bestaande HTML-code blijft hetzelfde...
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>On Demand Asset Usage</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f0f2f5;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .back-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .products {
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
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
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
            padding: 15px;
            box-sizing: border-box;
            font-family: monospace;
            font-size: 12px;
            text-align: left;
        }

        .debug-panel.minimized {
            transform: translateX(calc(100% - 30px));
        }

        .debug-panel-toggle {
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px 0 0 4px;
            padding: 10px;
            cursor: pointer;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            height: 100px;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back-button">Back to main menu</a>
    <h1>On Demand Asset usage</h1>

    <?php if (!empty($error)): ?>
        <div style="color: red; margin: 20px 0;">
            Error: <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($transactions)): ?>
        <table>
            <thead>
            <tr>
                <th>Machine</th>
                <th>User</th>
                <th>Badge Code</th>
                <th>Products</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($transaction['machineId']) ?><br>
                        <small><?= htmlspecialchars($transaction['machineName']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($transaction['firstName']) ?>
                        <?= htmlspecialchars($transaction['middleName']) ?>
                        <?= htmlspecialchars($transaction['lastName']) ?>
                    </td>
                    <td><?= htmlspecialchars($transaction['badgeCode']) ?></td>
                    <td>
                        <?php foreach ($transaction['products'] as $product): ?>
                            <div class="products">
                                <?= htmlspecialchars($product['productName']) ?>
                                (<?= htmlspecialchars($product['receivedCount']) ?>x)
                                - €<?= htmlspecialchars($product['priceIncVat']) ?>
                            </div>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?= date('d-m-Y H:i', strtotime($transaction['received_at'])) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No transactions found.</p>
    <?php endif; ?>
</div>

<?php
// Get config to check if debug mode is enabled
$config = [];
if (file_exists('conf/config.json')) {
    $config = json_decode(file_get_contents('conf/config.json'), true);
}

// Prepare debug information
$debugInfo = [
    'dbFile' => $dbFile,
    'logFile' => $logFile,
    'transactionCount' => isset($transactions) ? count($transactions) : 0,
    'error' => isset($error) ? $error : null,
    'requestMethod' => $_SERVER['REQUEST_METHOD']
];
?>

<?php if (isset($config['debug']) && $config['debug'] === true): ?>
<div id="debugConsole" class="debug-panel">
    <button id="toggleDebugConsole" class="debug-panel-toggle">Show/Hide Debug</button>
    <div style="margin-bottom: 10px;">
        <h3 style="margin: 0;">Debug Information</h3>
    </div>
    <div id="debugConsoleContent">
        <p><strong>Database File:</strong> <?= htmlspecialchars($debugInfo['dbFile']) ?></p>
        <p><strong>Log File:</strong> <?= htmlspecialchars($debugInfo['logFile']) ?></p>
        <p><strong>Request Method:</strong> <?= htmlspecialchars($debugInfo['requestMethod']) ?></p>
        <p><strong>Transaction Count:</strong> <?= htmlspecialchars($debugInfo['transactionCount']) ?></p>
        <?php if ($debugInfo['error']): ?>
            <p><strong>Error:</strong> <?= htmlspecialchars($debugInfo['error']) ?></p>
        <?php endif; ?>

        <?php if (file_exists($logFile)): ?>
            <p><strong>Recent Log Entries:</strong></p>
            <pre style="max-height: 200px; overflow-y: auto; background: #f1f1f1; padding: 10px; font-size: 11px;">
<?php
// Display the last 10 lines of the log file
$logContent = file_exists($logFile) ? file($logFile) : [];
$lastLines = array_slice($logContent, -10);
echo htmlspecialchars(implode('', $lastLines));
?>
            </pre>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('toggleDebugConsole');
        const debugConsole = document.getElementById('debugConsole');

        // Initialize as minimized
        debugConsole.classList.add('minimized');

        toggleButton.addEventListener('click', function() {
            debugConsole.classList.toggle('minimized');
        });
    });
</script>
<?php endif; ?>
</body>
</html>
