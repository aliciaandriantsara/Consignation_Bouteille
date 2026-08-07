<?php
//Traitement de l'insciption d'un client
//recoit les donnees POST de register.html et effectue tous les INSERT
//Repond en Json sucess true ou success false message ...


//0.Configuration
//header sert a envoyer des en tetes HTTP au navigateur avant d'envoyer le contenu de la page
header('Content-Type: application/json');//le contenu envoye par le serveur est du json, sans ce header le navigateur pourrait croire que la reponse est du html ou du texte simple
header('X-Content-Type-Options: nosniff');//Cette securite dit au navigateur n'essaie pas de deviner le type du fichier utilise uniquement le content-type fourni

require_once '../config/db.php'; //fournit le $conn (mysqli) donc la connexion a la base de donnees mysql

//1.Verification preliminaire
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['sucess' => false, 'message' => 'Methode non autorisee']);
	//convertit les donnees php en format json
	exit;
}

//fonction utilitaire nettoyer un valeur
function clean(string $val): string {
	return trim(htmlspecialchars($var, ENT_QUOTES, 'UTF-8'));
	//nettoyer une chaine de caractere puis empecher l'injection de code html/javascript
	//htmlspecialchars() transforme certains caracteres speciaux html en entites securisees pour que le navigateur affiche le texte au lieu d'executer un script par exemple
	
}

//fonction utilitaire repond en json et stopper
function respond(bool $sucsess, string $message = '', array $extra = []): void {
	echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));//fusion des 2 tableaux 
	exit;
}

//2. recuperation et validation des donnees
//champ obligateoire
$role = 'client';//toujours fixe cote serveur on accepte pas tout ce qui vient du client
$email = clean($_POST['email'] ?? '');
$password = $_POST['password'] ?? '');
$nom = clean($_POST['nom'] ?? '');
$prenom = clean($POST['prenom'] ?? '');
$cin = clean($_POST['cin'] ?? '');//recupere la valeur envoyee par la formulaire 
//?? '' operateur de coalescence nulle si la valeur existe utilise la sinon utilsie ''


//Validation email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	//filter_var($email, FILTER_VALIDATE_EMAIL) sert a verifier si une variable est valide selon le filtre
	respond(false, 'Adresse email invalide.');
}

//validate mot de passe
if (strlen($password) < 6) {
	respond(false, 'Le mot de passe doit contenir au moins 6 caracteres.');
}

//validation nom / prenoms
if (empty($nom) || empty($prenom)) {
	respond(false, 'Le nom et le prenom sont obligateoire.');
}

//validation cin
if (empty($cin)) {
	respond(false, 'Le numero CIN est obligateoire.');
}

//telephones
$telephones  = [];
if (!empty($_POST['telephone'])) {
	$telephones = json_decode($_POST['telephones'], true);//le true signifie convertit le tableau json en tableau associatif en php
	if (!is_array($telephone) || count($telephones) === 0) { //count sert a compter le nombre d'elements dans un tableau
		respond(false, 'Veuillez renseigner au moin un numero de telephone.');
	}
	foreach ($telephones as $t) {
		if (empty($t['numero'])) {
			respond(false, 'Un numero de telephone est vide.');
		}
	}

}


//adresse json
$adresses = [];
if (!empty($_POST['adresses'])) {
	$adresses = json_decode($_POST['adresses'], true);//convertit le json en tableau associatif en php grace au true
	if (!is_array($adresses) || count($adresses) === 0) {
		respond(false, 'Veuillez renseigner au moins une adresse');
	}
	foreeach ($adresses as $a) {
	if (empty($a['ville'])) {
		respond(false, 'La ville est obligateoire pour chaque adresse.');
		}
	}
}

//Verifier que l'email n'est pas deja utilise
$stmt =  mysqli_prepare($conn, "SELECT id FROM useres WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {	// sert a obtenir le nombre de ligne retournee par une requete preparee
	mysqli_close($stmt);
	respond(false, 'Cette adresse email est deja utilisee.');
}
mysqli_stmt_close($stmt);

//verification que le cin n'est pas deja utilisee
$stmt = mysqli_prepare($conn, "SELECT id FROM clients WHERE cin = ?");
mysqli_stmt_bind_param($stmt, 's', $cin);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
	mysqli_stmt_close($stmt);
	respond(false, 'Ce numero est deja enregistre.');
}
mysqli_stmt_close($stmt);

//Hasahge du mot de passe
$password_hash = password_hash($password, PASSWORD_BCRYPT);

//Generation du code unique du client
//format CLI-timestamp random 6 chars
$qr_code = 'CLI-' . time() . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

//Transactions tous les insert en une seule fois soit reussie ou tout annule
mysqli_begin_transaction($conn);

try {
	//insert dans users
	$stmt =  mysqli_prepare($conn, "INSERT INTO users (email, password, role, created_at) VALUES (?, ?, 'clien', NOW())");
	mysqli_stmt_bind_param($stmt, 's', $email, $password_hash);
	mysqli_execute($stmt);
	$user_id = mysqli_insert_id($conn);
	mysqli_stmt_close($stmt);

	if (!user_id) {
		throw new Exception("Erreur lors de la creation du compe");
	}

	//insert dans noms
	$stmt = mysqli_prepare($conn, 
}
