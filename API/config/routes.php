<?php

declare(strict_types=1);

use App\Application\Controller\ExerciseController;
use App\Application\Controller\AuthController;
use App\Application\Controller\MeController;
use App\Application\Controller\AdminController;
use App\Application\Security\AuthMiddleware;
use App\Application\Security\RoleMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (
    App $app,
    ExerciseController $controller,
    AuthController $authController,
    MeController $meController,
    AdminController $adminController,
    AuthMiddleware $authMiddleware,
    RoleMiddleware $adminRoleMiddleware
): void {
    $app->get('/', [$controller, 'listQueries']);
    $app->get('/{id:[0-9]+}', [$controller, 'runQuery']);

    $app->group('/auth', function (RouteCollectorProxy $group) use ($authController): void {
        $group->post('/register', [$authController, 'register']);
        $group->post('/login',    [$authController, 'login']);
        $group->post('/refresh',  [$authController, 'refresh']);
        $group->post('/logout',   [$authController, 'logout']);
    });

    $app->group('/me', function (RouteCollectorProxy $group) use ($meController): void {
        $group->get('', [$meController, 'show']);
        $group->patch('', [$meController, 'update']);
        $group->patch('/password', [$meController, 'changePassword']);
        $group->get('/fornitore', [$meController, 'showSupplier']);
        $group->patch('/fornitore', [$meController, 'updateSupplier']);
    })->add($authMiddleware);

    $app->group('/admin', function (RouteCollectorProxy $group) use ($adminController): void {
        $group->get('/fornitori', [$adminController, 'listSuppliers']);
        $group->post('/fornitori', [$adminController, 'createSupplier']);
        $group->patch('/fornitori/{fid:[0-9]+}', [$adminController, 'updateSupplier']);
        $group->delete('/fornitori/{fid:[0-9]+}', [$adminController, 'deleteSupplier']);

        $group->get('/pezzi', [$adminController, 'listParts']);
        $group->post('/pezzi', [$adminController, 'createPart']);
        $group->patch('/pezzi/{pid:[0-9]+}', [$adminController, 'updatePart']);
        $group->delete('/pezzi/{pid:[0-9]+}', [$adminController, 'deletePart']);

        $group->get('/catalogo', [$adminController, 'listCatalog']);
        $group->post('/catalogo', [$adminController, 'createCatalogItem']);
        $group->patch('/catalogo/{fid:[0-9]+}/{pid:[0-9]+}', [$adminController, 'updateCatalogItem']);
        $group->delete('/catalogo/{fid:[0-9]+}/{pid:[0-9]+}', [$adminController, 'deleteCatalogItem']);
    })->add($adminRoleMiddleware)->add($authMiddleware);

};
