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
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

try {
    // Check if manga_chapters table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'manga_chapters'")->rowCount() > 0;

    if ($tableExists) {
        $stmt = $pdo->query("
            SELECT m.*,
                (SELECT COUNT(*) FROM manga_chapters c WHERE c.manga_id = m.id) AS chapter_count
            FROM mangas m
            ORDER BY m.date_added DESC
        ");
    } else {
        $stmt = $pdo->query("SELECT *, 0 AS chapter_count FROM mangas ORDER BY date_added DESC");
    }

    $mangas = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'mangas' => $mangas]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>