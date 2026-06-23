let currentPage = 'accueil';
let searchTimeout = null;
let currentUser = null;
let myPurchasedBookIds = [];
let myActiveBorrows = {};
let myActiveReservations = {};
let selectedBookId = null;
let selectedBook = null;
let cardObserver = null;
const FAVORIS_KEY = 'biblio_favoris';

document.addEventListener('DOMContentLoaded', init);

async function init() {
    try {
        const result = await AuthAPI.me();
        if (!result.authenticated) {
            window.location.href = 'login.html';
            return;
        }
        if (result.csrf_token) userCsrfToken = result.csrf_token;
        currentUser = result.data;
        renderUser(currentUser);
        await refreshMyPurchases();
        await refreshMyBorrows();
        await refreshMyReservations();
        bindNavigation();
        bindMobileMenu();
        bindCatalogFilters();
        bindProfile();
        bindLogout();
        loadBooks();
        loadEmprunts();
        loadReservations();
        loadAchats();
        loadProfile();
    } catch (err) {
        window.location.href = 'login.html';
    }
}

async function refreshMyBorrows() {
    try {
        const result = await EmpruntsAPI.getAll();
        myActiveBorrows = {};
        result.data.forEach(function (e) {
            if (e.statut === 'actif') {
                myActiveBorrows[Number(e.livre_id)] = e;
            }
        });
    } catch (err) {
        myActiveBorrows = {};
    }
}

async function refreshMyReservations() {
    try {
        const result = await ReservationsAPI.getAll();
        myActiveReservations = {};
        result.data.forEach(function (r) {
            if (r.statut === 'actif') {
                myActiveReservations[Number(r.livre_id)] = r;
            }
        });
    } catch (err) {
        myActiveReservations = {};
    }
}

function isBookReservedByMe(bookId) {
    return !!myActiveReservations[Number(bookId)];
}

function isBookBorrowedByMe(bookId) {
    return !!myActiveBorrows[Number(bookId)];
}

async function refreshMyPurchases() {
    try {
        const result = await AchatsAPI.getAll();
        myPurchasedBookIds = result.data.map(function (a) { return Number(a.livre_id); });
    } catch (err) {
        myPurchasedBookIds = [];
    }
}

function renderUser(user) {
    const initials = (user.prenom.charAt(0) + user.nom.charAt(0)).toUpperCase();
    document.getElementById('user-avatar').textContent = initials;
    document.getElementById('user-name').textContent = user.prenom + ' ' + user.nom;
    document.getElementById('user-role').textContent = 'Membre';
    document.getElementById('user-role').className = 'user-role role-membre';
}

function bindLogout() {
    document.getElementById('btn-logout').addEventListener('click', async function () {
        await AuthAPI.logout();
        window.location.href = 'login.html';
    });
}

function bindNavigation() {
    document.querySelectorAll('.nav-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            switchPage(link.dataset.page);
            closeMobileMenu();
        });
    });
}

function switchPage(pageKey) {
    currentPage = pageKey;
    document.querySelectorAll('.nav-link').forEach(function (link) {
        link.classList.toggle('active', link.dataset.page === pageKey);
    });
    document.querySelectorAll('.page').forEach(function (section) {
        section.classList.toggle('active', section.id === 'page-' + pageKey);
    });

    if (pageKey === 'accueil') loadBooks();
    else if (pageKey === 'emprunts') loadEmprunts();
    else if (pageKey === 'reservations') loadReservations();
    else if (pageKey === 'achats') loadAchats();
    else if (pageKey === 'profil') loadProfile();
}

function bindMobileMenu() {
    const toggle = document.getElementById('menu-toggle');
    const nav = document.getElementById('header-nav');

    toggle.addEventListener('click', function () {
        const open = nav.classList.toggle('open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
}

function closeMobileMenu() {
    document.getElementById('header-nav').classList.remove('open');
    document.getElementById('menu-toggle').setAttribute('aria-expanded', 'false');
}

function getActiveFilter(name) {
    const active = document.querySelector('[data-' + name + '].chip.active');
    return active ? active.dataset[name] : '';
}

function bindCatalogFilters() {
    document.getElementById('search-input').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadBooks, 300);
    });

    document.getElementById('status-chips').addEventListener('click', function (e) {
        const chip = e.target.closest('.chip');
        if (!chip) return;
        document.querySelectorAll('#status-chips .chip').forEach(function (c) {
            c.classList.remove('active');
        });
        chip.classList.add('active');
        loadBooks();
    });

    document.getElementById('category-chips').addEventListener('click', function (e) {
        const chip = e.target.closest('.chip');
        if (!chip) return;
        document.querySelectorAll('#category-chips .chip').forEach(function (c) {
            c.classList.remove('active');
        });
        chip.classList.add('active');
        loadBooks();
    });
}

function bindProfile() {
    document.getElementById('profile-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        await saveProfile();
    });
}

async function loadProfile() {
    const result = await AuthAPI.getProfile();
    const user = result.data;
    document.getElementById('profile-nom').value = user.nom;
    document.getElementById('profile-prenom').value = user.prenom;
    document.getElementById('profile-email').value = user.email;
    document.getElementById('profile-telephone').value = user.telephone || '';
    document.getElementById('profile-date').value = formatDate(user.date_inscription);
    document.getElementById('profile-password').value = '';
    document.getElementById('profile-confirm').value = '';
}

async function saveProfile() {
    const profile = {
        nom: document.getElementById('profile-nom').value.trim(),
        prenom: document.getElementById('profile-prenom').value.trim(),
        telephone: document.getElementById('profile-telephone').value.trim(),
        mot_de_passe: document.getElementById('profile-password').value,
        confirmer_mot_de_passe: document.getElementById('profile-confirm').value,
    };

    try {
        const result = await AuthAPI.updateProfile(profile);
        currentUser = result.data;
        renderUser(currentUser);
        document.getElementById('profile-password').value = '';
        document.getElementById('profile-confirm').value = '';
        showToast('Profil mis a jour', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

function renderCategoryChips(categories) {
    const container = document.getElementById('category-chips');
    const current = getActiveFilter('categorie');
    let html = '<button type="button" class="chip' + (current === '' ? ' active' : '') + '" data-categorie="">Tous</button>';
    categories.forEach(function (cat) {
        const name = cat.categorie || cat;
        const active = current === name ? ' active' : '';
        html += '<button type="button" class="chip' + active + '" data-categorie="' + escapeHtml(name) + '">' +
            escapeHtml(name) + '</button>';
    });
    container.innerHTML = html;
}

function bookInitials(titre) {
    return titre.split(' ').slice(0, 2).map(function (w) { return w.charAt(0); }).join('').toUpperCase() || '📖';
}

function coverGradient(id) {
    const hues = [320, 280, 240, 200, 160, 40, 0];
    const h = hues[id % hues.length];
    return 'linear-gradient(145deg, hsl(' + h + ', 30%, 15%) 0%, hsl(' + h + ', 50%, 35%) 100%)';
}

async function loadBooks() {
    const grid = document.getElementById('books-grid');
    grid.innerHTML = '<p class="catalog-loading">Chargement du catalogue...</p>';
    selectedBookId = null;
    selectedBook = null;

    try {
        await refreshMyPurchases();
        await refreshMyBorrows();
        const books = (await BooksAPI.getAll({
            search: document.getElementById('search-input').value,
            categorie: getActiveFilter('categorie'),
            statut: getActiveFilter('statut'),
        })).data;

        const categories = [];
        const seen = {};
        books.forEach(function (b) {
            if (b.categorie && !seen[b.categorie]) {
                seen[b.categorie] = true;
                categories.push(b.categorie);
            }
        });
        renderCategoryChips(categories);

        if (!books.length) {
            grid.innerHTML = '<p class="catalog-empty">Aucun livre trouve pour ces filtres.</p>';
            return;
        }

        grid.innerHTML = books.map(function (book, index) {
            return buildBookCardShell(book, index);
        }).join('');

        bindCardReveal();
    } catch (err) {
        if (err.status === 401) window.location.href = 'login.html';
        else grid.innerHTML = '<p class="catalog-empty">' + escapeHtml(err.message) + '</p>';
    }
}

function isBookAvailable(book) {
    const stock = Number(book.stock_disponible ?? 0);
    const total = Number(book.stock_total ?? 0);
    return stock > 0 && total > 0 && book.statut !== 'reserve' && book.statut !== 'vendu';
}

function isBookReservable(book) {
    const stock = Number(book.stock_disponible ?? 0);
    const total = Number(book.stock_total ?? 0);
    return total > 0 && stock <= 0 && book.statut !== 'vendu' && book.statut !== 'reserve';
}

function stockLabel(book) {
    const d = Number(book.stock_disponible ?? 0);
    const t = Number(book.stock_total ?? 0);
    if (t <= 0) return 'Rupture de stock';
    if (book.statut === 'reserve') return 'Reserve par la bibliotheque';
    return d + ' ex. disponible' + (d > 1 ? 's' : '') + ' / ' + t;
}

function buildBookActions(book, extraClass) {
    const cls = extraClass || 'btn-sm';
    const id = book.id;

    if (myPurchasedBookIds.indexOf(Number(id)) !== -1) {
        return '<span class="badge badge-vendu">Deja achete</span>';
    }
    if (isBookBorrowedByMe(id)) {
        return '<span class="badge badge-emprunte">Emprunt en cours</span>' +
            '<span class="book-card-hint">A rapporter a la bibliotheque</span>';
    }
    if (isBookReservedByMe(id)) {
        return '<span class="badge badge-reserve">Deja reserve</span>' +
            '<button type="button" class="btn btn-secondary ' + cls + '" onclick="event.stopPropagation(); cancelReservation(' + id + ')">Annuler</button>';
    }
    if (book.statut === 'reserve') {
        return '<span class="badge badge-reserve">Reserve bibliotheque</span>' +
            '<span class="book-card-hint">Indisponible temporairement</span>';
    }
    if (isBookAvailable(book)) {
        return '<button type="button" class="btn btn-secondary ' + cls + '" onclick="event.stopPropagation(); borrowBook(' + id + ')">Emprunter</button>' +
            '<button type="button" class="btn btn-primary ' + cls + '" onclick="event.stopPropagation(); buyBook(' + id + ')">Acheter</button>';
    }
    if (isBookReservable(book)) {
        return '<span class="book-card-unavail">Indisponible a l emprunt</span>' +
            '<button type="button" class="btn btn-secondary ' + cls + '" onclick="event.stopPropagation(); reserveBook(' + id + ')">Reserver</button>';
    }
    return '<span class="book-card-unavail">' + escapeHtml(stockLabel(book)) + '</span>';
}

function updateBookCardState(book) {
    const card = document.getElementById('book-card-' + book.id);
    if (!card) return;

    const footer = card.querySelector('.book-card-footer');
    if (footer) footer.outerHTML = buildCardFooter(book);

    const hover = card.querySelector('.book-card-hover');
    if (hover) {
        hover.querySelectorAll('.book-card-buy-hover, .book-card-borrow-hover').forEach(function (el) { el.remove(); });
        const badge = hover.querySelector('.badge');
        if (badge) badge.outerHTML = statusBadge(book.statut);
        if (book.statut === 'disponible' && isBookAvailable(book) && !isBookBorrowedByMe(book.id) && myPurchasedBookIds.indexOf(Number(book.id)) === -1) {
            const wrap = document.createElement('div');
            wrap.className = 'book-card-hover-actions';
            wrap.innerHTML =
                '<button type="button" class="btn btn-secondary btn-sm book-card-borrow-hover" onclick="event.stopPropagation(); borrowBook(' + book.id + ')">Emprunter</button>' +
                '<button type="button" class="btn btn-primary btn-sm book-card-buy-hover" onclick="event.stopPropagation(); buyBook(' + book.id + ')">Acheter · ' + formatPrice(book.prix) + '</button>';
            hover.appendChild(wrap);
        } else if (isBookReservable(book) && !isBookReservedByMe(book.id) && !isBookBorrowedByMe(book.id) && myPurchasedBookIds.indexOf(Number(book.id)) === -1) {
            const wrap = document.createElement('div');
            wrap.className = 'book-card-hover-actions';
            wrap.innerHTML =
                '<button type="button" class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); reserveBook(' + book.id + ')">Reserver</button>';
            hover.appendChild(wrap);
        }
    }

    if (selectedBookId === Number(book.id)) {
        selectedBook = book;
        refreshSelectedCardActions();
    }
}

function buildCardFooter(book) {
    let html = '<div class="book-card-footer">';
    html += '<span class="book-card-price">' + formatPrice(book.prix) + ' · ' + escapeHtml(stockLabel(book)) + '</span>';
    html += '<div class="book-card-actions-row">' + buildBookActions(book, 'btn-sm') + '</div>';
    html += '</div>';
    return html;
}

function buildBookCardShell(book, index) {
    const showBorrowBuy = isBookAvailable(book) && !isBookBorrowedByMe(book.id) && myPurchasedBookIds.indexOf(Number(book.id)) === -1;
    const showReserve = isBookReservable(book) && !isBookReservedByMe(book.id) && !isBookBorrowedByMe(book.id) && myPurchasedBookIds.indexOf(Number(book.id)) === -1;
    let hoverActions = '';
    if (showBorrowBuy) {
        hoverActions = '<div class="book-card-hover-actions">' +
            '<button type="button" class="btn btn-secondary btn-sm book-card-borrow-hover" onclick="event.stopPropagation(); borrowBook(' + book.id + ')">Emprunter</button>' +
            '<button type="button" class="btn btn-primary btn-sm book-card-buy-hover" onclick="event.stopPropagation(); buyBook(' + book.id + ')">Acheter · ' + formatPrice(book.prix) + '</button>' +
        '</div>';
    } else if (showReserve) {
        hoverActions = '<div class="book-card-hover-actions">' +
            '<button type="button" class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); reserveBook(' + book.id + ')">Reserver</button>' +
        '</div>';
    }

    return '<article class="book-card" id="book-card-' + book.id + '" data-id="' + book.id + '" style="--i:' + index + '">' +
        '<button type="button" class="book-card-trigger" aria-expanded="false" onclick="toggleBookCard(' + book.id + ')">' +
            '<div class="book-card-cover" style="background:' + coverGradient(book.id) + '">' +
                '<span class="book-card-initials">' + escapeHtml(bookInitials(book.titre)) + '</span>' +
                '<div class="book-card-hover">' +
                    '<h3 class="book-card-title">' + escapeHtml(book.titre) + '</h3>' +
                    '<p class="book-card-meta">' + escapeHtml(book.auteur) + '</p>' +
                    statusBadge(book.statut) +
                    hoverActions +
                '</div>' +
            '</div>' +
        '</button>' +
        buildCardFooter(book) +
        '<div class="book-card-expand" aria-hidden="true">' +
            '<div class="book-card-expand-inner"></div>' +
        '</div>' +
    '</article>';
}

function bindCardReveal() {
    if (cardObserver) cardObserver.disconnect();
    cardObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) entry.target.classList.add('is-visible');
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.book-card').forEach(function (card) {
        cardObserver.observe(card);
    });
}

async function toggleBookCard(id) {
    const card = document.getElementById('book-card-' + id);
    if (!card) return;

    if (selectedBookId === id) {
        closeBookCard();
        return;
    }

    try {
        const book = (await BooksAPI.getById(id)).data;
        selectedBookId = id;
        selectedBook = book;

        document.getElementById('books-grid').classList.add('has-selection');
        document.querySelectorAll('.book-card').forEach(function (c) {
            c.classList.toggle('selected', c.dataset.id === String(id));
            c.classList.toggle('dimmed', c.dataset.id !== String(id));
        });

        const trigger = card.querySelector('.book-card-trigger');
        const expand = card.querySelector('.book-card-expand');
        const inner = card.querySelector('.book-card-expand-inner');

        trigger.setAttribute('aria-expanded', 'true');
        expand.setAttribute('aria-hidden', 'false');
        inner.innerHTML = buildBookExpandContent(book);
        expand.classList.add('open');

        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } catch (err) {
        showToast(err.message, 'error');
    }
}

function closeBookCard() {
    if (!selectedBookId) return;
    const card = document.getElementById('book-card-' + selectedBookId);
    if (card) {
        card.querySelector('.book-card-trigger').setAttribute('aria-expanded', 'false');
        const expand = card.querySelector('.book-card-expand');
        expand.classList.remove('open');
        expand.setAttribute('aria-hidden', 'true');
    }
    document.getElementById('books-grid').classList.remove('has-selection');
    document.querySelectorAll('.book-card').forEach(function (c) {
        c.classList.remove('selected', 'dimmed');
    });
    selectedBookId = null;
    selectedBook = null;
}

function buildBookExpandContent(book) {
    let actions = '';

    const favClass = isFavorite(book.id) ? ' btn-favori active' : ' btn-favori';
    actions += '<button type="button" class="btn btn-secondary btn-sm' + favClass + '" onclick="event.stopPropagation(); toggleFavorite(' + book.id + ')">' +
        (isFavorite(book.id) ? '★ Favori' : '☆ Favoris') + '</button>';
    actions += buildBookActions(book, 'btn-sm');

    const bio = book.bio_auteur || (book.auteur + ' est un auteur reconnu dans le genre ' + book.categorie + '.');
    const desc = book.description || 'Description non disponible.';

    return '<div class="book-expand-info">' +
        '<p class="book-expand-author">' + escapeHtml(book.auteur) + '</p>' +
        '<p class="book-expand-tags">' +
            '<span class="book-tag">' + escapeHtml(book.categorie) + '</span>' +
            '<span class="book-expand-year">' + (book.annee || '—') + '</span>' +
            ' · ' + formatPrice(book.prix) +
            ' · ' + escapeHtml(stockLabel(book)) +
        '</p>' +
        '<p class="book-expand-resume">' + escapeHtml(truncateText(book.description || 'Aucun resume disponible.', 200)) + '</p>' +
        '<button type="button" class="book-expand-toggle" onclick="toggleCardMore(' + book.id + ')" aria-expanded="false">' +
            'En savoir plus <span class="book-accordion-arrow">▼</span>' +
        '</button>' +
        '<div class="book-expand-more" id="book-more-' + book.id + '">' +
            '<div class="book-expand-more-section">' +
                '<h4>Biographie</h4><p>' + escapeHtml(bio) + '</p>' +
            '</div>' +
            '<div class="book-expand-more-section">' +
                '<h4>Description</h4><p>' + escapeHtml(desc) + '</p>' +
            '</div>' +
            '<div class="book-expand-more-section">' +
                '<h4>Details</h4>' +
                '<ul class="book-meta-list">' +
                    '<li><span>ISBN</span><strong>' + escapeHtml(book.isbn || '—') + '</strong></li>' +
                    '<li><span>Pages</span><strong>' + (book.pages || '—') + '</strong></li>' +
                    '<li><span>Editeur</span><strong>' + escapeHtml(book.editeur || '—') + '</strong></li>' +
                    '<li><span>Note</span><strong>' + (book.note ? book.note + ' / 5' : '—') + '</strong></li>' +
                '</ul>' +
            '</div>' +
        '</div>' +
        '<div class="book-expand-actions">' + actions + '</div>' +
    '</div>';
}

function toggleCardMore(id) {
    const panel = document.getElementById('book-more-' + id);
    const btn = panel.previousElementSibling;
    const open = panel.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}

function refreshSelectedCardActions() {
    if (!selectedBookId || !selectedBook) return;
    const inner = document.querySelector('#book-card-' + selectedBookId + ' .book-card-expand-inner');
    if (inner) inner.innerHTML = buildBookExpandContent(selectedBook);
}

async function loadAchats() {
    const grid = document.getElementById('purchases-grid');
    grid.innerHTML = '<p class="catalog-loading">Chargement...</p>';

    try {
        const achats = (await AchatsAPI.getAll()).data;

        if (!achats.length) {
            grid.innerHTML = '<p class="catalog-empty">Aucun achat pour le moment. Explorez le catalogue !</p>';
            return;
        }

        grid.innerHTML = achats.map(function (a, index) {
            return '<article class="purchase-card is-visible" style="--i:' + index + '">' +
                '<div class="purchase-card-cover" style="background:' + coverGradient(a.livre_id || index) + '">' +
                    '<span class="book-card-initials">' + escapeHtml(bookInitials(a.titre)) + '</span>' +
                '</div>' +
                '<div class="purchase-card-body">' +
                    '<h3>' + escapeHtml(a.titre) + '</h3>' +
                    '<p class="purchase-card-author">' + escapeHtml(a.auteur) + '</p>' +
                    '<p class="purchase-card-meta">' +
                        '<span class="book-tag">' + escapeHtml(a.categorie) + '</span>' +
                        '<strong>' + formatPrice(a.prix_paye) + '</strong>' +
                    '</p>' +
                    '<p class="purchase-card-date">Acheté le ' + formatDate(a.date_achat) + '</p>' +
                '</div>' +
            '</article>';
        }).join('');
    } catch (err) {
        if (err.status === 401) window.location.href = 'login.html';
        else grid.innerHTML = '<p class="catalog-empty">' + escapeHtml(err.message) + '</p>';
    }
}

async function loadEmprunts() {
    const grid = document.getElementById('borrows-grid');
    grid.innerHTML = '<p class="catalog-loading">Chargement...</p>';

    try {
        const emprunts = (await EmpruntsAPI.getAll()).data;
        const actifs = emprunts.filter(function (e) { return e.statut === 'actif'; });

        if (!actifs.length) {
            grid.innerHTML = '<p class="catalog-empty">Aucun emprunt en cours. Empruntez un livre disponible dans le catalogue !</p>';
            return;
        }

        grid.innerHTML = actifs.map(function (e, index) {
            return '<article class="purchase-card is-visible" style="--i:' + index + '">' +
                '<div class="purchase-card-cover" style="background:' + coverGradient(e.livre_id || index) + '">' +
                    '<span class="book-card-initials">' + escapeHtml(bookInitials(e.titre)) + '</span>' +
                '</div>' +
                '<div class="purchase-card-body">' +
                    '<h3>' + escapeHtml(e.titre) + '</h3>' +
                    '<p class="purchase-card-author">' + escapeHtml(e.auteur) + '</p>' +
                    '<p class="purchase-card-meta"><span class="book-tag">' + escapeHtml(e.categorie) + '</span></p>' +
                    '<p class="purchase-card-date">Emprunte le ' + formatDate(e.date_emprunt) + '</p>' +
                    '<p class="purchase-card-date">Retour prevu le ' + formatDate(e.date_retour_prevue) + '</p>' +
                    '<p class="purchase-card-hint">Rapportez ce livre a la bibliotheque. Le retour sera valide par un administrateur.</p>' +
                '</div>' +
            '</article>';
        }).join('');
    } catch (err) {
        if (err.status === 401) window.location.href = 'login.html';
        else grid.innerHTML = '<p class="catalog-empty">' + escapeHtml(err.message) + '</p>';
    }
}

async function borrowBook(id) {
    if (!confirm('Confirmer l emprunt de ce livre ? (21 jours)')) return;

    try {
        await EmpruntsAPI.borrow(id);
        showToast('Emprunt confirme !', 'success');
        await refreshMyBorrows();
        const book = (await BooksAPI.getById(id)).data;
        updateBookCardState(book);
        if (currentPage === 'emprunts') loadEmprunts();
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function buyBook(id) {
    if (!confirm('Confirmer l achat de ce livre ?')) return;

    try {
        await AchatsAPI.buy(id);
        showToast('Achat reussi !', 'success');
        await refreshMyPurchases();
        const book = (await BooksAPI.getById(id)).data;
        updateBookCardState(book);
        if (currentPage === 'achats') loadAchats();
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function reserveBook(id) {
    if (!confirm('Reserver ce livre ? Vous serez prioritaire a son retour.')) return;

    try {
        await ReservationsAPI.reserve(id);
        showToast('Reservation enregistree !', 'success');
        await refreshMyReservations();
        const book = (await BooksAPI.getById(id)).data;
        updateBookCardState(book);
        if (currentPage === 'reservations') loadReservations();
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function cancelReservation(id) {
    if (!confirm('Annuler cette reservation ?')) return;

    try {
        await ReservationsAPI.cancel(id);
        showToast('Reservation annulee.', 'success');
        await refreshMyReservations();
        const book = (await BooksAPI.getById(id)).data;
        updateBookCardState(book);
        if (currentPage === 'reservations') loadReservations();
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function loadReservations() {
    const grid = document.getElementById('reservations-grid');
    grid.innerHTML = '<p class="catalog-loading">Chargement...</p>';

    try {
        const reservations = (await ReservationsAPI.getAll()).data;

        if (!reservations.length) {
            grid.innerHTML = '<p class="catalog-empty">Aucune reservation. Reservez un livre indisponible dans le catalogue !</p>';
            return;
        }

        grid.innerHTML = reservations.map(function (r, index) {
            return '<article class="purchase-card is-visible" style="--i:' + index + '">' +
                '<div class="purchase-card-cover" style="background:' + coverGradient(r.livre_id || index) + '">' +
                    '<span class="book-card-initials">' + escapeHtml(bookInitials(r.titre)) + '</span>' +
                '</div>' +
                '<div class="purchase-card-body">' +
                    '<h3>' + escapeHtml(r.titre) + '</h3>' +
                    '<p class="purchase-card-author">' + escapeHtml(r.auteur) + '</p>' +
                    '<p class="purchase-card-meta"><span class="book-tag">' + escapeHtml(r.categorie) + '</span></p>' +
                    '<p class="purchase-card-date">Reserve le ' + formatDate(r.date_reservation) + '</p>' +
                    '<p class="purchase-card-hint">Vous serez averti lorsque le livre sera de nouveau disponible.</p>' +
                    '<button type="button" class="btn btn-secondary btn-sm" onclick="cancelReservation(' + r.livre_id + ')">Annuler la reservation</button>' +
                '</div>' +
            '</article>';
        }).join('');
    } catch (err) {
        if (err.status === 401) window.location.href = 'login.html';
        else grid.innerHTML = '<p class="catalog-empty">' + escapeHtml(err.message) + '</p>';
    }
}

function formatPrice(value) {
    return parseFloat(value).toFixed(2).replace('.', ',') + ' €';
}

function statusBadge(statut) {
    const labels = {
        disponible: 'Disponible',
        emprunte: 'Emprunte',
        reserve: 'Reserve',
        vendu: 'Vendu',
    };
    return '<span class="badge badge-' + statut + '">' + (labels[statut] || statut) + '</span>';
}

function getFavoris() {
    try {
        return JSON.parse(localStorage.getItem(FAVORIS_KEY) || '[]');
    } catch (err) {
        return [];
    }
}

function isFavorite(id) {
    return getFavoris().indexOf(id) !== -1;
}

function toggleFavorite(id) {
    let favoris = getFavoris();
    if (favoris.indexOf(id) !== -1) {
        favoris = favoris.filter(function (f) { return f !== id; });
        showToast('Retire des favoris', 'success');
    } else {
        favoris.push(id);
        showToast('Ajoute aux favoris', 'success');
    }
    localStorage.setItem(FAVORIS_KEY, JSON.stringify(favoris));
    if (selectedBook && selectedBook.id === id) refreshSelectedCardActions();
}

function truncateText(text, max) {
    if (text.length <= max) return text;
    return text.substring(0, max).trim() + '…';
}
