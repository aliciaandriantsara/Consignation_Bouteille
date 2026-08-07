<?php
// app/controllers/AuthController.php

class AuthController {

    public function login(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']    ?? '');
            //trim supprime les espaces au début et à la fin de la chaîne
            $pass  = $_POST['password'] ?? ''; 

            require_once APP.'/models/UtilisateurModel.php';
            //charge le fichier UtilisateurModel.php pour pouvoir utiliser la classe UtilisateurModel

            $user = (new UtilisateurModel())->findActifByEmail($email);
            //trouver l'utilisateur actif par email

            if ($user && password_verify($pass, $user['mot_de_passe'])) {
                //password_verify vérifie si le mot de passe correspond au hash stocké dans la base de données
                session_regenerate_id(true);
                //Lorsque l'utilisateur se connecte , php utilise un identifiant de session pour suivre l'etat de la session 
                //cette fonction cree un nouvel identifiant de session pour l'utiilisateur et supprime l'ancien identifiant de session pour eviter les attaques de fixations de sessions
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user']       = $user;
                //on enregistre tout le tableau dans la variable de session $_SESSION['user'] pour pouvoir acceder a toutes les informations de l'utilisateur connecté
                redirect($user['role'] . '/dashboard');
                //envoie le navigateur vers le tableau de bord correspondant au role de l'utilisateur connecte 

            }
            flashSet('error', 'Identifiants incorrects ou compte inactif.');
            //si la connexion echoue on enregistre un message d'erreur dans la session pour l'afficher sur la page de connexion lorsqu'elle sera rechargee
        }
        view('auth/login');
        //charge la vue de connexion pour que l'utilisateur puisse saisir ses identifiants lorsque l'utilisateur arrive sur la page avec la mehode get ou que les identifiants sont incorrects avec un message d'erreur temporaire
    }

//     Utilisateur ouvre /login
//         │
//         ▼
// Méthode GET ?
//         │
//         ├── Oui
//         │      ▼
//         │  Afficher le formulaire
//         │
//         ▼
// Utilisateur remplit le formulaire
//         │
//         ▼
// Méthode POST
//         │
//         ▼
// Récupération email + mot de passe
//         │
//         ▼
// Recherche de l'utilisateur actif par email
//         │
//         ▼
// Utilisateur trouvé ?
//         │
//    ┌────┴────┐
//    │         │
//  Non        Oui
//    │         │
//    ▼         ▼
// Erreur   Vérification du mot de passe
//              │
//         ┌────┴────┐
//         │         │
//       Faux      Vrai
//         │         │
//         ▼         ▼
//    Message    Régénération de la session
//    d'erreur        │
//                    ▼
//           Stockage des informations
//           dans `$_SESSION`
//                    │
//                    ▼
//       Redirection vers `role/dashboard`



    public function logout(?string $p = null): void {
        logout();
        //fonction php golbale qui detruit toutes les données associées à la session en cours
    }
}


//Structure MVC (Model-View-Controller) de l'application :
//Les fonctions view() et redirect() savent elle memes comment interpreter les chaines qu'elles contiennent
//C'est la fonction view() qui sait que 'auth/login' correspond au fichier app/views/auth/login.php
//C'est la fonction redirect() qui sait que 'role/dashboard' correspond à l'URL /role/dashboard
