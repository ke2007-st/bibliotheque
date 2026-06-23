async function adminRequest(endpoint, options = {}) {
    const url = ADMIN_API.baseUrl + endpoint;
    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {}),
    };

    if (adminCsrfToken && options.method && options.method !== 'GET') {
        headers['X-CSRF-Token'] = adminCsrfToken;
    }

    const response = await fetch(url, {
        credentials: 'include',
        ...options,
        headers,
    });

    const data = await response.json();

    if (!response.ok || data.success === false) {
        const error = new Error(data.error || 'Erreur API admin');
        error.status = response.status;
        throw error;
    }

    if (data.csrf_token) {
        adminCsrfToken = data.csrf_token;
    }

    return data;
}

const AdminAuthAPI = {
    login(credentials) {
        return adminRequest(ADMIN_API.endpoints.login, {
            method: 'POST',
            body: JSON.stringify(credentials),
        });
    },
    logout() {
        return adminRequest(ADMIN_API.endpoints.logout, { method: 'POST' });
    },
    me() {
        return adminRequest(ADMIN_API.endpoints.me);
    },
};

const AdminBooksAPI = {
    getAll(filters = {}) {
        const params = new URLSearchParams();
        if (filters.search) params.set('search', filters.search);
        if (filters.statut) params.set('statut', filters.statut);
        const q = params.toString();
        return adminRequest(ADMIN_API.endpoints.books + (q ? '?' + q : ''));
    },
    getById(id) {
        return adminRequest(ADMIN_API.endpoints.books + '?id=' + id);
    },
    create(book) {
        return adminRequest(ADMIN_API.endpoints.books, {
            method: 'POST',
            body: JSON.stringify(book),
        });
    },
    update(book) {
        return adminRequest(ADMIN_API.endpoints.books, {
            method: 'PUT',
            body: JSON.stringify(book),
        });
    },
    delete(id) {
        return adminRequest(ADMIN_API.endpoints.books + '?id=' + id, {
            method: 'DELETE',
        });
    },
};

const AdminStatsAPI = {
    get() {
        return adminRequest(ADMIN_API.endpoints.stats);
    },
};

const AdminUsersAPI = {
    getAll(filters = {}) {
        const params = new URLSearchParams();
        params.set('role', 'membre');
        if (filters.search) params.set('search', filters.search);
        return adminRequest(ADMIN_API.endpoints.users + '?' + params.toString());
    },
    delete(id) {
        return adminRequest(ADMIN_API.endpoints.users + '?id=' + id, {
            method: 'DELETE',
        });
    },
};

const AdminEmpruntsAPI = {
    getAll() {
        return adminRequest(ADMIN_API.endpoints.emprunts);
    },
    confirmReturn(empruntId) {
        return adminRequest(ADMIN_API.endpoints.emprunts, {
            method: 'DELETE',
            body: JSON.stringify({ emprunt_id: empruntId }),
        });
    },
};

const AdminReservationsAPI = {
    getAll() {
        return adminRequest(ADMIN_API.endpoints.reservations);
    },
    cancel(reservationId) {
        return adminRequest(ADMIN_API.endpoints.reservations, {
            method: 'DELETE',
            body: JSON.stringify({ reservation_id: reservationId }),
        });
    },
};

function showToast(message, type) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = 'toast show ' + (type || '');
    setTimeout(function () { toast.classList.remove('show'); }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('fr-FR');
}

function setFormLoading(form, loading) {
    const btn = form.querySelector('button[type="submit"]');
    if (!btn) return;
    btn.disabled = loading;
    btn.dataset.originalText = btn.dataset.originalText || btn.textContent;
    btn.textContent = loading ? 'Chargement...' : btn.dataset.originalText;
}

function showFormError(form, message) {
    let el = form.querySelector('.form-error');
    if (!el) {
        el = document.createElement('p');
        el.className = 'form-error';
        form.insertBefore(el, form.firstChild);
    }
    el.textContent = message;
    el.style.display = message ? 'block' : 'none';
}

function statusBadge(statut) {
    const labels = { disponible: 'Disponible', emprunte: 'Emprunte', reserve: 'Reserve', vendu: 'Vendu' };
    return '<span class="badge badge-' + statut + '">' + (labels[statut] || statut) + '</span>';
}

function formatPrice(value) {
    return parseFloat(value).toFixed(2).replace('.', ',') + ' €';
}

async function requireAdminAuth() {
    const result = await AdminAuthAPI.me();
    if (!result.authenticated) {
        window.location.href = 'login.html';
        return null;
    }
    if (result.csrf_token) adminCsrfToken = result.csrf_token;
    return result.data;
}
