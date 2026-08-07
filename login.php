<?php
session_start();

//Connexion MySQL avec mysqli
$conn =  mysqli_connect('localhost', 'php_user', 'motdepasse123', 'consignation');

if (!$conn) {
	die("Erreur de connexion" . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email']);
	$password = $_POST['password'];

	//Requete preparee avec mysqli
	$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
	mysqli_stmt_bind_param($stmt, "s", $email);
	mysqli_stmt_execute($stmt);

	$result =  mysqli_stmt_get_result($stmt);
	$user = mysqli_fetch_assoc($result);

	//Verifier le mot de passe
	if ($user && password_verify($password, $user['password'])) {
		$_SESSION['user_id'] = $user['id'];
		$_SESSION['email'] = $user['email'];


		header('Location: dashboard.php');
		exit;
	} else {
		header('Location: index.php?error=1');
		exit;
	}
} else { 
	header('Location: index.php');
	exit;
}
mysqli_close($conn);
?>

