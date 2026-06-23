let userCsrfToken = '';

const API_CONFIG = {
    baseUrl: '/api',
    endpoints: {
        books: '/books.php',
        stats: '/stats.php',
        register: '/auth/register.php',
        login: '/auth/login.php',
        logout: '/auth/logout.php',
        me: '/auth/me.php',
        profile: '/auth/profile.php',
        achats: '/achats.php',
    },
    headers: {
        'Content-Type': 'application/json',
    },
};
