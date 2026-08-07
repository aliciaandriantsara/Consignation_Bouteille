<?php
//Traitement de l'inscription du client
//Recoit les donnees POST de register.html et effectue tous les insert
//repond en JSON


header('Content-Type: application/json');//indique que le contenu retourne par le serveur est du JSON
header('X-Content-Type-Options: nosniff');//Dis au navigateur n'essaie pas de deviner le type du fichier utilise uniquement le Content-Type fourni


require once '../config/db.php';

//On accepte que les requetes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
	//convertir les donnees php en format JSON
	
	exit;
}

//fonction utilitaire nettoyer une valeur
//nettoyer et securiser une chaine de caractere provenant d'un utulisateur (formulaire, URL, etc)

function clean(string $val): string {	//ici : string est un type de retour de la fonction donc la fonction retournera obligateoirement une chaine de caractere
	return trim(htmlspecialchars($var, ENT_QUOTES, 'UTF-*'));
	//htmlspecialchars() onvertit les caractere html speciaux en entites HTML:w
	//ENT_QUOTES convertit ;=>$#039 et '=>&quot;
	//UTF_8 definit l'encodage utilise
	//trim() supprime les surfaces inutiles
	//sert a nettoyer les entrees utilisateurs et a eviter les attaques XSS et uniformiser les donnees 
}

//fonction utilitaire : repondre en JSON et stopper
function respond(bool $success, string $message = '', array $extra = []): void {
	echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
	exit;
	//sert generalement dans une API PHP pour envoyer une reponse JSON standardisee et arreter immediatement le script
	//$message a pour valeur par defaut ''
	//$extra est un array ou tableau vide par defaut
	//;  void la fonction ne retourne aucune valeur
	//array merge est une fonction qui fusionne plusieurs tableaux
	//json_encode convertit le tableau PHO en JSON
}

$role = 'client';	//toujours fixe cote serveur on n'accepte jamais ce qui vient du client
$email = clean($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$nom = clean($_POST['nom'] ?? '');
$prenom = clean($_POST['prenom'] ?? '');
$cin = clean($_POST['cin'] ?? '');

//validation email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { //filter blabla sert a valider si une chaine est une adresse email valide, il filtre valide et nettoie les donnees ici si $email respecte le format d'une adresse email
	respond(false, 'Adresse email invalide');
}

///Validation du mot de passe
if (strlen($password) < 6 ) {
	respond(false, 'Le mot de passe dit contenir au moins 6 caracteres.');
}

//Validation nom et prenom
if (empty($nom) || empty($prenom)) {
	respond(false, 'Le nom et le prenom sont obligateoire');
}

//Validation CIN
if (empty($cin)) {
	respond(false, 'Le numero cin est obligateoire');
}

//Telephone JSON
$telephone = [];
if (!empty($_POST ['telephones'])) {
	$telephones = jsone_decode($_POST['telephone'], true);//convertir du json en donnees php
	if (!is_array($telephones) || count($telephones) === 0) {
		respond(false, 'Veuillez renseigner au moins un numero de telephone.');
	}
	foreach ($telephone as $t) {
		if (empty($t['numero'])) {
			respond(false, 'Un numero de telephone est vide.');
		}

	}
}

//Adresse JSON
$adresses = [];
if (!empty($_POST['adresses'])) {
	$adresses = json_decode($_POST['adresses'], true);
	if(!is_array($adresses) || count($adresses) === 0) {
		respond(false, 'Veuillez renseigner au moins une adresse.');
	}
	
	foreach ($adresses as $a) {
		if (empty($a['ville'])) {
			respond(false, 'La ville est obligateoire pour chaque adresse.');
		}
	}
}

//Verifier que l'email n'est pas deja utilise
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param$stmt, 's', $email);
//lier une variable php  a un parametre dans une requete sql
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {//sert a obtenir le nombre de ligne retournees par une requete preparee par mysqli
	mysqli_stmt_close($stmt);
	respond(false, 'Cette adresse email est deja utilisee.');
}



}


?>
