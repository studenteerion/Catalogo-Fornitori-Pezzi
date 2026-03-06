// Main JavaScript per la homepage delle query

let isUserAuthenticated = false;
let currentUserRole = null;

document.addEventListener('DOMContentLoaded', async function() {
    await checkAuthentication();
    setupAuthButtons();
    loadQueries();
});

async function checkAuthentication() {
    try {
        const response = await fetch(`${API_BASE_URL}/me`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });

        if (!response.ok) {
            isUserAuthenticated = false;
            currentUserRole = null;
            return;
        }

        const data = await response.json();
        isUserAuthenticated = true;
        const rawRole = data?.account?.ruolo;
        currentUserRole = typeof rawRole === 'string' ? rawRole.toLowerCase() : null;
    } catch (error) {
        console.log('Utente non autenticato');
        isUserAuthenticated = false;
        currentUserRole = null;
    }
}

function setupAuthButtons() {
    const authButtonsContainer = document.getElementById('auth-buttons');
    
    if (isUserAuthenticated) {
        // Utente autenticato: mostra dashboard e logout
        const dashboardPath = currentUserRole === 'admin' ? '/admin_dashboard' : '/fornitore_dashboard';
        authButtonsContainer.innerHTML = `
            <a href="${dashboardPath}" class="btn btn-outline-light">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <button class="btn btn-outline-light" onclick="logout()">Logout</button>
        `;
    } else {
        // Utente non autenticato: mostra login/registrazione
        authButtonsContainer.innerHTML = `
            <a href="/login" class="btn btn-outline-light">Login / Registrazione</a>
        `;
    }
}

async function loadQueries() {
    const loadingElement = document.getElementById('home-loading');
    const errorElement = document.getElementById('home-error');
    const cardsContainer = document.getElementById('query-cards');

    try {
        const response = await fetch(`${API_BASE_URL}/`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });
        
        if (!response.ok) {
            throw new Error(`Errore HTTP: ${response.status}`);
        }

        const data = await response.json();
        const queries = data.queries; // L'API ritorna { queries: [...] }
        
        // Nascondi il loading
        loadingElement.classList.add('d-none');

        // Verifica che ci siano query
        if (!queries || queries.length === 0) {
            errorElement.textContent = 'Nessuna query disponibile';
            errorElement.classList.remove('d-none');
            return;
        }

        // Crea le card per ogni query
        queries.forEach(query => {
            const card = createQueryCard(query);
            cardsContainer.appendChild(card);
        });

    } catch (error) {
        console.error('Errore nel caricamento delle query:', error);
        loadingElement.classList.add('d-none');
        errorElement.textContent = `Errore nel caricamento delle query: ${error.message}`;
        errorElement.classList.remove('d-none');
    }
}

function createQueryCard(query) {
    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-4 d-flex';

    col.innerHTML = `
        <div class="card query-card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Query ${query.id}</h5>
                <p class="card-text">${query.description}</p>
                <a href="/query/${query.id}" class="btn btn-primary">Vai alla query</a>
            </div>
        </div>
    `;

    return col;
}
