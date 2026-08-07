<?php

class LivreurModel {
    private PDO $db;
    public function __construct() {
        $this->db = getDB();
    }

    public function findByEmail(string $email): ?array {
        $s = $this->db->prepare('SELECT * FROM livreur WHERE email_utilisateur = ?');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }
}
?>