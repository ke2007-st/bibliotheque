/**
 * Configuration deploiement — modifier les URLs pour la production.
 * Local Docker : laisser /api (meme domaine).
 * Front separe : mettre l URL permanente du backend (HTTPS obligatoire).
 *
 * Exemple production :
 * window.__BIBLIO_API_URL__ = 'https://biblio-api.up.railway.app/api';
 * window.__BIBLIO_ADMIN_API_URL__ = 'https://biblio-api.up.railway.app/api/admin';
 */
window.__BIBLIO_API_URL__ = window.__BIBLIO_API_URL__ || '/api';
window.__BIBLIO_ADMIN_API_URL__ = window.__BIBLIO_ADMIN_API_URL__ || '/api/admin';
