<?php
// Détection auto : si le nom de la base contient "if0_", on est sur InfinityFree
if (strpos($_SERVER['HTTP_HOST'] ?? '', 'infinityfreeapp.com') !== false) {
    define('DB_HOST', 'sql308.infinityfree.com');
    define('DB_NAME', 'if0_42566435_consignation');
    define('DB_USER', 'if0_42566435');
    define('DB_PASS', 'Andmioali9');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'consignation');
    define('DB_USER', 'php_user');
    define('DB_PASS', 'motdepasse123');
}
// define('DB_CHAR', 'utf8mb4');

// ... reste du fichier inchangé
// define('DB_HOST', 'sql308.infinityfree.com');//la base mysql est sur mon ordinateur
// define('DB_NAME', 'if0_42566435_consignation');
// define('DB_USER', 'if0_42566435');
// define('DB_PASS', 'Andmioali9');
// define('DB_CHAR', 'utf8mb4');
//permet de stocker accents emoji caractere speciaux de plusieurs langues
//pour creer la connexion entre php et mysql avec pdo

function getDB(): PDO {
    //Cette fonction retourne un objet PDO
    static $pdo = null;//la premiere fois que la fonction est appelee $pdo est null, les fois suivantes elle contient l'objet PDO cree lors du premier appel
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHAR;
        //Data Source Name une chaine de caractere qui contient les informations necessaires pour se connecter a la base de donnees

        //options pdo pour configurer le comportement de la connexion a la base de donnees
        $opts = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //si erreur sql lancer une exception car sans cela pdo peut cacher les erreurs et rendre le debuggage plus difficile
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  //quand tu recuperes les donnees mysql les donnees seront dans un tableau associatif ou les noms des colonnes sont les cles du tableau
            PDO::ATTR_EMULATE_PREPARES => false, //desactive les fausses requetes preparees de pdo pour utiliser les vraies requetes preparees de mysql qui sont plus surs contre les injections sql
        ];
        try {
            // DEBUG TEMPORAIRE
            die(json_encode([
                'user' => DB_USER,
                'pass_length' => strlen(DB_PASS),
                'pass_hex' => bin2hex(DB_PASS)
            ]));
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        } catch (PDOException $e) { //si erreur dans try executer ce bloc ici $e contient l'erreur 
            http_response_code(500);
            die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
            //die arrete immediatement le script 
            //json_encode transforme le tableau associatif PHP en une chaine de caractere au format json pour que le client puisse comprends l'erreur
        }
    }

    return $pdo;
}
?>