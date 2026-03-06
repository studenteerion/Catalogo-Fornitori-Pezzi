// Gestione autenticazione con HttpOnly cookies

function switchTab(tab) {
    const loginBtn = document.getElementById('login-tab');
    const registerBtn = document.getElementById('register-tab');
    const loginForm = document.getElementById('login-form-container');
    const registerForm = document.getElementById('register-form-container');
    const subtitle = document.getElementById('header-subtitle');

    if (tab === 'login') {
        loginBtn.classList.add('active');
        registerBtn.classList.remove('active');
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
        subtitle.textContent = 'Accedi al Catalogo';
        document.getElementById('login-error').classList.remove('show');
    } else {
        loginBtn.classList.remove('active');
        registerBtn.classList.add('active');
        loginForm.classList.add('hidden');
        registerForm.classList.remove('hidden');
        subtitle.textContent = 'Crea un nuovo account';
        document.getElementById('register-error').classList.remove('show');
        document.getElementById('register-success').classList.remove('show');
    }
}

function clearErrors() {
    document.getElementById('login-error').classList.remove('show');
    document.getElementById('register-error').classList.remove('show');
    document.getElementById('register-success').classList.remove('show');
}

function showError(elementId, message) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.classList.add('show');
}

function showSuccess(elementId, message) {
    const element = document.getElementById(elementId);
    element.textContent = message;
    element.classList.add('show');
}

async function handleLogin(event) {
    event.preventDefault();

    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    try {
        const response = await fetch(`${API_BASE_URL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                email: email,
                password: password,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Errore durante il login');
        }

        // Login riuscito: redirect in base al ruolo
        const ruolo = String(data?.account?.ruolo ?? '').toLowerCase();
        if (ruolo === 'admin') {
            window.location.href = '/admin_dashboard';
        } else {
            window.location.href = '/fornitore_dashboard';
        }
    } catch (error) {
        console.error('Errore login:', error);
        showError('login-error', error.message);
    }
}

async function handleRegister(event) {
    event.preventDefault();

    const email = document.getElementById('register-email').value;
    const nome = document.getElementById('register-nome').value;
    const indirizzo = document.getElementById('register-indirizzo').value;
    const password = document.getElementById('register-password').value;
    const confirm = document.getElementById('register-confirm').value;

    if (!validateEmail(email)) {
        showError('register-error', 'Email non valida');
        return;
    }

    if (password !== confirm) {
        showError('register-error', 'Le password non coincidono');
        return;
    }

    if (!nome || !indirizzo) {
        showError('register-error', 'Nome fornitore e indirizzo sono obbligatori');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                email: email,
                password: password,
                fnome: nome,
                indirizzo: indirizzo,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 409) {
                throw new Error('Email già registrata');
            }
            throw new Error(data.error || 'Errore durante la registrazione');
        }

        // Registrazione riuscita
        showSuccess('register-success', 'Registrazione completata! Ora accedi con le tue credenziali.');
        document.getElementById('register-form').reset();

    } catch (error) {
        console.error('Errore registrazione:', error);
        showError('register-error', error.message);
    }
}

function isAuthenticated() {
    // Con HttpOnly cookies, non possiamo verificare il token dal frontend
    // Usiamo un endpoint specifico per verificare l'autenticazione
    return null; // Verrà aggiornato con una chiamata API
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

async function logout() {
    try {
        const response = await fetch(`${API_BASE_URL}/auth/logout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' // Questo sarà gestito dal cookie
            },
            credentials: 'include',
        });

        // Il cookie viene cancellato automaticamente dalla risposta
        if (response.ok) {
            window.location.href = '/logout_success';
        } else {
            window.location.href = '/logout_success';
        }
    } catch (error) {
        console.error('Errore logout:', error);
        window.location.href = '/logout_success';
    }
}

// Funzione per aggiungere credenziali nei fetch
function getAuthHeaders() {
    return {
        'Content-Type': 'application/json',
    };
}

