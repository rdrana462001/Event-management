<?php
/**
 * Database Connection Configuration
 * Supports Clever Cloud MySQL (Environment Variables & Direct Credentials)
 */

// Clever Cloud provides environment variables automatically when deployed.
// You can also replace the default values below with your Clever Cloud MySQL credentials.
$host     = getenv('MYSQL_ADDON_HOST')     ?: 'your-clever-cloud-host.services.clever-cloud.com';
$user     = getenv('MYSQL_ADDON_USER')     ?: 'your-clever-cloud-username';
$password = getenv('MYSQL_ADDON_PASSWORD') ?: 'your-clever-cloud-password';
$database = getenv('MYSQL_ADDON_DB')       ?: 'your-clever-cloud-database-name';
$port     = (int)(getenv('MYSQL_ADDON_PORT') ?: 3306);

// Establish database connection
$conn = mysqli_connect($host, $user, $password, $database, $port);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Set character set to utf8mb4
mysqli_set_charset($conn, "utf8mb4");
?>

