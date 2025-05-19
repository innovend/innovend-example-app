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
        
        // Database verbinding
        $db = new SQLite3($dbFile);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Database connected\n", FILE_APPEND);
        
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
    $db = new SQLite3($dbFile);
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
</body>
</html>