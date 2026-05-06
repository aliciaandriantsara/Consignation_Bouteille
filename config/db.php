<?php
$conn = mysqli_connect('localhost', 'php_user', 'motdepasse123', 'consignation');

if (!$conn) {
	die("Erreur connexion : " . mysqli_connect_error());
}
