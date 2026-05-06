<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8">
		<title>Connexion</title>
	</head>
	<body>
		<h2>Connexion</h2>
		<!--Affiche les erreurs passes en URL-->
		<?php
			if(isset($_GET['error'])): 
		?>
		<p style="color:red;">Email ou mot de passe incorrect</p>
		<?php endif; ?>

		<form action="login.php" method="POST">
			<label>Email : </label>
			<input type="email" name="email" required><br><br>

			<label>Password : </label>
			<input type="password" name="password" required><br><br>

			<button type="submit">Se connecter</button>
		</form>
	</body>

</html>
