<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Repository\SupplierRepository;
use DomainException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SupplierController
{
    /**
     * Inietta il repository con operazioni dedicate al fornitore autenticato.
     */
    public function __construct(private SupplierRepository $repository)
    {
    }

    /**
     * Restituisce il catalogo del fornitore autenticato.
     *
     * L'identita del fornitore viene ricavata da `authAccount` popolato dal middleware.
     */
    public function listMyCatalog(Request $request, Response $response): Response
    {
        $fid = $this->getSupplierIdFromAuth($request);

        if ($fid === null) {
            return $this->json($response, [
                'error' => 'Fornitore non autenticato.',
            ], 401);
        }

        $catalog = $this->repository->getSupplierCatalog($fid);

        return $this->json($response, [
            'catalogo' => $catalog,
        ]);
    }

    /**
     * Elenca tutti i pezzi disponibili nel sistema.
     *
     * Endpoint utile per consentire al fornitore di selezionare pezzi da inserire in catalogo.
     */
    public function listAllParts(Request $request, Response $response): Response
    {
        $parts = $this->repository->listAllParts();

        return $this->json($response, [
            'pezzi' => $parts,
        ]);
    }

    /**
     * Crea un nuovo pezzo nell'anagrafica pezzi.
     * Valida `pnome` e `colore` prima dell'inserimento.
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
     * Aggiunge un pezzo al catalogo del fornitore autenticato con relativo costo.
     *
     * Richiede `pid` valido e `costo > 0`; conflitti e vincoli dominio
     * vengono restituiti come errori 422.
     */
    public function addToCatalog(Request $request, Response $response): Response
    {
        $fid = $this->getSupplierIdFromAuth($request);

        if ($fid === null) {
            return $this->json($response, [
                'error' => 'Fornitore non autenticato.',
            ], 401);
        }

        $payload = $this->payload($request);
        $pid = (int) ($payload['pid'] ?? 0);
        $costo = (float) ($payload['costo'] ?? 0);

        if ($pid <= 0 || $costo <= 0) {
            return $this->json($response, [
                'error' => 'Campi richiesti: pid (valido), costo (> 0).',
            ], 422);
        }

        try {
            $catalogItem = $this->repository->addToCatalog($fid, $pid, $costo);

            return $this->json($response, [
                'catalogo' => $catalogItem,
            ], 201);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Aggiorna il costo di un pezzo gia presente nel catalogo del fornitore.
     */
    public function updateCatalogItem(Request $request, Response $response, array $args): Response
    {
        $fid = $this->getSupplierIdFromAuth($request);

        if ($fid === null) {
            return $this->json($response, [
                'error' => 'Fornitore non autenticato.',
            ], 401);
        }

        $pid = (int) ($args['pid'] ?? 0);
        $payload = $this->payload($request);
        $costo = (float) ($payload['costo'] ?? 0);

        if ($costo <= 0) {
            return $this->json($response, [
                'error' => 'Il costo deve essere maggiore di 0.',
            ], 422);
        }

        try {
            $catalogItem = $this->repository->updateCatalogItem($fid, $pid, $costo);

            return $this->json($response, [
                'catalogo' => $catalogItem,
            ]);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Rimuove un pezzo dal catalogo del fornitore autenticato.
     * Restituisce 404 se l'elemento non e presente.
     */
    public function removeFromCatalog(Request $request, Response $response, array $args): Response
    {
        $fid = $this->getSupplierIdFromAuth($request);

        if ($fid === null) {
            return $this->json($response, [
                'error' => 'Fornitore non autenticato.',
            ], 401);
        }

        $pid = (int) ($args['pid'] ?? 0);

        try {
            $deleted = $this->repository->removeFromCatalog($fid, $pid);

            if (!$deleted) {
                return $this->json($response, [
                    'error' => 'Elemento del catalogo non trovato.',
                ], 404);
            }

            return $this->json($response, [
                'message' => 'Elemento rimosso dal catalogo.',
            ]);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    // ==================== Helper Methods ====================

    /**
     * Estrae il `fid` dell'utente autenticato dagli attributi request.
     * Ritorna null se assente o non coerente con un account fornitore.
     */
    private function getSupplierIdFromAuth(Request $request): ?int
    {
        // authAccount viene allegato da AuthMiddleware dopo la validazione token.
        $account = $request->getAttribute('authAccount');
        
        if ($account === null || !isset($account['fid'])) {
            return null;
        }

        return (int) $account['fid'];
    }

    /**
     * Converte il body JSON in array associativo.
     * Per body vuoto o JSON non valido ritorna array vuoto.
     */
    private function payload(Request $request): array
    {
        // Parsing JSON difensivo: body vuoto/non valido produce payload vuoto.
        $rawBody = (string) $request->getBody();
        if ($rawBody === '') {
            return [];
        }

        $data = json_decode($rawBody, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Utility di serializzazione JSON usata da tutte le azioni del controller.
     * Accetta anche tipi non-array (`mixed`) per uniformare le risposte.
     */
    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $response->getBody()->write($payload !== false ? $payload : '{}');
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
