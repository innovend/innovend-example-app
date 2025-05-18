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
            width: 400px;
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

        .button.status {
            background-color: #28a745;
        }

        .button.status:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ITSM Demo application</h1>
        <p>This application demonstrates automated flows as they can be implemented in </p>
        <a href="start.php" class="button">Start asset reservation</a>
        <a href="status.php" class="button status">Asset reservation overview</a>
        <a href="ondemand.php" class="button">Show On Demand Asset Usage</a>
    </div>
</body>
</html>