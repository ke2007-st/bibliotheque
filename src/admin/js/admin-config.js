const ADMIN_API = {
    baseUrl: window.__BIBLIO_ADMIN_API_URL__ || '/api/admin',
    endpoints: {
        login: '/auth/login.php',
        logout: '/auth/logout.php',
        me: '/auth/me.php',
        books: '/books.php',
        stats: '/stats.php',
        users: '/users.php',
        emprunts: '/emprunts.php',
        reservations: '/reservations.php',
    },
};

let adminCsrfToken = '';
