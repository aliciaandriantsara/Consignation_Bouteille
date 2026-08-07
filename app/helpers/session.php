<?php
// app/helpers/session.php

// Stocke les sessions dans MySQL au lieu du système de fichiers

class SessionDB implements SessionHandlerInterface {
    //SessionHandlerInterface est un interface de gestion des sessions en php qui definit les methodes a implementer pour gerer les sessions

    private PDO $db;

    //php appelle automatiquement ces methodes lorsqu'une session est demarree a l'aide de session_start() dans public/index.php
    public function open(string $path, string $name): bool {
        $this->db = getDB();
        //getDB() est une fonction definie dans app/helpers/db.php qui retourne un objet PDO representant la connexion a la base de donnees
        //getDB() est appelee ici pour etablir la connexion a la base de donnees et stocker l'objet PDO dans la propriete $db de la classe SessionDB
        return true;
    }

    public function close(): bool {
        return true;
    }

    //Lire les données de session depuis la base de données
    public function read(string $id): string|false {
        //$id ici est un identifiant de session unique genere par php pour chaque utilisateur qui se connecte au site et prepare la requete sql suivante
        $s = $this->db->prepare(
            'SELECT donnees FROM sessions WHERE id = ?');
        $s->execute([$id]);
        $row = $s->fetch();//recupere la premiere ligne du resultat de la requete sql sous forme de tableau associatif
        return $row ? $row['donnees'] : '';
    }

    public function write(string $id, string $data): bool {
        $s = $this->db->prepare(
            'INSERT INTO sessions (id, donnees, derniere_activite)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
               donnees            = ?,
               derniere_activite  = ?');
            //Avec ON DUPLICATE KEY UPDATE, si une session avec le meme id existe deja dans la table sessions, alors les colonnes donnees et derniere_activite sont mises a jour avec les nouvelles valeurs fournies
        $s->execute([$id, $data, time(), $data, time()]);
        return true;
    }


    public function destroy(string $id): bool {
        $s = $this->db->prepare('DELETE FROM sessions WHERE id = ?');
        $s->execute([$id]);
        return true;
    }

    //gc garbage collector ramasse miette
    //supprimer les anciennes sessions expirees de la base de donnees

    public function gc(int $max_lifetime): int|false {//retourneun int ou un false
        $expire = time() - $max_lifetime;
        $s = $this->db->prepare(
            'DELETE FROM sessions WHERE derniere_activite < ?');
        $s->execute([$expire]);
        return $s->rowCount();//retourne le nombre de lignes affectees par la requete sql, c'est a dire le nombre de sessions supprimees
        //toute actions qui a ete inactive depuis max life time seront suppprimer
    }
}
