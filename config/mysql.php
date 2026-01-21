<?php

/**
 * MySQL Database Configuration
 *
 * This file establishes a connection to the MySQL database using PDO.
 * It defines the database host, username, password, and database name.
 * If the connection fails, it terminates the script with an error message.
 */

$mysql_host = 'localhost';
$mysql_user = 'root';
$mysql_password = '';
$mysql_dbname = 'manga_collection';

/**
* Creates a PDO instance for database connection.
*
* @var PDO $pdo The PDO object for interacting with the database.
* Configured with:
* - Error mode set to exceptions.
* - Default fetch mode set to associative arrays.
* - Character set to UTF-8MB4.
*/
try {

    $pdo = new PDO(
        "mysql:host=$mysql_host;dbname=$mysql_dbname;charset=utf8mb4",
        $mysql_user,
        $mysql_password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {

    die("Erreur de connexion : " . $e->getMessage());
}
?>