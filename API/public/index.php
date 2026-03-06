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

$pdo = (new PdoFactory($settings['db']))->create();
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

$app->add(new CorsMiddleware());
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$authMiddleware = new AuthMiddleware($authRepository, $app->getResponseFactory());
$adminRoleMiddleware = new RoleMiddleware('ADMIN', $app->getResponseFactory());
$supplierRoleMiddleware = new RoleMiddleware('FORNITORE', $app->getResponseFactory());

$routes = require __DIR__ . '/../config/routes.php';
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