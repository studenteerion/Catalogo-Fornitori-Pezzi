// Dashboard Admin - Gestione completa del sistema

let fornitoreModal;
let pezzoModal;
let catalogoModal;
let accountModal;
let adminModal;
let detailsModal;

const adminTableState = {
    fornitori: { data: [], filtered: [], page: 1, pageSize: 10, search: '', sortBy: null, sortDirection: 'asc' },
    pezzi: { data: [], filtered: [], page: 1, pageSize: 10, search: '', sortBy: null, sortDirection: 'asc' },
    catalogo: { data: [], filtered: [], page: 1, pageSize: 10, search: '', sortBy: null, sortDirection: 'asc' },
    account: { data: [], filtered: [], page: 1, pageSize: 10, search: '', sortBy: null, sortDirection: 'asc' },
    admins: { data: [], filtered: [], page: 1, pageSize: 10, search: '', sortBy: null, sortDirection: 'asc' },
};

document.addEventListener('DOMContentLoaded', async function() {
    const canAccess = await enforceAdminAccess();
    if (!canAccess) {
        return;
    }

    setupNavigation();
    setupTableControls();
    setupTabAutoRefresh();
    clearAdminPasswordFields();
    await loadStatistics();
    await loadFornitori();
    await loadPezzi();
    await loadCatalogo();
    await loadAccounts();
    await loadAdmins();
    await loadProfilo();
    
    // Initialize modals
    fornitoreModal = new bootstrap.Modal(document.getElementById('fornitoreModal'));
    pezzoModal = new bootstrap.Modal(document.getElementById('pezzoModal'));
    catalogoModal = new bootstrap.Modal(document.getElementById('catalogoModal'));
    accountModal = new bootstrap.Modal(document.getElementById('accountModal'));
    adminModal = new bootstrap.Modal(document.getElementById('adminModal'));
    detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    
    // Setup form handlers
    document.getElementById('profilo-form').addEventListener('submit', handleProfiloSubmit);
});

async function enforceAdminAccess() {
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
        const ruolo = String(data?.account?.ruolo ?? '').toLowerCase();

        if (ruolo !== 'admin') {
            window.location.href = '/fornitore_dashboard';
            return false;
        }

        return true;
    } catch (error) {
        window.location.href = '/login';
        return false;
    }
}

function clearAdminPasswordFields() {
    document.getElementById('profilo-old-password').value = '';
    document.getElementById('profilo-new-password').value = '';
    document.getElementById('profilo-confirm-password').value = '';
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

function setupTabAutoRefresh() {
    const tabs = document.querySelectorAll('#admin-tabs button[data-bs-toggle="pill"]');

    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', async function(event) {
            const target = event.target.getAttribute('data-bs-target');

            try {
                switch (target) {
                    case '#fornitori':
                        await loadFornitori();
                        break;
                    case '#pezzi':
                        await loadPezzi();
                        break;
                    case '#catalogo':
                        await loadCatalogo();
                        break;
                    case '#account':
                        await loadAccounts();
                        break;
                    case '#admins':
                        await loadAdmins();
                        break;
                    case '#profilo':
                        await loadProfilo();
                        break;
                    default:
                        break;
                }

                await loadStatistics();
            } catch (error) {
                console.error('Errore refresh tab:', error);
            }
        });
    });
}

function setupTableControls() {
    const sections = ['fornitori', 'pezzi', 'catalogo', 'account', 'admins'];

    sections.forEach((section) => {
        const searchInput = document.getElementById(`${section}-search`);
        const pageSizeSelect = document.getElementById(`${section}-page-size`);

        if (searchInput) {
            searchInput.addEventListener('input', (event) => {
                adminTableState[section].search = String(event.target.value || '').toLowerCase();
                adminTableState[section].page = 1;
                applyTableFilter(section);
            });
        }

        if (pageSizeSelect) {
            pageSizeSelect.addEventListener('change', (event) => {
                adminTableState[section].pageSize = event.target.value === 'all' ? Number.MAX_SAFE_INTEGER : Number(event.target.value);
                adminTableState[section].page = 1;
                renderSection(section);
            });
        }
    });
}

function setSectionData(section, rows) {
    adminTableState[section].data = Array.isArray(rows) ? rows : [];
    applyTableFilter(section);
}

function applyTableFilter(section) {
    const state = adminTableState[section];
    const term = state.search;

    if (!term) {
        state.filtered = [...state.data];
    } else {
        state.filtered = state.data.filter((row) => {
            return Object.values(row || {}).some((value) => String(value ?? '').toLowerCase().includes(term));
        });
    }

    // Apply sorting
    if (state.sortBy) {
        state.filtered.sort((a, b) => {
            const aVal = a[state.sortBy];
            const bVal = b[state.sortBy];
            
            // Handle null/undefined
            if (aVal == null && bVal == null) return 0;
            if (aVal == null) return 1;
            if (bVal == null) return -1;
            
            // Numeric comparison
            if (!isNaN(aVal) && !isNaN(bVal)) {
                const result = Number(aVal) - Number(bVal);
                return state.sortDirection === 'asc' ? result : -result;
            }
            
            // String comparison
            const aStr = String(aVal).toLowerCase();
            const bStr = String(bVal).toLowerCase();
            const result = aStr.localeCompare(bStr, 'it', { numeric: true });
            return state.sortDirection === 'asc' ? result : -result;
        });
    }

    const totalPages = getTotalPages(section);
    if (state.page > totalPages) {
        state.page = totalPages;
    }

    renderSection(section);
}

function changeSectionSort(section, columnKey) {
    const state = adminTableState[section];
    
    // Toggle sort direction if clicking the same column
    if (state.sortBy === columnKey) {
        state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        state.sortBy = columnKey;
        state.sortDirection = 'asc';
    }
    
    state.page = 1;
    applyTableFilter(section);
    updateTableHeaderStyles(section);
}

function updateTableHeaderStyles(section) {
    const table = document.getElementById(`${section}-table`);
    if (!table) return;
    
    const headers = table.querySelectorAll('thead th[data-sort]');
    headers.forEach(header => {
        header.classList.remove('sort-asc', 'sort-desc');
        
        const columnKey = header.getAttribute('data-sort');
        if (columnKey === adminTableState[section].sortBy) {
            const direction = adminTableState[section].sortDirection;
            header.classList.add(direction === 'asc' ? 'sort-asc' : 'sort-desc');
        }
    });
}

function getTotalPages(section) {
    const state = adminTableState[section];
    if (state.pageSize === Number.MAX_SAFE_INTEGER) {
        return 1;
    }

    return Math.max(1, Math.ceil(state.filtered.length / Math.max(1, state.pageSize)));
}

function getPagedRows(section) {
    const state = adminTableState[section];
    if (state.pageSize === Number.MAX_SAFE_INTEGER) {
        return state.filtered;
    }

    const start = (state.page - 1) * state.pageSize;
    const end = start + state.pageSize;
    return state.filtered.slice(start, end);
}

function changeSectionPage(section, page) {
    const totalPages = getTotalPages(section);
    adminTableState[section].page = Math.min(Math.max(1, page), totalPages);
    renderSection(section);
}

function renderSection(section) {
    switch (section) {
        case 'fornitori':
            renderFornitoriTable();
            break;
        case 'pezzi':
            renderPezziTable();
            break;
        case 'catalogo':
            renderCatalogoTable();
            break;
        case 'account':
            renderAccountTable();
            break;
        case 'admins':
            renderAdminsTable();
            break;
        default:
            break;
    }
}

function renderPagination(section) {
    const state = adminTableState[section];
    const totalPages = getTotalPages(section);
    const container = document.getElementById(`${section}-pagination`);
    const info = document.getElementById(`${section}-info`);

    if (!container || !info) {
        return;
    }

    const total = state.filtered.length;
    info.textContent = total === 0
        ? 'Nessun risultato'
        : `${total} risultati · Pagina ${state.page} di ${totalPages}`;

    container.innerHTML = '';
    if (totalPages <= 1) {
        return;
    }

    const prev = document.createElement('li');
    prev.className = `page-item ${state.page === 1 ? 'disabled' : ''}`;
    prev.innerHTML = `<a class="page-link" href="#">Precedente</a>`;
    prev.addEventListener('click', (event) => {
        event.preventDefault();
        changeSectionPage(section, state.page - 1);
    });
    container.appendChild(prev);

    // Genera range di pagine intelligente (sempre mostra pagina 1 e ultima)
    const pages = new Set();
    pages.add(1); // Sempre prima pagina
    pages.add(totalPages); // Sempre ultima pagina
    
    // Aggiungi pagine attorno a quella corrente
    for (let i = Math.max(1, state.page - 1); i <= Math.min(totalPages, state.page + 1); i++) {
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
        item.className = `page-item ${page === state.page ? 'active' : ''}`;
        item.innerHTML = `<a class="page-link" href="#">${page}</a>`;
        item.addEventListener('click', (event) => {
            event.preventDefault();
            changeSectionPage(section, page);
        });
        container.appendChild(item);
        lastRenderedPage = page;
    }

    const next = document.createElement('li');
    next.className = `page-item ${state.page === totalPages ? 'disabled' : ''}`;
    next.innerHTML = `<a class="page-link" href="#">Successiva</a>`;
    next.addEventListener('click', (event) => {
        event.preventDefault();
        changeSectionPage(section, state.page + 1);
    });
    container.appendChild(next);
}

// ==================== Statistiche ====================
async function loadStatistics() {
    try {
        // Carica fornitori
        const fornitoriResp = await fetch(`${API_BASE_URL}/admin/fornitori`, {
            credentials: 'include',
        });
        const fornitoriData = await fornitoriResp.json();
        document.getElementById('stat-fornitori').textContent = fornitoriData.fornitori?.length || 0;

        // Carica pezzi
        const pezziResp = await fetch(`${API_BASE_URL}/admin/pezzi`, {
            credentials: 'include',
        });
        const pezziData = await pezziResp.json();
        document.getElementById('stat-pezzi').textContent = pezziData.pezzi?.length || 0;

        // Carica catalogo
        const catalogoResp = await fetch(`${API_BASE_URL}/admin/catalogo`, {
            credentials: 'include',
        });
        const catalogoData = await catalogoResp.json();
        document.getElementById('stat-catalogo').textContent = catalogoData.catalogo?.length || 0;

        // Carica account
        const accountResp = await fetch(`${API_BASE_URL}/admin/accounts`, {
            credentials: 'include',
        });
        const accountData = await accountResp.json();
        const accounts = accountData.accounts || [];
        const supplierAccounts = accounts.filter(a => String(a.ruolo || '').toLowerCase() !== 'admin');
        const adminAccounts = accounts.filter(a => String(a.ruolo || '').toLowerCase() === 'admin');
        document.getElementById('stat-account').textContent = supplierAccounts.length;
        document.getElementById('stat-admin').textContent = adminAccounts.length;

    } catch (error) {
        console.error('Errore caricamento statistiche:', error);
    }
}

// ==================== Fornitori ====================
async function loadFornitori() {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/fornitori`, {
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento dei fornitori');
        }

        const data = await response.json();
        const fornitori = data.fornitori || [];
        setSectionData('fornitori', fornitori);

    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento dei fornitori');
    }
}

function renderFornitoriTable() {
    const tbody = document.getElementById('fornitori-tbody');
    const thead = document.querySelector('#fornitori-table thead tr');
    const rows = getPagedRows('fornitori');

    // Render sortable headers - SEMPRE (non solo la prima volta)
    if (thead) {
        const headers = ['fid', 'fnome', 'indirizzo'];
        const labels = ['ID', 'Nome', 'Indirizzo'];
        thead.innerHTML = labels.map((label, i) => 
            `<th class="sortable" data-sort="${headers[i]}">${label}</th>`
        ).join('') + '<th>Azioni</th>';
        
        // Aggiungi sempre i listener
        thead.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                changeSectionSort('fornitori', th.getAttribute('data-sort'));
            });
        });
        
        // Applica stili immediati
        updateTableHeaderStyles('fornitori');
    }

    if (adminTableState.fornitori.filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nessun fornitore presente</td></tr>';
        renderPagination('fornitori');
        return;
    }

    tbody.innerHTML = rows.map(f => `
        <tr>
            <td>${f.fid}</td>
            <td>${escapeHtml(f.fnome)}</td>
            <td>${escapeHtml(f.indirizzo)}</td>
            <td>
                <button class="btn btn-sm btn-warning action-btn" onclick="editFornitore(${f.fid})">
                    <i class="bi bi-pencil"></i> Modifica
                </button>
                <button class="btn btn-sm btn-danger action-btn" onclick="deleteFornitore(${f.fid})">
                    <i class="bi bi-trash"></i> Elimina
                </button>
            </td>
        </tr>
    `).join('');

    renderPagination('fornitori');
}

function showAddFornitoreModal() {
    document.getElementById('fornitoreModalTitle').textContent = 'Aggiungi Fornitore';
    document.getElementById('fornitore-fid').value = '';
    document.getElementById('fornitore-nome').value = '';
    document.getElementById('fornitore-indirizzo').value = '';
    fornitoreModal.show();
}

async function editFornitore(fid) {
    try {
        const response = await fetch(`${API_BASE_URL}/fornitori/${fid}`, {
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento del fornitore');
        }

        const data = await response.json();
        const fornitore = data.fornitore;

        document.getElementById('fornitoreModalTitle').textContent = 'Modifica Fornitore';
        document.getElementById('fornitore-fid').value = fornitore.fid;
        document.getElementById('fornitore-nome').value = fornitore.fnome;
        document.getElementById('fornitore-indirizzo').value = fornitore.indirizzo;
        fornitoreModal.show();

    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento del fornitore');
    }
}

async function saveFornitore() {
    const fid = document.getElementById('fornitore-fid').value;
    const nome = document.getElementById('fornitore-nome').value.trim();
    const indirizzo = document.getElementById('fornitore-indirizzo').value.trim();

    if (!nome || !indirizzo) {
        showError('Nome e indirizzo sono obbligatori');
        return;
    }

    try {
        const isEdit = fid !== '';
        const url = isEdit ? `${API_BASE_URL}/admin/fornitori/${fid}` : `${API_BASE_URL}/admin/fornitori`;
        const method = isEdit ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ fnome: nome, indirizzo: indirizzo }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Errore durante il salvataggio');
        }

        showSuccess(isEdit ? 'Fornitore modificato con successo' : 'Fornitore aggiunto con successo');
        fornitoreModal.hide();
        await loadFornitori();
        await loadCatalogo();
        await loadAccounts();
        await loadStatistics();

    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

async function deleteFornitore(fid) {
    if (!confirm('Sei sicuro di voler eliminare questo fornitore?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/admin/fornitori/${fid}`, {
            method: 'DELETE',
            credentials: 'include',
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Errore durante l\'eliminazione');
        }

        showSuccess('Fornitore eliminato con successo');
        await loadFornitori();
        await loadCatalogo();
        await loadAccounts();
        await loadStatistics();

    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

// ==================== Pezzi ====================
async function loadPezzi() {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/pezzi`, {
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento dei pezzi');
        }

        const data = await response.json();
        const pezzi = data.pezzi || [];
        setSectionData('pezzi', pezzi);

    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento dei pezzi');
    }
}

function renderPezziTable() {
    const tbody = document.getElementById('pezzi-tbody');
    const thead = document.querySelector('#pezzi-table thead tr');
    const rows = getPagedRows('pezzi');

    // Render sortable headers - SEMPRE (non solo la prima volta)
    if (thead) {
        const headers = ['pid', 'pnome', 'colore'];
        const labels = ['ID', 'Nome', 'Colore'];
        thead.innerHTML = labels.map((label, i) => 
            `<th class="sortable" data-sort="${headers[i]}">${label}</th>`
        ).join('') + '<th>Azioni</th>';
        
        // Aggiungi sempre i listener
        thead.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                changeSectionSort('pezzi', th.getAttribute('data-sort'));
            });
        });
        
        // Applica stili immediati
        updateTableHeaderStyles('pezzi');
    }

    if (adminTableState.pezzi.filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nessun pezzo presente</td></tr>';
        renderPagination('pezzi');
        return;
    }

    tbody.innerHTML = rows.map(p => `
        <tr>
            <td>${p.pid}</td>
            <td>${escapeHtml(p.pnome)}</td>
            <td><span class="badge bg-secondary">${escapeHtml(p.colore)}</span></td>
            <td>
                <button class="btn btn-sm btn-warning action-btn" onclick="editPezzo(${p.pid})">
                    <i class="bi bi-pencil"></i> Modifica
                </button>
                <button class="btn btn-sm btn-danger action-btn" onclick="deletePezzo(${p.pid})">
                    <i class="bi bi-trash"></i> Elimina
                </button>
            </td>
        </tr>
    `).join('');

    renderPagination('pezzi');
}

function showAddPezzoModal() {
    document.getElementById('pezzoModalTitle').textContent = 'Aggiungi Pezzo';
    document.getElementById('pezzo-pid').value = '';
    document.getElementById('pezzo-nome').value = '';
    document.getElementById('pezzo-colore').value = '';
    pezzoModal.show();
}

async function editPezzo(pid) {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/pezzi`, {
            credentials: 'include',
        });
        if (!response.ok) {
            throw new Error('Errore nel caricamento dei pezzi');
        }

        const data = await response.json();
        const pezzo = (data.pezzi || []).find(p => Number(p.pid) === Number(pid));

        if (!pezzo) {
            throw new Error('Pezzo non trovato');
        }

        document.getElementById('pezzoModalTitle').textContent = 'Modifica Pezzo';
        document.getElementById('pezzo-pid').value = pezzo.pid;
        document.getElementById('pezzo-nome').value = pezzo.pnome;
        document.getElementById('pezzo-colore').value = pezzo.colore;
        pezzoModal.show();

    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento del pezzo');
    }
}

async function savePezzo() {
    const pid = document.getElementById('pezzo-pid').value;
    const pnome = document.getElementById('pezzo-nome').value.trim();
    const colore = document.getElementById('pezzo-colore').value.trim();

    if (!pnome || !colore) {
        showError('Nome e colore sono obbligatori');
        return;
    }

    try {
        const isEdit = pid !== '';
        const url = isEdit ? `${API_BASE_URL}/admin/pezzi/${pid}` : `${API_BASE_URL}/admin/pezzi`;
        const method = isEdit ? 'PATCH' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ pnome, colore }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Errore durante il salvataggio');
        }

        showSuccess(isEdit ? 'Pezzo modificato con successo' : 'Pezzo aggiunto con successo');
        pezzoModal.hide();
        await loadPezzi();
        await loadCatalogo();
        await loadStatistics();

    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

async function deletePezzo(pid) {
    if (!confirm('Sei sicuro di voler eliminare questo pezzo?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/admin/pezzi/${pid}`, {
            method: 'DELETE',
            credentials: 'include',
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Errore durante l\'eliminazione');
        }

        showSuccess('Pezzo eliminato con successo');
        await loadPezzi();
        await loadCatalogo();
        await loadStatistics();

    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

// ==================== Catalogo ====================
async function loadCatalogo() {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/catalogo`, {
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento del catalogo');
        }

        const data = await response.json();
        const catalogo = data.catalogo || [];
        setSectionData('catalogo', catalogo);

    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento del catalogo');
    }
}

function renderCatalogoTable() {
    const tbody = document.getElementById('catalogo-tbody');
    const thead = document.querySelector('#catalogo-table thead tr');
    const rows = getPagedRows('catalogo');

    // Render sortable headers - SEMPRE (non solo la prima volta)
    if (thead) {
        const headers = ['fnome', 'pnome', 'costo'];
        const labels = ['Fornitore', 'Pezzo', 'Costo'];
        thead.innerHTML = labels.map((label, i) => 
            `<th class="sortable" data-sort="${headers[i]}">${label}</th>`
        ).join('') + '<th>Azioni</th>';
        
        // Aggiungi sempre i listener
        thead.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                changeSectionSort('catalogo', th.getAttribute('data-sort'));
            });
        });
        
        // Applica stili immediati
        updateTableHeaderStyles('catalogo');
    }

    if (adminTableState.catalogo.filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nessuna voce nel catalogo</td></tr>';
        renderPagination('catalogo');
        return;
    }

    tbody.innerHTML = rows.map(c => `
        <tr>
            <td>
                <button type="button" class="catalog-link" onclick="showSupplierDetails(${c.fid})">
                    ${escapeHtml(c.fnome || `Fornitore #${c.fid}`)}
                </button>
            </td>
            <td>
                <button type="button" class="catalog-link" onclick="showPartDetails(${c.pid})">
                    ${escapeHtml(c.pnome || `Pezzo #${c.pid}`)}
                </button>
            </td>
            <td>€ ${parseFloat(c.costo).toFixed(2)}</td>
            <td>
                <button class="btn btn-sm btn-warning action-btn" onclick="editCatalogo(${c.fid}, ${c.pid})">
                    <i class="bi bi-pencil"></i> Modifica
                </button>
                <button class="btn btn-sm btn-danger action-btn" onclick="deleteCatalogo(${c.fid}, ${c.pid})">
                    <i class="bi bi-trash"></i> Elimina
                </button>
            </td>
        </tr>
    `).join('');

    renderPagination('catalogo');
}

async function showSupplierDetails(fid) {
    try {
        const response = await fetch(`${API_BASE_URL}/fornitori/${fid}`, {
            credentials: 'include',
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Errore nel caricamento fornitore');
        }

        const supplier = data.fornitore || {};
        document.getElementById('detailsModalTitle').textContent = 'Dettagli Fornitore';
        document.getElementById('detailsModalBody').innerHTML = `
            <div class="mb-2"><strong>ID:</strong> ${escapeHtml(String(supplier.fid ?? fid))}</div>
            <div class="mb-2"><strong>Nome:</strong> ${escapeHtml(String(supplier.fnome ?? '-'))}</div>
            <div><strong>Indirizzo:</strong> ${escapeHtml(String(supplier.indirizzo ?? '-'))}</div>
        `;
        detailsModal.show();
    } catch (error) {
        console.error('Errore:', error);
        showError(error.message || 'Errore nel caricamento fornitore');
    }
}

async function showPartDetails(pid) {
    try {
        const response = await fetch(`${API_BASE_URL}/pezzi/${pid}`, {
            credentials: 'include',
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Errore nel caricamento pezzo');
        }

        const part = data.pezzo || {};
        document.getElementById('detailsModalTitle').textContent = 'Dettagli Pezzo';
        document.getElementById('detailsModalBody').innerHTML = `
            <div class="mb-2"><strong>ID:</strong> ${escapeHtml(String(part.pid ?? pid))}</div>
            <div class="mb-2"><strong>Nome:</strong> ${escapeHtml(String(part.pnome ?? '-'))}</div>
            <div><strong>Colore:</strong> ${escapeHtml(String(part.colore ?? '-'))}</div>
        `;
        detailsModal.show();
    } catch (error) {
        console.error('Errore:', error);
        showError(error.message || 'Errore nel caricamento pezzo');
    }
}

async function loadOptionsForCatalogo() {
    try {
        // Carica fornitori
        const fornitoriResp = await fetch(`${API_BASE_URL}/admin/fornitori`, {
            credentials: 'include',
        });
        const fornitoriData = await fornitoriResp.json();
        const fornitori = fornitoriData.fornitori || [];

        const fidSelect = document.getElementById('catalogo-fid');
        fidSelect.innerHTML = '<option value="">Seleziona fornitore...</option>' +
            fornitori.map(f => `<option value="${f.fid}">${escapeHtml(f.fnome)}</option>`).join('');

        // Carica pezzi
        const pezziResp = await fetch(`${API_BASE_URL}/admin/pezzi`, {
            credentials: 'include',
        });
        const pezziData = await pezziResp.json();
        const pezzi = pezziData.pezzi || [];

        const pidSelect = document.getElementById('catalogo-pid');
        pidSelect.innerHTML = '<option value="">Seleziona pezzo...</option>' +
            pezzi.map(p => `<option value="${p.pid}">${escapeHtml(p.pnome)}</option>`).join('');

    } catch (error) {
        console.error('Errore:', error);
        throw error;
    }
}

async function showAddCatalogoModal() {
    try {
        document.getElementById('catalogoModalTitle').textContent = 'Aggiungi al Catalogo';
        document.getElementById('catalogo-fid-original').value = '';
        document.getElementById('catalogo-pid-original').value = '';
        document.getElementById('catalogo-fid').value = '';
        document.getElementById('catalogo-pid').value = '';
        document.getElementById('catalogo-costo').value = '';
        
        await loadOptionsForCatalogo();
        catalogoModal.show();
    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento del form');
    }
}

async function editCatalogo(fid, pid) {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/catalogo`, {
            credentials: 'include',
        });
        if (!response.ok) {
            throw new Error('Errore nel caricamento del catalogo');
        }

        const data = await response.json();
        const item = (data.catalogo || []).find(c => Number(c.fid) === Number(fid) && Number(c.pid) === Number(pid));

        if (!item) {
            throw new Error('Voce di catalogo non trovata');
        }

        document.getElementById('catalogoModalTitle').textContent = 'Modifica Catalogo';
        document.getElementById('catalogo-fid-original').value = item.fid;
        document.getElementById('catalogo-pid-original').value = item.pid;
        document.getElementById('catalogo-costo').value = item.costo;

        await loadOptionsForCatalogo();
        document.getElementById('catalogo-fid').value = item.fid;
        document.getElementById('catalogo-pid').value = item.pid;
        
        catalogoModal.show();

    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento della voce di catalogo');
    }
}

async function saveCatalogo() {
    const fidOriginal = document.getElementById('catalogo-fid-original').value;
    const pidOriginal = document.getElementById('catalogo-pid-original').value;
    const fid = Number(document.getElementById('catalogo-fid').value);
    const pid = Number(document.getElementById('catalogo-pid').value);
    const costo = parseFloat(document.getElementById('catalogo-costo').value);

    if (!fid || !pid || isNaN(costo)) {
        showError('Tutti i campi sono obbligatori');
        return;
    }

    try {
        const isEdit = fidOriginal !== '' && pidOriginal !== '';
        let url, method;
        
        if (isEdit) {
            url = `${API_BASE_URL}/admin/catalogo/${fidOriginal}/${pidOriginal}`;
            method = 'PATCH';
        } else {
            url = `${API_BASE_URL}/admin/catalogo`;
            method = 'POST';
        }

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ fid, pid, costo }),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Errore durante il salvataggio');
        }

        showSuccess(isEdit ? 'Catalogo modificato con successo' : 'Voce aggiunta al catalogo con successo');
        catalogoModal.hide();
        await loadCatalogo();
        await loadStatistics();

    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

async function deleteCatalogo(fid, pid) {
    if (!confirm('Sei sicuro di voler eliminare questa voce dal catalogo?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/admin/catalogo/${fid}/${pid}`, {
            method: 'DELETE',
            credentials: 'include',
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Errore durante l\'eliminazione');
        }

        showSuccess('Voce eliminata dal catalogo con successo');
        await loadCatalogo();
        await loadStatistics();

    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

// ==================== Account ====================
async function loadAccounts() {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/accounts`, {
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento degli account');
        }

        const data = await response.json();
        const accounts = (data.accounts || []).filter(a => String(a.ruolo || '').toLowerCase() !== 'admin');
        setSectionData('account', accounts);

    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento degli account');
    }
}

function renderAccountTable() {
    const tbody = document.getElementById('account-tbody');
    const thead = document.querySelector('#account-table thead tr');
    const rows = getPagedRows('account');

    // Render sortable headers - SEMPRE (non solo la prima volta)
    if (thead) {
        const headers = ['aid', 'email', 'fnome'];
        const labels = ['ID', 'Email', 'Fornitore'];
        thead.innerHTML = labels.map((label, i) => 
            `<th class="sortable" data-sort="${headers[i]}">${label}</th>`
        ).join('') + '<th>Azioni</th>';
        
        // Aggiungi sempre i listener
        thead.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                changeSectionSort('account', th.getAttribute('data-sort'));
            });
        });
        
        // Applica stili immediati
        updateTableHeaderStyles('account');
    }

    if (adminTableState.account.filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nessun account fornitore presente</td></tr>';
        renderPagination('account');
        return;
    }

    tbody.innerHTML = rows.map(a => `
        <tr>
            <td>${a.aid}</td>
            <td>${escapeHtml(a.email)}</td>
            <td>${a.fnome ? escapeHtml(a.fnome) : '-'}</td>
            <td>
                <button class="btn btn-sm btn-warning action-btn" onclick="editAccount(${a.aid})">
                    <i class="bi bi-pencil"></i> Modifica
                </button>
                <button class="btn btn-sm btn-danger action-btn" onclick="deleteAccount(${a.aid})">
                    <i class="bi bi-trash"></i> Elimina
                </button>
            </td>
        </tr>
    `).join('');

    renderPagination('account');
}

async function loadAdmins() {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/accounts`, {
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento degli admin');
        }

        const data = await response.json();
        const admins = (data.accounts || []).filter(a => String(a.ruolo || '').toLowerCase() === 'admin');
        setSectionData('admins', admins);

    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento degli admin');
    }
}

function renderAdminsTable() {
    const tbody = document.getElementById('admins-tbody');
    const thead = document.querySelector('#admins-table thead tr');
    const rows = getPagedRows('admins');

    // Render sortable headers - SEMPRE (non solo la prima volta)
    if (thead) {
        const headers = ['aid', 'email', 'ruolo'];
        const labels = ['ID', 'Email', 'Ruolo'];
        thead.innerHTML = labels.map((label, i) => 
            `<th class="sortable" data-sort="${headers[i]}">${label}</th>`
        ).join('');
        
        // Aggiungi sempre i listener
        thead.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                changeSectionSort('admins', th.getAttribute('data-sort'));
            });
        });
        
        // Applica stili immediati
        updateTableHeaderStyles('admins');
    }

    if (adminTableState.admins.filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center">Nessun admin presente</td></tr>';
        renderPagination('admins');
        return;
    }

    tbody.innerHTML = rows.map(a => `
        <tr>
            <td>${a.aid}</td>
            <td>${escapeHtml(a.email)}</td>
            <td><span class="badge bg-danger">${escapeHtml(String(a.ruolo))}</span></td>
        </tr>
    `).join('');

    renderPagination('admins');
}

function openAdminModal() {
    document.getElementById('admin-email-modal').value = '';
    document.getElementById('admin-password-modal').value = '';
    adminModal.show();
}

async function saveAdmin() {
    const email = document.getElementById('admin-email-modal').value.trim();
    const password = document.getElementById('admin-password-modal').value;

    if (!email) {
        showError('Email obbligatoria');
        return;
    }

    if (!password) {
        showError('Password obbligatoria');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/admin/admins`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ email, password }),
        });

        const data = await response.json();
        if (!response.ok) {
            if (response.status === 409) {
                throw new Error('Email già registrata');
            }
            throw new Error(data.error || 'Errore durante la creazione admin');
        }

        showSuccess('Admin creato con successo');
        adminModal.hide();
        await loadAdmins();
        await loadStatistics();
    } catch (error) {
        console.error('Errore:', error);
        showError(error.message || 'Errore durante la creazione admin');
    }
}

async function deleteAccount(aid) {
    if (!confirm('Sei sicuro di voler eliminare questo account?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/admin/accounts/${aid}`, {
            method: 'DELETE',
            credentials: 'include',
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Errore durante l\'eliminazione');
        }

        showSuccess('Account eliminato con successo');
        await loadAccounts();
        await loadAdmins();
        await loadStatistics();

    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

async function loadSupplierOptionsForAccount(selectedFid = '') {
    const select = document.getElementById('account-fid-modal');
    select.innerHTML = '<option value="">Seleziona azienda...</option>';

    const response = await fetch(`${API_BASE_URL}/admin/fornitori`, {
        credentials: 'include',
    });

    if (!response.ok) {
        throw new Error('Errore nel caricamento dei fornitori');
    }

    const data = await response.json();
    const fornitori = data.fornitori || [];

    fornitori.forEach(f => {
        const option = document.createElement('option');
        option.value = String(f.fid);
        option.textContent = `${f.fnome} (#${f.fid})`;
        if (String(selectedFid) === String(f.fid)) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

async function showAddAccountModal() {
    try {
        document.getElementById('accountModalTitle').textContent = 'Aggiungi Account Fornitore';
        document.getElementById('account-aid').value = '';
        document.getElementById('account-email-modal').value = '';
        document.getElementById('account-password-modal').value = '';
        document.getElementById('account-password-hint').textContent = '(Obbligatoria in creazione)';

        await loadSupplierOptionsForAccount();
        accountModal.show();
    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento del form account');
    }
}

async function editAccount(aid) {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/accounts/${aid}`, {
            credentials: 'include',
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Errore nel caricamento account');
        }

        const account = data.account;
        document.getElementById('accountModalTitle').textContent = 'Modifica Account Fornitore';
        document.getElementById('account-aid').value = account.aid;
        document.getElementById('account-email-modal').value = account.email;
        document.getElementById('account-password-modal').value = '';
        document.getElementById('account-password-hint').textContent = '(Lascia vuota per non cambiarla)';

        await loadSupplierOptionsForAccount(account.fid);
        accountModal.show();
    } catch (error) {
        console.error('Errore:', error);
        showError(error.message || 'Errore nel caricamento account');
    }
}

async function saveAccount() {
    const aid = document.getElementById('account-aid').value;
    const email = document.getElementById('account-email-modal').value.trim();
    const password = document.getElementById('account-password-modal').value;
    const fid = Number(document.getElementById('account-fid-modal').value);

    if (!email) {
        showError('Email obbligatoria');
        return;
    }

    if (!fid) {
        showError('Seleziona un fornitore');
        return;
    }

    try {
        const isEdit = aid !== '';
        const url = isEdit ? `${API_BASE_URL}/admin/accounts/${aid}` : `${API_BASE_URL}/admin/accounts`;
        const method = isEdit ? 'PUT' : 'POST';

        const payload = {
            email,
            fid,
        };

        if (isEdit) {
            if (password) {
                payload.password = password;
            }
        } else {
            if (!password) {
                showError('Password obbligatoria in creazione');
                return;
            }
            payload.password = password;
        }

        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.error || 'Errore durante il salvataggio account');
        }

        showSuccess(isEdit ? 'Account fornitore modificato con successo' : 'Account fornitore creato con successo');
        accountModal.hide();
        await loadAccounts();
        await loadAdmins();
        await loadStatistics();
    } catch (error) {
        console.error('Errore:', error);
        showError(error.message || 'Errore durante il salvataggio account');
    }
}

// ==================== Profilo ====================
async function loadProfilo() {
    try {
        clearAdminPasswordFields();

        const response = await fetch(`${API_BASE_URL}/me`, {
            credentials: 'include',
        });

        if (!response.ok) {
            throw new Error('Errore nel caricamento del profilo');
        }

        const data = await response.json();
        document.getElementById('profilo-email').value = data.account.email;

    } catch (error) {
        console.error('Errore:', error);
        showError('Errore nel caricamento del profilo');
    }
}

async function handleProfiloSubmit(event) {
    event.preventDefault();

    const email = document.getElementById('profilo-email').value.trim();
    const oldPassword = document.getElementById('profilo-old-password').value;
    const newPassword = document.getElementById('profilo-new-password').value;
    const confirmPassword = document.getElementById('profilo-confirm-password').value;

    if (!email) {
        showError('Email obbligatoria');
        return;
    }

    // Validazione password
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

    const payload = { email: email };
    if (oldPassword && newPassword) {
        payload.oldPassword = oldPassword;
        payload.newPassword = newPassword;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/me`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.error || 'Errore durante l\'aggiornamento');
        }

        showSuccess('Profilo aggiornato con successo');
        clearAdminPasswordFields();

    } catch (error) {
        console.error('Errore:', error);
        showError(error.message);
    }
}

// ==================== Utilities ====================
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
