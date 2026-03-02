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
    public function __construct(
        private string $requiredRole,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $account = $request->getAttribute('authAccount');
        $role = strtoupper((string) ($account['ruolo'] ?? ''));

        if ($role !== strtoupper($this->requiredRole)) {
            return $this->jsonResponse([
                'error' => 'Permessi insufficienti.',
            ], 403);
        }

        return $handler->handle($request);
    }

    /** @param array<string,mixed> $data */
    private function jsonResponse(array $data, int $status): Response
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
