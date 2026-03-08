<?php

declare(strict_types=1);

use App\Application\Controller\ExerciseController;
use App\Application\Controller\AdminController;
use App\Application\Controller\MeController;
use App\Application\Controller\SupplierController;
use App\Application\Repository\AdminRepository;
use App\Application\Repository\ExerciseRepository;
use App\Application\Repository\SupplierRepository;
use App\Application\Controller\AuthController;
use App\Application\Repository\AuthRepository;
use App\Application\Security\AuthMiddleware;
use App\Application\Security\CorsMiddleware;
use App\Application\Security\RoleMiddleware;
use App\Infrastructure\Database\AuthSchemaManager;
use App\Infrastructure\Database\PdoFactory;
use Slim\Factory\AppFactory;
    
require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/../config/settings.php';

// Istanza PDO condivisa da repository e controller per tutto il ciclo della richiesta.
$pdo = (new PdoFactory($settings['db']))->create();
// Garantisce l'esistenza delle tabelle di auth/sessione prima di gestire richieste.
(new AuthSchemaManager($pdo))->ensure();

$exerciseRepository = new ExerciseRepository($pdo);

$adminRepository = new AdminRepository($pdo);
$adminController = new AdminController($adminRepository);

$supplierRepository = new SupplierRepository($pdo);
$supplierController = new SupplierController($supplierRepository);

$authRepository = new AuthRepository($pdo);
$authController = new AuthController($authRepository);
$meController = new MeController($authRepository);

$exerciseController = new ExerciseController($exerciseRepository, $adminRepository);

$app = AppFactory::create();

// Stack middleware globale: CORS -> parsing body -> routing -> gestione errori.
$app->add(new CorsMiddleware());
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Middleware di sicurezza usati nelle route:
// - AuthMiddleware: valida sessione/token e allega `authAccount` alla request.
// - RoleMiddleware: applica autorizzazione basata su ruolo (`ADMIN`/`FORNITORE`).
$authMiddleware = new AuthMiddleware($authRepository, $app->getResponseFactory());
$adminRoleMiddleware = new RoleMiddleware('ADMIN', $app->getResponseFactory());
$supplierRoleMiddleware = new RoleMiddleware('FORNITORE', $app->getResponseFactory());

$routes = require __DIR__ . '/../config/routes.php';
// La registrazione delle route e' delegata a config/routes.php per mantenere pulito il bootstrap.
$routes(
	$app,
	$exerciseController,
	$authController,
	$meController,
	$adminController,
	$supplierController,
	$authMiddleware,
	$adminRoleMiddleware,
	$supplierRoleMiddleware
);

$app->run();