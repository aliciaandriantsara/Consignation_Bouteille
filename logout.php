<?php
session_start();
session_destroy();	//Supprime toutes les donnees de cette session

header("Location: index.php");
exit;
?>
