<?php

declare(strict_types=1);

use App\Application\Controller\ExerciseController;
use App\Application\Controller\AdminController;
use App\Application\Controller\MeController;
use App\Application\Repository\AdminRepository;
use App\Application\Repository\ExerciseRepository;
use App\Application\Controller\AuthController;
use App\Application\Repository\AuthRepository;
use App\Application\Security\AuthMiddleware;
use App\Application\Security\RoleMiddleware;
use App\Infrastructure\Database\AuthSchemaManager;
use App\Infrastructure\Database\PdoFactory;
use Slim\Factory\AppFactory;
    
require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/../config/settings.php';

$pdo = (new PdoFactory($settings['db']))->create();
(new AuthSchemaManager($pdo))->ensure();

$exerciseRepository = new ExerciseRepository($pdo);
$exerciseController = new ExerciseController($exerciseRepository);

$authRepository = new AuthRepository($pdo);
$authController = new AuthController($authRepository);
$meController = new MeController($authRepository);

$adminRepository = new AdminRepository($pdo);
$adminController = new AdminController($adminRepository);

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$authMiddleware = new AuthMiddleware($authRepository, $app->getResponseFactory());
$adminRoleMiddleware = new RoleMiddleware('ADMIN', $app->getResponseFactory());

$routes = require __DIR__ . '/../config/routes.php';
$routes(
	$app,
	$exerciseController,
	$authController,
	$meController,
	$adminController,
	$authMiddleware,
	$adminRoleMiddleware
);

$app->run();