// Configurazione dell'applicazione
const API_BASE_URL = 'https://solid-funicular-v66r565pv5673x4v7-8000.app.github.dev';

// Auto-reindirizza a login se non autenticato (per pagine protette)
function requireAuth() {
    if (typeof isAuthenticated === 'function' && !isAuthenticated()) {
        window.location.href = '/login';
    }
}
