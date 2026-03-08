# Autenticazione e Protezione Endpoint

Questo documento descrive il flusso completo di autenticazione e autorizzazione nell'API, indicando quali funzioni vengono chiamate e in quali file.

## 1. Bootstrap e wiring middleware

File: `API/public/index.php`

Funzioni/azioni principali:
- `PdoFactory::create()` crea la connessione PDO condivisa.
- `AuthSchemaManager::ensure()` garantisce la tabella `AccountSession`.
- Creazione middleware:
  - `new AuthMiddleware($authRepository, $app->getResponseFactory())`
  - `new RoleMiddleware('ADMIN', $app->getResponseFactory())`
  - `new RoleMiddleware('FORNITORE', $app->getResponseFactory())`
- Caricamento route da `API/config/routes.php` tramite closure `$routes(...)`.

## 2. Mappa endpoint pubblici e protetti

File: `API/config/routes.php`

Endpoint pubblici:
- `GET /` -> `ExerciseController::listQueries`
- `GET /{id}` -> `ExerciseController::runQuery`
- `GET /fornitori/{fid}` -> `ExerciseController::getSupplier`
- `GET /pezzi/{pid}` -> `ExerciseController::getPart`
- Gruppo `POST /auth/*`:
  - `/register` -> `AuthController::register`
  - `/login` -> `AuthController::login`
  - `/refresh` -> `AuthController::refresh`
  - `/logout` -> `AuthController::logout`

Endpoint autenticati:
- Gruppo `/me` con `->add($authMiddleware)`
  - `GET /me` -> `MeController::show`
  - `PATCH /me` -> `MeController::update`

Endpoint autenticati + autorizzati per ruolo:
- Gruppo `/admin` con `->add($adminRoleMiddleware)->add($authMiddleware)`
- Gruppo `/supplier` con `->add($supplierRoleMiddleware)->add($authMiddleware)`

Nota su Slim middleware order:
- L'ultimo `add()` e il primo ad essere eseguito.
- Quindi su `/admin` e `/supplier` l'ordine effettivo e:
  1) `AuthMiddleware::process`
  2) `RoleMiddleware::process`
  3) Handler/controller della route

## 3. Flusso autenticazione (login/sessione)

### 3.1 Registrazione

File: `API/src/Application/Controller/AuthController.php`
- `AuthController::register()`
  - Valida payload (`email`, `password`, `fnome`, `indirizzo`)
  - Chiama `AuthRepository::registerSupplier(...)`
  - Se presente `accessToken`, imposta cookie `auth_token` HttpOnly e rimuove il token dal JSON

File: `API/src/Application/Repository/AuthRepository.php`
- `AuthRepository::registerSupplier(...)`
  - Verifica email unica con `findAccountByEmail(...)`
  - Transazione:
    - INSERT in `Fornitori`
    - INSERT in `Account` con password hashata
  - Chiama `buildAuthPayload($aid)`
- `buildAuthPayload(...)`
  - Chiama `createAccessToken(...)`
  - Chiama `getAccountById(...)`
  - Ritorna `accessToken`, `expiresAt`, `account`

### 3.2 Login

File: `API/src/Application/Controller/AuthController.php`
- `AuthController::login()`
  - Valida input
  - Chiama `AuthRepository::login($email, $password)`
  - Imposta cookie `auth_token` se login valido

File: `API/src/Application/Repository/AuthRepository.php`
- `AuthRepository::login(...)`
  - Usa `findAccountByEmail(...)`
  - Verifica password con `password_verify(...)`
  - Se valida, ritorna `buildAuthPayload(...)`

### 3.3 Refresh token

File: `API/src/Application/Controller/AuthController.php`
- `AuthController::refresh()`
  - Estrae token con `extractToken()` (cookie-first, fallback bearer)
  - Chiama `AuthRepository::refresh($token)`

File: `API/src/Application/Repository/AuthRepository.php`
- `AuthRepository::refresh(...)`
  - Verifica token con `findAccountByAccessToken(...)`
  - Revoca token corrente con `revokeToken(...)`
  - Ritorna nuovo payload con `buildAuthPayload(...)`

### 3.4 Logout

File: `API/src/Application/Controller/AuthController.php`
- `AuthController::logout()`
  - Estrae token
  - Chiama `AuthRepository::logout($token)`
  - Scade cookie `auth_token`

File: `API/src/Application/Repository/AuthRepository.php`
- `AuthRepository::logout(...)` -> delega a `revokeToken(...)`

## 4. Come viene protetto un endpoint

### 4.1 AuthMiddleware

File: `API/src/Application/Security/AuthMiddleware.php`
- `AuthMiddleware::process(...)`
  1) Chiama `extractToken(...)`
  2) Verifica token via `AuthRepository::findAccountByAccessToken(...)`
  3) Se valido: `withAttribute('authAccount', $account)` e passa al prossimo handler
  4) Se non valido: risponde `401` con `jsonResponse(...)`

Funzioni interne usate:
- `extractToken(...)`: cookie `auth_token` o bearer
- `extractBearerToken(...)`: parsing header `Authorization`
- `jsonResponse(...)`: risposta JSON uniforme

### 4.2 RoleMiddleware

File: `API/src/Application/Security/RoleMiddleware.php`
- `RoleMiddleware::process(...)`
  1) Legge `authAccount` dalla request
  2) Confronta ruolo account con `$requiredRole`
  3) Se mismatch: `403` con `jsonResponse(...)`
  4) Se ok: passa al prossimo handler

## 5. Dove viene usato `authAccount`

La request attribute `authAccount` viene letta in:
- `API/src/Application/Security/RoleMiddleware.php` -> `RoleMiddleware::process(...)`
- `API/src/Application/Controller/MeController.php` -> `show()`, `update()`
- `API/src/Application/Controller/SupplierController.php` -> `getSupplierIdFromAuth()`

In pratica:
- `AuthMiddleware` autentica e popola il contesto utente.
- `RoleMiddleware` autorizza in base al ruolo.
- I controller usano `authAccount` per applicare logica scoped all'utente autenticato.

## 6. CORS e preflight (supporto al flusso auth da frontend)

File: `API/src/Application/Security/CorsMiddleware.php`
- `CorsMiddleware::process(...)` gestisce preflight `OPTIONS` e header CORS.
- Consente credenziali (`Access-Control-Allow-Credentials: true`), necessario quando il token e in cookie HttpOnly cross-origin.

## 7. Sequenza rapida end-to-end

1. Client chiama `POST /auth/login` con credenziali.
2. `AuthController::login()` valida e chiama `AuthRepository::login()`.
3. Repository crea sessione (`createAccessToken`) e ritorna payload.
4. Controller scrive cookie `auth_token` HttpOnly.
5. Client chiama endpoint protetto (es. `GET /me`).
6. `AuthMiddleware::process()` valida cookie token e inietta `authAccount`.
7. Se route ha controllo ruolo, `RoleMiddleware::process()` verifica `ruolo`.
8. Controller esegue la logica business con i dati utente autenticato.
