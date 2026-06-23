let currentView = 'dashboard';
let searchTimeout = null;
let currentUser = null;
let myPurchasedBookIds = [];

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
        bindNavigation();
        bindFilters();
        bindProfile();
        bindLogout();
        loadDashboard();
    } catch (err) {
        window.location.href = 'login.html';
    }
}

async function refreshMyPurchases() {
    try {
        const result = await AchatsAPI.getAll();
        myPurchasedBookIds = result.data.map(function (a) { return a.livre_id; });
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
    document.querySelectorAll('.nav-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchView(btn.dataset.view);
        });
    });
}

function switchView(view) {
    currentView = view;
    document.querySelectorAll('.nav-btn').forEach(function (btn) {
        btn.classList.toggle('active', btn.dataset.view === view);
    });
    document.querySelectorAll('.view').forEach(function (section) {
        section.classList.toggle('active', section.id === 'view-' + view);
    });

    const titles = {
        dashboard: 'Tableau de bord',
        livres: 'Catalogue des livres',
        achats: 'Mes achats',
        profil: 'Mon profil',
    };
    const subtitles = {
        dashboard: 'Consultez le catalogue de la bibliotheque',
        livres: 'Parcourez et achetez des livres disponibles',
        achats: 'Historique de vos achats',
        profil: 'Gerer votre compte membre',
    };
    document.getElementById('page-title').textContent = titles[view] || 'Dashboard';
    document.getElementById('page-subtitle').textContent = subtitles[view] || '';

    if (view === 'dashboard') loadDashboard();
    else if (view === 'livres') loadBooks();
    else if (view === 'achats') loadAchats();
    else if (view === 'profil') loadProfile();
}

function bindFilters() {
    document.getElementById('search-input').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadBooks, 300);
    });
    document.getElementById('filter-categorie').addEventListener('change', loadBooks);
    document.getElementById('filter-statut').addEventListener('change', loadBooks);
    document.getElementById('btn-refresh').addEventListener('click', loadBooks);
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

async function loadDashboard() {
    try {
        const stats = (await StatsAPI.get()).data;
        document.getElementById('stat-total').textContent = stats.total;
        document.getElementById('stat-disponibles').textContent = stats.disponibles;
        document.getElementById('stat-mes-achats').textContent = stats.mes_achats;
        document.getElementById('stat-depense').textContent = formatPrice(stats.total_depense);
        renderCategoryList(stats.categories);
        renderRecentList(stats.recents);
        updateCategoryFilters(stats.categories);
    } catch (err) {
        if (err.status === 401) window.location.href = 'login.html';
        else showToast(err.message, 'error');
    }
}

function renderCategoryList(categories) {
    const list = document.getElementById('category-list');
    list.innerHTML = categories.length
        ? categories.map(function (cat) {
            return '<li><span>' + escapeHtml(cat.categorie) + '</span><strong>' + cat.total + '</strong></li>';
        }).join('')
        : '<li>Aucune categorie</li>';
}

function renderRecentList(recents) {
    const list = document.getElementById('recent-list');
    list.innerHTML = recents.length
        ? recents.map(function (book) {
            return '<li><span>' + escapeHtml(book.titre) + '</span>' + statusBadge(book.statut) + '</li>';
        }).join('')
        : '<li>Aucun livre recent</li>';
}

function updateCategoryFilters(categories) {
    const select = document.getElementById('filter-categorie');
    const current = select.value;
    select.innerHTML = '<option value="">Toutes categories</option>';
    categories.forEach(function (cat) {
        select.innerHTML += '<option value="' + escapeHtml(cat.categorie) + '">' + escapeHtml(cat.categorie) + '</option>';
    });
    select.value = current;
}

async function loadBooks() {
    const tbody = document.getElementById('books-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="loading">Chargement...</td></tr>';

    try {
        await refreshMyPurchases();
        const books = (await BooksAPI.getAll({
            search: document.getElementById('search-input').value,
            categorie: document.getElementById('filter-categorie').value,
            statut: document.getElementById('filter-statut').value,
        })).data;

        if (!books.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty">Aucun livre trouve</td></tr>';
            return;
        }

        tbody.innerHTML = books.map(function (book) {
            const alreadyOwned = myPurchasedBookIds.indexOf(book.id) !== -1;
            let action = '—';
            if (alreadyOwned) {
                action = '<span class="badge badge-vendu">Deja achete</span>';
            } else if (book.statut === 'disponible') {
                action = '<button class="btn btn-primary btn-sm" onclick="buyBook(' + book.id + ')">Acheter ' + formatPrice(book.prix) + '</button>';
            } else {
                action = '<span class="text-muted-sm">Indisponible</span>';
            }

            return '<tr>' +
                '<td><strong>' + escapeHtml(book.titre) + '</strong></td>' +
                '<td>' + escapeHtml(book.auteur) + '</td>' +
                '<td>' + escapeHtml(book.categorie) + '</td>' +
                '<td>' + formatPrice(book.prix) + '</td>' +
                '<td>' + (book.annee || '—') + '</td>' +
                '<td>' + statusBadge(book.statut) + '</td>' +
                '<td>' + action + '</td>' +
            '</tr>';
        }).join('');
    } catch (err) {
        if (err.status === 401) window.location.href = 'login.html';
        else tbody.innerHTML = '<tr><td colspan="7" class="empty">' + escapeHtml(err.message) + '</td></tr>';
    }
}

async function loadAchats() {
    const tbody = document.getElementById('achats-tbody');
    tbody.innerHTML = '<tr><td colspan="5" class="loading">Chargement...</td></tr>';

    try {
        const achats = (await AchatsAPI.getAll()).data;

        if (!achats.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty">Aucun achat pour le moment</td></tr>';
            return;
        }

        tbody.innerHTML = achats.map(function (a) {
            return '<tr>' +
                '<td><strong>' + escapeHtml(a.titre) + '</strong></td>' +
                '<td>' + escapeHtml(a.auteur) + '</td>' +
                '<td>' + escapeHtml(a.categorie) + '</td>' +
                '<td>' + formatPrice(a.prix_paye) + '</td>' +
                '<td>' + formatDate(a.date_achat) + '</td>' +
            '</tr>';
        }).join('');
    } catch (err) {
        if (err.status === 401) window.location.href = 'login.html';
        else tbody.innerHTML = '<tr><td colspan="5" class="empty">' + escapeHtml(err.message) + '</td></tr>';
    }
}

async function buyBook(id) {
    if (!confirm('Confirmer l achat de ce livre ?')) return;

    try {
        await AchatsAPI.buy(id);
        showToast('Achat reussi !', 'success');
        await refreshMyPurchases();
        if (currentView === 'dashboard') loadDashboard();
        else if (currentView === 'livres') loadBooks();
        else if (currentView === 'achats') loadAchats();
    } catch (err) {
        showToast(err.message, 'error');
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
