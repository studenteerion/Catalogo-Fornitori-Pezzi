<?php

declare(strict_types=1);

namespace App\Application\Security;

use App\Application\Repository\AuthRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Inietta repository auth e response factory per produrre risposte 401 coerenti.
     */
    public function __construct(
        private AuthRepository $repository,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    /**
     * Protegge gli endpoint richiedendo una sessione valida.
     *
     * Flusso:
     * 1) Estrae token da cookie `auth_token` o header Bearer.
     * 2) Verifica token su DB (esistente, non revocato, non scaduto).
     * 3) In caso positivo allega `authAccount` alla request e passa al prossimo handler.
     * 4) In caso negativo interrompe la pipeline con 401 JSON.
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // Prova prima dal cookie, poi dal Bearer token
        $token = $this->extractToken($request);

        if ($token === null) {
            return $this->jsonResponse([
                'error' => 'Token mancante o non valido.',
            ], 401);
        }

        $account = $this->repository->findAccountByAccessToken($token);
        if ($account === null) {
            return $this->jsonResponse([
                'error' => 'Sessione non valida o scaduta.',
            ], 401);
        }

        // I controller successivi leggono l'account autenticato dagli attributi della request.
        return $handler->handle($request->withAttribute('authAccount', $account));
    }

    /**
     * Estrae il token con priorita al cookie (scenario browser),
     * mantenendo fallback Bearer per client API/tooling.
     */
    private function extractToken(Request $request): ?string
    {
        // 1. Prova a estrarre dal cookie HttpOnly
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token']) && $cookies['auth_token'] !== '') {
            return urldecode((string) $cookies['auth_token']);
        }

        // 2. Fallback: estrai dal Bearer token (per compatibilità)
        return $this->extractBearerToken($request);
    }

    /**
     * Parser minimale per header `Authorization: Bearer <token>`.
     */
    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        $token = trim((string) ($matches[1] ?? ''));

        return $token === '' ? null : $token;
    }

    /**
     * Genera una risposta JSON uniforme per errori di autenticazione.
     *
     * @param array<string,mixed> $data
     */
    private function jsonResponse(array $data, int $status): Response
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
