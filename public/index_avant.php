<!--
define('DB_HOST', 'localhost');
define('DB_NAME', 'consignation');
define('DB_USER', 'php_user');
define('DB_PASS', 'motdepasse123');
-->

<?php

session_start();//garder les informations sur un utilisateur connecte sauvegarde dans $SESSION quelque part dans un fichier sur l'ordinateur meme si 
//il change de page on se souvient de lui

//definir des constantes
define('ROOT', dirname(__DIR__));//DIR : dossier du fichier actuel, dirname prend le dossier parent du dossier du fichier actuel
define('APP', ROOT . '/app');//creer une constante APP qui contient /var/www/html/consigne/app

//charge les fichiers suivants et execute leur contenu
require APP . '/config/database.php';
require APP . '/helpers/auth.php';
require APP . '/helpers/response.php';

//autoload controllers + models
spl_autoload_register(function (string $class): void { //php charge automatiquement la classe quand elle est utilisee, fonction sans nom, 
    foreach ([APP . '/controllers/', APP . '/models'] as $dir) { //parcourt les dossieres /var/www/html/consigne/app/controllers ou models comme $dir
        $f = $dir . $class . '.php';// construire le nom du fichier
        if (file_exists($f)) { //si le fichier existe charge le fichier et execute son contenu
            require $f; 
            return;
        }
    }
});

//routing
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'); //pour savoir quelle page l'utilisateur veut ouvrir
//$_SERVER contient l'url demande exemple auth/login
//PHP URL PATH : pour extraire la partie de l'url qui correspond au chemin, trim pour enlever les / au debut et a la fin de l'url
//auth/login devient auth/login sans les / au debut et a la fin
$method = $_SERVER['REQUEST_METHOD'];//pour savoir si l'utilisateur a fait une requete GET ou POST
$parts = explode('/', $uri); //transforme auth/login en tableau ['auth', 'login']

$base = strtolower($parts[0] ?? 'auth');
//si valeur absente utilise aut
//convertit la chaine de caractere en miniscule
$controllerName = ucfirst($base) . 'Controller';
//ucfirst : convertit la premiere lettre en majuscule, pour que auth devienne AuthController
$action = $parts[1] ?? 'index';
// si la partie de l'url correspondant a l'action est absente utilise index
$param = $parts[2] ?? null;
//si l'utilisateur n'est pas connecte et qu'il essaie d'acceder a une page autre que auth redirige le vers la page de connexion

//MILA JERENA HOE FA AVY AIZA IZY NO LOGGED IN NA TSY LOGGED IN
//pages publiques
if ($base !== 'auth' && !isLoggedIn()) {//si l'utilisateur n'est pas connecte et qu'il essaie d'acceder a une page autre que auth  il est redirige vers la page de connexion
    redirect('/auth/login');
}

//verifier compte actif
if (isLoggedIn() && (currentUser()['status'] ?? 'actif') !== 'actif') {
    logout();
}

$file = APP . '/controllers/' . $controllerName . '.php';
if (!file_exists($file)) {
    http_response_code(404);
    require APP . 'views/shared/404.php';
    exit;
}

require $file;
if (!class_exists($controllerName)) {
    //teste si la classe existe vraiment dans le fichier
    http_response_code(404);
    require APP . '/views/shared/404.php';
    exit;
}

$ctrl = new $controllerName();
//creattion d'un nouveau controller a partir du nom de la classe
//creer un objet a partir d'une classe
if (!method_exists($ctrl, $action)) {
    http_response_code(404);
    require APP . '/views/shared/404.php';
    exit;
}
$ctrl->$action($param);
//JE NE SAIS PAS DE QUELLE ACTION ON PARLE 

?>