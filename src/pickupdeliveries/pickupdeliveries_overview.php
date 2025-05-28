<?php
$config = json_decode(file_get_contents('../conf/config.json'), true);

// Get pickup deliveries data
$pickupDeliveries = [];
$clientId = $config['clientId'] ?? '';

if ($clientId !== '') {
    $apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
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

    if ($httpStatus === 200) {
        $pickupDeliveries = json_decode($response, true);

        // Sort pickup deliveries by CreatedOn in descending order
        usort($pickupDeliveries, function($a, $b) {
            return strtotime($b['CreatedOn']) - strtotime($a['CreatedOn']);
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
    <?php if (!empty($pickupDeliveries)): ?>
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
        <div class="no-data">No Click & Collect orders found.</div>
    <?php endif; ?>
</div>
</body>
</html>