<?php
// app/controllers/AuthController.php

class AuthController {

    public function login(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']    ?? '');
            $pass  =      $_POST['password'] ?? '';

            require_once APP.'/models/UtilisateurModel.php';
            $user = (new UtilisateurModel())->findActifByEmail($email);

            if ($user && password_verify($pass, $user['mot_de_passe'])) {
                session_regenerate_id(true);
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user']       = $user;
                redirect($user['role'] . '/dashboard');
            }
            flashSet('error', 'Identifiants incorrects ou compte inactif.');
        }
        view('auth/login');
    }

    public function logout(?string $p = null): void {
        logout();
    }
}
