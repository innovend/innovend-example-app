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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            width: 500px;
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
        <h1>Innovend Example App</h1>
        <p>This application contains examples on how our API's can be implemented and used.</p>

        <div class="button-group">
            <h2>Stock Reservations</h2>
            <a href="stockreservations/reservation_start.php" class="button">Start product reservation</a>
            <a href="stockreservations/reservation_status.php" class="button">Reservation overview</a>
        </div>

        <div class="button-group">
            <h2>Cick & Collect (pickup deliveries)</h2>
            <a href="pickupdeliveries/pickupdeliveries_start.php" class="button">Create click & collect order</a>
            <a href="pickupdeliveries/pickupdeliveries_overview.php" class="button">Click & Collect overview</a>
            <a href="pickupdeliveries/pickupdelivieries_return.php" class="button">Create return to locker code</a>
        </div>

        <div class="button-group">
            <h2>Webhook receivers</h2>
            <a href="ondemand.php" class="button">Show received webhook sales data</a>
        </div>

        <div class="button-group">
            <h2>System</h2>
            <a href="conf/config_editor.php" class="button admin">Edit Configuration</a>
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
        <button id="toggleDebugConsole" class="debug-panel-toggle">Show/Hide Debug</button>
        <div style="margin-bottom: 10px;">
            <h3 style="margin: 0;">Debug Information</h3>
        </div>
        <div id="debugConsoleContent">
            <p>No API calls were made on this page.</p>
            <p>This is the main landing page of the application.</p>
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
