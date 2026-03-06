# Catalogo Fornitori Pezzi

Applicazione web full-stack per la gestione di fornitori, pezzi e catalogo prezzi.

Il progetto e diviso in due servizi Slim 4:
- `API/`: backend REST JSON con autenticazione e ruoli
- `Frontend/`: server web che espone le pagine HTML/CSS/JS

## Architettura

- `API` gira su `http://localhost:8000`
- `Frontend` gira su `http://localhost:8080`
- Database: MariaDB/MySQL (schema principale in `gestione_fornitori.sql`)

Le pagine Frontend effettuano chiamate HTTP all'API (CORS abilitato nel middleware backend).

## Struttura progetto

- `gestione_fornitori.sql`: schema e dati di esempio del database
- `start.sh`: avvio rapido di Apache, MariaDB, Frontend e API
- `API/`
- `API/public/index.php`: bootstrap backend
- `API/config/routes.php`: rotte REST
- `API/config/settings.php`: configurazione connessione database
- `API/src/Application/Controller/`: controller (`Auth`, `Admin`, `Supplier`, `Me`, `Exercise`)
- `API/src/Application/Repository/`: accesso dati SQL
- `Frontend/`
- `Frontend/public/index.php`: bootstrap frontend
- `Frontend/config/routes.php`: rotte pagine
- `Frontend/src/Application/FrontendController.php`: rendering template e redirect per ruolo
- `Frontend/public/js/`: logica client (login, dashboard, query, admin)
- `Frontend/templates/`: template HTML/PHP

## Requisiti

- PHP (consigliato 8.x)
- Estensioni PHP: `pdo_mysql`, `curl`
- Composer
- MariaDB o MySQL

## Setup locale

1. Installa dipendenze Composer in entrambi i servizi:

```bash
cd API && composer install
cd ../Frontend && composer install
```

2. Crea il database e importa lo schema:

```bash
mysql -u <utente> -p < gestione_fornitori.sql
```

3. Configura le credenziali DB backend in `API/config/settings.php`:
- `dsn` (host, porta, nome db)
- `user`
- `pass`

4. Verifica la base URL API lato frontend in `Frontend/public/js/config.js`.
Per sviluppo locale, usa tipicamente:

```js
const API_BASE_URL = 'http://localhost:8000';
```

5. Avvia i servizi.

Opzione A, script unico:

```bash
./start.sh
```

Opzione B, manuale in due terminali:

```bash
php -S localhost:8000 -t API/public
php -S localhost:8080 -t Frontend/public
```

6. Apri il frontend su `http://localhost:8080`.

## Autenticazione e sessione

- Login e registrazione sono gestiti da `API/src/Application/Controller/AuthController.php`.
- La sessione e mantenuta con cookie `auth_token` (`HttpOnly`, `Secure`, `SameSite=None`).
- Le chiamate fetch dal frontend usano `credentials: 'include'`.
- L'accesso alle rotte protette e gestito da middleware:
- `AuthMiddleware` per autenticazione
- `RoleMiddleware` per autorizzazione per ruolo (`ADMIN`, `FORNITORE`)

## Rotte principali API

Base URL: `http://localhost:8000`

Pubbliche:
- `GET /` elenco query disponibili
- `GET /{id}` esegue query con id numerico
- `GET /fornitori/{fid}` dettaglio fornitore
- `GET /pezzi/{pid}` dettaglio pezzo
- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/refresh`
- `POST /auth/logout`

Protette (utente autenticato):
- `GET /me`
- `PATCH /me`

Protette ruolo `ADMIN` (`/admin/...`):
- CRUD fornitori
- CRUD pezzi
- CRUD catalogo
- CRUD query
- CRUD account e creazione admin

Protette ruolo `FORNITORE` (`/supplier/...`):
- gestione catalogo personale
- consultazione/creazione pezzi

Riferimento completo: `API/config/routes.php`.

## Rotte principali Frontend

Base URL: `http://localhost:8080`

- `/` homepage
- `/query/{id}` pagina dettaglio query
- `/login` login/registrazione
- `/fornitore_dashboard` dashboard fornitore
- `/admin_dashboard` dashboard admin
- `/logout_success` pagina conferma logout

Riferimento completo: `Frontend/config/routes.php`.

## Note operative

- `API/public/index.php` invoca `AuthSchemaManager` all'avvio per garantire la presenza delle tabelle di autenticazione.
- Se usi HTTPS/remote dev environment, mantieni configurazioni coerenti con cookie `Secure` e CORS.
- In locale puro HTTP, il comportamento dei cookie `Secure` dipende dall'ambiente/browser.