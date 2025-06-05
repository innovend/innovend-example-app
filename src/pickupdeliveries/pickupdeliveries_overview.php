<?php
$config = json_decode(file_get_contents('../conf/config.json'), true);

// Get machine data
$apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
$machines = [];
$selectedMachine = '';
$selectedMachineName = '';

// Check if a machine is selected
if (isset($_POST['vendingmachine']) && !empty($_POST['vendingmachine'])) {
    $selectedMachine = $_POST['vendingmachine'];
}

// Get list of machines
$machinesUrl = "{$apiBaseUrl}/api/external/machines";
$headers = [
    "x-api-key: {$config['apiKey']}",
    "Accept: application/json"
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

if ($httpStatus === 200) {
    $machines = json_decode($response, true);
    usort($machines, fn($a, $b) => $a['Id'] <=> $b['Id']);

    // Find the name of the selected machine
    if ($selectedMachine !== '') {
        foreach ($machines as $machine) {
            if ($machine['Id'] == $selectedMachine) {
                $selectedMachineName = $machine['Name'];
                break;
            }
        }
    }
}

// Get pickup deliveries data
$pickupDeliveries = [];
$clientId = $selectedMachine; // Use selected machine ID as client ID

if ($clientId !== '') {
    $pickupDeliveriesUrl = "{$apiBaseUrl}/api/external/pickupdeliveries/{$clientId}?daysBackDeliveries=30";

    $headers = [
        "x-api-key: {$config['apiKey']}",
        "Accept: application/json",
        "Content-Type: application/json"
    ];

    $curl = curl_init($pickupDeliveriesUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ":" . $config['password']);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($curl);
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // Store API call information for debug console
    $pickupDeliveriesApiDebugInfo = [
        'url' => $pickupDeliveriesUrl,
        'headers' => $headers,
        'status' => $httpStatus,
        'response' => $response
    ];

    if ($httpStatus === 200) {
        $pickupDeliveries = json_decode($response, true);

        // Sort pickup deliveries by CreatedOn in descending order
        usort($pickupDeliveries, function($a, $b) {
            $timeA = isset($a['CreatedOn']) && $a['CreatedOn'] !== null ? strtotime($a['CreatedOn']) : 0;
            $timeB = isset($b['CreatedOn']) && $b['CreatedOn'] !== null ? strtotime($b['CreatedOn']) : 0;
            return $timeB - $timeA;
        });
    }
}

// Status mapping function
function getStatusText($statusId) {
    return match ($statusId) {
        1 => 'Created',
        2 => 'Filled',
        3 => 'Delivered',
        4 => 'Returned',
        5 => 'Expired',
        default => 'Status ' . $statusId
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Click & Collect Overview</title>
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

        .status-created {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-filled {
            background-color: #fff3e0;
            color: #e65100;
        }

        .status-delivered {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-returned {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .status-expired {
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
    <script>
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
    </div>

    <h1>Click & Collect Overview</h1>

    <?php if ($selectedMachine === ''): ?>
        <!-- Machine selection screen -->
        <p>Please select a machine location to view Click & Collect orders:</p>
        <form method="POST" action="">
            <div style="margin: 20px 0; max-width: 400px;">
                <label for="vendingmachine" style="display: block; margin-bottom: 10px; font-weight: bold;">Select a location:</label>
                <select name="vendingmachine" id="vendingmachine" required style="width: 100%; padding: 10px; margin-bottom: 20px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Vending Machine location</option>
                    <?php foreach ($machines as $machine): ?>
                        <option value="<?= htmlspecialchars($machine['Id']) ?>">
                            <?= htmlspecialchars($machine['Id']) ?> - <?= htmlspecialchars($machine['Name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" style="background-color: #007bff; color: white; border: none; border-radius: 4px; padding: 12px 20px; font-size: 16px; cursor: pointer; width: 100%;">View Orders</button>
            </div>
        </form>
    <?php elseif (!empty($pickupDeliveries)): ?>
        <!-- Display selected machine name -->
        <div style="margin-bottom: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">
            <strong>Selected Location:</strong> <?= htmlspecialchars($selectedMachine . ' - ' . $selectedMachineName) ?>
            <form method="POST" action="" style="display: inline-block; margin-left: 20px;">
                <button type="submit" style="background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-size: 14px;">Change Location</button>
            </form>
        </div>

        <table>
            <thead>
            <tr>
                <th>ID ↕</th>
                <th>Machine ID ↕</th>
                <th>Machine Name ↕</th>
                <th>Order Nr ↕</th>
                <th>Status ↕</th>
                <th>Unlock Code ↕</th>
                <th>Delivery Date ↕</th>
                <th>Customer Name ↕</th>
                <th>Created On ↕</th>
                <th>Filled On ↕</th>
                <th>Delivered On ↕</th>
                <th>Returned On ↕</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pickupDeliveries as $delivery): ?>
                <tr>
                    <td><?= htmlspecialchars($delivery['Id']) ?></td>
                    <td><?= htmlspecialchars($delivery['MachineId']) ?></td>
                    <td><?= htmlspecialchars($delivery['MachineName']) ?></td>
                    <td><?= htmlspecialchars($delivery['OrderNr'] ?: '-') ?></td>
                    <td>
                        <?php
                        $statusClass = match ($delivery['Status']) {
                            1 => 'status-created',
                            2 => 'status-filled',
                            3 => 'status-delivered',
                            4 => 'status-returned',
                            5 => 'status-expired',
                            default => 'status-other'
                        };
                        $statusText = getStatusText($delivery['Status']);
                        ?>
                        <span class="status <?= $statusClass ?>">
                            <?= $statusText ?>
                        </span>
                    </td>
                    <td><span class="unlock-code"><?= htmlspecialchars($delivery['UnlockCode'] ?: '-') ?></span></td>
                    <td><?= $delivery['DeliveryDate'] ? htmlspecialchars(substr($delivery['DeliveryDate'], 0, 4) . '-' . substr($delivery['DeliveryDate'], 4, 2) . '-' . substr($delivery['DeliveryDate'], 6, 2)) : '-' ?></td>
                    <td><?= htmlspecialchars($delivery['CustomerName'] ?: '-') ?></td>
                    <td><?= $delivery['CreatedOn'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($delivery['CreatedOn']))) : '-' ?></td>
                    <td><?= $delivery['FilledOn'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($delivery['FilledOn']))) : '-' ?></td>
                    <td><?= $delivery['DeliveredOn'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($delivery['DeliveredOn']))) : '-' ?></td>
                    <td><?= $delivery['ReturnedOn'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($delivery['ReturnedOn']))) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <!-- Display selected machine name with no orders -->
        <div style="margin-bottom: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">
            <strong>Selected Location:</strong> <?= htmlspecialchars($selectedMachine . ' - ' . $selectedMachineName) ?>
            <form method="POST" action="" style="display: inline-block; margin-left: 20px;">
                <button type="submit" style="background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 5px 10px; cursor: pointer; font-size: 14px;">Change Location</button>
            </form>
        </div>
        <div class="no-data">No Click & Collect orders found for this location.</div>
    <?php endif; ?>
</div>

<?php if (isset($config['debug']) && $config['debug'] === true): ?>
<div id="debugConsole" class="debug-panel">
    <button id="toggleDebugConsole" class="debug-panel-toggle">Show/Hide Debug</button>
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
        <p><strong>Response Body:</strong><br><pre style="max-height: 200px; overflow-y: auto; background: #f1f1f1; padding: 10px; font-size: 11px;"><?= htmlspecialchars($machinesApiDebugInfo['response']) ?></pre></p>

        <?php if (isset($pickupDeliveriesApiDebugInfo)): ?>
        <hr style="margin: 20px 0;">
        <h4>Pickup Deliveries API Call</h4>
        <p><strong>API URL:</strong> <?= htmlspecialchars($pickupDeliveriesApiDebugInfo['url']) ?></p>
        <p><strong>Request Headers:</strong><br>
        <?php foreach ($pickupDeliveriesApiDebugInfo['headers'] as $header): ?>
            <?= htmlspecialchars($header) ?><br>
        <?php endforeach; ?>
        </p>
        <p><strong>Response Status:</strong> <?= htmlspecialchars($pickupDeliveriesApiDebugInfo['status']) ?></p>
        <p><strong>Response Body:</strong><br><pre style="max-height: 200px; overflow-y: auto; background: #f1f1f1; padding: 10px; font-size: 11px;"><?= htmlspecialchars($pickupDeliveriesApiDebugInfo['response']) ?></pre></p>
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
