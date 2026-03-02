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
    public function __construct(private ExerciseRepository $repository)
    {
    }

    public function health(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'status' => 'ok',
        ]);
    }

    public function listQueries(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'queries' => $this->repository->listQueries(),
        ]);
    }

    public function runQuery(Request $request, Response $response, array $args): Response
    {
        $queryId = (int) ($args['id'] ?? 0);
        $queryParams = $request->getQueryParams();

        $page = isset($queryParams['page'])
            ? filter_var($queryParams['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : null;
        $pageSize = isset($queryParams['pageSize'])
            ? filter_var($queryParams['pageSize'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : null;

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

    /** @param array<string,mixed> $data */
    private function json(Response $response, array $data, int $statusCode = 200): Response
    {
        $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $response->getBody()->write((string) $payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
