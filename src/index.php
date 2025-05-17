<?php
// Genereer ServiceNow ticketnummer (voorbeeld: INC123456)
$ticketNumber = "INC" . rand(100000, 999999);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select a vending machine</title>
    <script>
        function submitFormIfValid(select) {
            if (select.value !== "") {
                document.getElementById('machineForm').submit();
            }
        }
    </script>
</head>
<body>
<h1>Ticket number: <?= htmlspecialchars($ticketNumber) ?></h1>

<form id="machineForm" method="GET" action="stock.php">
    <input type="hidden" name="ticket" value="<?= htmlspecialchars($ticketNumber) ?>">
    <label for="vendingmachine">Location:</label>
    <select name="vendingmachine" id="vendingmachine" onchange="submitFormIfValid(this)">
        <option value="">Select a location</option>
        <?php
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

        if ($httpStatus === 200) {
            $machines = json_decode($response, true);
            usort($machines, fn($a, $b) => $a['Id'] <=> $b['Id']);
            foreach ($machines as $machine) {
                echo "<option value=\"{$machine['Id']}\">{$machine['Id']} - {$machine['Name']}</option>";
            }
        } else {
            echo "<option disabled>Error loading machines</option>";
        }
        ?>
    </select>
</form>
</body>
</html>
