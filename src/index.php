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
        <h1>Innovend Example App</h1>
        <p>This application contains examples on how our API's can be implemented and used.</p>
        <a href="reservation_start.php" class="button">Start product reservation</a>
        <a href="reservation_status.php" class="button">Reservation overview</a>
        <a href="ondemand.php" class="button">Show On Demand Asset Usage</a>
        <a href="pickupdelivieries_return.php" class="button">Create return to locker code</a>
        <a href="config_editor.php" class="button" style="background-color: #6c757d;">Edit Configuration</a>
    </div>
</body>
</html>
