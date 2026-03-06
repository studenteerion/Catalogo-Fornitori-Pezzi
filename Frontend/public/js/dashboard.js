// Dashboard Fornitore - Gestione dati account e catalogo

let inEditMode = false;
let catalogModalInstance = null;
let createPartModalInstance = null;
let editCostModalInstance = null;
let supplierCatalogData = [];
let supplierCatalogFiltered = [];
let supplierCatalogPage = 1;
let supplierCatalogPageSize = 10;
let supplierCatalogSearch = '';
let supplierCatalogSortBy = null;
let supplierCatalogSortDirection = 'asc';

document.addEventListener('DOMContentLoaded', async function() {
    const canAccess = await enforceSupplierAccess();
    if (!canAccess) {
        return;
    }

    setupNavigation();
    clearPasswordFields();
    await loadData();
    setupCatalogControls();
    await loadCatalog();
    setupEventListeners();
    setupTabListeners();
    
    // Inizializza i modali
    catalogModalInstance = new bootstrap.Modal(document.getElementById('addToCatalogModal'));
    createPartModalInstance = new bootstrap.Modal(document.getElementById('createPartModal'));
    editCostModalInstance = new bootstrap.Modal(document.getElementById('editCostModal'));
});

async function enforceSupplierAccess() {
    try {
        const response = await fetch(`${API_BASE_URL}/me`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });

        if (!response.ok) {
            window.location.href = '/login';
            return false;
        }

        const data = await response.json();
        const role = String(data?.account?.ruolo ?? '').toLowerCase();

        if (role === 'admin') {
            window.location.href = '/admin_dashboard';
            return false;
        }

        return true;
    } catch (error) {
        window.location.href = '/login';
        return false;
    }
}

function clearPasswordFields() {
    document.getElementById('account-old-password').value = '';
    document.getElementById('account-new-password').value = '';
    document.getElementById('account-confirm-password').value = '';
}

// ==================== Navigazione ====================
function setupNavigation() {
    const navButtons = document.getElementById('nav-buttons');
    navButtons.innerHTML = `
        <a href="/" class="btn btn-outline-light me-2">
            <i class="bi bi-house"></i> Home
        </a>
        <button class="btn btn-outline-light" onclick="logout()">Logout</button>
    `;
}

// ==================== Caricamento Dati ====================
async function loadData() {
    try {
        const response = await fetch(`${API_BASE_URL}/me`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento dei dati');
        }

        const data = await response.json();
        const account = data.account;

        // Popola account - solo email
        document.getElementById('account-email').value = account.email || '';

        // Popola fornitore se esiste
        if (account.fnome) {
            document.getElementById('supplier-nome').value = account.fnome || '';
            document.getElementById('supplier-indirizzo').value = account.indirizzo || '';
        }

    } catch (error) {
        console.error('Errore caricamento dati:', error);
        showError('Errore nel caricamento dei dati');
    }
}

// ==================== Event Listeners ====================
function setupEventListeners() {
    // Edit Account (che ora include anche fornitore)
    document.getElementById('edit-account-btn').addEventListener('click', toggleAccountEdit);
    document.getElementById('cancel-account-btn').addEventListener('click', cancelAccountEdit);
    document.getElementById('account-form').addEventListener('submit', handleAccountSubmit);
}

// ==================== Account & Supplier Edit (Unified) ====================
function toggleAccountEdit() {
    inEditMode = !inEditMode;

    const emailInput = document.getElementById('account-email');
    const nomeInput = document.getElementById('supplier-nome');
    const indirizzoInput = document.getElementById('supplier-indirizzo');
    const oldPasswordGroup = document.getElementById('old-password-group');
    const newPasswordGroup = document.getElementById('new-password-group');
    const confirmPasswordGroup = document.getElementById('confirm-password-group');
    const oldPasswordInput = document.getElementById('account-old-password');
    const newPasswordInput = document.getElementById('account-new-password');
    const confirmPasswordInput = document.getElementById('account-confirm-password');
    const buttonGroup = document.getElementById('account-buttons');
    const editBtn = document.getElementById('edit-account-btn');
    const passwordHint = document.getElementById('password-hint');

    if (inEditMode) {
        clearPasswordFields();
        emailInput.disabled = false;
        emailInput.readOnly = false;
        nomeInput.disabled = false;
        indirizzoInput.disabled = false;
        oldPasswordGroup.classList.remove('hidden');
        newPasswordGroup.classList.remove('hidden');
        confirmPasswordGroup.classList.remove('hidden');
        oldPasswordInput.disabled = false;
        newPasswordInput.disabled = false;
        confirmPasswordInput.disabled = false;
        buttonGroup.classList.remove('hidden');
        passwordHint.style.display = 'block';
        editBtn.innerHTML = '<i class="bi bi-x-circle"></i> Annulla';
        emailInput.focus();
    } else {
        emailInput.disabled = true;
        emailInput.readOnly = true;
        nomeInput.disabled = true;
        indirizzoInput.disabled = true;
        oldPasswordGroup.classList.add('hidden');
        newPasswordGroup.classList.add('hidden');
        confirmPasswordGroup.classList.add('hidden');
        oldPasswordInput.disabled = true;
        newPasswordInput.disabled = true;
        confirmPasswordInput.disabled = true;
        buttonGroup.classList.add('hidden');
        passwordHint.style.display = 'none';
        editBtn.innerHTML = '<i class="bi bi-pencil-square"></i> Modifica';
    }
}

function cancelAccountEdit() {
    inEditMode = false;
    document.getElementById('account-buttons').classList.add('hidden');
    document.getElementById('edit-account-btn').innerHTML = '<i class="bi bi-pencil-square"></i> Modifica';
    document.getElementById('old-password-group').classList.add('hidden');
    document.getElementById('new-password-group').classList.add('hidden');
    document.getElementById('confirm-password-group').classList.add('hidden');
    document.getElementById('account-email').disabled = true;
    document.getElementById('account-email').readOnly = true;
    document.getElementById('supplier-nome').disabled = true;
    document.getElementById('supplier-indirizzo').disabled = true;
    document.getElementById('account-old-password').disabled = true;
    document.getElementById('account-new-password').disabled = true;
    document.getElementById('account-confirm-password').disabled = true;
    
    // Reload data
    loadData();
    
    // Pulisci i campi password
    clearPasswordFields();
}

async function handleAccountSubmit(event) {
    event.preventDefault();

    const email = document.getElementById('account-email').value.trim();
    const nome = document.getElementById('supplier-nome').value.trim();
    const indirizzo = document.getElementById('supplier-indirizzo').value.trim();
    const oldPassword = document.getElementById('account-old-password').value;
    const newPassword = document.getElementById('account-new-password').value;
    const confirmPassword = document.getElementById('account-confirm-password').value;

    // Validazioni
    if (!email) {
        showError('Email obbligatoria');
        return;
    }

    if (!validateEmail(email)) {
        showError('Email non valida');
        return;
    }

    if (!nome || !indirizzo) {
        showError('Nome fornitore e indirizzo sono obbligatori');
        return;
    }

    // Se l'utente ha inserito nuove password
    if (newPassword || oldPassword || confirmPassword) {
        if (!oldPassword || !newPassword || !confirmPassword) {
            showError('Compila tutti i campi password o lasciali vuoti');
            return;
        }

        if (newPassword !== confirmPassword) {
            showError('Le password non coincidono');
            return;
        }
    }

    // Costruisci payload unificato con tutti i campi
    const payload = {
        email: email,
        fnome: nome,
        indirizzo: indirizzo,
    };

    // Aggiungi password se fornite
    if (oldPassword && newPassword) {
        payload.oldPassword = oldPassword;
        payload.newPassword = newPassword;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/me`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Errore durante l\'aggiornamento');
        }

        showSuccess('Informazioni aggiornate con successo!');
        resetAccountEdit();

    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

function resetAccountEdit() {
    inEditMode = false;
    document.getElementById('account-buttons').classList.add('hidden');
    document.getElementById('edit-account-btn').innerHTML = '<i class="bi bi-pencil-square"></i> Modifica';
    document.getElementById('old-password-group').classList.add('hidden');
    document.getElementById('new-password-group').classList.add('hidden');
    document.getElementById('confirm-password-group').classList.add('hidden');
    document.getElementById('account-email').disabled = true;
    document.getElementById('account-email').readOnly = true;
    document.getElementById('supplier-nome').disabled = true;
    document.getElementById('supplier-indirizzo').disabled = true;
    document.getElementById('account-old-password').disabled = true;
    document.getElementById('account-new-password').disabled = true;
    document.getElementById('account-confirm-password').disabled = true;
    
    // Pulisci i campi password
    clearPasswordFields();
}

// ==================== Alert Utilities ====================
function showError(message) {
    showToast('error', message);
}

function showSuccess(message) {
    showToast('success', message);
}

function showToast(type, message) {
    const container = document.getElementById('toast-container');
    if (!container) {
        return;
    }

    const durationMs = 10000;
    const toast = document.createElement('div');
    toast.className = `app-toast ${type === 'error' ? 'app-toast-error' : 'app-toast-success'}`;

    const content = document.createElement('div');
    content.className = 'app-toast-content';

    const icon = document.createElement('i');
    icon.className = `app-toast-icon bi ${type === 'error' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill'}`;

    const text = document.createElement('div');
    text.className = 'app-toast-message';
    text.textContent = String(message || '');

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'app-toast-close';
    closeButton.setAttribute('aria-label', 'Chiudi notifica');
    closeButton.innerHTML = '<i class="bi bi-x-lg"></i>';

    content.appendChild(icon);
    content.appendChild(text);
    content.appendChild(closeButton);

    const progress = document.createElement('div');
    progress.className = 'app-toast-progress';

    const progressBar = document.createElement('div');
    progressBar.className = 'app-toast-progress-bar';
    progress.appendChild(progressBar);

    toast.appendChild(content);
    toast.appendChild(progress);
    container.appendChild(toast);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            progressBar.style.width = '0%';
        });
    });

    const timeoutId = setTimeout(() => {
        removeToast(toast);
    }, durationMs);

    closeButton.addEventListener('click', () => {
        clearTimeout(timeoutId);
        removeToast(toast);
    });
}

function removeToast(toast) {
    if (!toast || !toast.parentElement) {
        return;
    }

    toast.classList.add('app-toast-hide');
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 180);
}

// ==================== Validation ====================
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// ==================== Gestione Tab ====================
function setupTabListeners() {
    const catalogTab = document.getElementById('catalog-tab');
    if (catalogTab) {
        catalogTab.addEventListener('shown.bs.tab', function() {
            loadCatalog();
        });
    }
}

function setupCatalogControls() {
    const searchInput = document.getElementById('catalog-search');
    const pageSizeSelect = document.getElementById('catalog-page-size');

    if (searchInput) {
        searchInput.addEventListener('input', (event) => {
            supplierCatalogSearch = String(event.target.value || '').toLowerCase();
            supplierCatalogPage = 1;
            applyCatalogFilter();
        });
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', (event) => {
            supplierCatalogPageSize = event.target.value === 'all' ? Number.MAX_SAFE_INTEGER : Number(event.target.value);
            supplierCatalogPage = 1;
            renderCatalog();
        });
    }
}

// ==================== Catalogo Fornitore ====================
async function loadCatalog() {
    try {
        const response = await fetch(`${API_BASE_URL}/supplier/catalog`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento del catalogo');
        }

        const data = await response.json();
        const catalog = data.catalogo || [];
        supplierCatalogData = catalog;
        applyCatalogFilter();
        updateStatistics(catalog);
    } catch (error) {
        console.error('Errore caricamento catalogo:', error);
        showError('Errore nel caricamento del catalogo');
        supplierCatalogData = [];
        applyCatalogFilter();
        updateStatistics([]);
    }
}

function updateStatistics(catalog) {
    // Numero di pezzi
    const itemsCount = catalog.length;
    document.getElementById('stat-catalog-items').textContent = itemsCount;
}

function applyCatalogFilter() {
    if (!supplierCatalogSearch) {
        supplierCatalogFiltered = [...supplierCatalogData];
    } else {
        supplierCatalogFiltered = supplierCatalogData.filter((item) => {
            return Object.values(item || {}).some((value) => String(value ?? '').toLowerCase().includes(supplierCatalogSearch));
        });
    }

    // Apply sorting
    if (supplierCatalogSortBy) {
        supplierCatalogFiltered.sort((a, b) => {
            const aVal = a[supplierCatalogSortBy];
            const bVal = b[supplierCatalogSortBy];
            
            // Handle null/undefined
            if (aVal == null && bVal == null) return 0;
            if (aVal == null) return 1;
            if (bVal == null) return -1;
            
            // Numeric comparison
            if (!isNaN(aVal) && !isNaN(bVal)) {
                const result = Number(aVal) - Number(bVal);
                return supplierCatalogSortDirection === 'asc' ? result : -result;
            }
            
            // String comparison
            const aStr = String(aVal).toLowerCase();
            const bStr = String(bVal).toLowerCase();
            const result = aStr.localeCompare(bStr, 'it', { numeric: true });
            return supplierCatalogSortDirection === 'asc' ? result : -result;
        });
    }

    const totalPages = getCatalogTotalPages();
    if (supplierCatalogPage > totalPages) {
        supplierCatalogPage = totalPages;
    }

    renderCatalog();
}

function changeCatalogSort(columnKey) {
    if (supplierCatalogSortBy === columnKey) {
        supplierCatalogSortDirection = supplierCatalogSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        supplierCatalogSortBy = columnKey;
        supplierCatalogSortDirection = 'asc';
    }
    
    supplierCatalogPage = 1;
    applyCatalogFilter();
    updateCatalogHeaderStyles();
}

function updateCatalogHeaderStyles() {
    const table = document.getElementById('catalog-table');
    if (!table) return;
    
    const headers = table.querySelectorAll('thead th[data-sort]');
    headers.forEach(header => {
        header.classList.remove('sort-asc', 'sort-desc');
        
        const columnKey = header.getAttribute('data-sort');
        if (columnKey === supplierCatalogSortBy) {
            const direction = supplierCatalogSortDirection;
            header.classList.add(direction === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    });
}

function getCatalogTotalPages() {
    if (supplierCatalogPageSize === Number.MAX_SAFE_INTEGER) {
        return 1;
    }

    return Math.max(1, Math.ceil(supplierCatalogFiltered.length / Math.max(1, supplierCatalogPageSize)));
}

function getCatalogPagedRows() {
    if (supplierCatalogPageSize === Number.MAX_SAFE_INTEGER) {
        return supplierCatalogFiltered;
    }

    const start = (supplierCatalogPage - 1) * supplierCatalogPageSize;
    return supplierCatalogFiltered.slice(start, start + supplierCatalogPageSize);
}

function changeCatalogPage(page) {
    const totalPages = getCatalogTotalPages();
    supplierCatalogPage = Math.min(Math.max(1, page), totalPages);
    renderCatalog();
}

function renderCatalogPagination() {
    const container = document.getElementById('catalog-pagination');
    const info = document.getElementById('catalog-info');
    const totalPages = getCatalogTotalPages();
    const total = supplierCatalogFiltered.length;

    if (!container || !info) {
        return;
    }

    info.textContent = total === 0
        ? 'Nessun risultato'
        : `${total} risultati · Pagina ${supplierCatalogPage} di ${totalPages}`;

    container.innerHTML = '';
    if (totalPages <= 1) {
        return;
    }

    const prev = document.createElement('li');
    prev.className = `page-item ${supplierCatalogPage === 1 ? 'disabled' : ''}`;
    prev.innerHTML = '<a class="page-link" href="#">Precedente</a>';
    prev.addEventListener('click', (event) => {
        event.preventDefault();
        changeCatalogPage(supplierCatalogPage - 1);
    });
    container.appendChild(prev);

    // Genera range di pagine intelligente (sempre mostra pagina 1 e ultima)
    const pages = new Set();
    pages.add(1); // Sempre prima pagina
    pages.add(totalPages); // Sempre ultima pagina
    
    // Aggiungi pagine attorno a quella corrente
    for (let i = Math.max(1, supplierCatalogPage - 1); i <= Math.min(totalPages, supplierCatalogPage + 1); i++) {
        pages.add(i);
    }
    
    const sortedPages = Array.from(pages).sort((a, b) => a - b);
    
    // Render page numbers con ellissi
    let lastRenderedPage = 0;
    for (const page of sortedPages) {
        if (page - lastRenderedPage > 1) {
            const ellipsisItem = document.createElement('li');
            ellipsisItem.className = 'page-item disabled';
            ellipsisItem.innerHTML = '<span class="page-link">...</span>';
            container.appendChild(ellipsisItem);
        }
        
        const item = document.createElement('li');
        item.className = `page-item ${page === supplierCatalogPage ? 'active' : ''}`;
        item.innerHTML = `<a class="page-link" href="#">${page}</a>`;
        item.addEventListener('click', (event) => {
            event.preventDefault();
            changeCatalogPage(page);
        });
        container.appendChild(item);
        lastRenderedPage = page;
    }

    const next = document.createElement('li');
    next.className = `page-item ${supplierCatalogPage === totalPages ? 'disabled' : ''}`;
    next.innerHTML = '<a class="page-link" href="#">Successiva</a>';
    next.addEventListener('click', (event) => {
        event.preventDefault();
        changeCatalogPage(supplierCatalogPage + 1);
    });
    container.appendChild(next);
}

function renderCatalog() {
    const tbody = document.getElementById('catalog-table-body');
    const thead = document.querySelector('#catalog-table thead tr');
    const rows = getCatalogPagedRows();
    
    // Render sortable headers - SEMPRE (non solo la prima volta)
    if (thead) {
        const headers = ['pid', 'pnome', 'colore', 'costo'];
        const labels = ['ID', 'Nome Pezzo', 'Colore', 'Costo'];
        thead.innerHTML = labels.map((label, i) => 
            `<th class="sortable" data-sort="${headers[i]}">${label}</th>`
        ).join('') + '<th>Azioni</th>';
        
        // Aggiungi sempre i listener
        thead.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                changeCatalogSort(th.getAttribute('data-sort'));
            });
        });
        
        // Applica stili immediati
        updateCatalogHeaderStyles();
    }
    
    if (supplierCatalogFiltered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nessun pezzo nel tuo catalogo</td></tr>';
        renderCatalogPagination();
        return;
    }

    tbody.innerHTML = rows.map(item => `
        <tr>
            <td>${item.pid}</td>
            <td>${item.pnome || 'N/A'}</td>
            <td>${item.colore || 'N/A'}</td>
            <td>€ ${parseFloat(item.costo).toFixed(2)}</td>
            <td>
                <button class="btn btn-sm btn-primary action-btn" onclick="openEditCostModal(${item.pid}, '${escapeHtml(item.pnome)}', ${item.costo})" title="Modifica costo">
                    <i class="bi bi-pencil"></i> Modifica
                </button>
                <button class="btn btn-sm btn-danger action-btn" onclick="removeCatalogItem(${item.pid})" title="Rimuovi dal catalogo">
                    <i class="bi bi-trash"></i> Rimuovi
                </button>
            </td>
        </tr>
    `).join('');

    renderCatalogPagination();
}

async function openAddToCatalogModal() {
    // Carica tutti i pezzi disponibili
    try {
        const response = await fetch(`${API_BASE_URL}/supplier/pezzi`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento dei pezzi');
        }

        const data = await response.json();
        const pezzi = data.pezzi || [];

        const select = document.getElementById('catalog-pid');
        select.innerHTML = '<option value="">-- Seleziona un pezzo --</option>' + 
            pezzi.map(p => `<option value="${p.pid}">${p.pnome} (${p.colore})</option>`).join('');

        document.getElementById('catalog-costo').value = '';
        catalogModalInstance.show();
    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento dei pezzi disponibili');
    }
}

async function saveCatalogItem() {
    const pid = parseInt(document.getElementById('catalog-pid').value);
    const costo = parseFloat(document.getElementById('catalog-costo').value);

    if (!pid || !costo || costo <= 0) {
        showError('Seleziona un pezzo e inserisci un costo valido');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/supplier/catalog`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({ pid, costo }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Errore durante l\'aggiunta al catalogo');
        }

        showSuccess('Pezzo aggiunto al catalogo con successo!');
        catalogModalInstance.hide();
        loadCatalog();
    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

function openCreatePartModal() {
    document.getElementById('part-nome').value = '';
    document.getElementById('part-colore').value = '';
    catalogModalInstance.hide();
    createPartModalInstance.show();
}

async function createNewPart() {
    const pnome = document.getElementById('part-nome').value.trim();
    const colore = document.getElementById('part-colore').value.trim();

    if (!pnome || !colore) {
        showError('Compila tutti i campi');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/supplier/pezzi`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({ pnome, colore }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Errore durante la creazione del pezzo');
        }

        showSuccess('Pezzo creato con successo!');
        createPartModalInstance.hide();
        
        // Riapri il modal di aggiunta al catalogo con i pezzi aggiornati
        setTimeout(() => {
            openAddToCatalogModal();
        }, 300);
    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

function openEditCostModal(pid, pnome, costo) {
    document.getElementById('edit-pid').value = pid;
    document.getElementById('edit-part-name').value = pnome;
    document.getElementById('edit-costo').value = parseFloat(costo).toFixed(2);
    editCostModalInstance.show();
}

async function updateCatalogCost() {
    const pid = parseInt(document.getElementById('edit-pid').value);
    const costo = parseFloat(document.getElementById('edit-costo').value);

    if (!pid || !costo || costo <= 0) {
        showError('Inserisci un costo valido');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/supplier/catalog/${pid}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({ costo }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Errore durante l\'aggiornamento');
        }

        showSuccess('Costo aggiornato con successo!');
        editCostModalInstance.hide();
        loadCatalog();
    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

async function removeCatalogItem(pid) {
    if (!confirm('Sei sicuro di voler rimuovere questo pezzo dal tuo catalogo?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/supplier/catalog/${pid}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Errore durante la rimozione');
        }

        showSuccess('Pezzo rimosso dal catalogo');
        loadCatalog();
    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
