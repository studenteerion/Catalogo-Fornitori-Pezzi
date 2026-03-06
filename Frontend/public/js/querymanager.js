// JavaScript per la gestione delle query con paginazione, ordinamento, ricerca e details in modal

let currentQueryId;
let currentPage = 1;
let currentPageSize = 10;
let currentOrderBy = null;
let currentOrderDir = 'asc';
let allResults = [];
let totalPages = 1;
let totalResults = 0;
let filteredResults = [];
let currentDescription = 'Query risultati';

const ORIGINAL_QUERY_COLUMNS = {
    1: ['nome'],
    2: ['nome'],
    3: ['nome'],
    4: ['nome'],
    5: ['id'],
    6: ['nome_pezzo', 'nome_fornitore'],
    7: ['id'],
    8: ['id'],
    9: ['id'],
    10: ['id']
};

const DETAIL_CLICKABLE_COLUMNS = new Set(['nome', 'id', 'nome_pezzo', 'nome_fornitore']);

document.addEventListener('DOMContentLoaded', function() {
    // Estrai l'ID della query dal titolo della pagina
    const titleText = document.getElementById('page-title').textContent;
    const match = titleText.match(/#(\d+)/);
    currentQueryId = match ? match[1] : null;

    if (!currentQueryId) {
        document.getElementById('error').textContent = 'ID query non trovato';
        return;
    }

    // Event listeners
    document.getElementById('sort-column').addEventListener('change', (e) => {
        currentOrderBy = e.target.value || null;
        currentPage = 1;
        loadQuery();
    });

    document.getElementById('sort-direction').addEventListener('change', (e) => {
        currentOrderDir = e.target.value;
        currentPage = 1;
        loadQuery();
    });

    document.getElementById('page-size').addEventListener('change', (e) => {
        currentPageSize = e.target.value === 'all' ? 999999 : parseInt(e.target.value);
        currentPage = 1;
        loadQuery();
    });

    document.getElementById('table-search').addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        currentPage = 1;
        filterResults(searchTerm);
    });

    loadQuery();
});

async function loadQuery() {
    const loadingElement = document.getElementById('query-subtitle');
    const errorElement = document.getElementById('error');

    try {
        let url = `${API_BASE_URL}/${currentQueryId}?page=${currentPage}&pageSize=${currentPageSize}`;
        
        if (currentOrderBy) {
            url += `&orderBy=${currentOrderBy}&orderDir=${currentOrderDir}`;
        }

        const response = await fetch(url, {
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

        if (data.error) {
            throw new Error(data.error);
        }

        currentDescription = data.description || 'Query risultati';
        // Mostra la description come subtitle
        loadingElement.textContent = currentDescription;
        errorElement.textContent = '';
        allResults = data.results || [];
        filteredResults = allResults;
        totalPages = data.pagination.totalPages;
        totalResults = data.pagination.totalResults;

        // Popola il select di ordinamento con le colonne disponibili
        if (allResults.length > 0) {
            populateSortColumns(getVisibleColumns(allResults[0]));
        }

        renderTable(filteredResults, data.pagination);
        renderPagination(data.pagination);

    } catch (error) {
        console.error('Errore nel caricamento della query:', error);
        loadingElement.textContent = 'Errore nel caricamento';
        errorElement.textContent = `Errore: ${error.message}`;
    }
}

function populateSortColumns(columns) {
    const sortSelect = document.getElementById('sort-column');
    const currentValue = sortSelect.value;
    
    // Se non è già stato compilato, aggiungile
    if (sortSelect.children.length === 1) {
        columns.forEach((col, index) => {
            const option = document.createElement('option');
            option.value = col;
            option.textContent = capitalize(col);
            sortSelect.appendChild(option);
        });
        
        // Seleziona la prima colonna di default
        if (columns.length > 0) {
            sortSelect.value = columns[0];
            currentOrderBy = columns[0];
        }
    }
}

function capitalize(text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

function filterResults(searchTerm) {
    const visibleColumns = allResults.length > 0 ? getVisibleColumns(allResults[0]) : [];

    if (!searchTerm) {
        filteredResults = allResults;
    } else {
        filteredResults = allResults.filter(row => {
            return visibleColumns.some(col => String(row[col] ?? '').toLowerCase().includes(searchTerm));
        });
    }

    // Pagina filtered results
    const pageSize = currentPageSize || 10;
    const pageResults = filteredResults.slice(
        (currentPage - 1) * pageSize,
        currentPage * pageSize
    );

    renderTable(pageResults, {
        page: currentPage,
        totalPages: Math.ceil(filteredResults.length / pageSize),
        totalResults: filteredResults.length
    });

    document.getElementById('search-info').textContent = 
        filteredResults.length === allResults.length 
            ? '' 
            : `${filteredResults.length} risultati trovati`;
}

function renderTable(results, pagination) {
    if (!results || results.length === 0) {
        document.getElementById('table-header').innerHTML = '';
        document.getElementById('table-body').innerHTML = '<tr><td colspan="100%" class="text-center text-muted">Nessun dato disponibile</td></tr>';
        return;
    }

    const columns = getVisibleColumns(results[0]);

    // Crea l'header
    const thead = document.getElementById('table-header');
    thead.innerHTML = columns.map(col => `<th>${escapeHtml(capitalize(col))}</th>`).join('');

    // Crea le righe
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = results.map((row) => {
        const rowData = encodeURIComponent(JSON.stringify(row));
        return `<tr>
            ${columns.map(col => renderCell(rowData, row, col)).join('')}
        </tr>`;
    }).join('');

    document.getElementById('query-subtitle').textContent = currentDescription;
    document.getElementById('search-info').textContent =
        `${pagination.totalResults} risultati totali - Pagina ${pagination.page} di ${pagination.totalPages}`;
}

function renderPagination(pagination) {
    const paginationContainer = document.getElementById('pagination');
    paginationContainer.innerHTML = '';

    // Bottone precedente
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${pagination.page === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = '<a class="page-link" href="#" onclick="goToPage(' + (pagination.page - 1) + '); return false;">Precedente</a>';
    paginationContainer.appendChild(prevLi);

    // Genera range di pagine intelligente (sempre mostra pagina 1 e ultima)
    const pages = new Set();
    pages.add(1); // Sempre prima pagina
    pages.add(pagination.totalPages); // Sempre ultima pagina
    
    // Aggiungi pagine attorno a quella corrente
    for (let i = Math.max(1, pagination.page - 1); i <= Math.min(pagination.totalPages, pagination.page + 1); i++) {
        pages.add(i);
    }
    
    const sortedPages = Array.from(pages).sort((a, b) => a - b);
    
    // Render page numbers con ellissi
    let lastRenderedPage = 0;
    for (const page of sortedPages) {
        if (page - lastRenderedPage > 1) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = '<span class="page-link">...</span>';
            paginationContainer.appendChild(ellipsisLi);
        }
        
        const li = document.createElement('li');
        li.className = `page-item ${page === pagination.page ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${page}); return false;">${page}</a>`;
        paginationContainer.appendChild(li);
        lastRenderedPage = page;
    }

    // Bottone prossimo
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${pagination.page === pagination.totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = '<a class="page-link" href="#" onclick="goToPage(' + (pagination.page + 1) + '); return false;">Prossimo</a>';
    paginationContainer.appendChild(nextLi);
}

function goToPage(page) {
    currentPage = page;
    loadQuery();
}

function renderCell(rowData, row, column) {
    const rawValue = String(row[column] ?? '');
    const safeValue = escapeHtml(rawValue);

    if (!DETAIL_CLICKABLE_COLUMNS.has(column)) {
        return `<td>${safeValue}</td>`;
    }

    return `<td><a href="#" onclick="showEntityDetails(event, '${rowData}', '${column}')">${safeValue}</a></td>`;
}

function showEntityDetails(event, encodedRow, column) {
    event.preventDefault();

    try {
        const row = JSON.parse(decodeURIComponent(encodedRow));
        const details = resolveEntityDetails(row, column);

        if (!details) {
            showDetails(encodedRow);
            return;
        }

        renderDetailModal(details.title, details.data);
    } catch (error) {
        console.error('Errore apertura dettagli:', error);
    }
}

function resolveEntityDetails(row, column) {
    const details = row._details || {};
    const queryId = Number(currentQueryId);

    if (column === 'nome_pezzo') {
        return details.pezzo ? { title: 'Dettagli Pezzo', data: details.pezzo } : null;
    }

    if (column === 'nome_fornitore') {
        return details.fornitore ? { title: 'Dettagli Fornitore', data: details.fornitore } : null;
    }

    if (column === 'nome') {
        if ([1, 4].includes(queryId) && details.pezzo) {
            return { title: 'Dettagli Pezzo', data: details.pezzo };
        }

        if ([2, 3].includes(queryId) && details.fornitore) {
            return { title: 'Dettagli Fornitore', data: details.fornitore };
        }
    }

    if (column === 'id') {
        if ([5, 7, 8, 9].includes(queryId) && details.fornitore) {
            return { title: 'Dettagli Fornitore', data: details.fornitore };
        }

        if (queryId === 10 && details.pezzo) {
            return { title: 'Dettagli Pezzo', data: details.pezzo };
        }

        if (details.fornitore) {
            return { title: 'Dettagli Fornitore', data: details.fornitore };
        }

        if (details.pezzo) {
            return { title: 'Dettagli Pezzo', data: details.pezzo };
        }
    }

    return null;
}

function renderDetailModal(title, data) {
    const detailContent = document.getElementById('detail-content');
    const modalTitle = document.getElementById('detailModalTitle');

    let html = '';
    Object.entries(data).forEach(([key, value]) => {
        html += `
            <div class="mb-2">
                <strong>${escapeHtml(capitalize(key))}:</strong> ${escapeHtml(String(value ?? ''))}
            </div>
        `;
    });

    modalTitle.textContent = title;
    detailContent.innerHTML = html;

    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
}

function showDetails(encodedRow) {
    try {
        const row = JSON.parse(decodeURIComponent(encodedRow));
        const fallbackData = {};
        const visibleColumns = getVisibleColumns(row);

        visibleColumns.forEach((key) => {
            fallbackData[key] = row[key];
        });

        renderDetailModal('Dettagli Riga', fallbackData);
    } catch (error) {
        console.error('Errore nel parsing dei dati:', error);
    }
}

function getVisibleColumns(row) {
    const queryId = Number(currentQueryId);
    const preferredColumns = ORIGINAL_QUERY_COLUMNS[queryId] || [];

    if (preferredColumns.length > 0) {
        return preferredColumns.filter((col) => Object.prototype.hasOwnProperty.call(row, col));
    }

    return Object.keys(row).filter((col) => !col.startsWith('_'));
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

