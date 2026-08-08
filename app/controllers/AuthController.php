<?php
// app/controllers/AuthController.php

class AuthController
{

    public function login(?string $p = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']    ?? '');
            //trim supprime les espaces au début et à la fin de la chaîne
            $pass  = $_POST['password'] ?? '';

            require_once APP . '/models/UtilisateurModel.php';
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



    public function logout(?string $p = null): void
    {
        logout();
        //fonction php golbale qui detruit toutes les données associées à la session en cours
    }

    public function register(?string $p = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email       = trim($_POST['email'] ?? '');
            $pass        = $_POST['password'] ?? '';
            $passConfirm = $_POST['password_confirm'] ?? '';
            $cin         = trim($_POST['cin'] ?? '');
            $nom         = trim($_POST['nom'] ?? '');
            $prenom      = trim($_POST['prenom'] ?? '');
            $telephone   = trim($_POST['telephone'] ?? '') ?: null;

            require_once APP . '/models/UtilisateurModel.php';
            require_once APP . '/models/ClientModel.php';
            $utilisateurM = new UtilisateurModel();
            $clientM      = new ClientModel();

            // Validation
            $erreurs = [];
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';
            if (strlen($pass) < 8) $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            if ($pass !== $passConfirm) $erreurs[] = 'Les mots de passe ne correspondent pas.';
            if ($cin === '') $erreurs[] = 'Le CIN est obligatoire.';
            if ($nom === '' || $prenom === '') $erreurs[] = 'Nom et prénom sont obligatoires.';

            if (empty($erreurs)) {
                if ($utilisateurM->findByEmail($email)) $erreurs[] = 'Cet email est déjà utilisé.';
                if ($clientM->findByCIN($cin))          $erreurs[] = 'Ce CIN est déjà enregistré.';
            }

            if (!empty($erreurs)) {
                flashSet('error', implode(' ', $erreurs));
                view('auth/register');
                return;
            }

            // Rôle FIXÉ ici en dur, jamais lu depuis $_POST — personne ne peut s'auto-déclarer autre chose que "client"
            $db = getDB();
            try {
                $db->beginTransaction();
                $utilisateurM->creer($email, $pass, 'client');
                $clientM->creer($cin, $email, $nom, $prenom, $telephone);
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                flashSet('error', 'Erreur lors de la création du compte. Réessayez.');
                view('auth/register');
                return;
            }

            flashSet('success', 'Compte créé avec succès ! Vous pouvez vous connecter.');
            redirect('auth/login');
            return;
        }
        view('auth/register');
    }

    public function forgotPassword(?string $p = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email       = trim($_POST['email'] ?? '');
            $cin         = trim($_POST['cin'] ?? '');
            $nom         = trim($_POST['nom'] ?? '');
            $prenom      = trim($_POST['prenom'] ?? '');
            $pass        = $_POST['password'] ?? '';
            $passConfirm = $_POST['password_confirm'] ?? '';

            require_once APP . '/models/UtilisateurModel.php';
            require_once APP . '/models/ClientModel.php';
            $utilisateurM = new UtilisateurModel();
            $clientM      = new ClientModel();

            $erreurs = [];
            if (strlen($pass) < 8) $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            if ($pass !== $passConfirm) $erreurs[] = 'Les mots de passe ne correspondent pas.';

            if (empty($erreurs)) {
                $user   = $utilisateurM->findByEmail($email);
                $client = $clientM->findByCIN($cin);

                // Tous les champs doivent correspondre à LA MÊME fiche client
                // strcasecmp : comparaison insensible à la casse pour nom/prénom (évite de bloquer sur une majuscule mal tapée)
                $valide = $user && $user['role'] === 'client'
                    && $client
                    && $client['email_utilisateur'] === $email
                    && strcasecmp($client['nom'], $nom) === 0
                    && strcasecmp($client['prenom'], $prenom) === 0;

                if (!$valide) $erreurs[] = 'Les informations fournies ne correspondent à aucun compte.';
            }

            if (!empty($erreurs)) {
                flashSet('error', implode(' ', $erreurs));
                view('auth/forgot-password');
                return;
            }

            $utilisateurM->updateMotDePasse($email, $pass);
            flashSet('success', 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');
            redirect('auth/login');
            return;
        }
        view('auth/forgot-password');
    }
}


//Structure MVC (Model-View-Controller) de l'application :
//Les fonctions view() et redirect() savent elle memes comment interpreter les chaines qu'elles contiennent
//C'est la fonction view() qui sait que 'auth/login' correspond au fichier app/views/auth/login.php
//C'est la fonction redirect() qui sait que 'role/dashboard' correspond à l'URL /role/dashboard
