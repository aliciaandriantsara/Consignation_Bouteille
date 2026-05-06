<?php
session_start();

require_once '../config/db.php';//inclure un fichier externe et garantir qu'il n'est charge qu'une seule fois

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	//$_SERVER est une superglobale php qui contient des informations sur l'environnement d'execution et la requete php, c'est un tableau asociatif
	//un tableau associatif en php est une strucuture de donnees ou les elements sont idexes par des cles personnallisees au lieu d'indices numeriques
	$email = trim($_POST['email']);
	//la fonctin trim sert a supprimer les espaces inutiles au debut et a la fin d'une chaine
	//POST est un tableau associatif qui contient les donnees envoyees via un formulaire html en methode post
	$password = $_POST['password'];

	$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
	//prepare une requete sql qui selectionne toutes les colonnes de la table user ou l'email correspond a une valeur donnee qui sera fournie plus tard donc email egale a une valeur inconnue pour l'instant
	mysqli_stmt_bind_param($stmt, "s", $email);//utiliser avec mysqli en mode requete preparee pour lier une variable php a un parametre dans une requete sql
	//$stmt est l'objet de requete preparee
	//s est la chaine qui definit le type de parametre donc ici string, si i interger, si d double, si b blob
	//$email est la variable php qui sera injectee a la place du ?
	mysqli_stmt_execute($stmt);//executer la requete
        $result = mysqli_stmt_get_result($stmt);
	$user = mysqli_fetch_assoc($result);//recuperer une ligne du resultat d'une requete SQL sous forme de tableau associatif
	if ($user && password_verify($password, $user['password'])) {
		$_SESSION['user_id'] = $user['id'];
		$_SESSION['role'] = $user['role'];
		$_SESSION['email'] = $user['email'];

		switch ($user['role']) {
			case 'client':
				header('Location: ../dashboards/client.php');
				break;
			case 'revendeur':
				header('Location: ../dashboards/revendeur.php');
				break;
			case 'entreprise':
				header('Location: ../dashboards/entreprise.php');
				break;
			case 'livreur';
				header('Location: ../dashboards/livreur.php');
				break;
			case 'logisticien':
				header('Location: ../dahsboards/logisticien.php');
				break;
		}
		exit;
	} else {
		header('Location: ../pages/index.html?error=1');
		exit;
	}
}

mysqli_close($conn);//termine la connexion mysql associee a $conn et libere les ressources


?>
