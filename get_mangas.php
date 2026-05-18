<?php
// --------------------------------------------------------------------------------------------------------------------
// <copyright file="get_mangas.php" company="Jeeo Corporation">
// Copyright (c) Jeeo Corporation. All rights reserved.
// </copyright>
// --------------------------------------------------------------------------------------------------------------------


session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/config/mysql.php';

/**
 * Retrieves the list of manga titles along with their ratings and the number of chapters
 * - Verifies that the user is logged in
 * - Ensures backward compatibility (adds the “rating” column if necessary)
 * - Handles the absence of the `manga_chapters` table in older versions
 */
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

/**
 * Retrieves a list of manga titles along with their ratings and number of chapters.
 *
 * - Automatically adds the `rating` column if it does not exist (soft migration).
 * - Checks for the existence of the `manga_chapters` table.
 * - Returns the manga sorted by date added, with:
 *      - their rating,
 *      - the number of associated chapters (chapter_count).
 *
 * @return void Returns a JSON containing success=true and the list of manga, or success=false in case of an error.
 */
try {
    // Ensure rating column exists (graceful migration)
    try {
        $pdo->query("SELECT rating FROM mangas LIMIT 1");
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE mangas ADD COLUMN rating TINYINT UNSIGNED DEFAULT 0 AFTER notes");
    }

    // Check if manga_chapters table exists
    $chaptersExist = $pdo->query("SHOW TABLES LIKE 'manga_chapters'")->rowCount() > 0;

    if ($chaptersExist) {
        $stmt = $pdo->query("
            SELECT m.*,
                COALESCE(m.rating, 0) AS rating,
                (SELECT COUNT(*) FROM manga_chapters c WHERE c.manga_id = m.id) AS chapter_count
            FROM mangas m
            ORDER BY m.date_added DESC
        ");
    } else {
        $stmt = $pdo->query("SELECT *, COALESCE(rating, 0) AS rating, 0 AS chapter_count FROM mangas ORDER BY date_added DESC");
    }

    $mangas = $stmt->fetchAll();
    echo json_encode(['success' => true, 'mangas' => $mangas]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>