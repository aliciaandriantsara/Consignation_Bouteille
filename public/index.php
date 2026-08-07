<?php
session_start();

define('ROOT', dirname(__DIR__));
define('APP',  ROOT . '/app');

require APP . '/config/database.php';
require APP . '/helpers/auth.php';
require APP . '/helpers/response.php';

spl_autoload_register(function (string $class): void {
    foreach ([APP.'/controllers/', APP.'/models/'] as $dir) {
        $f = $dir . $class . '.php';
        if (file_exists($f)) { require $f; return; }
    }
});

// ── Routing corrigé ──────────────────────────────────────
// PATH_INFO contient ce qui est APRÈS index.php
// ex: /index.php/auth/login → PATH_INFO = /auth/login
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
$pathInfo = trim($pathInfo, '/');

// Si vide (accès direct à /index.php ou /), rediriger vers login
if (empty($pathInfo)) {
    redirect('auth/login');
}

$parts  = explode('/', $pathInfo);
$base   = strtolower($parts[0] ?? '');
$action = $parts[1] ?? 'index';
$param  = $parts[2] ?? null;
$ctrl   = ucfirst($base) . 'Controller';

// Vérification session
if ($base !== 'auth' && !isLoggedIn()) {
    redirect('auth/login');
}
if (isLoggedIn() && (currentUser()['statut_compte'] ?? 'actif') !== 'actif') {
    logout();
}

// Charger le controller
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
