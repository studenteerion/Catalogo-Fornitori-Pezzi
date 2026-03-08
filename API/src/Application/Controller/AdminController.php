<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Repository\AdminRepository;
use DomainException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AdminController
{
    /**
     * Inietta il repository amministrativo con operazioni CRUD su dominio e account.
     */
    public function __construct(private AdminRepository $repository)
    {
    }

    /**
     * Restituisce l'elenco completo dei fornitori.
     */
    public function listSuppliers(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'fornitori' => $this->repository->listSuppliers(),
        ]);
    }

    /**
     * Restituisce il dettaglio di un fornitore identificato da `fid`.
     * Torna 404 se il record non esiste.
     */
    public function getSupplier(Request $request, Response $response, array $args): Response
    {
        $fid = (int) ($args['fid'] ?? 0);
        $supplier = $this->repository->getSupplierById($fid);

        if ($supplier === null) {
            return $this->json($response, ['error' => 'Fornitore non trovato.'], 404);
        }

        return $this->json($response, ['fornitore' => $supplier]);
    }

    /**
     * Crea un nuovo fornitore.
     * Valida i campi obbligatori (`fnome`, `indirizzo`) prima di delegare al repository.
     */
    public function createSupplier(Request $request, Response $response): Response
    {
        $payload = $this->payload($request);
        $name = trim((string) ($payload['fnome'] ?? ''));
        $address = trim((string) ($payload['indirizzo'] ?? ''));

        if ($name === '' || $address === '') {
            return $this->json($response, [
                'error' => 'Campi richiesti: fnome, indirizzo.',
            ], 422);
        }

        $supplier = $this->repository->createSupplier($name, $address);

        return $this->json($response, [
            'fornitore' => $supplier,
        ], 201);
    }

    /**
     * Aggiorna in modo parziale i dati di un fornitore.
     * Il repository gestisce validazione dei campi aggiornabili e casi di errore dominio.
     */
    public function updateSupplier(Request $request, Response $response, array $args): Response
    {
        $fid = (int) ($args['fid'] ?? 0);

        try {
            // Aggiornamento parziale: il repository valida i campi ammessi e payload vuoti.
            $supplier = $this->repository->updateSupplier($fid, $this->payload($request));

            return $this->json($response, [
                'fornitore' => $supplier,
            ]);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Elimina un fornitore per id.
     * Restituisce 404 se il fornitore non e presente.
     */
    public function deleteSupplier(Request $request, Response $response, array $args): Response
    {
        $fid = (int) ($args['fid'] ?? 0);
        $deleted = $this->repository->deleteSupplier($fid);

        if (!$deleted) {
            return $this->json($response, [
                'error' => 'Fornitore non trovato.',
            ], 404);
        }

        return $this->json($response, [
            'message' => 'Fornitore eliminato.',
        ]);
    }

    /**
     * Restituisce la lista dei pezzi anagrafici.
     */
    public function listParts(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'pezzi' => $this->repository->listParts(),
        ]);
    }

    /**
     * Crea un nuovo pezzo validando nome e colore.
     */
    public function createPart(Request $request, Response $response): Response
    {
        $payload = $this->payload($request);
        $name = trim((string) ($payload['pnome'] ?? ''));
        $color = trim((string) ($payload['colore'] ?? ''));

        if ($name === '' || $color === '') {
            return $this->json($response, [
                'error' => 'Campi richiesti: pnome, colore.',
            ], 422);
        }

        $part = $this->repository->createPart($name, $color);

        return $this->json($response, [
            'pezzo' => $part,
        ], 201);
    }

    /**
     * Aggiorna in modo parziale un pezzo esistente (`pid`).
     */
    public function updatePart(Request $request, Response $response, array $args): Response
    {
        $pid = (int) ($args['pid'] ?? 0);

        try {
            $part = $this->repository->updatePart($pid, $this->payload($request));

            return $this->json($response, [
                'pezzo' => $part,
            ]);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Elimina un pezzo e ritorna 404 se assente.
     */
    public function deletePart(Request $request, Response $response, array $args): Response
    {
        $pid = (int) ($args['pid'] ?? 0);
        $deleted = $this->repository->deletePart($pid);

        if (!$deleted) {
            return $this->json($response, [
                'error' => 'Pezzo non trovato.',
            ], 404);
        }

        return $this->json($response, [
            'message' => 'Pezzo eliminato.',
        ]);
    }

    /**
     * Restituisce la vista completa del catalogo (fornitore-pezzo-costo).
     */
    public function listCatalog(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'catalogo' => $this->repository->listCatalog(),
        ]);
    }

    /**
     * Inserisce una nuova riga nel catalogo (`fid`, `pid`, `costo`).
     * Richiede identificativi validi e costo numerico.
     */
    public function createCatalogItem(Request $request, Response $response): Response
    {
        $payload = $this->payload($request);

        $fid = (int) ($payload['fid'] ?? 0);
        $pid = (int) ($payload['pid'] ?? 0);
        $cost = (float) ($payload['costo'] ?? 0);

        if ($fid <= 0 || $pid <= 0) {
            return $this->json($response, [
                'error' => 'Campi richiesti: fid > 0, pid > 0, costo.',
            ], 422);
        }

        $item = $this->repository->createCatalogItem($fid, $pid, $cost);

        return $this->json($response, [
            'catalogo' => $item,
        ], 201);
    }

    /**
     * Aggiorna il costo di un elemento catalogo identificato da (`fid`, `pid`).
     */
    public function updateCatalogItem(Request $request, Response $response, array $args): Response
    {
        $fid = (int) ($args['fid'] ?? 0);
        $pid = (int) ($args['pid'] ?? 0);
        $payload = $this->payload($request);
        $cost = (float) ($payload['costo'] ?? 0);

        try {
            $item = $this->repository->updateCatalogItem($fid, $pid, $cost);

            return $this->json($response, [
                'catalogo' => $item,
            ]);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 404);
        }
    }

    /**
     * Elimina un elemento dal catalogo tramite chiave composta (`fid`, `pid`).
     */
    public function deleteCatalogItem(Request $request, Response $response, array $args): Response
    {
        $fid = (int) ($args['fid'] ?? 0);
        $pid = (int) ($args['pid'] ?? 0);
        $deleted = $this->repository->deleteCatalogItem($fid, $pid);

        if (!$deleted) {
            return $this->json($response, [
                'error' => 'Record catalogo non trovato.',
            ], 404);
        }

        return $this->json($response, [
            'message' => 'Record catalogo eliminato.',
        ]);
    }

    // ==================== Query ====================

    /**
     * Elenca le query salvate nella tabella `Query`.
     */
    public function listQuery(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'query' => $this->repository->listQuery(),
        ]);
    }

    /**
     * Restituisce una singola query salvata (`qid`).
     */
    public function getQuery(Request $request, Response $response, array $args): Response
    {
        $qid = (int) ($args['qid'] ?? 0);
        $query = $this->repository->getQueryById($qid);

        if ($query === null) {
            return $this->json($response, ['error' => 'Query non trovata.'], 404);
        }

        return $this->json($response, ['query' => $query]);
    }

    /**
     * Crea una nuova query descrittiva.
     * Richiede il campo `descrizione` non vuoto.
     */
    public function createQuery(Request $request, Response $response): Response
    {
        $payload = $this->payload($request);
        $descrizione = trim((string) ($payload['descrizione'] ?? ''));

        if ($descrizione === '') {
            return $this->json($response, [
                'error' => 'Campo richiesto: descrizione.',
            ], 422);
        }

        $query = $this->repository->createQuery($descrizione);

        return $this->json($response, [
            'query' => $query,
        ], 201);
    }

    /**
     * Aggiorna la descrizione di una query esistente.
     */
    public function updateQuery(Request $request, Response $response, array $args): Response
    {
        $qid = (int) ($args['qid'] ?? 0);

        try {
            $query = $this->repository->updateQuery($qid, $this->payload($request));

            return $this->json($response, [
                'query' => $query,
            ]);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Rimuove una query dalla base dati.
     */
    public function deleteQuery(Request $request, Response $response, array $args): Response
    {
        $qid = (int) ($args['qid'] ?? 0);
        $deleted = $this->repository->deleteQuery($qid);

        if (!$deleted) {
            return $this->json($response, [
                'error' => 'Query non trovata.',
            ], 404);
        }

        return $this->json($response, [
            'message' => 'Query eliminata.',
        ]);
    }

    // ==================== Accounts ====================

    /**
     * Elenca tutti gli account (admin e fornitore) con dati essenziali.
     */
    public function listAccounts(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'accounts' => $this->repository->listAccounts(),
        ]);
    }

    /**
     * Restituisce il dettaglio di un account fornitore.
     * Se l'account e admin, il repository genera errore dominio.
     */
    public function getSupplierAccount(Request $request, Response $response, array $args): Response
    {
        $aid = (int) ($args['aid'] ?? 0);

        try {
            $account = $this->repository->getSupplierAccountById($aid);

            if ($account === null) {
                return $this->json($response, [
                    'error' => 'Account non trovato.',
                ], 404);
            }

            return $this->json($response, [
                'account' => $account,
            ]);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Crea un account fornitore collegato a un `fid` esistente.
     * Gestisce validazioni base e conflitti email (409).
     */
    public function createSupplierAccount(Request $request, Response $response): Response
    {
        $payload = $this->payload($request);
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $fid = (int) ($payload['fid'] ?? 0);

        if ($email === '' || $password === '' || $fid <= 0) {
            return $this->json($response, [
                'error' => 'Campi richiesti: email, password, fid.',
            ], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, [
                'error' => 'Email non valida.',
            ], 422);
        }

        try {
            $account = $this->repository->createSupplierAccount($email, $password, $fid);

            return $this->json($response, [
                'account' => $account,
            ], 201);
        } catch (DomainException $exception) {
            $status = $exception->getMessage() === 'Email già registrata.' ? 409 : 422;

            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], $status);
        }
    }

    /**
     * Aggiorna account fornitore (email/fid/password) in modo parziale.
     * Applica una validazione preliminare dell'email a livello controller.
     */
    public function updateSupplierAccount(Request $request, Response $response, array $args): Response
    {
        $aid = (int) ($args['aid'] ?? 0);
        $payload = $this->payload($request);

        // Validazione email anticipata per restituire un 422 chiaro prima del repository.
        if (array_key_exists('email', $payload)) {
            $email = trim((string) $payload['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json($response, [
                    'error' => 'Email non valida.',
                ], 422);
            }
            $payload['email'] = $email;
        }

        try {
            $account = $this->repository->updateSupplierAccount($aid, $payload);

            return $this->json($response, [
                'account' => $account,
            ]);
        } catch (DomainException $exception) {
            $status = $exception->getMessage() === 'Email già registrata.' ? 409 : 422;

            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], $status);
        }
    }

    /**
     * Elimina un account non-admin.
     * La protezione contro la cancellazione admin e applicata nel repository.
     */
    public function deleteAccount(Request $request, Response $response, array $args): Response
    {
        $aid = (int) ($args['aid'] ?? 0);

        try {
            $deleted = $this->repository->deleteAccount($aid);

            if (!$deleted) {
                return $this->json($response, [
                    'error' => 'Account non trovato.',
                ], 404);
            }

            return $this->json($response, [
                'message' => 'Account eliminato.',
            ]);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Crea un nuovo account amministratore.
     */
    public function createAdmin(Request $request, Response $response): Response
    {
        $payload = $this->payload($request);
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json($response, [
                'error' => 'Campi richiesti: email, password.',
            ], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, [
                'error' => 'Email non valida.',
            ], 422);
        }

        try {
            $account = $this->repository->createAdminAccount($email, $password);

            return $this->json($response, [
                'account' => $account,
            ], 201);
        } catch (DomainException $exception) {
            $status = $exception->getMessage() === 'Email già registrata.' ? 409 : 422;

            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], $status);
        }
    }

    // ==================== Helper Methods ====================

    /**
     * Converte il body JSON in array associativo.
     * Ritorna array vuoto per body assente o non valido.
     *
     * @return array<string,mixed>
     */
    private function payload(Request $request): array
    {
        $rawBody = (string) $request->getBody();
        if ($rawBody === '') {
            return [];
        }

        $data = json_decode($rawBody, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Serializza e restituisce una risposta JSON standard del controller.
     *
     * @param array<string,mixed> $data
     */
    private function json(Response $response, array $data, int $statusCode = 200): Response
    {
        $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $response->getBody()->write((string) $payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
