<?php
session_start(); //Pour gerer les sessions utilisateurs, intialiser une session si aucune 
//n'existe encore
//ou reprendre une session existante via un identifiant de session ou souvent stocke dans un cookie
//PHP verifie si un identifiant de session (ex: PHPSESSID) est present cote client
//SI oui il charge les donnees associees a cette session
//sinon il cree une nouvelle session et rend disponible la superglobale $_SESSION

function require_role($role) {
	if (!isset($_SESSION['user_id']) { //isset($_SESSION['']) est une verification d'existance et de non nullite
		//True donc si la cle user_id existe existe et ne vaut pas nulle
		//False si elle n'existe pas ou existe mais nulle
		//N"OUBLIE PAS QU'IL Y A ENCORE UNE NEGATION 

		header('Location: pages/index.html');
		//sert a rediriger le navigateur vers un autre url
		//header envoie un en tete HTTP au navigateur
		//Location est un entete speciale qui indique va a cette adresse
		exit;
	}

	if ($_SESSION['role'] !== $role) {
		//SIgnifie que le role stocke en session est different de la variable role
		header('Location: pages/index.html');
		exit;
	}
}
