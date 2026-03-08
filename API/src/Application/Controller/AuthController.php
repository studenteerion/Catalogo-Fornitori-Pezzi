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
    /**
     * Inietta il repository che gestisce registrazione, login e sessioni.
     */
    public function __construct(private AuthRepository $repository)
    {
    }

    /**
     * Registra un nuovo account fornitore.
     *
     * Flusso:
     * 1) Legge e valida i campi obbligatori dal body JSON.
     * 2) Delega al repository la creazione atomica di fornitore + account.
     * 3) Se presente, salva l'access token in cookie HttpOnly e non lo espone nel JSON.
     *
     * Risposte principali:
     * - 201 in caso di registrazione riuscita.
     * - 409 se email gia registrata.
     * - 422 per payload non valido.
     * - 500 per errori inattesi.
     */
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
            // Crea fornitore + account e restituisce un nuovo token di accesso.
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

    /**
     * Autentica un account esistente tramite email/password.
     *
     * Flusso:
     * 1) Valida credenziali minime nel body.
     * 2) Chiede al repository di verificare password e creare una nuova sessione.
     * 3) In caso di successo salva il token nel cookie HttpOnly e restituisce i dati account.
     *
     * Risposte principali:
     * - 200 login riuscito.
     * - 401 credenziali errate.
     * - 422 campi mancanti/non validi.
     */
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

    /**
     * Rigenera la sessione partendo da un token esistente.
     *
     * Estrae il token da cookie o header Authorization, poi delega al repository
     * la rotazione del token (revoca del precedente e creazione del nuovo).
     *
     * Risposte principali:
     * - 200 token rinnovato.
     * - 401 token assente, invalido o sessione scaduta.
     */
    public function refresh(Request $request, Response $response): Response
    {
        // Supporta auth con priorita' al cookie e fallback su header Authorization.
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

    /**
     * Esegue il logout revocando il token corrente.
     *
     * Oltre alla revoca server-side, imposta un cookie scaduto per rimuovere
     * il token dal browser client.
     *
     * Risposte principali:
     * - 200 logout completato.
     * - 401 token assente o invalido.
     */
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

    /**
     * Legge il body della richiesta e lo converte in array associativo.
     *
     * Se il body e vuoto o JSON malformato, ritorna array vuoto per permettere
     * ai metodi chiamanti di gestire una validazione uniforme.
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
     * Estrae il token di sessione con strategia cookie-first.
     *
     * Ordine di priorita:
     * 1) Cookie `auth_token` (scenario browser).
     * 2) Header `Authorization: Bearer ...` (compatibilita client API).
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
     * Estrae il Bearer token dall'header Authorization.
     *
     * Ritorna null se header assente, formato non valido o token vuoto.
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
     * Scrive una risposta JSON standardizzata.
     *
     * Imposta content-type, serializza i dati in JSON leggibile e applica
     * lo status HTTP richiesto.
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
