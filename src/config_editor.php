<?php
// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process form data
    $apiKey = $_POST['apiKey'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $debug = isset($_POST['debug']) ? true : false;

    // Create config array
    $config = [
        'apiKey' => $apiKey,
        'username' => $username,
        'password' => $password,
        'debug' => $debug
    ];

    // Convert to JSON and save to file
    $jsonConfig = json_encode($config, JSON_PRETTY_PRINT);

    // Check if we can write to the file
    if (!is_writable('config.json') && file_exists('config.json')) {
        $message = 'Error: config.json is not writable! To fix this issue:

If running directly on Windows:
- Right-click the file, select Properties > Security > Edit
- Add the web server user (typically IUSR or the specific application pool identity)
- Grant Write permission

If running in Docker:
- On Windows host: chmod 666 src/config.json (using Git Bash or WSL)
- On Linux/Mac host: chmod 666 src/config.json
- Or make the file owned by the www-data user: chown www-data:www-data src/config.json';
        $result = false;
    } else {
        $result = file_put_contents('config.json', $jsonConfig);
        // Set message based on result
        $message = $result ? 'Configuration saved successfully!' : 'Error saving configuration!';
    }
}

// Load current config
$config = json_decode(file_get_contents('config.json'), true);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Config Editor</title>
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
            width: 400px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"], 
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
        }

        .checkbox-group label {
            margin-left: 10px;
            font-weight: normal;
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
            margin: 10px;
            width: 80%;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .button.secondary {
            background-color: #6c757d;
        }

        .button.secondary:hover {
            background-color: #5a6268;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .json-preview {
            text-align: left;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            overflow-wrap: break-word;
            margin-top: 20px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Config Editor</h1>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $result ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="config_editor.php">
            <div class="form-group">
                <label for="apiKey">API Key:</label>
                <input type="text" id="apiKey" name="apiKey" value="<?php echo htmlspecialchars($config['apiKey']); ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($config['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($config['password']); ?>" required>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="debug" name="debug" <?php echo $config['debug'] ? 'checked' : ''; ?>>
                <label for="debug">Enable Debug Mode</label>
            </div>

            <div class="json-preview">
                <h3>Current Configuration:</h3>
                <pre><?php echo htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT)); ?></pre>
            </div>

            <button type="submit" class="button">Save Configuration</button>
            <a href="index.php" class="button secondary">Back to Home</a>
        </form>
    </div>
</body>
</html>
