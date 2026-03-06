<?php

declare(strict_types=1);

namespace App\Application\Repository;

use InvalidArgumentException;
use PDO;

final class ExerciseRepository
{
    private const DESCRIPTIONS = [
        1 => 'Pezzi per cui esiste almeno un fornitore',
        2 => 'Fornitori che forniscono ogni pezzo',
        3 => 'Fornitori che forniscono tutti i pezzi rossi',
        4 => 'Pezzi forniti da Acme e da nessun altro',
        5 => 'Fornitori che hanno costo superiore alla media per almeno un pezzo',
        6 => 'Per ciascun pezzo, fornitore/i con costo massimo',
        7 => 'Fornitori che forniscono solo pezzi rossi',
        8 => 'Fornitori con almeno un pezzo rosso e uno verde',
        9 => 'Fornitori con almeno un pezzo rosso o verde',
        10 => 'Pezzi forniti da almeno due fornitori',
    ];

    private const QUERIES = [

        // Questa query restituisce l'elenco dei nomi dei pezzi (p.pnome) che sono venduti
        // da almeno un fornitore, eliminando eventuali duplicati e ordinandoli alfabeticamente.
        //
        // Funzionamento passo-passo:
        // 1. JOIN tra Pezzi e Catalogo su p.pid = c.pid → prende solo i pezzi presenti nel Catalogo.
        // 2. SELECT DISTINCT → evita che un pezzo venduto da più fornitori compaia più volte.
        // 3. ORDER BY p.pnome → ordina i nomi dei pezzi in ordine alfabetico.


        1 => "
            SELECT DISTINCT p.pid, p.pnome AS nome
            FROM Pezzi p
            JOIN Catalogo c ON c.pid = p.pid
            ORDER BY p.pnome
        ",

        // Questa query seleziona i fornitori che forniscono **tutti i pezzi** disponibili.
        //
        // Funzionamento:
        // 1. La subquery interna verifica, per ogni pezzo, se il fornitore lo vende (Catalogo).
        //    Se non lo vende → la riga passa (pezzo non fornito).
        // 2. La subquery esterna raccoglie tutti i pezzi che il fornitore **non fornisce**.
        // 3. Il NOT EXISTS esterno controlla se la lista dei pezzi non forniti è vuota:
        //      - vuota → il fornitore vende tutto → viene restituito
        //      - contiene elementi → fornitore scartato
        //
        // In pratica, la query restituisce i fornitori che coprono l’intero insieme dei pezzi.

        2 => "
            SELECT f.fid, f.fnome AS nome
            FROM Fornitori f
            WHERE NOT EXISTS (
                SELECT 1
                FROM Pezzi p
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM Catalogo c
                    WHERE c.fid = f.fid AND c.pid = p.pid
                )
            )
            ORDER BY f.fnome
        ",

        // Questa query seleziona i nomi dei fornitori che vendono **tutti i pezzi rossi**.
        //
        // Funzionamento:
        // 1. La subquery più interna controlla se il fornitore f vende il pezzo p (Catalogo).
        // 2. La subquery intermedia scorre solo i pezzi rossi e crea la lista di quelli
        //    che il fornitore NON fornisce.
        // 3. Il NOT EXISTS esterno verifica che la lista dei pezzi rossi non forniti sia vuota:
        //      - vuota → il fornitore vende tutti i pezzi rossi → restituito
        //      - non vuota → il fornitore manca almeno un pezzo rosso → scartato
        //
        // In sintesi: restituisce tutti i fornitori che coprono **l’intero insieme dei pezzi rossi**.

        3 => "
            SELECT f.fid, f.fnome AS nome
            FROM Fornitori f
            WHERE NOT EXISTS (
                SELECT 1
                FROM Pezzi p
                WHERE p.colore = 'rosso'
                AND NOT EXISTS (
                    SELECT 1
                    FROM Catalogo c
                    WHERE c.fid = f.fid AND c.pid = p.pid
                )
            )
            ORDER BY f.fnome
        ",

        // Questa query seleziona i nomi dei pezzi (p.pnome) che sono venduti esclusivamente
        // dal fornitore "Acme".
        //
        // Funzionamento:
        // 1. La subquery con EXISTS verifica che il pezzo sia venduto dal fornitore "Acme".
        // 2. La subquery con NOT EXISTS verifica che nessun altro fornitore venda lo stesso pezzo.
        // 3. La condizione combinata (EXISTS AND NOT EXISTS) restituisce solo i pezzi venduti
        //    esclusivamente da Acme.
        // 4. ORDER BY p.pnome ordina alfabeticamente i pezzi selezionati.

        4 => "
            SELECT p.pid, p.pnome AS nome
            FROM Pezzi p
            WHERE EXISTS (
                SELECT 1
                FROM Catalogo c
                JOIN Fornitori f ON f.fid = c.fid
                WHERE c.pid = p.pid AND f.fnome = 'Acme'
            )
            AND NOT EXISTS (
                SELECT 1
                FROM Catalogo c2
                JOIN Fornitori f2 ON f2.fid = c2.fid
                WHERE c2.pid = p.pid AND f2.fnome <> 'Acme'
            )
            ORDER BY p.pnome
        ",

        // Questa query seleziona gli ID dei fornitori (c.fid) che vendono almeno un pezzo a
        // un prezzo superiore alla media dei prezzi di quel pezzo.
        //
        // Funzionamento:
        // 1. La subquery interna calcola la media dei prezzi (AVG(costo)) per il pezzo corrente.
        // 2. La condizione WHERE seleziona solo le righe in cui il costo del fornitore è
        //    maggiore della media del pezzo.
        // 3. DISTINCT evita di riportare lo stesso fornitore più volte se supera la media in
        //    più pezzi.
        // 4. ORDER BY c.fid ordina i fornitori in ordine crescente.

        5 => "
            SELECT DISTINCT c.fid AS id
            FROM Catalogo c
            WHERE c.costo > (
                SELECT AVG(c2.costo)
                FROM Catalogo c2
                WHERE c2.pid = c.pid
            )
            ORDER BY c.fid
        ",

        // Questa query seleziona i pezzi e i fornitori che vendono ogni pezzo al prezzo massimo.
        //
        // Funzionamento:
        // 1. La subquery interna calcola il prezzo massimo (MAX(costo)) per ogni pezzo (pid) e le viene attribuito l'alias m.
        // 2. Il JOIN con la tabella m filtra solo le righe del Catalogo che corrispondono
        //    al prezzo massimo del pezzo.
        // 3. JOIN con Pezzi per ottenere il nome del pezzo (p.pnome).
        // 4. JOIN con Fornitori per ottenere il nome del fornitore (f.fnome).
        // 5. ORDER BY p.pnome, f.fnome ordina alfabeticamente per pezzo e fornitore.
        //
        // Risultato: tutti i fornitori che vendono ciascun pezzo al prezzo massimo disponibile.

        6 => "
            SELECT p.pid, f.fid, p.pnome AS nome_pezzo, f.fnome AS nome_fornitore
            FROM Catalogo c
            JOIN (
                SELECT pid, MAX(costo) AS max_costo
                FROM Catalogo
                GROUP BY pid
            ) m ON m.pid = c.pid AND m.max_costo = c.costo
            JOIN Pezzi p ON p.pid = c.pid
            JOIN Fornitori f ON f.fid = c.fid
            ORDER BY p.pnome, f.fnome
        ",

        // Questa query seleziona gli ID dei fornitori che vendono solo pezzi rossi.
        //
        // Funzionamento:
        // 1. EXISTS interna: verifica che il fornitore abbia almeno un pezzo nel Catalogo.
        // 2. NOT EXISTS interna: verifica che il fornitore **non venda pezzi di colore diverso da rosso**.
        //    - Se la subquery trova anche un solo pezzo non rosso → fornitore scartato.
        // 3. La condizione combinata (EXISTS AND NOT EXISTS) restituisce solo i fornitori
        //    che vendono almeno un pezzo e tutti i pezzi sono rossi.
        // 4. ORDER BY f.fid ordina i fornitori per ID.

        7 => "
            SELECT f.fid AS id
            FROM Fornitori f
            WHERE EXISTS (
                SELECT 1
                FROM Catalogo c
                WHERE c.fid = f.fid
            )
            AND NOT EXISTS (
                SELECT 1
                FROM Catalogo c
                JOIN Pezzi p ON p.pid = c.pid
                WHERE c.fid = f.fid AND p.colore <> 'rosso'
            )
            ORDER BY f.fid
        ",

        // Questa query seleziona gli ID dei fornitori che vendono almeno un pezzo rosso
        // e almeno un pezzo verde.
        //
        // Funzionamento:
        // 1. La prima subquery con EXISTS verifica che il fornitore venda almeno un pezzo rosso.
        // 2. La seconda subquery con EXISTS verifica che il fornitore venda almeno un pezzo verde.
        // 3. La condizione combinata (EXISTS AND EXISTS) restituisce solo i fornitori che vendono
        //    entrambi i colori.
        // 4. ORDER BY f.fid ordina i fornitori per ID.

        8 => "
            SELECT f.fid AS id
            FROM Fornitori f
            WHERE EXISTS (
                SELECT 1
                FROM Catalogo c
                JOIN Pezzi p ON p.pid = c.pid
                WHERE c.fid = f.fid AND p.colore = 'rosso'
            )
            AND EXISTS (
                SELECT 1
                FROM Catalogo c
                JOIN Pezzi p ON p.pid = c.pid
                WHERE c.fid = f.fid AND p.colore = 'verde'
            )
            ORDER BY f.fid
        ",

        // Questa query seleziona gli ID dei fornitori che vendono almeno un pezzo rosso o verde.
        //
        // Funzionamento:
        // 1. JOIN tra Fornitori, Catalogo e Pezzi per avere tutte le combinazioni
        //    (fornitore, pezzo).
        // 2. WHERE p.colore IN ('rosso', 'verde') filtra solo i pezzi rossi o verdi.
        // 3. SELECT DISTINCT f.fid elimina duplicati, così ogni fornitore appare una sola volta.
        // 4. ORDER BY f.fid ordina i fornitori per ID.

        9 => "
            SELECT DISTINCT f.fid AS id
            FROM Fornitori f
            JOIN Catalogo c ON c.fid = f.fid
            JOIN Pezzi p ON p.pid = c.pid
            WHERE p.colore IN ('rosso', 'verde')
            ORDER BY f.fid
        ",

        // Questa query seleziona gli ID dei pezzi venduti da almeno due fornitori diversi.
        //
        // Funzionamento:
        // 1. GROUP BY c.pid raggruppa tutte le righe del Catalogo per pezzo.
        // 2. COUNT(DISTINCT c.fid) conta quanti fornitori diversi vendono ogni pezzo.
        // 3. HAVING COUNT(DISTINCT c.fid) >= 2 mantiene solo i pezzi venduti da almeno 2 fornitori.
        // 4. ORDER BY c.pid ordina i pezzi per ID.

        10 => "
            SELECT c.pid AS id
            FROM Catalogo c
            GROUP BY c.pid
            HAVING COUNT(DISTINCT c.fid) >= 2
            ORDER BY c.pid
        "
    ];

    private const ORDERABLE_COLUMNS = [
        1 => ['pid', 'nome'],
        2 => ['fid', 'nome'],
        3 => ['fid', 'nome'],
        4 => ['pid', 'nome'],
        5 => ['id'],
        6 => ['pid', 'fid', 'nome_pezzo', 'nome_fornitore'],
        7 => ['id'],
        8 => ['id'],
        9 => ['id'],
        10 => ['id'],
    ];

    private const DEFAULT_ORDER = [
        1 => ['nome'],
        2 => ['nome'],
        3 => ['nome'],
        4 => ['nome'],
        5 => ['id'],
        6 => ['nome_pezzo', 'nome_fornitore'],
        7 => ['id'],
        8 => ['id'],
        9 => ['id'],
        10 => ['id'],
    ];

    private const QUERY_ENTITY_HINTS = [
        1 => ['nome' => 'part'],
        2 => ['nome' => 'supplier'],
        3 => ['nome' => 'supplier'],
        4 => ['nome' => 'part'],
        5 => ['id' => 'supplier'],
        6 => ['nome_pezzo' => 'part', 'nome_fornitore' => 'supplier'],
        7 => ['id' => 'supplier'],
        8 => ['id' => 'supplier'],
        9 => ['id' => 'supplier'],
        10 => ['id' => 'part'],
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array{id:int,description:string}> */
    public function listQueries(): array
    {
        $items = [];

        foreach (self::DESCRIPTIONS as $id => $description) {
            $items[] = [
                'id' => $id,
                'description' => $description,
            ];
        }

        return $items;
    }

    public function getSupplierDetailsById(int $fid): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT fid, fnome, indirizzo
             FROM Fornitori
             WHERE fid = :fid
             LIMIT 1'
        );
        $statement->execute(['fid' => $fid]);

        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    public function getPartDetailsById(int $pid): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT pid, pnome, colore
             FROM Pezzi
             WHERE pid = :pid
             LIMIT 1'
        );
        $statement->execute(['pid' => $pid]);

        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /** @return array{description:string,query:string,rows:array<int,array<string,mixed>>,pagination:array<string,int>} */
    public function runQuery(
        int $id,
        ?int $page = null,
        ?int $pageSize = null,
        ?string $orderBy = null,
        ?string $orderDir = null
    ): array
    {
        $query = self::QUERIES[$id] ?? null;
        $description = self::DESCRIPTIONS[$id] ?? null;

        if ($query === null) {
            throw new InvalidArgumentException('Query non valida. Usa un id da 1 a 10.');
        }

        $usePagination = $page !== null && $pageSize !== null;

        $safePage = $usePagination ? max(1, $page) : 1;
        $safePageSize = $usePagination ? max(1, $pageSize) : 0;
        $offset = $usePagination ? (($safePage - 1) * $safePageSize) : 0;

        $allowedColumns = self::ORDERABLE_COLUMNS[$id] ?? [];
        $safeOrderDir = strtoupper((string) $orderDir) === 'DESC' ? 'DESC' : 'ASC';
        $safeOrderBy = null;

        if ($orderBy !== null && in_array($orderBy, $allowedColumns, true)) {
            $safeOrderBy = $orderBy;
        }

        if ($safeOrderBy === null) {
            $defaultOrder = self::DEFAULT_ORDER[$id] ?? [];

            if ($defaultOrder !== []) {
                $safeOrderBy = implode(', ', $defaultOrder);
                $safeOrderDir = 'ASC';
            }
        }

        $countQuery = sprintf('SELECT COUNT(*) AS total FROM (%s) q', $query);
        $countStatement = $this->pdo->query($countQuery);
        $total = (int) ($countStatement->fetchColumn() ?: 0);

        if (!$usePagination) {
            $safePageSize = $total;
            $totalPages = $total === 0 ? 0 : 1;
        } else {
            $totalPages = $total === 0 ? 0 : (int) ceil($total / $safePageSize);
        }

        $paginatedQuery = sprintf('SELECT * FROM (%s) q', $query);

        if ($safeOrderBy !== null) {
            $paginatedQuery .= sprintf(' ORDER BY %s %s', $safeOrderBy, $safeOrderDir);
        }

        if ($usePagination) {
            $paginatedQuery .= sprintf(' LIMIT %d OFFSET %d', $safePageSize, $offset);
        }

        $statement = $this->pdo->query($paginatedQuery);
        $rows = $statement->fetchAll();
        $rows = $this->enrichRows($id, $rows);

        return [
            'description' => $description,
            'query' => $paginatedQuery,
            'rows' => $rows,
            'pagination' => [
                'page' => $safePage,
                'pageSize' => $safePageSize,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
        ];
    }

    /** @param array<int,array<string,mixed>> $rows
     *  @return array<int,array<string,mixed>>
     */
    private function enrichRows(int $queryId, array $rows): array
    {
        $hints = self::QUERY_ENTITY_HINTS[$queryId] ?? [];
        $supplierByIdCache = [];
        $partByIdCache = [];
        $suppliersByNameCache = [];
        $partsByNameCache = [];

        foreach ($rows as $index => $row) {
            $details = [];

            if (array_key_exists('fid', $row) && is_numeric((string) $row['fid'])) {
                $supplier = $this->getSupplierById((int) $row['fid'], $supplierByIdCache);
                if ($supplier !== null) {
                    $details['fornitore'] = $supplier;
                }
            }

            if (array_key_exists('pid', $row) && is_numeric((string) $row['pid'])) {
                $part = $this->getPartById((int) $row['pid'], $partByIdCache);
                if ($part !== null) {
                    $details['pezzo'] = $part;
                }
            }

            if (array_key_exists('id', $row) && is_numeric((string) $row['id'])) {
                $entityType = $hints['id'] ?? null;
                $entityId = (int) $row['id'];

                if ($entityType === 'supplier') {
                    $supplier = $this->getSupplierById($entityId, $supplierByIdCache);
                    if ($supplier !== null) {
                        $details['fornitore'] = $supplier;
                    }
                }

                if ($entityType === 'part') {
                    $part = $this->getPartById($entityId, $partByIdCache);
                    if ($part !== null) {
                        $details['pezzo'] = $part;
                    }
                }
            }

            foreach ($hints as $column => $entityType) {
                if (!is_string($column) || $column === 'id') {
                    continue;
                }

                if ($entityType === 'supplier' && array_key_exists('fornitore', $details)) {
                    continue;
                }

                if ($entityType === 'part' && array_key_exists('pezzo', $details)) {
                    continue;
                }

                if (!array_key_exists($column, $row) || !is_string($row[$column])) {
                    continue;
                }

                $name = trim($row[$column]);
                if ($name === '') {
                    continue;
                }

                if ($entityType === 'supplier') {
                    $matches = $this->findSuppliersByName($name, $suppliersByNameCache);
                    if ($matches !== []) {
                        $details['fornitori_matches'] = $matches;

                        if (count($matches) === 1 && !array_key_exists('fornitore', $details)) {
                            $details['fornitore'] = $matches[0];
                        }

                        $details['fornitore_ref'] = [
                            'status' => count($matches) === 1 ? 'unique' : 'ambiguous',
                            'id' => count($matches) === 1 ? (int) $matches[0]['id'] : null,
                            'count' => count($matches),
                        ];
                    }
                }

                if ($entityType === 'part') {
                    $matches = $this->findPartsByName($name, $partsByNameCache);
                    if ($matches !== []) {
                        $details['pezzi_matches'] = $matches;

                        if (count($matches) === 1 && !array_key_exists('pezzo', $details)) {
                            $details['pezzo'] = $matches[0];
                        }

                        $details['pezzo_ref'] = [
                            'status' => count($matches) === 1 ? 'unique' : 'ambiguous',
                            'id' => count($matches) === 1 ? (int) $matches[0]['id'] : null,
                            'count' => count($matches),
                        ];
                    }
                }
            }

            if ($details !== []) {
                $row['_details'] = $details;
            }

            $rows[$index] = $row;
        }

        return $rows;
    }

    /** @param array<int,array<string,mixed>|null> $cache
     *  @return array<string,mixed>|null
     */
    private function getSupplierById(int $fid, array &$cache): ?array
    {
        if (array_key_exists($fid, $cache)) {
            return $cache[$fid];
        }

        $statement = $this->pdo->prepare('SELECT fid, fnome, indirizzo FROM Fornitori WHERE fid = :fid LIMIT 1');
        $statement->execute(['fid' => $fid]);
        $result = $statement->fetch();

        $cache[$fid] = $result === false ? null : $this->mapSupplierRow($result);
        return $cache[$fid];
    }

    /** @param array<int,array<string,mixed>|null> $cache
     *  @return array<string,mixed>|null
     */
    private function getPartById(int $pid, array &$cache): ?array
    {
        if (array_key_exists($pid, $cache)) {
            return $cache[$pid];
        }

        $statement = $this->pdo->prepare('SELECT pid, pnome, colore FROM Pezzi WHERE pid = :pid LIMIT 1');
        $statement->execute(['pid' => $pid]);
        $result = $statement->fetch();

        $cache[$pid] = $result === false ? null : $this->mapPartRow($result);
        return $cache[$pid];
    }

    /** @param array<string,array<int,array<string,mixed>>> $cache
     *  @return array<int,array<string,mixed>>
     */
    private function findSuppliersByName(string $name, array &$cache): array
    {
        $cacheKey = strtolower($name);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $statement = $this->pdo->prepare(
            'SELECT fid, fnome, indirizzo FROM Fornitori WHERE LOWER(fnome) = LOWER(:nome) ORDER BY fid'
        );
        $statement->execute(['nome' => $name]);

        $rows = $statement->fetchAll();
        $cache[$cacheKey] = array_map(fn (array $row): array => $this->mapSupplierRow($row), $rows);

        return $cache[$cacheKey];
    }

    /** @param array<string,array<int,array<string,mixed>>> $cache
     *  @return array<int,array<string,mixed>>
     */
    private function findPartsByName(string $name, array &$cache): array
    {
        $cacheKey = strtolower($name);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $statement = $this->pdo->prepare(
            'SELECT pid, pnome, colore FROM Pezzi WHERE LOWER(pnome) = LOWER(:nome) ORDER BY pid'
        );
        $statement->execute(['nome' => $name]);

        $rows = $statement->fetchAll();
        $cache[$cacheKey] = array_map(fn (array $row): array => $this->mapPartRow($row), $rows);

        return $cache[$cacheKey];
    }

    /** @param array<string,mixed> $row
     *  @return array<string,mixed>
     */
    private function mapSupplierRow(array $row): array
    {
        return [
            'id' => isset($row['fid']) ? (int) $row['fid'] : null,
            'nome' => (string) ($row['fnome'] ?? ''),
            'indirizzo' => (string) ($row['indirizzo'] ?? ''),
        ];
    }

    /** @param array<string,mixed> $row
     *  @return array<string,mixed>
     */
    private function mapPartRow(array $row): array
    {
        return [
            'id' => isset($row['pid']) ? (int) $row['pid'] : null,
            'nome' => (string) ($row['pnome'] ?? ''),
            'colore' => (string) ($row['colore'] ?? ''),
        ];
    }
}
