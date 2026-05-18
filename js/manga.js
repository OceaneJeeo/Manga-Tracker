// --------------------------------------------------------------------------------------------------------------------
// <copyright file="manga.js" company="Jeeo Corporation">
// Copyright (c) Jeeo Corporation. All rights reserved.
// </copyright>
// --------------------------------------------------------------------------------------------------------------------

/**
 * MangaTracker v2.0 — Client-Side Module
 * Features: grid/list view, sort/filter, ratings, detail modal,
 *           quick +1, import/export, toast notifications, themes
 */

'use strict';

// ── Constants ─────────────────────────────────────────────────────────────────

const LANG_FLAGS = {
    fr:'🇫🇷', en:'🇬🇧', ja:'🇯🇵', es:'🇪🇸', de:'🇩🇪',
    it:'🇮🇹', pt:'🇵🇹', ko:'🇰🇷', zh:'🇨🇳', other:'🌐'
};

const LANG_NAMES = {
    fr:'Français', en:'English', ja:'日本語', es:'Español', de:'Deutsch',
    it:'Italiano', pt:'Português', ko:'한국어', zh:'中文', other:'Other'
};

// ── State ─────────────────────────────────────────────────────────────────────

let mangas        = [];
let view          = localStorage.getItem('mt_view') || 'grid';
let sortBy        = localStorage.getItem('mt_sort') || 'date_added';
let sortDir       = localStorage.getItem('mt_sort_dir') || 'desc';
let filterStatus  = 'all';
let filterLang    = 'all';
let searchQuery   = '';
let pendingDeleteId = null;
let currentChaptersMangaId = null;
let editingRating = 0;

// ── Init ──────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    applyView(view);
    document.getElementById('sortSelect').value = sortBy + '_' + sortDir;
    loadMangas();
    bindGlobalEvents();
});

// ════════════════════════════════════════
// THEME
// ════════════════════════════════════════

function initTheme() {
    const saved = localStorage.getItem('mt_theme') || 'dark';
    applyTheme(saved);
}

function applyTheme(theme) {
    document.body.className = '';
    if (theme !== 'dark') document.body.classList.add(theme);
    localStorage.setItem('mt_theme', theme);
    document.querySelectorAll('.theme-dot').forEach(d => {
        d.classList.toggle('active', d.dataset.theme === theme);
    });
    // Update toggle icon in navbar
    const ico = document.getElementById('themeIcon');
    if (ico) ico.textContent = theme === 'light' ? '🌙' : theme === 'amoled' ? '⚫' : '☀️';
}

// ════════════════════════════════════════
// DATA LOADING
// ════════════════════════════════════════

async function loadMangas() {
    try {
        const res  = await fetch('get_mangas.php');
        const data = await res.json();
        if (data.success) {
            mangas = data.mangas;
            renderAll();
            updateStats();
        } else {
            toast('Erreur de chargement : ' + data.error, 'error');
        }
    } catch (e) {
        toast('Impossible de charger la collection', 'error');
    }
}

// ════════════════════════════════════════
// FILTERING & SORTING
// ════════════════════════════════════════

function getFiltered() {
    let list = [...mangas];

    // Status filter
    if (filterStatus !== 'all') list = list.filter(m => m.status === filterStatus);

    // Language filter
    if (filterLang !== 'all') list = list.filter(m => (m.language || 'fr') === filterLang);

    // Search
    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        list = list.filter(m =>
            m.title.toLowerCase().includes(q) ||
            (m.notes && m.notes.toLowerCase().includes(q)) ||
            m.current_chapter.toLowerCase().includes(q)
        );
    }

    // Sort
    list.sort((a, b) => {
        let va, vb;
        switch (sortBy) {
            case 'title':
                va = a.title.toLowerCase(); vb = b.title.toLowerCase();
                break;
            case 'date_updated':
                va = new Date(a.date_updated || a.date_added);
                vb = new Date(b.date_updated || b.date_added);
                break;
            case 'current_chapter':
                va = parseFloat(a.current_chapter) || 0;
                vb = parseFloat(b.current_chapter) || 0;
                break;
            case 'rating':
                va = parseInt(a.rating) || 0;
                vb = parseInt(b.rating) || 0;
                break;
            default: // date_added
                va = new Date(a.date_added);
                vb = new Date(b.date_added);
        }
        if (va < vb) return sortDir === 'asc' ? -1 : 1;
        if (va > vb) return sortDir === 'asc' ? 1 : -1;
        return 0;
    });

    return list;
}

function setSort(val) {
    const parts = val.split('_');
    sortDir = parts.pop();
    sortBy  = parts.join('_');
    localStorage.setItem('mt_sort', sortBy);
    localStorage.setItem('mt_sort_dir', sortDir);
    renderAll();
}

function setFilterStatus(status) {
    filterStatus = status;
    document.querySelectorAll('.fp-status').forEach(el => {
        el.classList.toggle('active', el.dataset.status === status);
    });
    renderAll();
}

function setFilterLang(lang) {
    filterLang = lang;
    document.querySelectorAll('.fp-lang').forEach(el => {
        el.classList.toggle('active', el.dataset.lang === lang);
    });
    renderAll();
}

function handleSearch(q) {
    searchQuery = q.trim();
    const clear = document.getElementById('navSearchClear');
    if (clear) clear.style.display = searchQuery ? 'block' : 'none';

    const info = document.getElementById('searchInfo');
    if (!searchQuery) {
        if (info) info.style.display = 'none';
        renderAll();
        return;
    }

    const filtered = getFiltered();
    if (info) {
        info.style.display = 'block';
        info.textContent = filtered.length === 0
            ? `Aucun résultat pour "${q}"`
            : `${filtered.length} manga${filtered.length > 1 ? 's' : ''} trouvé${filtered.length > 1 ? 's' : ''} pour "${q}"`;
    }
    renderAll();
}

function clearSearch() {
    const inp = document.getElementById('navSearch');
    if (inp) inp.value = '';
    handleSearch('');
    if (inp) inp.focus();
}

// ════════════════════════════════════════
// RENDERING
// ════════════════════════════════════════

function renderAll() {
    const filtered  = getFiltered();
    const reading   = filtered.filter(m => m.status === 'reading');
    const completed = filtered.filter(m => m.status === 'completed');

    const showRead = filterStatus === 'all' || filterStatus === 'reading';
    const showComp = filterStatus === 'all' || filterStatus === 'completed';

    const readBlock = document.getElementById('readingBlock');
    const compBlock = document.getElementById('completedBlock');
    const empty     = document.getElementById('emptyState');

    if (filtered.length === 0) {
        readBlock.style.display = 'none';
        compBlock.style.display = 'none';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    if (showRead && reading.length > 0) {
        readBlock.style.display = 'block';
        document.getElementById('countReading').textContent = reading.length;
        renderGrid(document.getElementById('gridReading'), reading);
    } else {
        readBlock.style.display = 'none';
    }

    if (showComp && completed.length > 0) {
        compBlock.style.display = 'block';
        document.getElementById('countCompleted').textContent = completed.length;
        renderGrid(document.getElementById('gridCompleted'), completed);
    } else {
        compBlock.style.display = 'none';
    }
}

function renderGrid(container, list) {
    if (view === 'list') {
        container.className = 'manga-list';
        container.innerHTML = list.map(renderRow).join('');
    } else {
        container.className = 'manga-grid';
        container.innerHTML = list.map(renderCard).join('');
    }
}

function renderCard(m) {
    const flag    = LANG_FLAGS[m.language || 'fr'] || '🌐';
    const stars   = renderStars(m.rating);
    const title   = esc(m.title);
    // Safe: use data-id on card, never put URL/title inside onclick string
    const coverHtml = m.image
        ? `<img src="${esc(m.image)}" alt="${title}" loading="lazy" onerror="this.parentNode.innerHTML='<div class=\\'card-cover-placeholder\\'>📖</div>'">`
        : `<div class="card-cover-placeholder">📖</div>`;

    return `
    <div class="manga-card ${m.status === 'completed' ? 'completed' : ''}" data-id="${m.id}">
        <div class="card-cover">
            ${coverHtml}
            <span class="badge badge-lang">${flag}</span>
            ${m.status === 'completed' ? '<span class="badge badge-completed">✅</span>' : ''}
            ${m.chapter_count > 0 ? `<span class="badge badge-chapters">📦 ${m.chapter_count}</span>` : ''}
            ${m.rating > 0 ? `<span class="badge badge-rating">★ ${m.rating}</span>` : ''}
            <div class="card-overlay">
                ${m.notes ? `<div class="overlay-note">${esc(m.notes)}</div>` : ''}
                <div class="overlay-actions">
                    <button class="oa-btn oa-read"    onclick="event.stopPropagation();openMangaLink(${m.id})">▶ Lire</button>
                    <button class="oa-btn oa-archive" onclick="event.stopPropagation();openChaptersModal(${m.id})">📦</button>
                    <button class="oa-btn oa-edit"    onclick="event.stopPropagation();openEditModal(${m.id})">✏️</button>
                    <button class="oa-btn oa-del"     onclick="event.stopPropagation();confirmDelete(${m.id})">🗑️</button>
                </div>
            </div>
        </div>
        <div class="card-info" onclick="openDetailModal(${m.id})">
            <div class="card-title" title="${title}">${title}</div>
            <div class="card-chapter">📖 ${esc(m.current_chapter)}</div>
        </div>
        <div class="card-actions">
            <button class="btn-quick btn-quick-read" onclick="event.stopPropagation();openMangaLink(${m.id})" title="Lire">▶ Lire</button>
            <button class="btn-quick" onclick="event.stopPropagation();quickNextChapter(${m.id})" title="Incrémenter le chapitre">+1 ch.</button>
        </div>
    </div>`;
}

function renderRow(m) {
    const flag    = LANG_FLAGS[m.language || 'fr'] || '🌐';
    const title   = esc(m.title);
    const coverHtml = m.image
        ? `<img src="${esc(m.image)}" alt="${title}" loading="lazy" onerror="this.style.display='none'">`
        : `<div class="row-cover-placeholder">📖</div>`;

    return `
    <div class="manga-row" data-id="${m.id}" onclick="openDetailModal(${m.id})">
        <div class="row-cover">${coverHtml}</div>
        <div class="row-main">
            <div class="row-title">${title}</div>
            <div class="row-meta">
                <span class="row-tag status-${m.status}">${m.status === 'reading' ? '📖 En cours' : '✅ Terminé'}</span>
                <span class="row-chapter">Ch. ${esc(m.current_chapter)}</span>
                ${m.notes ? `<span class="row-note">${esc(m.notes)}</span>` : ''}
            </div>
        </div>
        <div class="row-lang" title="${LANG_NAMES[m.language||'fr']||m.language}">${flag}</div>
        ${m.rating > 0 ? `<div class="row-stars">${renderStars(m.rating)}</div>` : '<div class="row-stars" style="width:4rem"></div>'}
        <div class="row-chapters-count">${m.chapter_count > 0 ? `📦 ${m.chapter_count}` : ''}</div>
        <div class="row-actions" onclick="event.stopPropagation()">
            <button class="ra-btn read"   onclick="openMangaLink(${m.id})">▶</button>
            <button class="ra-btn"        onclick="quickNextChapter(${m.id})">+1</button>
            <button class="ra-btn"        onclick="openChaptersModal(${m.id})">📦</button>
            <button class="ra-btn"        onclick="openEditModal(${m.id})">✏️</button>
            <button class="ra-btn del"    onclick="confirmDelete(${m.id})">🗑️</button>
        </div>
    </div>`;
}

function renderStars(rating) {
    if (!rating || rating <= 0) return '';
    let s = '';
    for (let i = 1; i <= 5; i++) {
        s += `<span style="color:${i <= rating ? '#f59e0b' : '#374151'}">★</span>`;
    }
    return s;
}

// ════════════════════════════════════════
// STATS
// ════════════════════════════════════════

function updateStats() {
    const reading   = mangas.filter(m => m.status === 'reading').length;
    const completed = mangas.filter(m => m.status === 'completed').length;
    const chapters  = mangas.reduce((s, m) => s + (parseInt(m.chapter_count) || 0), 0);
    const rated     = mangas.filter(m => m.rating > 0);
    const avgRating = rated.length ? (rated.reduce((s,m)=>s+parseInt(m.rating),0)/rated.length).toFixed(1) : '—';

    document.getElementById('statTotal').textContent     = mangas.length;
    document.getElementById('statReading').textContent   = reading;
    document.getElementById('statCompleted').textContent = completed;
    document.getElementById('statChapters').textContent  = chapters;
    document.getElementById('statRating').textContent    = avgRating;

    // Update last update
    if (mangas.length > 0) {
        const last = mangas.reduce((a, b) => {
            const da = new Date(a.date_updated || a.date_added);
            const db = new Date(b.date_updated || b.date_added);
            return db > da ? b : a;
        });
        const d = new Date(last.date_updated || last.date_added);
        const el = document.getElementById('statLastUpdate');
        if (el) el.textContent = relativeDate(d);
    }

    // Build language filter dynamically
    buildLangFilter();
}

function buildLangFilter() {
    const langs = [...new Set(mangas.map(m => m.language || 'fr'))];
    const wrap  = document.getElementById('langFilters');
    if (!wrap) return;
    const current = filterLang;

    let html = `<button class="filter-pill fp-lang ${current==='all'?'active':''}" data-lang="all" onclick="setFilterLang('all')">🌍 Toutes</button>`;
    langs.forEach(l => {
        html += `<button class="filter-pill fp-lang ${current===l?'active':''}" data-lang="${l}" onclick="setFilterLang('${l}')">${LANG_FLAGS[l]||'🌐'} ${LANG_NAMES[l]||l}</button>`;
    });
    wrap.innerHTML = html;
}

// ════════════════════════════════════════
// VIEW TOGGLE
// ════════════════════════════════════════

function applyView(v) {
    view = v;
    localStorage.setItem('mt_view', v);
    document.querySelectorAll('.view-btn').forEach(b => b.classList.toggle('active', b.dataset.view === v));
    renderAll();
}

// ════════════════════════════════════════
// QUICK +1 CHAPTER
// ════════════════════════════════════════

async function quickNextChapter(id) {
    const manga = mangas.find(m => +m.id === +id);
    if (!manga) return;

    // Try to increment numeric part
    const match = manga.current_chapter.match(/^(\D*)(\d+(?:\.\d+)?)(\D*)$/);
    let next;
    if (match) {
        const num = parseFloat(match[2]);
        next = match[1] + (Number.isInteger(num) ? (num + 1).toString() : (num + 0.5).toFixed(1)) + match[3];
    } else {
        // Non-numeric, just append a prompt
        const input = prompt(`Chapitre actuel : "${manga.current_chapter}"\nNouveau chapitre :`, manga.current_chapter);
        if (!input) return;
        next = input.trim();
    }

    const fd = new FormData();
    fd.append('id', id);
    fd.append('title', manga.title);
    fd.append('readingLink', manga.reading_link);
    fd.append('currentChapter', next);
    fd.append('status', manga.status);
    fd.append('language', manga.language || 'fr');
    fd.append('notes', manga.notes || '');
    if (manga.image && !manga.image.startsWith('img/')) fd.append('imageUrl', manga.image);
    if (manga.rating) fd.append('rating', manga.rating);

    try {
        const res  = await fetch('add_manga.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            manga.current_chapter = next;
            toast(`Ch. ${next} — "${manga.title}"`, 'success');
            renderAll();
        } else {
            toast('Erreur : ' + data.error, 'error');
        }
    } catch (e) {
        toast('Erreur réseau', 'error');
    }
}

// ════════════════════════════════════════
// ADD / EDIT MODAL
// ════════════════════════════════════════

function openAddModal() {
    document.getElementById('formTitle').textContent = '➕ Ajouter un manga';
    document.getElementById('mangaForm').reset();
    document.getElementById('mangaId').value = '';
    document.getElementById('formStatus').value = 'reading';
    document.getElementById('formLang').value = 'fr';
    editingRating = 0;
    renderStarInput(0);
    updateImagePreview('');
    openModal('addModal');
}

function openEditModal(id) {
    const m = mangas.find(x => +x.id === +id);
    if (!m) return;

    document.getElementById('formTitle').textContent  = '✏️ Modifier le manga';
    document.getElementById('mangaId').value          = m.id;
    document.getElementById('formMangaTitle').value   = m.title;
    document.getElementById('formImageUrl').value     = m.image && !m.image.startsWith('img/') ? m.image : '';
    document.getElementById('formReadingLink').value  = m.reading_link;
    document.getElementById('formChapter').value      = m.current_chapter;
    document.getElementById('formStatus').value       = m.status || 'reading';
    document.getElementById('formLang').value         = m.language || 'fr';
    document.getElementById('formNotes').value        = m.notes || '';
    editingRating = parseInt(m.rating) || 0;
    renderStarInput(editingRating);
    updateImagePreview(m.image || '');
    openModal('addModal');
}

document.getElementById('mangaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveManga');
    btn.disabled = true;
    btn.textContent = '⏳ Sauvegarde…';

    const fd = new FormData(this);
    fd.append('rating', editingRating);

    try {
        const res  = await fetch('add_manga.php', { method:'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            closeModal('addModal');
            await loadMangas();
            toast('Manga sauvegardé !', 'success');
        } else {
            toast('Erreur : ' + data.error, 'error');
        }
    } catch (err) {
        toast('Erreur réseau', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Sauvegarder';
    }
});

// Image preview
document.getElementById('formImageFile').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const url = URL.createObjectURL(this.files[0]);
        updateImagePreview(url);
        document.getElementById('formImageUrl').value = '';
    }
});
document.getElementById('formImageUrl').addEventListener('input', function() {
    updateImagePreview(this.value);
});

function updateImagePreview(src) {
    const wrap = document.getElementById('imgPreview');
    if (!wrap) return;
    if (src) {
        wrap.innerHTML = `<img src="${esc(src)}" onerror="this.parentNode.innerHTML='<div class=\\'img-preview-placeholder\\'>📷</div>'">`;
    } else {
        wrap.innerHTML = `<div class="img-preview-placeholder">📷</div>`;
    }
}

// Star rating
function renderStarInput(val) {
    const wrap = document.getElementById('starInput');
    if (!wrap) return;
    let html = '';
    for (let i = 1; i <= 5; i++) {
        html += `<button type="button" class="star-btn ${i <= val ? 'active' : ''}" data-val="${i}" onclick="setRating(${i})">★</button>`;
    }
    wrap.innerHTML = html;
}

function setRating(val) {
    editingRating = (editingRating === val) ? 0 : val; // toggle off if same
    renderStarInput(editingRating);
}

// ════════════════════════════════════════
// DETAIL MODAL
// ════════════════════════════════════════

function openDetailModal(id) {
    const m = mangas.find(x => +x.id === +id);
    if (!m) return;

    const flag = LANG_FLAGS[m.language||'fr'] || '🌐';
    const dateAdded = new Date(m.date_added).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' });
    const dateUp    = new Date(m.date_updated||m.date_added).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' });

    document.getElementById('detailBody').innerHTML = `
        <div class="detail-hero">
            <div class="detail-cover">
                ${m.image
                    ? `<img src="${esc(m.image)}" alt="${esc(m.title)}" loading="lazy" onerror="this.parentNode.innerHTML='<div class=\\'detail-cover-placeholder\\'>📖</div>'">`
                    : `<div class="detail-cover-placeholder">📖</div>`}
            </div>
            <div class="detail-meta">
                <div class="detail-title">${esc(m.title)}</div>
                <div class="detail-tags">
                    <span class="detail-tag ${m.status}">${m.status === 'reading' ? '📖 En cours' : '✅ Terminé'}</span>
                    <span class="detail-tag lang">${flag} ${LANG_NAMES[m.language||'fr']||m.language||'fr'}</span>
                    ${m.chapter_count > 0 ? `<span class="detail-tag" style="background:var(--green-bg);color:var(--green)">📦 ${m.chapter_count} ch. archivés</span>` : ''}
                </div>
                <div class="detail-stars">${m.rating > 0 ? renderStars(m.rating) : '<span style="color:var(--text3);font-size:0.8rem">Non noté</span>'}</div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-key">Chapitre actuel</div><div class="detail-val">📖 ${esc(m.current_chapter)}</div></div>
                    <div class="detail-item"><div class="detail-key">Ajouté le</div><div class="detail-val">${dateAdded}</div></div>
                    <div class="detail-item"><div class="detail-key">Mis à jour</div><div class="detail-val">${dateUp}</div></div>
                    <div class="detail-item"><div class="detail-key">Chapitres archivés</div><div class="detail-val">${m.chapter_count || 0}</div></div>
                </div>
            </div>
        </div>
        ${m.notes ? `<div class="detail-notes">${esc(m.notes)}</div>` : ''}
        <div class="detail-footer-btns">
            <button class="btn btn-accent" onclick="openMangaLink(${m.id})">▶ Lire maintenant</button>
            <button class="btn btn-ghost"  onclick="closeModal('detailModal');openEditModal(${m.id})">✏️ Modifier</button>
            <button class="btn btn-ghost"  onclick="closeModal('detailModal');openChaptersModal(${m.id})">📦 Chapitres</button>
        </div>`;

    openModal('detailModal');
}

// ════════════════════════════════════════
// CHAPTERS MODAL
// ════════════════════════════════════════

async function openChaptersModal(mangaId) {
    const m = mangas.find(x => +x.id === +mangaId);
    const mangaTitle = m ? m.title : '';
    currentChaptersMangaId = mangaId;
    document.getElementById('chaptersMangaTitle').textContent = mangaTitle;
    document.getElementById('chapterMangaId').value = mangaId;
    document.getElementById('chapterUploadForm').reset();
    document.getElementById('chaptersList').innerHTML = '<p style="color:var(--text3);text-align:center;padding:2rem">Chargement…</p>';
    openModal('chaptersModal');
    await loadChapters(mangaId);
}

function closeChaptersModal() {
    closeModal('chaptersModal');
    currentChaptersMangaId = null;
}

async function loadChapters(mangaId) {
    try {
        const fd = new FormData();
        fd.append('action', 'list');
        fd.append('manga_id', mangaId);
        const res  = await fetch('manage_chapters.php', { method:'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            renderChapters(data.chapters);
        }
    } catch (e) {
        document.getElementById('chaptersList').innerHTML = '<p style="color:var(--red);text-align:center">Erreur de chargement</p>';
    }
}

function renderChapters(chapters) {
    const c = document.getElementById('chaptersList');
    if (!chapters.length) {
        c.innerHTML = '<p style="color:var(--text3);text-align:center;padding:2rem">Aucun chapitre archivé</p>';
        return;
    }
    c.innerHTML = chapters.map(ch => `
        <div class="chapter-item">
            <div class="ch-info">
                <div class="ch-number">📖 Chapitre ${esc(ch.chapter_number)}</div>
                <div class="ch-meta">${formatBytes(ch.file_size)} · Ajouté le ${new Date(ch.date_added).toLocaleDateString('fr-FR')}</div>
            </div>
            <div class="ch-actions">
                <button class="btn btn-ghost" style="padding:0.4rem 0.75rem;font-size:0.8rem;flex:initial" onclick="downloadChapter('${esc(ch.file_path)}')">📥 Télécharger</button>
                <button class="btn btn-danger" style="padding:0.4rem 0.75rem;font-size:0.8rem;flex:initial" onclick="deleteChapter(${ch.id},'${esc(ch.chapter_number)}')">🗑️</button>
            </div>
        </div>`).join('');
}

document.getElementById('chapterUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('[type="submit"]');
    const bar = document.getElementById('uploadProgress');
    const barInner = document.getElementById('uploadProgressBar');
    btn.disabled = true;
    btn.textContent = '⏳ Upload…';
    if (bar) { bar.style.display = 'block'; barInner.style.width = '30%'; }

    const fd = new FormData(this);
    try {
        if (barInner) barInner.style.width = '70%';
        const res  = await fetch('manage_chapters.php', { method:'POST', body: fd });
        const data = await res.json();
        if (barInner) barInner.style.width = '100%';
        if (data.success) {
            this.reset();
            await loadChapters(currentChaptersMangaId);
            await loadMangas(); // refresh counts
            toast('Chapitre uploadé !', 'success');
        } else {
            toast('Erreur : ' + data.error, 'error');
        }
    } catch (err) {
        toast('Erreur réseau', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = '📤 Uploader';
        if (bar) { setTimeout(() => { bar.style.display = 'none'; barInner.style.width = '0'; }, 600); }
    }
});

function downloadChapter(path) { window.open(path, '_blank'); }

async function deleteChapter(chapterId, num) {
    if (!confirm(`Supprimer le chapitre ${num} ?`)) return;
    try {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('chapter_id', chapterId);
        const res  = await fetch('manage_chapters.php', { method:'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await loadChapters(currentChaptersMangaId);
            await loadMangas();
            toast('Chapitre supprimé', 'info');
        } else {
            toast('Erreur : ' + data.error, 'error');
        }
    } catch (e) {
        toast('Erreur réseau', 'error');
    }
}

// ════════════════════════════════════════
// DELETE CONFIRM
// ════════════════════════════════════════

function confirmDelete(id) {
    const m = mangas.find(x => +x.id === +id);
    pendingDeleteId = id;
    document.getElementById('deleteItemName').textContent = m ? m.title : id;
    openModal('deleteModal');
}

function closeDeleteModal() {
    pendingDeleteId = null;
    closeModal('deleteModal');
}

async function executeDelete() {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('btnConfirmDelete');
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('id', pendingDeleteId);
        const res  = await fetch('delete_manga.php', { method:'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            closeModal('deleteModal');
            pendingDeleteId = null;
            await loadMangas();
            toast('Manga supprimé', 'info');
        } else {
            toast('Erreur : ' + data.error, 'error');
        }
    } catch (e) {
        toast('Erreur réseau', 'error');
    } finally {
        btn.disabled = false;
    }
}

// ════════════════════════════════════════
// IMPORT / EXPORT
// ════════════════════════════════════════

function exportCollection() {
    const data = {
        exportedAt: new Date().toISOString(),
        version: '2.0',
        count: mangas.length,
        mangas: mangas.map(m => ({
            title: m.title,
            image: m.image,
            reading_link: m.reading_link,
            current_chapter: m.current_chapter,
            status: m.status,
            language: m.language,
            notes: m.notes,
            rating: m.rating,
            date_added: m.date_added,
        }))
    };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url;
    a.download = `manga-tracker-export-${new Date().toISOString().slice(0,10)}.json`;
    a.click();
    URL.revokeObjectURL(url);
    toast('Collection exportée !', 'success');
}

function triggerImport() {
    document.getElementById('importFileInput').click();
}

document.getElementById('importFileInput').addEventListener('change', async function() {
    if (!this.files || !this.files[0]) return;
    const file = this.files[0];
    try {
        const text = await file.text();
        const json = JSON.parse(text);
        const list = json.mangas || (Array.isArray(json) ? json : null);
        if (!list || !list.length) { toast('Fichier invalide', 'error'); return; }

        if (!confirm(`Importer ${list.length} manga(s) ? Les entrées existantes ne seront pas supprimées.`)) return;

        let imported = 0;
        for (const m of list) {
            if (!m.title || !m.reading_link || !m.current_chapter) continue;
            const fd = new FormData();
            fd.append('title', m.title);
            fd.append('imageUrl', m.image || '');
            fd.append('readingLink', m.reading_link);
            fd.append('currentChapter', m.current_chapter);
            fd.append('status', m.status || 'reading');
            fd.append('language', m.language || 'fr');
            fd.append('notes', m.notes || '');
            if (m.rating) fd.append('rating', m.rating);
            const res = await fetch('add_manga.php', { method:'POST', body: fd });
            const data = await res.json();
            if (data.success) imported++;
        }
        await loadMangas();
        toast(`${imported} manga(s) importés !`, 'success');
    } catch (err) {
        toast('Erreur lors de l\'import', 'error');
    }
    this.value = '';
});

// ════════════════════════════════════════
// MODAL HELPERS
// ════════════════════════════════════════

function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('open');
    // Re-enable scroll only if no other modal open
    if (!document.querySelector('.modal-backdrop.open')) {
        document.body.style.overflow = '';
    }
}

// Close on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('open');
            if (!document.querySelector('.modal-backdrop.open')) {
                document.body.style.overflow = '';
            }
        }
    });
});

// ════════════════════════════════════════
// DROPDOWN MENU
// ════════════════════════════════════════

function toggleDropdown(id) {
    const menu = document.getElementById(id);
    const isOpen = menu.classList.contains('open');
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('open'));
    if (!isOpen) menu.classList.add('open');
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('open'));
    }
});

// ════════════════════════════════════════
// LINK HELPER
// ════════════════════════════════════════

function openLink(url) { window.open(url, '_blank', 'noopener'); }

// Safe link opener: reads URL from in-memory mangas array, never from inline HTML attr
function openMangaLink(id) {
    const m = mangas.find(x => +x.id === +id);
    if (m && m.reading_link) window.open(m.reading_link, '_blank', 'noopener');
}

// ════════════════════════════════════════
// TOAST NOTIFICATIONS
// ════════════════════════════════════════

function toast(msg, type = 'info', duration = 3500) {
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    const container = document.getElementById('toastContainer');
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<span>${icons[type] || 'ℹ️'}</span><span>${msg}</span>`;
    container.appendChild(el);
    setTimeout(() => {
        el.classList.add('out');
        el.addEventListener('animationend', () => el.remove());
    }, duration);
}

// ════════════════════════════════════════
// UTILS
// ════════════════════════════════════════

function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
}

function formatBytes(bytes) {
    if (!bytes) return '0 B';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
    if (bytes < 1073741824) return (bytes/1048576).toFixed(1) + ' MB';
    return (bytes/1073741824).toFixed(1) + ' GB';
}

function relativeDate(date) {
    const diff = Math.floor((Date.now() - date) / 1000);
    if (diff < 60) return 'à l\'instant';
    if (diff < 3600) return `il y a ${Math.floor(diff/60)} min`;
    if (diff < 86400) return `il y a ${Math.floor(diff/3600)} h`;
    if (diff < 604800) return `il y a ${Math.floor(diff/86400)} j`;
    return date.toLocaleDateString('fr-FR', { day:'numeric', month:'short' });
}

// ════════════════════════════════════════
// GLOBAL EVENTS
// ════════════════════════════════════════

function bindGlobalEvents() {
    // Escape key closes modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const open = document.querySelector('.modal-backdrop.open');
            if (open) {
                open.classList.remove('open');
                if (!document.querySelector('.modal-backdrop.open')) document.body.style.overflow = '';
            }
        }
    });
}