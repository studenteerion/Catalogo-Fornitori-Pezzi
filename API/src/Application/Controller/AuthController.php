<?php

declare(strict_types=1);

namespace App\Application\Controller;

use App\Application\Repository\AuthRepository;
use DomainException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

final class AuthController
{
    public function __construct(private AuthRepository $repository)
    {
    }

    public function register(Request $request, Response $response): Response
    {
        $payload = $this->payload($request);

        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $fnome = trim((string) ($payload['fnome'] ?? ''));
        $indirizzo = trim((string) ($payload['indirizzo'] ?? ''));

        if ($email === '' || $password === '' || $fnome === '' || $indirizzo === '') {
            return $this->json($response, [
                'error' => 'Campi richiesti: email, password, fnome, indirizzo.',
            ], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, [
                'error' => 'Email non valida.',
            ], 422);
        }

        try {
            $result = $this->repository->registerSupplier([
                'email' => $email,
                'password' => $password,
                'fnome' => $fnome,
                'indirizzo' => $indirizzo,
            ]);

            // Imposta il cookie HttpOnly se il token è disponibile
            if (isset($result['accessToken'])) {
                $response = $response->withHeader(
                    'Set-Cookie',
                    'auth_token=' . urlencode($result['accessToken']) . '; Path=/; HttpOnly; Secure; SameSite=None; Max-Age=86400'
                );
                // Rimuovi il token dal payload JSON
                unset($result['accessToken']);
            }

            return $this->json($response, $result, 201);
        } catch (DomainException $exception) {
            $status = $exception->getMessage() === 'Email già registrata.' ? 409 : 422;
            return $this->json($response, [
                'error' => $exception->getMessage(),
            ], $status);
        } catch (Throwable $exception) {
            return $this->json($response, [
                'error' => 'Errore durante la registrazione.',
                'details' => $exception->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request, Response $response): Response
    {
        $payload = $this->payload($request);

        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json($response, [
                'error' => 'Campi richiesti: email, password.',
            ], 422);
        }

        $result = $this->repository->login($email, $password);
        if ($result === null) {
            return $this->json($response, [
                'error' => 'Credenziali non valide.',
            ], 401);
        }

        // Imposta il cookie HttpOnly se il token è disponibile
        if (isset($result['accessToken'])) {
            $response = $response->withHeader(
                'Set-Cookie',
                'auth_token=' . urlencode($result['accessToken']) . '; Path=/; HttpOnly; Secure; SameSite=None; Max-Age=86400'
            );
            // Rimuovi il token dal payload JSON
            unset($result['accessToken']);
        }

        return $this->json($response, $result);
    }

    public function refresh(Request $request, Response $response): Response
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return $this->json($response, [
                'error' => 'Token mancante o non valido.',
            ], 401);
        }

        $result = $this->repository->refresh($token);
        if ($result === null) {
            return $this->json($response, [
                'error' => 'Sessione non valida o scaduta.',
            ], 401);
        }

        return $this->json($response, $result);
    }

    public function logout(Request $request, Response $response): Response
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return $this->json($response, [
                'error' => 'Token mancante o non valido.',
            ], 401);
        }

        $this->repository->logout($token);

        // Cancella il cookie HttpOnly
        $response = $response->withHeader(
            'Set-Cookie',
            'auth_token=; Path=/; HttpOnly; Secure; SameSite=None; Max-Age=0'
        );

        return $this->json($response, [
            'message' => 'Logout effettuato.',
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
    private function json(Response $response, array $data, int $statusCode = 200): Response
    {
        $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $response->getBody()->write((string) $payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
