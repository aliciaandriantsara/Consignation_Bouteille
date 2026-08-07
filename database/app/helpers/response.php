<?php

function jsonResponse(mixed $data, int $code = 200): void {
    //mixed $data veut dire accepte n'importe quel type de donnees
    //200 signifie le requete a reussi et le serveur a renvoye la reponse attendue
    http_response_code($code);
    //definit le code http envoye au navigateur
    header('Content-Type: application/json; charset=utf-8');
    //Envoie une entete http pour dire la reponse est du json encode en UTF 8
    //sans cela le naviagateur pourrait interpreter la reponse comme du html
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    //transforme les donnees php en json puis les affiche
    //JSON_UNESCAPED evite de transformer les caracteres accentuees en sequences d;unicode
    exit;
}

function view(string $tpl, array $data = []): void {
    //tpl nom de la vue
    //data tableau de donnees envoye a la vue
    extract($data, EXTR_SKIP);
    //transforme les cles du tableau en variables
    //EXTR_SKIP si une variable existe deja ne pas l'ecraser mais ignoree seulement la nouvelle variable
    $file = APP . '/views/' . $tpl . '.php';
    //construit le chemin du fichier de vue 
    if (!file_exists($file)) {
        http_response_code(500);
        die('Vue introuvable : ' . $tpl);
    }
    require $file;//charge et execute le fichier de vue
}

//cree un message temporaire
function flashSet(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    //stocke une information dans la session
    //SESSION est un tableau conserve entre plusieurs pages
}

//retourne le message flash
function flashGet(): ?array {//retourne soit un tableau soit null
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);//supprime le message de la session ainsi il ne sera affiche qu'une seule fois
    return $f;
}
?>