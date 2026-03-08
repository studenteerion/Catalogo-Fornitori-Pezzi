<?php

declare(strict_types=1);

namespace App\Application\Security;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RoleMiddleware implements MiddlewareInterface
{
    /**
     * Configura il ruolo richiesto per la sezione protetta.
     */
    public function __construct(
        private string $requiredRole,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    /**
     * Esegue il controllo autorizzativo a livello ruolo.
     *
     * Presuppone che AuthMiddleware abbia gia popolato `authAccount`.
     * Se il ruolo non coincide, ritorna 403 e blocca l'accesso alla route.
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $account = $request->getAttribute('authAccount');
        $role = strtoupper((string) ($account['ruolo'] ?? ''));

        // Controllo ruolo case-insensitive, applicato prima di entrare negli handler protetti.
        if ($role !== strtoupper($this->requiredRole)) {
            return $this->jsonResponse([
                'error' => 'Permessi insufficienti.',
            ], 403);
        }

        return $handler->handle($request);
    }

    /**
     * Genera risposta JSON standard per errori di autorizzazione.
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
