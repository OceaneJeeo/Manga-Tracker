<?php
/**
 * Main Page Script
 *
 * This script handles user authentication and displays the main manga collection interface.
 * It includes login/logout functionality and renders the HTML for the application.
 */

session_start();
header('Content-Type: text/html; charset=UTF-8');

/**
 * @var bool $isAuthenticated Whether the user is authenticated.
 */
$isAuthenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $correctPasswordHash = '$2y$10$uG5oKUr.DRYkszW.rGedEevkFaSesFCOiAHs.RS0dyNguEVMMzAe.';
    
    if (password_verify($_POST['password'], $correctPasswordHash)) {
        $_SESSION['authenticated'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Incorrect password";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Manga Collection</title>
    <link href="style/manga.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <?php if (!$isAuthenticated): ?>
        <!-- Login Page -->
        <div class="login-container">
            <div class="login-box">
                <div class="logo">📚 MangaTracker</div>
                <h2>Secure Access</h2>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        ❌ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required autofocus>
                    </div>
                    <button type="submit" class="btn-login">🔓 Sign In</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Main Application -->
        <nav class="navbar">
            <div class="navbar-content">
                <div class="logo">📚 MangaTracker</div>
                <div class="navbar-actions">
                    <span class="user-info">My personal collection</span>
                    <a href="?logout" class="btn-logout">🚪 Logout</a>
                </div>
            </div>
        </nav>

        <div class="container">
            <div class="header-section">
                <h1 class="page-title">My Collection</h1>
                <button class="add-btn" onclick="openModal()">
                    <span>+</span> Add a manga
                </button>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar" id="statsBar">
                <div class="stat-item">
                    <div class="stat-label">Total Manga</div>
                    <div class="stat-value" id="totalMangas">0</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Reading</div>
                    <div class="stat-value" id="totalReading" style="color: #10b981;">0</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value" id="totalCompleted" style="color: #6366f1;">0</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Last Update</div>
                    <div class="stat-value" id="lastUpdate" style="font-size: 1rem;">-</div>
                </div>
            </div>

            <!-- Reading Mangas Section -->
            <div class="section-header">
                <h2 class="section-title">📖 Currently Reading</h2>
                <span class="section-count" id="countReading">0</span>
            </div>
            <div id="mangaGridReading" class="manga-grid"></div>

            <!-- Completed Mangas Section -->
            <div class="section-header" style="margin-top: 3rem;">
                <h2 class="section-title">✅ Completed Manga</h2>
                <span class="section-count" id="countCompleted">0</span>
            </div>
            <div id="mangaGridCompleted" class="manga-grid"></div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state">
                <div class="empty-state-icon">📖</div>
                <p>No manga in your collection</p>
                <p style="font-size: 0.875rem;">Start by adding your first manga</p>
            </div>
        </div>

        <!-- Modal for adding/editing manga -->
        <div id="modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Add a manga</h2>
                    <button class="close-btn" onclick="closeModal()">×</button>
                </div>
                <form id="mangaForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="mangaId">
                    
                    <div class="form-group">
                        <label for="title">Manga Title *</label>
                        <input type="text" name="title" id="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="imageFile">Cover Image</label>
                        <input type="file" name="imageFile" id="imageFile" accept="image/*">
                        <small style="color: #9ca3af;">or enter a URL below</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="imageUrl">Image URL (optional)</label>
                        <input type="url" name="imageUrl" id="imageUrl">
                    </div>
                    
                    <div class="form-group">
                        <label for="readingLink">Reading Link *</label>
                        <input type="url" name="readingLink" id="readingLink" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="currentChapter">Current Chapter/Volume *</label>
                        <input type="text" name="currentChapter" id="currentChapter" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select name="status" id="status" required>
                            <option value="reading">📖 Currently Reading</option>
                            <option value="completed">✅ Completed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="language">Langue de lecture *</label>
                        <select name="language" id="language" required>
                            <option value="fr">🇫🇷 Français</option>
                            <option value="en">🇬🇧 Anglais</option>
                            <option value="ja">🇯🇵 Japonais</option>
                            <option value="es">🇪🇸 Espagnol</option>
                            <option value="de">🇩🇪 Allemand</option>
                            <option value="it">🇮🇹 Italien</option>
                            <option value="pt">🇵🇹 Portugais</option>
                            <option value="ko">🇰🇷 Coréen</option>
                            <option value="zh">🇨🇳 Chinois</option>
                            <option value="other">🌐 Autre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Personal Notes</label>
                        <textarea name="notes" id="notes"></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-save">Save</button>
                        <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal for managing chapters -->
        <div id="chaptersModal" class="modal">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>📦 Manage Chapters - <span id="chaptersMangaTitle"></span></h2>
                    <button class="close-btn" onclick="closeChaptersModal()">×</button>
                </div>
                
                <div class="chapters-upload-section">
                    <h3>Add a Chapter</h3>
                    <form id="chapterUploadForm" enctype="multipart/form-data">
                        <input type="hidden" id="chapterMangaId" name="manga_id">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="chapter-upload-grid">
                            <div class="form-group">
                                <label for="chapterNumber">Chapter # *</label>
                                <input type="text" id="chapterNumber" name="chapter_number" placeholder="e.g. 1, 2.5, 10..." required>
                            </div>
                            
                            <div class="form-group">
                                <label for="chapterFile">ZIP File *</label>
                                <input type="file" id="chapterFile" name="chapterFile" accept=".zip" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-save">📤 Upload Chapter</button>
                    </form>
                </div>
                
                <div class="chapters-list-section">
                    <h3>Available Chapters</h3>
                    <div id="chaptersList" class="chapters-list">
                        <p style="color: #9ca3af; text-align: center; padding: 2rem;">Loading...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Popup -->
        <div id="deletePopup" class="delete-popup">
            <div class="delete-popup-content">
                <div class="delete-popup-icon">🗑️</div>
                <h3>Confirm Deletion</h3>
                <p class="delete-popup-message">
                    Are you sure you want to delete<br>
                    "<span id="deleteItemName"></span>"?
                </p>
                <p class="delete-popup-warning">⚠️ This action is irreversible.</p>
                <div class="delete-popup-buttons">
                    <button class="btn-cancel" onclick="closeDeletePopup()">✖️ Cancel</button>
                    <button class="btn-confirm" onclick="executeDelete()">✔️ Delete</button>
                </div>
            </div>
        </div>

        <script src="js/manga.js"></script>
    <?php endif; ?>
</body>
</html>