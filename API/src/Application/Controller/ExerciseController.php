<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Repository\ExerciseRepository;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

final class ExerciseController
{
    /**
     * Inietta il repository che contiene query predefinite e lookup di dettaglio.
     */
    public function __construct(private ExerciseRepository $repository)
    {
    }

    /**
     * Endpoint di health-check minimale.
     * Utile per verificare rapidamente che API e routing siano operativi.
     */
    public function health(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'status' => 'ok',
        ]);
    }

    /**
     * Restituisce elenco id/descrizione delle query disponibili.
     */
    public function listQueries(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'queries' => $this->repository->listQueries(),
        ]);
    }

    /**
     * Esegue una query predefinita identificata da `id`.
     *
     * Supporta:
     * - Paginazione opzionale (`page`, `pageSize`).
     * - Ordinamento opzionale (`orderBy`, `orderDir`) validato dal repository.
     *
     * Risposte:
     * - 200 con risultati e metadati di paginazione.
     * - 400 per id query non valido.
     * - 500 per errori non gestiti durante l'esecuzione.
     */
    public function runQuery(Request $request, Response $response, array $args): Response
    {
        $queryId = (int) ($args['id'] ?? 0);
        $queryParams = $request->getQueryParams();

        // I parametri di paginazione sono opzionali, ma se presenti devono essere interi positivi.
        $page = isset($queryParams['page'])
            ? filter_var($queryParams['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : null;
        $pageSize = isset($queryParams['pageSize'])
            ? filter_var($queryParams['pageSize'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : null;

        // L'ordinamento viene sanificato a valle con whitelist colonne per id query.
        $orderBy = isset($queryParams['orderBy']) ? trim((string) $queryParams['orderBy']) : null;
        $orderDir = isset($queryParams['orderDir']) ? trim((string) $queryParams['orderDir']) : null;

        try {
            $result = $this->repository->runQuery(
                $queryId,
                $page === false ? null : $page,
                $pageSize === false ? null : $pageSize,
                $orderBy === '' ? null : $orderBy,
                $orderDir === '' ? null : $orderDir
            );

            return $this->json($response, [
                'results' => $result['rows'],
                'pagination' => $result['pagination'],
                'description' => $result['description'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 400);
        } catch (Throwable $exception) {
            return $this->json($response, [
                'error' => 'Errore durante l\'esecuzione query',
                'details' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Restituisce il dettaglio di un fornitore pubblico per `fid`.
     */
    public function getSupplier(Request $request, Response $response, array $args): Response
    {
        $fid = (int) ($args['fid'] ?? 0);

        try {
            $supplier = $this->repository->getSupplierDetailsById($fid);

            if ($supplier === null) {
                return $this->json($response, [
                    'error' => 'Fornitore non trovato.',
                ], 404);
            }

            return $this->json($response, [
                'fornitore' => $supplier,
            ]);
        } catch (Throwable $exception) {
            return $this->json($response, [
                'error' => 'Errore nel recupero del fornitore.',
                'details' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Restituisce il dettaglio di un pezzo pubblico per `pid`.
     */
    public function getPart(Request $request, Response $response, array $args): Response
    {
        $pid = (int) ($args['pid'] ?? 0);

        try {
            $part = $this->repository->getPartDetailsById($pid);

            if ($part === null) {
                return $this->json($response, [
                    'error' => 'Pezzo non trovato.',
                ], 404);
            }

            return $this->json($response, [
                'pezzo' => $part,
            ]);
        } catch (Throwable $exception) {
            return $this->json($response, [
                'error' => 'Errore nel recupero del pezzo.',
                'details' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Utility locale per produrre risposte JSON con status custom.
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
