<?php
// public/index.php

session_start();

// 1. Définir les constantes de chemin EN PREMIER
define('ROOT', dirname(__DIR__));
define('APP',  ROOT . '/app');

// 2. Charger la config DB
require APP . '/config/database.php';

// 3. Charger les helpers (isLoggedIn() est définie ici)
require APP . '/helpers/auth.php';
require APP . '/helpers/response.php';

// 4. Autoload controllers + models
spl_autoload_register(function (string $class): void {
    foreach ([APP.'/controllers/', APP.'/models/'] as $dir) {
        $f = $dir . $class . '.php';
        if (file_exists($f)) { require $f; return; }
    }
});

// 5. Routing
$uri    = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts  = explode('/', $uri);
$base   = strtolower($parts[0] ?? 'auth');
$ctrl   = ucfirst($base) . 'Controller';
$action = $parts[1] ?? 'index';
$param  = $parts[2] ?? null;

// 6. Vérification session (isLoggedIn() est disponible ici)
if ($base !== 'auth' && !isLoggedIn()) {
    redirect('auth/login');
}

if (isLoggedIn() && (currentUser()['statut_compte'] ?? 'actif') !== 'actif') {
    logout();
}

// 7. Charger et appeler le controller
$file = APP . '/controllers/' . $ctrl . '.php';
if (!file_exists($file)) {
    http_response_code(404);
    require APP . '/views/shared/404.php';
    exit;
}
require $file;
if (!class_exists($ctrl)) {
    http_response_code(404);
    require APP . '/views/shared/404.php';
    exit;
}
$c = new $ctrl();
if (!method_exists($c, $action)) {
    http_response_code(404);
    require APP . '/views/shared/404.php';
    exit;
}
$c->$action($param);