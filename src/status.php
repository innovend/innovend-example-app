<?php
$config = json_decode(file_get_contents('config.json'), true);

// Voeg deze functie toe voor het annuleren van reserveringen
function cancelReservation($stockreservationId, $machineId, $deliveryDate) {
    global $config;
    
    $cancelUrl = "https://api.vendingweb.eu/api/external/stockreservations/update/false/true";
    
    $headers = [
        "x-api-key: {$config['apiKey']}",
        "Accept: application/json",
        "Content-Type: application/json"
    ];

    $requestBody = json_encode([
        [
            "Id" => $stockreservationId,
            "MachineId" => $machineId,
            "DeliveryDate" => $deliveryDate,
            "StatusId" => 9,
            "IsPaid" => true
        ]
    ]);

    $curl = curl_init($cancelUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ":" . $config['password']);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);

    $response = curl_exec($curl);
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return $httpStatus === 200;
}

// Verwerk annulering als het formulier is verzonden
if (isset($_POST['cancel']) && isset($_POST['reservationId']) && isset($_POST['machineId']) && isset($_POST['deliveryDate'])) {
    $success = cancelReservation(
        (int)$_POST['reservationId'],
        (int)$_POST['machineId'],
        $_POST['deliveryDate']
    );
    
    if ($success) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Get machines for dropdown
$machinesUrl = "https://api.vendingweb.eu/api/external/machines";
$headers = [
    "x-api-key: {$config['apiKey']}",
    "Accept: application/json",
    "Content-Type: application/json"
];

$curl = curl_init($machinesUrl);
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

// Get reservations if machine is selected
$reservations = [];
$selectedMachine = $_GET['vendingmachine'] ?? '';

if ($selectedMachine !== '') {
    $reservationsUrl = "https://api.vendingweb.eu/api/external/stockreservations/stockreservationproducts";

    $requestBody = json_encode([
        "MachineId" => (int)$selectedMachine,
        "DaysBack" => 30,
        "StatusIds" => [1, 4, 6, 9],
        "DaysBackDelivered" => 30
    ]);

    $curl = curl_init($reservationsUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ":" . $config['password']);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);

    $response = curl_exec($curl);
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpStatus === 200) {
        $reservations = json_decode($response, true);

        // Sort reservations by DeliveryDate in descending order
        usort($reservations, function($a, $b) {
            return strtotime($b['DeliveryDate']) - strtotime($a['DeliveryDate']);
        });
    }
}

// Status mapping function
function getStatusText($statusId) {
    return match ($statusId) {
        1 => 'Ready to collect',
        4 => 'Expired',
        6 => 'Collected',
        9 => 'Cancelled',
        default => 'Status ' . $statusId
    };
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
            margin: 20px;
            background: #f0f2f5;
        }

        .container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        select {
            padding: 10px;
            font-size: 16px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 200px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            white-space: nowrap;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .back-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
        }

        .back-button:hover {
            background-color: #0056b3;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-ready {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-collected {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-expired {
            background-color: #fff3e0;
            color: #e65100;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }

        .status-other {
            background-color: #f5f5f5;
            color: #616161;
        }

        .unlock-code {
            font-family: monospace;
            font-size: 16px;
            font-weight: bold;
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .cancel-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .cancel-button:hover {
            background-color: #c82333;
        }
    </style>
    <script>
        function submitForm() {
            document.getElementById('machineForm').submit();
        }
    </script>
</head>
<body>
<div class="container">
    <div class="controls">
        <a href="index.php" class="back-button">Back to main menu</a>
        <form id="machineForm" method="GET" action="status.php">
            <select name="vendingmachine" onchange="submitForm()">
                <option value="">Select a location</option>
                <?php foreach ($machines as $machine): ?>
                    <option value="<?= htmlspecialchars($machine['Id']) ?>"
                        <?= $selectedMachine == $machine['Id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($machine['Id']) ?> - <?= htmlspecialchars($machine['Name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($selectedMachine !== ''): ?>
        <h1>Reservation Status for Machine <?= htmlspecialchars($selectedMachine) ?></h1>
        <?php if (!empty($reservations)): ?>
            <table>
                <thead>
                <tr>
                    <th>Machine ID</th>
                    <th>Created On</th>
                    <th>Delivery Date</th>
                    <th>Delivered On</th>
                    <th>Expiration Date</th>
                    <th>Status</th>
                    <th>Description</th>
                    <th>Serial Number</th>
                    <th>Unlock Code</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Acties</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <?php foreach ($reservation['Products'] as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['MachineId']) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($reservation['CreatedOn']))) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($reservation['DeliveryDate']))) ?></td>
                            <td><?= $reservation['DeliveredOn'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($reservation['DeliveredOn']))) : '-' ?></td>
                            <td><?= $reservation['ExpirationDate'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($reservation['ExpirationDate']))) : '-' ?></td>
                            <td>
                                        <span class="status <?=
                                        match ($reservation['StatusId']) {
                                            1 => 'status-ready',
                                            4 => 'status-expired',
                                            6 => 'status-collected',
                                            9 => 'status-cancelled',
                                            default => 'status-other'
                                        }
                                        ?>">
                                            <?= getStatusText($reservation['StatusId']) ?>
                                        </span>
                            </td>
                            <td><?= htmlspecialchars($product['Description']) ?></td>
                            <td><?= $product['SerialNr'] ? htmlspecialchars($product['SerialNr']) : '-' ?></td>
                            <td><span class="unlock-code"><?= htmlspecialchars($reservation['UnlockCode']) ?></span></td>
                            <td><?= htmlspecialchars($reservation['FirstName'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($reservation['LastName'] ?: '-') ?></td>
                            <td>
                                <?php if ($reservation['StatusId'] === 1): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel the reservation?');">
                                        <input type="hidden" name="reservationId" value="<?= htmlspecialchars($reservation['Id']) ?>">
                                        <input type="hidden" name="machineId" value="<?= htmlspecialchars($reservation['MachineId']) ?>">
                                        <input type="hidden" name="deliveryDate" value="<?= htmlspecialchars($reservation['DeliveryDate']) ?>">
                                        <button type="submit" name="cancel" class="cancel-button">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No reservations found for this machine.</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-data">Select a machine to view reservations.</div>
    <?php endif; ?>
</div>
</body>
</html>