<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Repository\AdminRepository;
use DomainException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AdminController
{
    public function __construct(private AdminRepository $repository)
    {
    }

    public function listSuppliers(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'fornitori' => $this->repository->listSuppliers(),
        ]);
    }

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

    public function updateSupplier(Request $request, Response $response, array $args): Response
    {
        $fid = (int) ($args['fid'] ?? 0);

        try {
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

    public function listParts(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'pezzi' => $this->repository->listParts(),
        ]);
    }

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

    public function listCatalog(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'catalogo' => $this->repository->listCatalog(),
        ]);
    }

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

    /** @return array<string,mixed> */
    private function payload(Request $request): array
    {
        $rawBody = (string) $request->getBody();
        if ($rawBody === '') {
            return [];
        }

        $data = json_decode($rawBody, true);

        return is_array($data) ? $data : [];
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
