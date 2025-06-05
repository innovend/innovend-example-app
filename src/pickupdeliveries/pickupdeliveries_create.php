<?php
// Process form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['vendingmachine'])) {
    // Redirect back to start page if accessed directly without form submission
    header('Location: pickupdeliveries_start.php');
    exit;
}

// Get selected machine and ticket number from POST
$selectedMachine = $_POST['vendingmachine'];
$ticketNumber = isset($_POST['ticket']) ? $_POST['ticket'] : "INC" . rand(100000, 999999);

// Generate a random 5-digit pickup code
$pickupCode = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

// Load configuration
$config = json_decode(file_get_contents('../conf/config.json'), true);

// Get machine data for display
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
$selectedMachineName = "Unknown";

if ($httpStatus === 200) {
    $machines = json_decode($response, true);
    foreach ($machines as $machine) {
        if ($machine['Id'] == $selectedMachine) {
            $selectedMachineName = $machine['Name'];
            break;
        }
    }
}

// Process order creation
$apiResponse = null;
$apiHttpStatus = null;
$payload = null;
$orderCreated = false;
$formErrors = [];

// If the form is submitted with all required fields
if (isset($_POST['create_order'])) {
    // Validate required fields
    $requiredFields = ['firstName', 'lastName', 'email'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $formErrors[$field] = 'This field is required';
        }
    }

    // Validate email format
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors['email'] = 'Please enter a valid email address';
    }

    // Validate numeric fields
    $numericFields = ['price', 'priceExclVat', 'priceVatPercentage'];
    foreach ($numericFields as $field) {
        if (!empty($_POST[$field]) && !is_numeric($_POST[$field])) {
            $formErrors[$field] = 'This field must be a number';
        }
    }

    // If no validation errors, proceed with API call
    if (empty($formErrors)) {
        // Call the API to create a pickup delivery
        $apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
        $apiUrl = "{$apiBaseUrl}/api/external/pickupdeliveries/create/true";

        // Get checkbox values (convert to boolean)
        $doSendEmailAfterCreate = isset($_POST['doSendEmailAfterCreate']) ? true : false;
        $doSendEmailAfterFill = isset($_POST['doSendEmailAfterFill']) ? true : false;
        $isPaid = isset($_POST['isPaid']) ? true : false;
        $requireAuthorization = isset($_POST['requireAuthorization']) ? true : false;

        // Set doSendEmail to true if email options are enabled
        $doSendEmail = $doSendEmailAfterCreate || $doSendEmailAfterFill;

        // Format delivery date if provided (convert from yyyy-mm-dd to yyyyMMdd)
        $deliveryDate = null;
        if (!empty($_POST['deliveryDate'])) {
            try {
                // Convert from HTML date input format (yyyy-mm-dd) to required API format (yyyyMMdd)
                $date = new DateTime($_POST['deliveryDate']);
                $deliveryDate = $date->format('Ymd');
            } catch (Exception $e) {
                // If date parsing fails, add an error
                $formErrors['deliveryDate'] = "Invalid date format. Please use the date picker.";
            }
        }

        $payload = json_encode([
            "MachineId" => $selectedMachine,
            "Description" => $_POST['description'] ?? '',
            "FirstName" => $_POST['firstName'],
            "LastName" => $_POST['lastName'],
            "Email" => $_POST['email'],
            "DoSendEmail" => $doSendEmail,
            "OrderNr" => $ticketNumber,
            "DeliveryDate" => $deliveryDate,
            "DeliveryTime" => $_POST['deliveryTime'] ?? '',
            "UnlockCode" => $pickupCode,
            "Price" => !empty($_POST['price']) ? (int)((float)$_POST['price'] * 100) : 0,
            "PriceExclVat" => !empty($_POST['priceExclVat']) ? (int)((float)$_POST['priceExclVat'] * 100) : 0,
            "PriceVatPercentage" => !empty($_POST['priceVatPercentage']) ? (float)$_POST['priceVatPercentage'] : 0,
            "ReturnUrl" => $_POST['returnUrl'] ?? '',
            "DoSendEmailAfterCreate" => $doSendEmailAfterCreate,
            "DoSendEmailAfterFill" => $doSendEmailAfterFill,
            "IsPaid" => $isPaid,
            "RequireAuthorization" => $requireAuthorization
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

        $orderCreated = true;
    }
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
            padding: 20px 0;
            overflow-x: hidden;
        }

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

        .container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px;
            margin: 0 auto;
        }

        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }

        input[type="checkbox"] {
            margin-right: 10px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }

        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            padding: 12px 20px;
            font-size: 16px;
            width: 100%;
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
            text-align: center;
        }

        .pickup-code {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
            padding: 15px;
            background-color: #e9f7ef;
            border: 1px solid #28a745;
            border-radius: 5px;
            color: #28a745;
            text-align: center;
        }

        .back-button {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            text-align: center;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .form-col {
            flex: 1;
            padding: 0 10px;
            min-width: 200px;
        }

        .section-title {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin: 30px 0 20px;
            color: #007bff;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .actions .back-button {
            margin: 0;
        }

        .actions button {
            margin: 0;
            width: auto;
        }

        @media (max-width: 768px) {
            .form-col {
                flex: 100%;
            }

            .actions {
                flex-direction: column;
            }

            .actions .back-button,
            .actions button {
                width: 100%;
                margin-bottom: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="ticket">Ticket number: <?= htmlspecialchars($ticketNumber) ?></div>

    <?php if ($orderCreated): ?>
        <h2>Click & Collect Order Created</h2>
        <p>Use the code below to collect your item from the locker:</p>
        <div class="pickup-code"><?= htmlspecialchars($pickupCode) ?></div>
        <p>Selected location: <?= htmlspecialchars($selectedMachine . ' - ' . $selectedMachineName) ?></p>

        <?php if (isset($config['debug']) && $config['debug'] === true): ?>
        <div id="debugConsole" class="debug-panel">
            <button id="toggleDebugConsole" class="debug-panel-toggle">Show/Hide Debug</button>
            <div style="margin-bottom: 10px;">
                <h3 style="margin: 0;">API Request/Response Log</h3>
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

                // Initialize as minimized if preferred
                debugConsole.classList.add('minimized');

                toggleButton.addEventListener('click', function() {
                    debugConsole.classList.toggle('minimized');
                });
            });
        </script>
        <?php endif; ?>

        <a href="../index.php" class="back-button">Back to main menu</a>
    <?php else: ?>
        <div class="form-header">
            <h2>Create Click & Collect Order</h2>
            <div class="ticket">Ticket number: <?= htmlspecialchars($ticketNumber) ?></div>
            <p><strong>Selected Location:</strong> <?= htmlspecialchars($selectedMachine . ' - ' . $selectedMachineName) ?></p>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="vendingmachine" value="<?= htmlspecialchars($selectedMachine) ?>">
            <input type="hidden" name="ticket" value="<?= htmlspecialchars($ticketNumber) ?>">
            <input type="hidden" name="create_order" value="1">

            <h3 class="section-title">Customer Information</h3>
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="firstName">First Name *</label>
                        <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($_POST['firstName'] ?? '') ?>" required>
                        <?php if (isset($formErrors['firstName'])): ?>
                            <div class="error-message"><?= htmlspecialchars($formErrors['firstName']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="lastName">Last Name *</label>
                        <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($_POST['lastName'] ?? '') ?>" required>
                        <?php if (isset($formErrors['lastName'])): ?>
                            <div class="error-message"><?= htmlspecialchars($formErrors['lastName']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <small>Enter a description for this order</small>
            </div>

            <h3 class="section-title">Delivery Details</h3>
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="deliveryDate">Delivery Date</label>
                        <input type="date" id="deliveryDate" name="deliveryDate" value="<?= htmlspecialchars($_POST['deliveryDate'] ?? date('Y-m-d')) ?>">
                        <?php if (isset($formErrors['deliveryDate'])): ?>
                            <div class="error-message"><?= htmlspecialchars($formErrors['deliveryDate']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="deliveryTime">Delivery Time</label>
                        <input type="time" id="deliveryTime" name="deliveryTime" value="<?= htmlspecialchars($_POST['deliveryTime'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <h3 class="section-title">Price Information</h3>
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="price">Total Price</label>
                        <input type="number" id="price" name="price" step="0.01" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                        <?php if (isset($formErrors['price'])): ?>
                            <div class="error-message"><?= htmlspecialchars($formErrors['price']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="priceExclVat">Price Excl. VAT</label>
                        <input type="number" id="priceExclVat" name="priceExclVat" step="0.01" value="<?= htmlspecialchars($_POST['priceExclVat'] ?? '') ?>">
                        <?php if (isset($formErrors['priceExclVat'])): ?>
                            <div class="error-message"><?= htmlspecialchars($formErrors['priceExclVat']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="priceVatPercentage">VAT Percentage</label>
                        <input type="number" id="priceVatPercentage" name="priceVatPercentage" step="0.01" value="<?= htmlspecialchars($_POST['priceVatPercentage'] ?? '') ?>">
                        <?php if (isset($formErrors['priceVatPercentage'])): ?>
                            <div class="error-message"><?= htmlspecialchars($formErrors['priceVatPercentage']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <h3 class="section-title">Additional Settings</h3>
            <div class="form-group">
                <label for="returnUrl">Return URL</label>
                <input type="text" id="returnUrl" name="returnUrl" value="<?= htmlspecialchars($_POST['returnUrl'] ?? '') ?>">
                <small>When added, we will send a webhook when the order is filled and when it is collected with the status.</small>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="isPaid" <?= isset($_POST['isPaid']) ? 'checked' : '' ?>>
                            Prepaid?
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="requireAuthorization" <?= isset($_POST['requireAuthorization']) ? 'checked' : '' ?>>
                            Require age verification?
                        </label>
                    </div>
                </div>
            </div>

            <h3 class="section-title">Email Settings</h3>
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="doSendEmailAfterCreate" name="doSendEmailAfterCreate" <?= isset($_POST['doSendEmailAfterCreate']) ? 'checked' : '' ?>>
                            Send Email After Create
                        </label>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group" id="sendEmailAfterFillGroup" style="display: none;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="doSendEmailAfterFill" <?= isset($_POST['doSendEmailAfterFill']) ? 'checked' : '' ?>>
                            Send Email After Fill
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group" id="emailAddressGroup" style="display: none;">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                <?php if (isset($formErrors['email'])): ?>
                    <div class="error-message"><?= htmlspecialchars($formErrors['email']) ?></div>
                <?php endif; ?>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const emailAfterCreateCheckbox = document.getElementById('doSendEmailAfterCreate');
                    const emailAfterFillGroup = document.getElementById('sendEmailAfterFillGroup');
                    const emailAddressGroup = document.getElementById('emailAddressGroup');
                    const emailAfterFillCheckbox = document.querySelector('input[name="doSendEmailAfterFill"]');

                    // Initial state
                    if (emailAfterCreateCheckbox.checked) {
                        emailAfterFillGroup.style.display = 'block';
                        emailAddressGroup.style.display = 'block';
                        emailAfterFillCheckbox.checked = true;
                    }

                    // Add event listener
                    emailAfterCreateCheckbox.addEventListener('change', function() {
                        if (this.checked) {
                            emailAfterFillGroup.style.display = 'block';
                            emailAddressGroup.style.display = 'block';
                            emailAfterFillCheckbox.checked = true;
                        } else {
                            emailAfterFillGroup.style.display = 'none';
                            emailAddressGroup.style.display = 'none';
                            emailAfterFillCheckbox.checked = false;
                        }
                    });
                });
            </script>

            <div class="actions">
                <a href="pickupdeliveries_start.php" class="back-button">Back to Location Selection</a>
                <button type="submit">Create Order</button>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
