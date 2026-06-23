async function apiRequest(endpoint, options = {}) {
    const url = API_CONFIG.baseUrl + endpoint;
    const headers = {
        ...API_CONFIG.headers,
        ...(options.headers || {}),
    };

    if (userCsrfToken && options.method && options.method !== 'GET') {
        headers['X-CSRF-Token'] = userCsrfToken;
    }

    const response = await fetch(url, {
        credentials: 'include',
        ...options,
        headers,
    });

    const data = await response.json();

    if (!response.ok || data.success === false) {
        const error = new Error(data.error || 'Erreur API');
        error.status = response.status;
        throw error;
    }

    if (data.csrf_token) {
        userCsrfToken = data.csrf_token;
    }

    return data;
}

const AuthAPI = {
    register(user) {
        return apiRequest(API_CONFIG.endpoints.register, {
            method: 'POST',
            body: JSON.stringify(user),
        });
    },
    login(credentials) {
        return apiRequest(API_CONFIG.endpoints.login, {
            method: 'POST',
            body: JSON.stringify(credentials),
        });
    },
    logout() {
        return apiRequest(API_CONFIG.endpoints.logout, { method: 'POST' });
    },
    me() {
        return apiRequest(API_CONFIG.endpoints.me);
    },
    getProfile() {
        return apiRequest(API_CONFIG.endpoints.profile);
    },
    updateProfile(profile) {
        return apiRequest(API_CONFIG.endpoints.profile, {
            method: 'PUT',
            body: JSON.stringify(profile),
        });
    },
};

const BooksAPI = {
    getAll(filters = {}) {
        const params = new URLSearchParams();
        if (filters.search) params.set('search', filters.search);
        if (filters.categorie) params.set('categorie', filters.categorie);
        if (filters.statut) params.set('statut', filters.statut);
        const query = params.toString();
        return apiRequest(API_CONFIG.endpoints.books + (query ? '?' + query : ''));
    },
    getById(id) {
        return apiRequest(API_CONFIG.endpoints.books + '?id=' + id);
    },
};

const StatsAPI = {
    get() {
        return apiRequest(API_CONFIG.endpoints.stats);
    },
};

const AchatsAPI = {
    getAll() {
        return apiRequest(API_CONFIG.endpoints.achats);
    },
    buy(livreId) {
        return apiRequest(API_CONFIG.endpoints.achats, {
            method: 'POST',
            body: JSON.stringify({ livre_id: livreId }),
        });
    },
};

const EmpruntsAPI = {
    getAll() {
        return apiRequest(API_CONFIG.endpoints.emprunts);
    },
    borrow(livreId) {
        return apiRequest(API_CONFIG.endpoints.emprunts, {
            method: 'POST',
            body: JSON.stringify({ livre_id: livreId }),
        });
    },
};

const ReservationsAPI = {
    getAll() {
        return apiRequest(API_CONFIG.endpoints.reservations);
    },
    reserve(livreId) {
        return apiRequest(API_CONFIG.endpoints.reservations, {
            method: 'POST',
            body: JSON.stringify({ livre_id: livreId }),
        });
    },
    cancel(livreId) {
        return apiRequest(API_CONFIG.endpoints.reservations, {
            method: 'DELETE',
            body: JSON.stringify({ livre_id: livreId }),
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
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric',
    });
}

function setFormLoading(form, loading) {
    const btn = form.querySelector('button[type="submit"]');
    if (!btn) return;
    btn.disabled = loading;
    btn.dataset.originalText = btn.dataset.originalText || btn.textContent;
    btn.textContent = loading ? 'Chargement...' : btn.dataset.originalText;
}

function showFormError(form, message) {
    let errorEl = form.querySelector('.form-error');
    if (!errorEl) {
        errorEl = document.createElement('p');
        errorEl.className = 'form-error';
        form.insertBefore(errorEl, form.firstChild);
    }
    errorEl.textContent = message;
    errorEl.style.display = message ? 'block' : 'none';
}
