<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
define('ROOT', __DIR__);
require ROOT . '/vendor/autoload.php';
define('APP',  ROOT . '/app');

require APP . '/config/database.php';
require APP . '/helpers/session.php';
require APP . '/helpers/auth.php';
require APP . '/helpers/response.php';

// Enregistrer le gestionnaire de session MySQL AVANT session_start()
$handler = new SessionDB();
session_set_save_handler($handler, true);
session_start();

spl_autoload_register(function (string $class): void {
    foreach ([APP.'/controllers/', APP.'/models/'] as $dir) {
        $f = $dir . $class . '.php';
        if (file_exists($f)) { require $f; return; }
    }
});

// $pathInfo = $_SERVER['PATH_INFO'] ?? '';
// $pathInfo = trim($pathInfo, '/');
$pathInfo = $_GET['url'] ?? ($_SERVER['PATH_INFO'] ?? '');
$pathInfo = trim($pathInfo, '/');

if (empty($pathInfo)) {
    redirect('auth/login');
}

$parts  = explode('/', $pathInfo);
$base   = strtolower($parts[0] ?? '');
$action = $parts[1] ?? 'index';
$param  = $parts[2] ?? null;
$ctrl   = ucfirst($base) . 'Controller';

if ($base !== 'auth' && !isLoggedIn()) {
    redirect('auth/login');
}
if (isLoggedIn() && (currentUser()['statut_compte'] ?? 'actif') !== 'actif') {
    logout();
}

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
