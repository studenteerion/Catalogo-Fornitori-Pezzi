<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Repository\AuthRepository;
use DomainException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class MeController
{
    /**
     * Inietta il repository auth per leggere/aggiornare l'account autenticato.
     */
    public function __construct(private AuthRepository $repository)
    {
    }

    /**
     * Restituisce il profilo dell'utente autenticato (`/me`).
     * L'id account viene letto dall'attributo `authAccount` impostato dal middleware.
     */
    public function show(Request $request, Response $response): Response
    {
        // authAccount viene iniettato da AuthMiddleware dopo la validazione del token.
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

    /**
     * Aggiorna i dati del profilo autenticato (email, password, dati fornitore).
     * Le regole di dominio sono centralizzate nel repository.
     */
    public function update(Request $request, Response $response): Response
    {
        // Gli aggiornamenti sono sempre limitati all'account autenticato corrente.
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

    /**
     * Converte il body JSON in array associativo.
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
     * Serializza e scrive una risposta JSON standardizzata.
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
