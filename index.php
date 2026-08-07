<?php
//echo "HTACCESS OK"; test

//Point d'entree unique ou front controller
session_start();

define('ROOT', dirname(_DIR));
define('APP', ROOT . '/app');

require APP . '/config/database.php';
require APP . '/helpers/auth.php';
require APP . '/helpers/response.php';

//Autoload simple des controllers et models
spl_autoload_register(function (string $class): void {
    $paths = [
        APP . '/controllers/' . $class . '.php',
        APP . '/models/'      . $class . '.php',
    ];

    foreach ($paths as $file) {
        if (file_exists($file)) { require $file; return; }
    }
});

//Routing
$uri = trim(parse_url
?>
