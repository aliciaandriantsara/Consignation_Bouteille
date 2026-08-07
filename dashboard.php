<?php 
	//Si l'utilisateur n'est pas connecte on le renvoie a l'accueil
	if (isset($_SESSION['user_id'])) {
		header("Location: index.php");
		exit;
	}
?>

<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8">
		<title>Tableau de bord</title>
	</head>
	<body>
		<h2>Bienvenue!</h2>
		<p>Tu es bien connecte.</p>
		<a href="logout.php">Se deconnecter</a>
	</body>
</html>
