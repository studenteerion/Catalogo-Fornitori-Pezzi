<?php
declare(strict_types=1);

use App\Application\FrontendController;

$frontendController = new FrontendController();

$app->get('/', [$frontendController, 'homepage']);
$app->get('/query/{id:[0-9]+}', [$frontendController, 'querypage']);
$app->get('/fornitore_dashboard', [$frontendController, 'dashboardPage']);
$app->get('/admin_dashboard', [$frontendController, 'adminDashboardPage']);
$app->get('/login', [$frontendController, 'loginPage']);
$app->get('/logout_success', [$frontendController, 'logoutSuccessPage']);