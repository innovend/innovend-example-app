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
</body>
</html>
