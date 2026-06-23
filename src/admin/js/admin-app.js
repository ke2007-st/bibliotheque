let adminView = 'dashboard';
let adminUser = null;
let searchTimeout = null;

document.addEventListener('DOMContentLoaded', initAdmin);

async function initAdmin() {
    try {
        adminUser = await requireAdminAuth();
        if (!adminUser) return;

        document.getElementById('admin-user-name').textContent = adminUser.prenom + ' ' + adminUser.nom;

        bindAdminNav();
        bindAdminBooks();
        bindAdminUsers();
        bindAdminLogout();
        loadAdminDashboard();
    } catch (err) {
        window.location.href = 'login.html';
    }
}

function bindAdminNav() {
    document.querySelectorAll('.admin-nav-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchAdminView(btn.dataset.view);
        });
    });

    document.getElementById('btn-admin-add-book').addEventListener('click', function () {
        openAdminModal();
    });
}

function switchAdminView(view) {
    adminView = view;
    document.querySelectorAll('.admin-nav-btn').forEach(function (btn) {
        btn.classList.toggle('active', btn.dataset.view === view);
    });
    document.querySelectorAll('.admin-view').forEach(function (el) {
        el.classList.toggle('active', el.id === 'admin-view-' + view);
    });

    const titles = { dashboard: 'Tableau de bord', livres: 'Gestion des livres', membres: 'Gestion des membres' };
    document.getElementById('admin-page-title').textContent = titles[view];
    document.getElementById('btn-admin-add-book').style.display = view === 'livres' || view === 'dashboard' ? '' : 'none';

    if (view === 'dashboard') loadAdminDashboard();
    else if (view === 'livres') loadAdminBooks();
    else if (view === 'membres') loadAdminUsers();
}

function bindAdminLogout() {
    document.getElementById('btn-admin-logout').addEventListener('click', async function () {
        await AdminAuthAPI.logout();
        window.location.href = 'login.html';
    });
}

function bindAdminBooks() {
    document.getElementById('adm-search').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadAdminBooks, 300);
    });
    document.getElementById('adm-filter-statut').addEventListener('change', loadAdminBooks);
    document.getElementById('adm-refresh-books').addEventListener('click', loadAdminBooks);
    document.getElementById('admin-modal-close').addEventListener('click', closeAdminModal);
    document.getElementById('adm-book-cancel').addEventListener('click', closeAdminModal);
    document.getElementById('admin-book-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        await saveAdminBook();
    });
}

function bindAdminUsers() {
    document.getElementById('adm-search-users').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadAdminUsers, 300);
    });
    document.getElementById('adm-refresh-users').addEventListener('click', loadAdminUsers);
}

async function loadAdminDashboard() {
    const stats = (await AdminStatsAPI.get()).data;
    document.getElementById('adm-stat-livres').textContent = stats.livres.total;
    document.getElementById('adm-stat-dispo').textContent = stats.livres.disponibles;
    document.getElementById('adm-stat-emprunt').textContent = stats.livres.empruntes;
    document.getElementById('adm-stat-membres').textContent = stats.utilisateurs.membres;

    document.getElementById('adm-category-list').innerHTML = stats.categories.map(function (c) {
        return '<li><span>' + escapeHtml(c.categorie) + '</span><strong>' + c.total + '</strong></li>';
    }).join('') || '<li>Aucune</li>';

    document.getElementById('adm-members-list').innerHTML = stats.recents_membres.map(function (m) {
        return '<li><span>' + escapeHtml(m.prenom + ' ' + m.nom) + '</span><small>' + formatDate(m.date_inscription) + '</small></li>';
    }).join('') || '<li>Aucun membre</li>';
}

async function loadAdminBooks() {
    const tbody = document.getElementById('adm-books-tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="empty">Chargement...</td></tr>';

    try {
        const books = (await AdminBooksAPI.getAll({
            search: document.getElementById('adm-search').value,
            statut: document.getElementById('adm-filter-statut').value,
        })).data;

        if (!books.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty">Aucun livre</td></tr>';
            return;
        }

        tbody.innerHTML = books.map(function (b) {
            return '<tr>' +
                '<td><strong>' + escapeHtml(b.titre) + '</strong></td>' +
                '<td>' + escapeHtml(b.auteur) + '</td>' +
                '<td>' + escapeHtml(b.categorie) + '</td>' +
                '<td>' + formatPrice(b.prix) + '</td>' +
                '<td>' + statusBadge(b.statut) + '</td>' +
                '<td class="actions">' +
                    '<button class="btn-admin-sm" onclick="editAdminBook(' + b.id + ')">Modifier</button>' +
                    '<button class="btn-admin-sm danger" onclick="deleteAdminBook(' + b.id + ')">Supprimer</button>' +
                '</td></tr>';
        }).join('');
    } catch (err) {
        if (err.status === 401) window.location.href = 'login.html';
        tbody.innerHTML = '<tr><td colspan="6" class="empty">' + escapeHtml(err.message) + '</td></tr>';
    }
}

async function loadAdminUsers() {
    const tbody = document.getElementById('adm-users-tbody');
    tbody.innerHTML = '<tr><td colspan="4" class="empty">Chargement...</td></tr>';

    try {
        const users = (await AdminUsersAPI.getAll({
            search: document.getElementById('adm-search-users').value,
        })).data;

        if (!users.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="empty">Aucun membre</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(function (u) {
            return '<tr>' +
                '<td>' + escapeHtml(u.prenom + ' ' + u.nom) + '</td>' +
                '<td>' + escapeHtml(u.email) + '</td>' +
                '<td>' + formatDate(u.date_inscription) + '</td>' +
                '<td><button class="btn-admin-sm danger" onclick="deleteAdminUser(' + u.id + ')">Supprimer</button></td>' +
            '</tr>';
        }).join('');
    } catch (err) {
        if (err.status === 401) window.location.href = 'login.html';
    }
}

function openAdminModal(book) {
    document.getElementById('admin-modal-title').textContent = book ? 'Modifier le livre' : 'Ajouter un livre';
    document.getElementById('adm-book-id').value = book ? book.id : '';
    document.getElementById('adm-book-titre').value = book ? book.titre : '';
    document.getElementById('adm-book-auteur').value = book ? book.auteur : '';
    document.getElementById('adm-book-isbn').value = book ? (book.isbn || '') : '';
    document.getElementById('adm-book-categorie').value = book ? book.categorie : 'General';
    document.getElementById('adm-book-annee').value = book ? (book.annee || '') : '';
    document.getElementById('adm-book-prix').value = book ? book.prix : '9.99';
    document.getElementById('adm-book-statut').value = book ? book.statut : 'disponible';
    document.getElementById('adm-book-description').value = book ? (book.description || '') : '';
    document.getElementById('admin-modal').classList.add('open');
}

function closeAdminModal() {
    document.getElementById('admin-modal').classList.remove('open');
    document.getElementById('admin-book-form').reset();
}

async function editAdminBook(id) {
    const book = (await AdminBooksAPI.getById(id)).data;
    openAdminModal(book);
}

async function saveAdminBook() {
    const id = document.getElementById('adm-book-id').value;
    const book = {
        titre: document.getElementById('adm-book-titre').value.trim(),
        auteur: document.getElementById('adm-book-auteur').value.trim(),
        isbn: document.getElementById('adm-book-isbn').value.trim(),
        categorie: document.getElementById('adm-book-categorie').value.trim() || 'General',
        annee: document.getElementById('adm-book-annee').value || null,
        prix: document.getElementById('adm-book-prix').value || 9.99,
        statut: document.getElementById('adm-book-statut').value,
        description: document.getElementById('adm-book-description').value.trim(),
    };

    try {
        if (id) {
            book.id = parseInt(id, 10);
            await AdminBooksAPI.update(book);
            showToast('Livre modifie', 'success');
        } else {
            await AdminBooksAPI.create(book);
            showToast('Livre ajoute', 'success');
        }

        closeAdminModal();
        if (adminView === 'dashboard') loadAdminDashboard();
        else loadAdminBooks();
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function deleteAdminBook(id) {
    if (!confirm('Supprimer ce livre ?')) return;
    try {
        await AdminBooksAPI.delete(id);
        showToast('Livre supprime', 'success');
        adminView === 'dashboard' ? loadAdminDashboard() : loadAdminBooks();
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function deleteAdminUser(id) {
    if (!confirm('Supprimer ce membre ?')) return;
    try {
        await AdminUsersAPI.delete(id);
        showToast('Membre supprime', 'success');
        loadAdminUsers();
    } catch (err) {
        showToast(err.message, 'error');
    }
}
