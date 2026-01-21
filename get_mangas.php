<?php
/**
 * Get Mangas Script
 *
 * This script retrieves all mangas from the database and returns them as JSON.
 * It requires user authentication.
 */

session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/mysql.php';

/**
 * Check authentication.
 * Ensures the user is authenticated before proceeding.
 */
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

/**
* Query to fetch all mangas ordered by date added descending.
*/
try {
    
    $stmt = $pdo->query("SELECT * FROM mangas ORDER BY date_added DESC");
    $mangas = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'mangas' => $mangas]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>