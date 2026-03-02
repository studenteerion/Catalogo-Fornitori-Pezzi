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
    public function __construct(
        private AuthRepository $repository,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $token = $this->extractBearerToken($request);

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

        return $handler->handle($request->withAttribute('authAccount', $account));
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        $token = trim((string) ($matches[1] ?? ''));

        return $token === '' ? null : $token;
    }

    /** @param array<string,mixed> $data */
    private function jsonResponse(array $data, int $status): Response
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
