<?php
class UtilisateurModel
{
    private PDO $db;
    //PHP Data Object: bibliotheque php qui sert a communiquer avec une base de donnes
    //la securite contre les injections sql
    //support de plusieurs base de donnees
    //requete preparees
    //gestion propre des erreurs

    public function __construct()
    {
        $this->db = getDB();
    }

    //trouver un utilisateur par son email et retourne un tableau associatif representant l'utilisateur ou null si l'utilisateur n'existe pas
    public function findByEmail(string $email): ?array
    {
        $s = $this->db->prepare('SELECT * FROM utilisateur WHERE email = ?');
        $s->execute([$email]);
        return $s->fetch() ?: null; //fetch recupere une ligne de la resultat du requete mysqlS
    }



    public function findActifByEmail(string $email): ?array
    {
        $s = $this->db->prepare("SELECT * FROM utilisateur WHERE email = ? AND statut_compte = 'actif'");
        $s->execute([$email]);
        return $s->fetch() ?: null;
        //fetch recupere une ligne de la resultat du requete mysql
    }

    // Créer un nouvel utilisateur avec mot de passe hashé
    public function creer(string $email, string $motDePasseClair, string $role): void
    {
        $hash = password_hash($motDePasseClair, PASSWORD_DEFAULT);
        // password_hash() génère un hash bcrypt sécurisé, avec un "sel" aléatoire intégré
        // C'est la même fonction qui a servi à générer les mots de passe de tes comptes de démo
        $s = $this->db->prepare("INSERT INTO utilisateur (email, mot_de_passe, role, statut_compte) VALUES (?, ?, ?, 'actif')");
        $s->execute([$email, $hash, $role]);
    }

    public function updateMotDePasse(string $email, string $motDePasseClair): void {
        $hash = password_hash($motDePasseClair, PASSWORD_DEFAULT);
        $s = $this->db->prepare('UPDATE utilisateur SET mot_de_passe = ? WHERE email = ?');
        $s->execute([$hash, $email]);
    }
}
