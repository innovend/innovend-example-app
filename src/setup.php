<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbFile = '/var/www/html/ondemand.db';
$message = '';
$error = '';

try {
    $db = new SQLite3($dbFile);

    // Maak de transactions tabel aan
    $query = "CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        machineId TEXT NOT NULL,
        machineName TEXT NOT NULL,
        firstName TEXT NOT NULL,
        middleName TEXT,
        lastName TEXT NOT NULL,
        badgeCode TEXT NOT NULL,
        products TEXT NOT NULL,
        received_at DATETIME NOT NULL
    )";

    $result = $db->exec($query);

    if ($result !== false) {
        $message = "Database tabel succesvol aangemaakt!";
    } else {
        $error = "Er is een fout opgetreden bij het aanmaken van de tabel: " . $db->lastErrorMsg();
    }

} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Get config to check if debug mode is enabled
$config = [];
if (file_exists('conf/config.json')) {
    $config = json_decode(file_get_contents('conf/config.json'), true);
}

// Prepare debug information
$debugInfo = [
    'dbFile' => $dbFile,
    'query' => $query,
    'result' => isset($result) ? ($result !== false ? 'Success' : 'Failed') : 'Not executed',
    'error' => $error
];
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Setup Database</title>
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
            max-width: 800px;
            margin: 0 auto;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
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
        <a href="index.php" class="back-button">Back to main menu</a>
        <h1>Setup Database</h1>

        <?php if ($message): ?>
            <p class="success"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>

    <?php if (isset($config['debug']) && $config['debug'] === true): ?>
    <div id="debugConsole" class="debug-panel">
        <button id="toggleDebugConsole" class="debug-panel-toggle">Show/Hide Debug</button>
        <div style="margin-bottom: 10px;">
            <h3 style="margin: 0;">Debug Information</h3>
        </div>
        <div id="debugConsoleContent">
            <p><strong>Database File:</strong> <?= htmlspecialchars($debugInfo['dbFile']) ?></p>
            <p><strong>SQL Query:</strong><br><pre><?= htmlspecialchars($debugInfo['query']) ?></pre></p>
            <p><strong>Query Result:</strong> <?= htmlspecialchars($debugInfo['result']) ?></p>
            <?php if ($debugInfo['error']): ?>
                <p><strong>Error:</strong> <?= htmlspecialchars($debugInfo['error']) ?></p>
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
