<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Product Reservering Systeem</title>
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
            width: 900px;
        }

        .feature-row {
            display: flex;
            margin-bottom: 25px;
            gap: 20px;
            align-items: stretch;
        }

        .button-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .explanation-container {
            flex: 1;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .explanation-container h3 {
            margin-top: 0;
            color: #495057;
            font-size: 18px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .explanation-container p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
        }

        .button-group {
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .button-group h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #495057;
            font-size: 20px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }

        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 15px 30px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            margin: 5px;
            width: 80%;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .button.status {
            background-color: #28a745;
        }

        .button.status:hover {
            background-color: #218838;
        }

        .button.admin {
            background-color: #6c757d;
        }

        .button.admin:hover {
            background-color: #5a6268;
        }

        /* Debug Console Styles */
        .debug-panel {
            position: fixed;
            top: 0;
            right: 0;
            width: 20%; /* 1/5 of screen width */
            height: 100vh;
            background-color: #f8f9fa;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
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
</head>
<body>
<div class="container">
    <h1>Innovend Example App</h1>
    <p>This application contains examples on how our API's can be implemented and used. Start by entering you API
        credentials and apiKey in the System menu. </p>

    <div class="feature-row">
        <div class="button-container">
            <div class="button-group">
                <h2>Stock Reservations</h2>
                <a href="stockreservations/reservation_start.php" class="button">Start product reservation</a>
                <a href="stockreservations/reservation_status.php" class="button">Reservation overview</a>
            </div>
        </div>
        <div class="explanation-container">
            <h3>About Stock Reservations</h3>
            <p>Stock Reservations allow you to reserve products from a vending machine before physically visiting it. This ensures the product will be available when you arrive.</p>
            <p>Use "Start product reservation" to begin a new reservation process, or "Reservation overview" to check the status of your existing reservations.</p>
        </div>
    </div>

    <div class="feature-row">
        <div class="button-container">
            <div class="button-group">
                <h2>Click & Collect (pickup deliveries)</h2>
                <a href="pickupdeliveries/pickupdeliveries_start.php" class="button">Create click & collect order</a>
                <a href="pickupdeliveries/pickupdeliveries_overview.php" class="button">Click & Collect overview</a>
                <a href="pickupdeliveries/pickupdelivieries_return.php" class="button">Create return to locker code</a>
            </div>
        </div>
        <div class="explanation-container">
            <h3>About Click & Collect</h3>
            <p>Click & Collect allows you to order products online and pick them up from a locker at your convenience.</p>
            <p>Create a new order, view your existing orders, or generate a return code if you need to return an item to a locker.</p>
        </div>
    </div>

    <div class="feature-row">
        <div class="button-container">
            <div class="button-group">
                <h2>System</h2>
                <a href="conf/config_editor.php" class="button admin">Edit Configuration</a>
            </div>
        </div>
        <div class="explanation-container">
            <h3>System Configuration</h3>
            <p>Configure your API credentials and application settings here.</p>
            <p>You'll need to set up your API key, username, password, and environment before using the other features of this application.</p>
        </div>
    </div>
</div>

<?php
// Get config to check if debug mode is enabled
$config = [];
if (file_exists('conf/config.json')) {
    $config = json_decode(file_get_contents('conf/config.json'), true);
}
?>

<?php if (isset($config['debug']) && $config['debug'] === true): ?>
    <div id="debugConsole" class="debug-panel">
        <div style="margin-bottom: 10px;">
            <h3 style="margin: 0;">Debug Information</h3>
        </div>
        <div id="debugConsoleContent">
            <p>No API calls were made on this page.</p>
            <p>This is the main landing page of the application.</p>
        </div>
    </div>
    <!-- Debug panel is now permanently visible -->
<?php endif; ?>
</body>
</html>
