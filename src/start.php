<?php
// Genereer ticketnummer bij iedere pagina-refresh
$ticketNumber = "INC" . rand(100000, 999999);

// Haal machinegegevens op
$config = json_decode(file_get_contents('config.json'), true);
$url = "https://api.vendingweb.eu/api/external/machines";
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
    <div class="ticket">Ticket number: <?= htmlspecialchars($ticketNumber) ?></div>
    <p>An employee made a request for an asset in your ITSM application. The request is approved. First select the
        desired IT vending machine location from the dropdown.</p>
    <form id="machineForm" method="GET" action="stock.php">
        <input type="hidden" name="ticket" value="<?= htmlspecialchars($ticketNumber) ?>">
        <label for="vendingmachine">Select a location:</label>
        <select name="vendingmachine" id="vendingmachine" onchange="submitFormIfValid(this)">
            <option value="">IT Vending Machine location</option>
            <?php foreach ($machines as $machine): ?>
                <option value="<?= htmlspecialchars($machine['Id']) ?>">
                    <?= htmlspecialchars($machine['Id']) ?> - <?= htmlspecialchars($machine['Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
</body>
</html>
