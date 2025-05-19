<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbFile = '/var/www/html/ondemand.db';

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
    
    if ($result) {
        echo "Database tabel succesvol aangemaakt!";
    } else {
        echo "Er is een fout opgetreden bij het aanmaken van de tabel: " . $db->lastErrorMsg();
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>