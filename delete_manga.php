<?php
// --------------------------------------------------------------------------------------------------------------------
// <copyright file="delete_manga.php" company="Jeeo Corporation">
// Copyright (c) Jeeo Corporation. All rights reserved.
// </copyright>
// --------------------------------------------------------------------------------------------------------------------

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
 * Handle manga deletion.
 * Deletes the manga record from the database and removes the associated image if it's stored locally.
 */
try {
    /**
     * @var int $id The ID of the manga to delete.
     */
    $id = (int)$_POST['id'];
    
    // Get the image before deletion
    $stmt = $pdo->prepare("SELECT image FROM mangas WHERE id = ?");
    $stmt->execute([$id]);
    $manga = $stmt->fetch();
    
    if (!$manga) {
        throw new Exception('Manga not found');
    }
    
    // Delete the image if it's local (starts with img/)
    if ($manga['image'] && strpos($manga['image'], 'img/') === 0 && file_exists($manga['image'])) {
        unlink($manga['image']);
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM mangas WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>