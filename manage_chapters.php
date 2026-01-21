<?php
/**
 * Chapter Management Script
 *
 * This script handles uploading, listing, and deleting manga chapters.
 * It processes form data for chapter uploads, retrieves chapter lists, and removes chapters from the database and filesystem.
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
 * @var string $action The action to perform (upload, list, delete).
 */
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'upload':
            /**
             * Upload a new chapter.
             */
            $mangaId = (int)$_POST['manga_id'];
            $chapterNumber = trim($_POST['chapter_number']);
            
            // Detailed validations
            if (!$mangaId) {
                throw new Exception('Manga ID missing');
            }
            
            if (!$chapterNumber) {
                throw new Exception('Chapter number missing');
            }
            
            if (!isset($_FILES['chapterFile'])) {
                throw new Exception('No file in request');
            }
            
            $fileError = $_FILES['chapterFile']['error'];
            if ($fileError !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File too large (PHP limit)',
                    UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
                    UPLOAD_ERR_PARTIAL => 'File partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Temporary directory missing',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
                    UPLOAD_ERR_EXTENSION => 'PHP extension stopped upload'
                ];
                $errorMsg = $errorMessages[$fileError] ?? "Unknown error ($fileError)";
                throw new Exception("Upload error: $errorMsg");
            }
            
            // Verify it's a ZIP file
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['chapterFile']['tmp_name']);
            finfo_close($finfo);
            
            if ($mimeType !== 'application/zip' && $mimeType !== 'application/x-zip-compressed') {
                throw new Exception("Invalid file type: $mimeType (ZIP required)");
            }
            
            $fileSize = $_FILES['chapterFile']['size'];
            if ($fileSize > 200 * 1024 * 1024) { // Max 200MB
                throw new Exception('File too large (max 200MB)');
            }
            
            // Get manga title
            $stmt = $pdo->prepare("SELECT title FROM mangas WHERE id = ?");
            $stmt->execute([$mangaId]);
            $manga = $stmt->fetch();
            
            if (!$manga) {
                throw new Exception("Manga not found (ID: $mangaId)");
            }
            
            // Clean title for filename
            $cleanTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $manga['title']);
            $cleanChapter = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $chapterNumber);
            
            // Create filename
            $filename = $cleanTitle . '_Chapter_' . $cleanChapter . '.zip';
            $uploadDir = 'archives/chapters/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Cannot create directory: $uploadDir");
                }
            }
            
            // Check permissions
            if (!is_writable($uploadDir)) {
                throw new Exception("Directory $uploadDir is not writable");
            }
            
            $filePath = $uploadDir . $filename;
            
            // Move the file
            if (!move_uploaded_file($_FILES['chapterFile']['tmp_name'], $filePath)) {
                throw new Exception("Failed to move file to: $filePath");
            }
            
            // Save to database
            $stmt = $pdo->prepare("
                INSERT INTO manga_chapters (manga_id, chapter_number, file_path, file_size)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $mangaId,
                $chapterNumber,
                $filePath,
                $fileSize
            ]);
            
            echo json_encode([
                'success' => true,
                'chapter' => [
                    'id' => $pdo->lastInsertId(),
                    'chapter_number' => $chapterNumber,
                    'file_path' => $filePath,
                    'file_size' => $fileSize
                ]
            ]);
            break;
            
        case 'list':
            /**
             * List chapters for a manga.
             */
            $mangaId = (int)$_POST['manga_id'];
            
            $stmt = $pdo->prepare("
                SELECT * FROM manga_chapters 
                WHERE manga_id = ? 
                ORDER BY CAST(chapter_number AS DECIMAL(10,2)) ASC
            ");
            $stmt->execute([$mangaId]);
            $chapters = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'chapters' => $chapters]);
            break;
            
        case 'delete':
            /**
             * Delete a chapter.
             */
            $chapterId = (int)$_POST['chapter_id'];
            
            // Get the file
            $stmt = $pdo->prepare("SELECT file_path FROM manga_chapters WHERE id = ?");
            $stmt->execute([$chapterId]);
            $chapter = $stmt->fetch();
            
            if (!$chapter) {
                throw new Exception('Chapter not found');
            }
            
            // Delete the file
            if (file_exists($chapter['file_path'])) {
                unlink($chapter['file_path']);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM manga_chapters WHERE id = ?");
            $stmt->execute([$chapterId]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            throw new Exception("Invalid action: $action");
    }
    
} catch (Exception $e) {
    // Log the full error
    error_log("Error in manage_chapters.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>