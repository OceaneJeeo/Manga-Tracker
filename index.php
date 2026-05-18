<?php
// --------------------------------------------------------------------------------------------------------------------
// <copyright file="index.php" company="Jeeo Corporation">
// Copyright (c) Jeeo Corporation. All rights reserved.
// </copyright>
// --------------------------------------------------------------------------------------------------------------------

session_start();
header('Content-Type: text/html; charset=UTF-8');
$isAuthenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

/**
 * Simple authentication mechanism using a hardcoded password hash.
 * In a production environment, consider using a more secure method (e.g., database, environment variable).
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $correctPasswordHash = '$2y$10$uG5oKUr.DRYkszW.rGedEevkFaSesFCOiAHs.RS0dyNguEVMMzAe.';
    if (password_verify($_POST['password'], $correctPasswordHash)) {
        $_SESSION['authenticated'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Mot de passe incorrect";
    }
}

/**
 * Handle logout action
 */
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MangaTracker - Ma Collection</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="style/manga.css" rel="stylesheet">
    <meta name="theme-color" content="#0d0f14">
</head>
<body>

<?php if (!$isAuthenticated): ?>
<!-- ═══════════════════ LOGIN PAGE ═══════════════════ -->
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">📚 MangaTracker</div>
        <p class="login-subtitle">Votre collection personnelle sécurisée</p>

        <?php if (isset($error)): ?>
        <div class="login-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" name="password" id="password" required autofocus placeholder="••••••••">
            </div>
            <button type="submit" class="btn-primary">🔓 Connexion</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════════ APP ═══════════════════ -->

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="#" class="nav-logo">📚 MangaTracker</a>

        <!-- Search -->
        <div class="nav-search">
            <span class="nav-search-icon">🔍</span>
            <input type="text" id="navSearch" placeholder="Rechercher un manga…" oninput="handleSearch(this.value)" autocomplete="off">
            <button class="nav-search-clear" id="navSearchClear" onclick="clearSearch()">✕</button>
        </div>

        <div class="nav-spacer"></div>

        <div class="nav-actions">
            <!-- Add button -->
            <button class="nav-btn accent" onclick="openAddModal()">+ Ajouter</button>

            <!-- Import/Export dropdown -->
            <div class="dropdown">
                <button class="nav-btn" onclick="toggleDropdown('menuImportExport')">
                    ⚙️ <span style="display:none" id="menuLabel">Options</span>
                </button>
                <div class="dropdown-menu" id="menuImportExport">
                    <button class="dropdown-item" onclick="exportCollection();toggleDropdown('menuImportExport')">📤 Exporter JSON</button>
                    <button class="dropdown-item" onclick="triggerImport();toggleDropdown('menuImportExport')">📥 Importer JSON</button>
                    <div class="dropdown-divider"></div>
                    <div style="padding:0.4rem 1rem 0;font-size:0.7rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.06em">Thème</div>
                    <div class="theme-options">
                        <div class="theme-dot dark"   data-theme="dark"   onclick="applyTheme('dark')"   title="Sombre"></div>
                        <div class="theme-dot light"  data-theme="light"  onclick="applyTheme('light')"  title="Clair"></div>
                        <div class="theme-dot amoled" data-theme="amoled" onclick="applyTheme('amoled')" title="AMOLED"></div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="?logout" class="dropdown-item danger">🚪 Déconnexion</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- HIDDEN inputs -->
<input type="file" id="importFileInput" accept=".json" style="display:none">

<!-- MAIN APP -->
<div class="app">

    <!-- Hero -->
    <div class="hero-bar">
        <h1 class="hero-title">Ma <span>Collection</span></h1>
        <div class="hero-actions">
            <!-- View toggle -->
            <div class="view-toggle">
                <button class="view-btn" data-view="grid" onclick="applyView('grid')" title="Vue grille">⊞</button>
                <button class="view-btn" data-view="list" onclick="applyView('list')" title="Vue liste">☰</button>
            </div>
            <!-- Sort -->
            <select class="sort-select" id="sortSelect" onchange="setSort(this.value)">
                <option value="date_added_desc">📅 Ajout récent</option>
                <option value="date_added_asc">📅 Ajout ancien</option>
                <option value="date_updated_desc">🔄 Mis à jour</option>
                <option value="title_asc">🔤 Titre A→Z</option>
                <option value="title_desc">🔤 Titre Z→A</option>
                <option value="current_chapter_desc">📖 Ch. ↑</option>
                <option value="rating_desc">⭐ Note ↓</option>
            </select>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card s-total">
            <div class="stat-icon">📚</div>
            <div class="stat-body">
                <div class="stat-val" id="statTotal">0</div>
                <div class="stat-lbl">Total</div>
            </div>
        </div>
        <div class="stat-card s-reading">
            <div class="stat-icon">📖</div>
            <div class="stat-body">
                <div class="stat-val" id="statReading">0</div>
                <div class="stat-lbl">En cours</div>
            </div>
        </div>
        <div class="stat-card s-done">
            <div class="stat-icon">✅</div>
            <div class="stat-body">
                <div class="stat-val" id="statCompleted">0</div>
                <div class="stat-lbl">Terminés</div>
            </div>
        </div>
        <div class="stat-card s-chapters">
            <div class="stat-icon">📦</div>
            <div class="stat-body">
                <div class="stat-val" id="statChapters">0</div>
                <div class="stat-lbl">Chapitres</div>
            </div>
        </div>
        <div class="stat-card s-score">
            <div class="stat-icon">⭐</div>
            <div class="stat-body">
                <div class="stat-val" id="statRating">—</div>
                <div class="stat-lbl">Note moy.</div>
            </div>
        </div>
    </div>

    <!-- Toolbar: Filters -->
    <div class="toolbar">
        <div class="toolbar-left">
            <!-- Status filters -->
            <button class="filter-pill fp-status active" data-status="all"       onclick="setFilterStatus('all')">📚 Tous</button>
            <button class="filter-pill fp-status"        data-status="reading"   onclick="setFilterStatus('reading')">📖 En cours</button>
            <button class="filter-pill fp-status"        data-status="completed" onclick="setFilterStatus('completed')">✅ Terminés</button>
        </div>
    </div>

    <!-- Language filters (built dynamically) -->
    <div class="toolbar" style="margin-top:-0.75rem">
        <div class="toolbar-left" id="langFilters" style="flex-wrap:wrap"></div>
    </div>

    <!-- Search info -->
    <div id="searchInfo" class="search-info" style="display:none"></div>

    <!-- Reading section -->
    <div class="section-block" id="readingBlock">
        <div class="section-head">
            <div class="section-title">📖 En cours de lecture</div>
            <span class="section-count" id="countReading">0</span>
        </div>
        <div id="gridReading" class="manga-grid"></div>
    </div>

    <!-- Completed section -->
    <div class="section-block" id="completedBlock">
        <div class="section-head">
            <div class="section-title">✅ Terminés</div>
            <span class="section-count" id="countCompleted">0</span>
        </div>
        <div id="gridCompleted" class="manga-grid"></div>
    </div>

    <!-- Empty state -->
    <div id="emptyState" class="empty" style="display:none">
        <div class="empty-icon">📖</div>
        <h3>Aucun manga dans la collection</h3>
        <p>Commencez par ajouter votre premier manga</p>
    </div>

</div><!-- .app -->

<!-- Toast container -->
<div id="toastContainer"></div>

<!-- ═══════════════════════════════════════════
     MODAL: Add / Edit Manga
═══════════════════════════════════════════ -->
<div class="modal-backdrop" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="formTitle">Ajouter un manga</h2>
            <button class="modal-close" onclick="closeModal('addModal')">×</button>
        </div>
        <div class="modal-body">
            <form id="mangaForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="mangaId">

                <!-- Tabs -->
                <div class="tab-row">
                    <button type="button" class="tab-btn active" data-tab="t-info"    onclick="switchTab('t-info',this)">📋 Infos</button>
                    <button type="button" class="tab-btn"        data-tab="t-image"   onclick="switchTab('t-image',this)">🖼️ Image</button>
                    <button type="button" class="tab-btn"        data-tab="t-notes"   onclick="switchTab('t-notes',this)">📝 Notes</button>
                </div>

                <!-- Tab: Infos -->
                <div class="tab-panel active" id="t-info">
                    <div class="form-field">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="title" id="formMangaTitle" class="form-input" required placeholder="One Piece, Naruto…">
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-label">Chapitre / Volume *</label>
                            <input type="text" name="currentChapter" id="formChapter" class="form-input" required placeholder="Chap. 1, Vol. 5…">
                        </div>
                        <div class="form-field">
                            <label class="form-label">Statut *</label>
                            <select name="status" id="formStatus" class="form-select" required>
                                <option value="reading">📖 En cours</option>
                                <option value="completed">✅ Terminé</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-label">Langue de lecture *</label>
                            <select name="language" id="formLang" class="form-select" required>
                                <option value="fr">🇫🇷 Français</option>
                                <option value="en">🇬🇧 English</option>
                                <option value="ja">🇯🇵 日本語</option>
                                <option value="es">🇪🇸 Español</option>
                                <option value="de">🇩🇪 Deutsch</option>
                                <option value="it">🇮🇹 Italiano</option>
                                <option value="pt">🇵🇹 Português</option>
                                <option value="ko">🇰🇷 한국어</option>
                                <option value="zh">🇨🇳 中文</option>
                                <option value="other">🌐 Autre</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label class="form-label">Note personnelle</label>
                            <div class="star-input" id="starInput"></div>
                            <input type="hidden" name="rating" id="formRating">
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Lien de lecture *</label>
                        <input type="url" name="readingLink" id="formReadingLink" class="form-input" required placeholder="https://…">
                    </div>
                </div>

                <!-- Tab: Image -->
                <div class="tab-panel" id="t-image">
                    <div class="form-field">
                        <label class="form-label">Fichier image (max 5 MB)</label>
                        <input type="file" name="imageFile" id="formImageFile" class="form-input" accept="image/*">
                        <p class="form-hint">JPEG, PNG, GIF, WebP — ou coller une URL ci-dessous</p>
                    </div>
                    <div class="form-field">
                        <label class="form-label">URL de l'image</label>
                        <input type="url" name="imageUrl" id="formImageUrl" class="form-input" placeholder="https://…">
                    </div>
                    <div class="img-preview-wrap" id="imgPreview">
                        <div class="img-preview-placeholder">📷</div>
                    </div>
                </div>

                <!-- Tab: Notes -->
                <div class="tab-panel" id="t-notes">
                    <div class="form-field">
                        <label class="form-label">Notes personnelles</label>
                        <textarea name="notes" id="formNotes" class="form-textarea" placeholder="Avis, arcs favoris, là où je me suis arrêté…" rows="6"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="submit" form="mangaForm" id="btnSaveManga" class="btn btn-accent">Sauvegarder</button>
            <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Annuler</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL: Detail View
═══════════════════════════════════════════ -->
<div class="modal-backdrop" id="detailModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h2>Détails</h2>
            <button class="modal-close" onclick="closeModal('detailModal')">×</button>
        </div>
        <div class="modal-body" id="detailBody"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL: Chapters
═══════════════════════════════════════════ -->
<div class="modal-backdrop" id="chaptersModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h2>📦 Chapitres — <span id="chaptersMangaTitle" style="color:var(--accent2)"></span></h2>
            <button class="modal-close" onclick="closeChaptersModal()">×</button>
        </div>
        <div class="modal-body">

            <!-- Upload zone -->
            <div class="ch-upload-zone">
                <strong style="display:block;margin-bottom:1rem;font-size:0.95rem">Ajouter un chapitre</strong>
                <form id="chapterUploadForm" enctype="multipart/form-data">
                    <input type="hidden" id="chapterMangaId" name="manga_id">
                    <input type="hidden" name="action" value="upload">
                    <div class="ch-upload-grid">
                        <div class="form-field">
                            <label class="form-label">Chapitre # *</label>
                            <input type="text" id="chapterNumber" name="chapter_number" class="form-input" placeholder="1, 2.5, 10…" required>
                        </div>
                        <div class="form-field">
                            <label class="form-label">Fichier ZIP *</label>
                            <input type="file" id="chapterFile" name="chapterFile" class="form-input" accept=".zip" required>
                        </div>
                    </div>
                    <!-- Progress bar -->
                    <div class="progress-bar-wrap" id="uploadProgress" style="display:none;margin-bottom:0.75rem">
                        <div class="progress-bar" id="uploadProgressBar"></div>
                    </div>
                    <button type="submit" class="btn btn-accent" style="width:100%">📤 Uploader le chapitre</button>
                </form>
            </div>

            <!-- List -->
            <strong style="display:block;margin-bottom:0.75rem;font-size:0.95rem">Chapitres disponibles</strong>
            <div id="chaptersList" class="chapters-list"></div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     MODAL: Delete Confirm
═══════════════════════════════════════════ -->
<div class="modal-backdrop" id="deleteModal">
    <div class="modal modal-sm">
        <div class="modal-body" style="padding-top:2rem">
            <div class="del-popup-icon">🗑️</div>
            <div class="del-popup-title">Confirmer la suppression</div>
            <p class="del-popup-msg">Supprimer définitivement<br>"<strong id="deleteItemName"></strong>" ?</p>
            <p class="del-popup-warn">⚠️ Cette action est irréversible.</p>
        </div>
        <div class="modal-footer">
            <button id="btnConfirmDelete" class="btn btn-danger" onclick="executeDelete()">✔️ Supprimer</button>
            <button class="btn btn-ghost" onclick="closeDeleteModal()">✖️ Annuler</button>
        </div>
    </div>
</div>

<!-- Tab switch helper -->
<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}
</script>

<script src="js/manga.js"></script>

<?php endif; ?>
</body>
</html>