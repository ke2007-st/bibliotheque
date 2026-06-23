const ADMIN_API = {
    baseUrl: '/api/admin',
    endpoints: {
        login: '/auth/login.php',
        logout: '/auth/logout.php',
        me: '/auth/me.php',
        books: '/books.php',
        stats: '/stats.php',
        users: '/users.php',
    },
};

let adminCsrfToken = '';
