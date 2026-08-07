<?php

//Fonctions d'authentification et de gestion des utilisateurs

function isLoggedIn(): bool {
    return isset($_SESSION['user_email']);
    //contenant les variables SESSION de php lorsqu'une session est demarree a l'aide de start_session() dans public/index.php
    //si la variable SESSION user_email existe alors l'utilisateur est connecte sinon il ne l'est pas
    //isset pour verifier si la variable existe et qu'elle n'est pas null
}

function currentUser(): ?array {
    //la tableau peut retourner un tableau ou null si l'utiisateur n'est pas connecte
    return $_SESSION['user'] ?? null;
}

//cette fonction verifir si l'utilisateur possede un role autorisee
function requireRole(string ...$roles): void {
    //le 3 points signifie un nombre variables d'arguments de type string, on peut appeler la fonction requireRole('admin', 'editor') ou requireRole('client') etc
    $user = currentUser();
    if (!$user || !in_array($user['role'], $roles)) {
        //verifie si le role de l'utilisateur n'est pas dans le tableau des roles 
        http_response_code(403);
        die('Acces refuse - role insuffisant.');
    }
}

function redirect(string $path): void {
    // header('Location: /index.php/' . ltrim($path, '/'));
    header('Location: /index.php?url=' . ltrim($path, '/'));

    //header permet d'envoye une en-tete http
    //ltrim pour enlever les / au debut du path si l'utilisateur a mis /auth/login ou auth/login on veut que ca marche dans les deux cas
    //le navigateur ne vois jamais le chemin physique dans le serveur
    //ce qu'il voit au lieu de cela http://localhost/consigne/public/index.php/auth/login
    //ce principe de mettre /auth/login apres index.php est une technique de routage pour faire croire au navigateur que les pages sont a la racin du site alors qu'elles sont en realites dans le dossier public
    //C'est une fonctionnalite d'apache/php appelee PATH_INFO
    //Quand le navigateur demande http://localhost/consigne/public/index.php/auth/login, apache reconnait que index.php est le fichier reel et que auth/login est juste une information supplementaire collee apres
    //il decoupe automatiquement comme ca : 
    //$_SERVER['SCRIPT_NAME'] = /consigne/public/index.php
    //$_SERVER['PATH_INFO'] = /auth/login
    //Apache a une regle simple : SI L"URL CONTIENT UN FICHIER EXISTANT , TOUT CE QUI VIENT APRES APPARTIENT A CE FICHIER
    //INformation supplementaire transmise au script index.php qui peut ensuite l'utiliser pour determiner quelle page afficher
    //Parametre de chemin 
    //Souvent utiliser dans les frameworks pour faire du routage c'est a dire determiner quelle page afficher en fonction de l'url demandee par l'utilisateur 
    

    exit;
}

function logout(): void {

    session_destroy();
    // detruit la session de l'utilisateur en cours, il est deconnecte
    redirect('auth/login');
    //redirige l'utilisateur vers la page de connexion apres la deconnexion
}
?>