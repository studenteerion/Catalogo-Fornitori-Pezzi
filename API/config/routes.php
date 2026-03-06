<?php

declare(strict_types=1);

use App\Application\Controller\ExerciseController;
use App\Application\Controller\AuthController;
use App\Application\Controller\MeController;
use App\Application\Controller\AdminController;
use App\Application\Controller\SupplierController;
use App\Application\Security\AuthMiddleware;
use App\Application\Security\RoleMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (
    App $app,
    ExerciseController $exerciseController,
    AuthController $authController,
    MeController $meController,
    AdminController $adminController,
    SupplierController $supplierController,
    AuthMiddleware $authMiddleware,
    RoleMiddleware $adminRoleMiddleware,
    RoleMiddleware $supplierRoleMiddleware
): void {
    // Catch-all per gestire le richieste OPTIONS (preflight CORS) su TUTTI i percorsi
    $app->options('/{routes:.*}', function ($request, $response) {
        return $response;
    });

    $app->get('/', [$exerciseController, 'listQueries']);
    $app->get('/{id:[0-9]+}', [$exerciseController, 'runQuery']);

    // Rotte pubbliche per dettagli risorse
    $app->get('/fornitori/{fid:[0-9]+}', [$exerciseController, 'getSupplier']);
    $app->get('/pezzi/{pid:[0-9]+}', [$exerciseController, 'getPart']);

    $app->group('/auth', function (RouteCollectorProxy $group) use ($authController): void {
        $group->post('/register', [$authController, 'register']);
        $group->post('/login',    [$authController, 'login']);
        $group->post('/refresh',  [$authController, 'refresh']);
        $group->post('/logout',   [$authController, 'logout']);
    });

    $app->group('/me', function (RouteCollectorProxy $group) use ($meController): void {
        $group->get('', [$meController, 'show']);
        $group->patch('', [$meController, 'update']);
    })->add($authMiddleware);

    $app->group('/admin', function (RouteCollectorProxy $group) use ($adminController): void {
        // Fornitori
        $group->get('/fornitori', [$adminController, 'listSuppliers']);
        $group->post('/fornitori', [$adminController, 'createSupplier']);
        $group->patch('/fornitori/{fid:[0-9]+}', [$adminController, 'updateSupplier']);
        $group->put('/fornitori/{fid:[0-9]+}', [$adminController, 'updateSupplier']);
        $group->delete('/fornitori/{fid:[0-9]+}', [$adminController, 'deleteSupplier']);

        // Pezzi
        $group->get('/pezzi', [$adminController, 'listParts']);
        $group->post('/pezzi', [$adminController, 'createPart']);
        $group->patch('/pezzi/{pid:[0-9]+}', [$adminController, 'updatePart']);
        $group->delete('/pezzi/{pid:[0-9]+}', [$adminController, 'deletePart']);

        // Catalogo
        $group->get('/catalogo', [$adminController, 'listCatalog']);
        $group->post('/catalogo', [$adminController, 'createCatalogItem']);
        $group->patch('/catalogo/{fid:[0-9]+}/{pid:[0-9]+}', [$adminController, 'updateCatalogItem']);
        $group->delete('/catalogo/{fid:[0-9]+}/{pid:[0-9]+}', [$adminController, 'deleteCatalogItem']);

        // Query
        $group->post('/query', [$adminController, 'createQuery']);
        $group->patch('/query/{qid:[0-9]+}', [$adminController, 'updateQuery']);
        $group->put('/query/{qid:[0-9]+}', [$adminController, 'updateQuery']);
        $group->delete('/query/{qid:[0-9]+}', [$adminController, 'deleteQuery']);

        // Accounts
        $group->get('/accounts', [$adminController, 'listAccounts']);
        $group->get('/accounts/{aid:[0-9]+}', [$adminController, 'getSupplierAccount']);
        $group->post('/accounts', [$adminController, 'createSupplierAccount']);
        $group->patch('/accounts/{aid:[0-9]+}', [$adminController, 'updateSupplierAccount']);
        $group->put('/accounts/{aid:[0-9]+}', [$adminController, 'updateSupplierAccount']);
        $group->delete('/accounts/{aid:[0-9]+}', [$adminController, 'deleteAccount']);

        // Admins
        $group->post('/admins', [$adminController, 'createAdmin']);
    })->add($adminRoleMiddleware)->add($authMiddleware);

    $app->group('/supplier', function (RouteCollectorProxy $group) use ($supplierController): void {
        // Catalogo del fornitore
        $group->get('/catalog', [$supplierController, 'listMyCatalog']);
        $group->post('/catalog', [$supplierController, 'addToCatalog']);
        $group->patch('/catalog/{pid:[0-9]+}', [$supplierController, 'updateCatalogItem']);
        $group->delete('/catalog/{pid:[0-9]+}', [$supplierController, 'removeFromCatalog']);

        // Pezzi (lista tutti per selezione, creazione nuovo)
        $group->get('/pezzi', [$supplierController, 'listAllParts']);
        $group->post('/pezzi', [$supplierController, 'createPart']);
    })->add($supplierRoleMiddleware)->add($authMiddleware);

};
