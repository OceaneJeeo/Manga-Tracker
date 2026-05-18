<?php
// --------------------------------------------------------------------------------------------------------------------
// <copyright file="add_manga.php" company="Jeeo Corporation">
// Copyright (c) Jeeo Corporation. All rights reserved.
// </copyright>
// --------------------------------------------------------------------------------------------------------------------

/*
 * Expected POST parameters:
 * - id (optional): If provided, updates the existing manga. Otherwise, creates a new one.
 * - title (required): The title of the manga.
 * - readingLink (required): The link to read the manga.
 * - currentChapter (required): The current chapter being read.
 * - status (optional): The reading status (default: "reading").
 * - language (optional): The language of the manga (default: "fr").
 * - notes (optional): Additional notes about the manga.
 * - imageUrl (optional): URL of the manga cover image.
 * - rating (optional): User rating for the manga (0-5).
 * - imageFile (optional): Uploaded image file for the manga cover.
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/config/mysql.php';

/**
 * Authentication check
 * Ensure the user is authenticated before allowing manga management.
 */
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

/**
 * Input validation and processing
 * Validates required fields and handles image upload if provided.
 * Also ensures the rating is within the allowed range (0-5).
 */
try {
    $id             = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $title          = trim($_POST['title'] ?? '');
    $readingLink    = trim($_POST['readingLink'] ?? '');
    $currentChapter = trim($_POST['currentChapter'] ?? '');
    $status         = trim($_POST['status'] ?? 'reading');
    $language       = trim($_POST['language'] ?? 'fr');
    $notes          = trim($_POST['notes'] ?? '');
    $imageUrl       = trim($_POST['imageUrl'] ?? '');
    $rating         = isset($_POST['rating']) && is_numeric($_POST['rating']) ? max(0, min(5, (int)$_POST['rating'])) : 0;
    $imagePath      = null;

    if (!$title)          throw new Exception('Le titre est requis');
    if (!$readingLink)    throw new Exception('Le lien de lecture est requis');
    if (!$currentChapter) throw new Exception('Le chapitre actuel est requis');

    // Image upload
    if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType  = finfo_file($finfo, $_FILES['imageFile']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé');
        }

        if ($_FILES['imageFile']['size'] > 5 * 1024 * 1024) {
            throw new Exception('Image trop grande (max 5 Mo)');
        }

        $ext      = strtolower(pathinfo($_FILES['imageFile']['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $title) . '.' . $ext;
        $uploadDir = 'img/manga/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $imagePath = $uploadDir . $filename;
        if (!move_uploaded_file($_FILES['imageFile']['tmp_name'], $imagePath)) {
            throw new Exception('Erreur lors du déplacement du fichier');
        }
    }

    // Ensure rating column exists (graceful migration)
    try {
        $pdo->query("SELECT rating FROM mangas LIMIT 1");
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE mangas ADD COLUMN rating TINYINT UNSIGNED DEFAULT 0 AFTER notes");
    }

    if ($id) {
        // Delete old image if replaced
        if ($imagePath) {
            $stmt = $pdo->prepare("SELECT image FROM mangas WHERE id = ?");
            $stmt->execute([$id]);
            $oldImage = $stmt->fetchColumn();
            if ($oldImage && strpos($oldImage, 'img/') === 0 && file_exists($oldImage)) {
                unlink($oldImage);
            }
        }

        $sql    = "UPDATE mangas SET title=?, reading_link=?, current_chapter=?, status=?, language=?, notes=?, rating=?";
        $params = [$title, $readingLink, $currentChapter, $status, $language, $notes, $rating];

        if ($imagePath) {
            $sql .= ", image=?";
            $params[] = $imagePath;
        } elseif ($imageUrl) {
            $sql .= ", image=?";
            $params[] = $imageUrl;
        }

        $sql .= ", date_updated=NOW() WHERE id=?";
        $params[] = $id;

        $pdo->prepare($sql)->execute($params);
    } else {
        $finalImage = $imagePath ?: $imageUrl;
        $stmt = $pdo->prepare("
            INSERT INTO mangas (title, image, reading_link, current_chapter, status, language, notes, rating, date_added)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$title, $finalImage, $readingLink, $currentChapter, $status, $language, $notes, $rating]);
        $id = $pdo->lastInsertId();
    }

    echo json_encode(['success' => true, 'id' => $id]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>