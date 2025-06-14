<?php
$config = json_decode(file_get_contents('../conf/config.json'), true);

// Voeg deze functie toe voor het annuleren van reserveringen
function cancelReservation($stockreservationId, $machineId, $deliveryDate) {
    global $config;
    global $cancelApiDebugInfo; // Make debug info available globally

    $apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
    $cancelUrl = "{$apiBaseUrl}/api/external/stockreservations/update/false/true";

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

    // Store API call information for debug console
    $cancelApiDebugInfo = [
        'url' => $cancelUrl,
        'headers' => $headers,
        'requestBody' => $requestBody,
        'status' => $httpStatus,
        'response' => $response
    ];

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
$apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
$machinesUrl = "{$apiBaseUrl}/api/external/machines";
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

// Store API call information for debug console
$machinesApiDebugInfo = [
    'url' => $machinesUrl,
    'headers' => $headers,
    'status' => $httpStatus,
    'response' => $response
];

$machines = [];
if ($httpStatus === 200) {
    $machines = json_decode($response, true);
    usort($machines, fn($a, $b) => $a['Id'] <=> $b['Id']);
}

// Get reservations if machine is selected
$reservations = [];
$selectedMachine = $_GET['vendingmachine'] ?? '';

if ($selectedMachine !== '') {
    $apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
    $reservationsUrl = "{$apiBaseUrl}/api/external/stockreservations/stockreservationproducts";

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

    // Store API call information for debug console
    $reservationsApiDebugInfo = [
        'url' => $reservationsUrl,
        'headers' => $headers,
        'requestBody' => $requestBody,
        'status' => $httpStatus,
        'response' => $response
    ];

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
            cursor: pointer;
        }

        th:hover {
            background-color: #e9ecef;
        }

        th::after {
            content: "";
            float: right;
            margin-top: 7px;
            border-width: 4px;
            border-style: solid;
            border-color: transparent;
            visibility: hidden;
        }

        th.sort-asc::after {
            border-bottom-color: #333;
            visibility: visible;
        }

        th.sort-desc::after {
            border-top-color: #333;
            visibility: visible;
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

        .paid-status {
            font-size: 18px;
            font-weight: bold;
        }

        .paid-true {
            color: #2e7d32; /* Green color */
        }

        .paid-false {
            color: #c62828; /* Red color */
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
    <script>
        function submitForm() {
            document.getElementById('machineForm').submit();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const table = document.querySelector('table');
            if (!table) return;

            const headers = table.querySelectorAll('th');
            const tableBody = table.querySelector('tbody');
            const rows = tableBody.querySelectorAll('tr');

            // Add click event to all headers
            headers.forEach((header, index) => {
                header.addEventListener('click', () => {
                    // Remove sort classes from all headers
                    headers.forEach(h => {
                        h.classList.remove('sort-asc', 'sort-desc');
                    });

                    // Determine sort direction
                    const isAscending = !header.classList.contains('sort-asc');

                    // Add appropriate sort class
                    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');

                    // Convert rows to array for sorting
                    const rowsArray = Array.from(rows);

                    // Sort the rows
                    rowsArray.sort((rowA, rowB) => {
                        const cellAElement = rowA.querySelectorAll('td')[index];
                        const cellBElement = rowB.querySelectorAll('td')[index];

                        // Get text content, handling special cases
                        let cellA = cellAElement.textContent.trim();
                        let cellB = cellBElement.textContent.trim();

                        // Special handling for status column (contains span with class)
                        if (cellAElement.querySelector('.status') && cellBElement.querySelector('.status')) {
                            cellA = cellAElement.querySelector('.status').textContent.trim();
                            cellB = cellBElement.querySelector('.status').textContent.trim();
                        }

                        // Special handling for cells with multiple lines (br tags)
                        if (cellAElement.innerHTML.includes('<br>') && cellBElement.innerHTML.includes('<br>')) {
                            // Just use the first line for comparison
                            cellA = cellAElement.innerHTML.split('<br>')[0].replace(/<[^>]*>/g, '').trim();
                            cellB = cellBElement.innerHTML.split('<br>')[0].replace(/<[^>]*>/g, '').trim();
                        }

                        // Handle date comparisons
                        if (cellA.match(/^\d{4}-\d{2}-\d{2}/) && cellB.match(/^\d{4}-\d{2}-\d{2}/)) {
                            const dateA = new Date(cellA.replace(/-/g, '/'));
                            const dateB = new Date(cellB.replace(/-/g, '/'));
                            return isAscending ? dateA - dateB : dateB - dateA;
                        }

                        // Handle numeric comparisons
                        if (!isNaN(cellA) && !isNaN(cellB)) {
                            return isAscending ? Number(cellA) - Number(cellB) : Number(cellB) - Number(cellA);
                        }

                        // Default string comparison
                        return isAscending 
                            ? cellA.localeCompare(cellB) 
                            : cellB.localeCompare(cellA);
                    });

                    // Remove all existing rows
                    rows.forEach(row => {
                        tableBody.removeChild(row);
                    });

                    // Add sorted rows
                    rowsArray.forEach(row => {
                        tableBody.appendChild(row);
                    });
                });
            });
        });
    </script>
</head>
<body>
<div class="container">
    <div class="controls">
        <a href="../index.php" class="back-button">Back to main menu</a>
        <form id="machineForm" method="GET" action="reservation_status.php">
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
                    <th>Machine ID ↕</th>
                    <th>Created On ↕</th>
                    <th>Delivery Date ↕</th>
                    <th>Delivered On ↕</th>
                    <th>Expiration Date ↕</th>
                    <th>Status ↕</th>
                    <th>Description ↕</th>
                    <th>Serial Number ↕</th>
                    <th>Unlock Code ↕</th>
                    <th>OrderNr ↕</th>
                    <th>Prepaid ↕</th>
                    <th>First Name ↕</th>
                    <th>Last Name ↕</th>
                    <th>Acties</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['MachineId']) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($reservation['CreatedOn']))) ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($reservation['DeliveryDate']))) ?></td>
                            <td><?= $reservation['DeliveredOn'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($reservation['DeliveredOn']))) : '-' ?></td>
                            <td><?= $reservation['ExpirationDate'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($reservation['ExpirationDate']))) : '-' ?></td>
                            <td>
                                <?php
                                // Check if the order is expired based on expiration date
                                $isExpired = false;
                                if ($reservation['ExpirationDate'] && strtotime($reservation['ExpirationDate']) < time()) {
                                    $isExpired = true;
                                }

                                // Use status-expired class and text if expired by date, otherwise use the original status
                                $statusClass = $isExpired ? 'status-expired' : match ($reservation['StatusId']) {
                                    1 => 'status-ready',
                                    4 => 'status-expired',
                                    6 => 'status-collected',
                                    9 => 'status-cancelled',
                                    default => 'status-other'
                                };

                                $statusText = $isExpired ? 'Expired' : getStatusText($reservation['StatusId']);
                                ?>
                                        <span class="status <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                            </td>
                            <td>
                                <?php 
                                $productDescriptions = [];
                                $serialNumbers = [];
                                foreach ($reservation['Products'] as $product) {
                                    $productDescriptions[] = htmlspecialchars($product['Description']);
                                    if ($product['SerialNr']) {
                                        $serialNumbers[] = htmlspecialchars($product['SerialNr']);
                                    }
                                }
                                echo implode('<br>', $productDescriptions);
                                ?>
                            </td>
                            <td><?= !empty($serialNumbers) ? implode('<br>', $serialNumbers) : '-' ?></td>
                            <td><span class="unlock-code"><?= htmlspecialchars($reservation['UnlockCode'] ?: '-') ?></span></td>
                            <td><?= htmlspecialchars($reservation['OrderNr'] ?: '-') ?></td>
                            <td>
                                <?php if (isset($reservation['IsPaid'])): ?>
                                    <span class="paid-status <?= $reservation['IsPaid'] ? 'paid-true' : 'paid-false' ?>">
                                        <?= $reservation['IsPaid'] ? '✓' : '✗' ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
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
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No reservations found for this machine.</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-data">Select a machine to view reservations.</div>
    <?php endif; ?>
</div>

<?php if (isset($config['debug']) && $config['debug'] === true): ?>
<div id="debugConsole" class="debug-panel">
    <div style="margin-bottom: 10px;">
        <h3 style="margin: 0;">API Request/Response Log</h3>
    </div>
    <div id="debugConsoleContent">
        <h4>Machines API Call</h4>
        <p><strong>API URL:</strong> <?= htmlspecialchars($machinesApiDebugInfo['url']) ?></p>
        <p><strong>Request Headers:</strong><br>
        <?php foreach ($machinesApiDebugInfo['headers'] as $header): ?>
            <?= htmlspecialchars($header) ?><br>
        <?php endforeach; ?>
        </p>
        <p><strong>Response Status:</strong> <?= htmlspecialchars($machinesApiDebugInfo['status']) ?></p>
        <p><strong>Response Body:</strong><br><?= htmlspecialchars($machinesApiDebugInfo['response']) ?></p>

        <?php if (isset($reservationsApiDebugInfo)): ?>
        <hr style="margin: 20px 0;">
        <h4>Reservations API Call</h4>
        <p><strong>API URL:</strong> <?= htmlspecialchars($reservationsApiDebugInfo['url']) ?></p>
        <p><strong>Request Headers:</strong><br>
        <?php foreach ($reservationsApiDebugInfo['headers'] as $header): ?>
            <?= htmlspecialchars($header) ?><br>
        <?php endforeach; ?>
        </p>
        <p><strong>Request Body:</strong><br><?= htmlspecialchars($reservationsApiDebugInfo['requestBody']) ?></p>
        <p><strong>Response Status:</strong> <?= htmlspecialchars($reservationsApiDebugInfo['status']) ?></p>
        <p><strong>Response Body:</strong><br><?= htmlspecialchars($reservationsApiDebugInfo['response']) ?></p>
        <?php endif; ?>

        <?php if (isset($cancelApiDebugInfo)): ?>
        <hr style="margin: 20px 0;">
        <h4>Cancel Reservation API Call</h4>
        <p><strong>API URL:</strong> <?= htmlspecialchars($cancelApiDebugInfo['url']) ?></p>
        <p><strong>Request Headers:</strong><br>
        <?php foreach ($cancelApiDebugInfo['headers'] as $header): ?>
            <?= htmlspecialchars($header) ?><br>
        <?php endforeach; ?>
        </p>
        <p><strong>Request Body:</strong><br><?= htmlspecialchars($cancelApiDebugInfo['requestBody']) ?></p>
        <p><strong>Response Status:</strong> <?= htmlspecialchars($cancelApiDebugInfo['status']) ?></p>
        <p><strong>Response Body:</strong><br><?= htmlspecialchars($cancelApiDebugInfo['response']) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Debug panel is now permanently visible -->
<?php endif; ?>
</body>
</html>
