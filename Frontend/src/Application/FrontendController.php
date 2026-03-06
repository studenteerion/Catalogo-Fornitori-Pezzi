<?php

declare(strict_types=1);

namespace App\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FrontendController
{
    private const API_BASE_URL = 'http://localhost:8000';

    private function frontendAuthToken(Request $request): ?string
    {
        $cookies = $request->getCookieParams();
        $token = (string) ($cookies['auth_token'] ?? '');

        return $token === '' ? null : $token;
    }

    private function renderTemplate(Response $response, string $templateName, array $replacements = []): Response
    {
        $path = __DIR__ . '/../../templates/' . $templateName;

        if (!file_exists($path)) {
            $response->getBody()->write("Errore: File {$templateName} non trovato in {$path}");
            return $response->withStatus(404);
        }

        $html = file_get_contents($path);

        foreach ($replacements as $placeholder => $value) {
            $html = str_replace($placeholder, (string) $value, $html);
        }

        $response->getBody()->write($html);
        return $response;
    }

    private function redirect(Response $response, string $path): Response
    {
        return $response
            ->withHeader('Location', $path)
            ->withStatus(302);
    }

    /** @return array<string,mixed>|null */
    private function fetchCurrentAccount(string $token): ?array
    {
        $curl = curl_init(rtrim(self::API_BASE_URL, '/') . '/me');

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 2);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Cookie: auth_token=' . $token,
        ]);

        $raw = curl_exec($curl);

        if ($raw === false) {
            curl_close($curl);
            return null;
        }

        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($status !== 200) {
            return null;
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded) || !isset($decoded['account']) || !is_array($decoded['account'])) {
            return null;
        }

        return $decoded['account'];
    }

    /**
     * Carica la pagina di login.
     */
    public function loginPage(Request $request, Response $response): Response
    {
        return $this->renderTemplate($response, 'login.php');
    }

    /**
     * Carica la homepage con le query.
     */
    public function homepage(Request $request, Response $response): Response
    {
        return $this->renderTemplate($response, 'index.html');
    }

    /**
     * Carica la pagina di dettaglio per una specifica query (1-10).
     */
    public function querypage(Request $request, Response $response, array $args): Response
    {
        $queryId = $args['id'];

        return $this->renderTemplate($response, 'query_view.html', [
            '{{QUERY_ID}}' => $queryId,
        ]);
    }

    /**
     * Carica la dashboard del fornitore.
     */
    public function dashboardPage(Request $request, Response $response): Response
    {
        $token = $this->frontendAuthToken($request);
        if ($token === null) {
            return $this->renderTemplate($response, 'fornitore_dashboard.html');
        }

        $account = $this->fetchCurrentAccount($token);

        if ($account === null) {
            return $this->redirect($response, '/login');
        }

        $ruolo = strtolower((string) ($account['ruolo'] ?? ''));
        if ($ruolo === 'admin') {
            return $this->redirect($response, '/admin_dashboard');
        }

        if ($ruolo !== 'fornitore') {
            return $this->redirect($response, '/login');
        }

        return $this->renderTemplate($response, 'fornitore_dashboard.html');
    }

    /**
     * Carica la dashboard admin.
     */
    public function adminDashboardPage(Request $request, Response $response): Response
    {
        $token = $this->frontendAuthToken($request);
        if ($token === null) {
            return $this->renderTemplate($response, 'admin_dashboard.html');
        }

        $account = $this->fetchCurrentAccount($token);

        if ($account === null) {
            return $this->redirect($response, '/login');
        }

        $ruolo = strtolower((string) ($account['ruolo'] ?? ''));
        if ($ruolo === 'fornitore') {
            return $this->redirect($response, '/fornitore_dashboard');
        }

        if ($ruolo !== 'admin') {
            return $this->redirect($response, '/login');
        }

        return $this->renderTemplate($response, 'admin_dashboard.html');
    }

    /**
     * Carica la pagina di logout success.
     */
    public function logoutSuccessPage(Request $request, Response $response): Response
    {
        return $this->renderTemplate($response, 'logout_success.html');
    }
}
