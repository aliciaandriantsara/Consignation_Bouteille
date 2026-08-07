<?php
class UtilisateurModel {
    private PDO $db;
    //PHP Data Object: bibliotheque php qui sert a communiquer avec une base de donnes
    //la securite contre les injections sql
    //support de plusieurs base de donnees
    //requete preparees
    //gestion propre des erreurs
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function findByEmail(string $email): ?array {
        $s = $this->db->prepare('SELECT * FROM utilisateur WHERE email = ?');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }

    public function findActifByEmail(string $email): ?array {
        $s = $this->db->prepare("SELECT * FROM utilisateur WHERE email = ? AND statut_compte = 'actif'");
        $s->execute([$email]);
        return $s->fetch() ?: null;
        //fetch recupere une ligne de la resultat du requete mysql
    }
}
?>