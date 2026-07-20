<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// $routes->get('/', 'Home::index');

$routes->get('/', 'AuthController::showLogin');
$routes->get('login', 'AuthController::showLogin');
$routes->post('login', 'AuthController::login');
$routes->post('/', 'AuthController::login');
$routes->get('logout', 'AuthController::logout');

$routes->group('admin', ['filter' => 'authAdmin'], function ($routes) {
    $routes->get('/', 'AdminController::dashboard');
    $routes->get('prefixes', 'AdminController::prefixes');
    $routes->post('prefixes/store', 'AdminController::storePrefixe');
    $routes->post('prefixes/update/(:num)', 'AdminController::updatePrefixe/$1');
    $routes->post('prefixes/delete/(:num)', 'AdminController::deletePrefixe/$1');
    $routes->get('baremes', 'AdminController::baremes');
    $routes->post('baremes/store', 'AdminController::storeBareme');
    $routes->post('baremes/update/(:num)', 'AdminController::updateBareme/$1');
    $routes->post('baremes/delete/(:num)', 'AdminController::deleteBareme/$1');
    $routes->get('comptes', 'AdminController::comptes');
    $routes->get('transactions', 'AdminController::transactions');
});

$routes->group('client', ['filter' => 'authClient:client'], function ($routes) {
    $routes->get('/', 'ClientController::dashboard');
    $routes->get('depot', 'ClientController::depot');
    $routes->post('depot', 'ClientController::doDepot');
    $routes->get('retrait', 'ClientController::retrait');
    $routes->post('retrait', 'ClientController::doRetrait');
    $routes->get('transfert', 'ClientController::transfert');
    $routes->post('transfert', 'ClientController::doTransfert');
    $routes->get('historique', 'ClientController::historique');
});

// Endpoint AJAX consommé par previewFrais() dans public/assets/js/client.js
// Accessible aux clients ET admins connectés (retrait/transfert côté client,
// aperçu éventuel côté formulaires admin pour les barèmes)
$routes->group('api', ['filter' => 'authClient:client'], function ($routes) {
    $routes->post('calculer-frais', 'Api\FraisController::calculer');
});