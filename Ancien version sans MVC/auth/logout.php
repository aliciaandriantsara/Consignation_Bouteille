<?php
session_start();

// Supprime toutes les variables de session
$_SESSION = array();

// Détruit la session
session_destroy();

// Redirection vers la page d'accueil
header('Location: ../pages/index.html');
exit;
?>
