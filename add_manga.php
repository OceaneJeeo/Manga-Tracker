<?php
/**
 * Add or Update Manga Script
 *
 * This script handles adding a new manga or updating an existing one in the database.
 * It processes form data, handles image uploads, and returns JSON responses.
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

try {
    $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;

    $title = trim($_POST['title']);

    $readingLink = trim($_POST['readingLink']);

    $currentChapter = trim($_POST['currentChapter']);

    $status = trim($_POST['status'] ?? 'reading');

    $language = trim($_POST['language'] ?? 'fr');

    $notes = trim($_POST['notes'] ?? '');

    $imageUrl = trim($_POST['imageUrl'] ?? '');

    $imagePath = null;

    // Image upload management
    if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
        /**
         * Allowed MIME types for image uploads.
         * @var array<string>
         */
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['imageFile']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('File type not allowed');
        }

        if ($_FILES['imageFile']['size'] > 5 * 1024 * 1024) {
            throw new Exception('Image too large (max 5MB)');
        }

        $extension = pathinfo($_FILES['imageFile']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $title) . '.' . $extension;
        
        $uploadDir = 'img/manga/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $imagePath = $uploadDir . $filename;
        if (!move_uploaded_file($_FILES['imageFile']['tmp_name'], $imagePath)) {
            throw new Exception('Error during upload');
        }
    }

    // Update or add
    if ($id) {
        // Get the old image if a new one is uploaded
        if ($imagePath) {
            $stmt = $pdo->prepare("SELECT image FROM mangas WHERE id = ?");
            $stmt->execute([$id]);
            $oldImage = $stmt->fetchColumn();
            
            // Delete the old image if it exists and starts with img/
            if ($oldImage && strpos($oldImage, 'img/') === 0 && file_exists($oldImage)) {
                unlink($oldImage);
            }
        }

        $sql = "UPDATE mangas SET title = ?, reading_link = ?, current_chapter = ?, status = ?, notes = ?";
        $params = [$title, $readingLink, $currentChapter, $status, $notes];
        
        if ($imagePath) {
            $sql .= ", image = ?";
            $params[] = $imagePath;
        } elseif ($imageUrl) {
            $sql .= ", image = ?";
            $params[] = $imageUrl;
        }
        
        $sql .= ", date_updated = NOW() WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        // New entry
        $finalImage = $imagePath ?: $imageUrl;
        
        $stmt = $pdo->prepare("
            INSERT INTO mangas (title, image, reading_link, current_chapter, status, language, notes, date_added)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$title, $finalImage, $readingLink, $currentChapter, $status, $language, $notes]);
        $id = $pdo->lastInsertId();
    }

    echo json_encode(['success' => true, 'id' => $id]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>