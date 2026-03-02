<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Repository\AuthRepository;
use DomainException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class MeController
{
    public function __construct(private AuthRepository $repository)
    {
    }

    public function show(Request $request, Response $response): Response
    {
        $authAccount = $request->getAttribute('authAccount');
        $aid = (int) ($authAccount['aid'] ?? 0);

        $account = $this->repository->getAccountById($aid);

        if ($account === null) {
            return $this->json($response, [
                'error' => 'Account non trovato.',
            ], 404);
        }

        return $this->json($response, [
            'account' => $account,
        ]);
    }

    public function update(Request $request, Response $response): Response
    {
        $authAccount = $request->getAttribute('authAccount');
        $aid = (int) ($authAccount['aid'] ?? 0);
        $payload = $this->payload($request);

        try {
            $account = $this->repository->updateAccount($aid, $payload);

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

    public function changePassword(Request $request, Response $response): Response
    {
        $authAccount = $request->getAttribute('authAccount');
        $aid = (int) ($authAccount['aid'] ?? 0);
        $payload = $this->payload($request);

        $oldPassword = (string) ($payload['oldPassword'] ?? '');
        $newPassword = (string) ($payload['newPassword'] ?? '');

        if ($oldPassword === '' || $newPassword === '') {
            return $this->json($response, [
                'error' => 'Campi richiesti: oldPassword, newPassword.',
            ], 422);
        }

        $changed = $this->repository->changePassword($aid, $oldPassword, $newPassword);

        if (!$changed) {
            return $this->json($response, [
                'error' => 'Password attuale non corretta.',
            ], 401);
        }

        return $this->json($response, [
            'message' => 'Password aggiornata con successo.',
        ]);
    }

    public function showSupplier(Request $request, Response $response): Response
    {
        $authAccount = $request->getAttribute('authAccount');
        $aid = (int) ($authAccount['aid'] ?? 0);

        $supplier = $this->repository->getSupplierByAccountId($aid);

        if ($supplier === null) {
            return $this->json($response, [
                'error' => 'Nessun fornitore associato a questo account.',
            ], 404);
        }

        return $this->json($response, [
            'fornitore' => $supplier,
        ]);
    }

    public function updateSupplier(Request $request, Response $response): Response
    {
        $authAccount = $request->getAttribute('authAccount');
        $aid = (int) ($authAccount['aid'] ?? 0);
        $payload = $this->payload($request);

        try {
            $supplier = $this->repository->updateSupplierByAccountId($aid, $payload);

            if ($supplier === null) {
                return $this->json($response, [
                    'error' => 'Nessun fornitore associato a questo account.',
                ], 404);
            }

            return $this->json($response, [
                'fornitore' => $supplier,
            ]);
        } catch (DomainException $exception) {
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], 422);
        }
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
