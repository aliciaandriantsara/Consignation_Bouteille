# 🍶 Consignation — Système de gestion des bouteilles réutilisables

## Architecture MVC — PHP + MySQL + HTML/CSS/JS vanilla

---

## Structure du projet

```
consignation/
├── database/
│   └── schema.sql               ← Schéma BDD + données de test
│
├── public/                      ← Racine web (document root Apache/Nginx)
│   ├── index.php                ← Front Controller (routeur unique)
│   ├── .htaccess                ← Réécriture d'URL Apache
│   ├── css/
│   │   └── app.css
│   └── js/
│       └── app.js
│
└── app/
    ├── config/
    │   └── database.php         ← Connexion PDO
    ├── helpers/
    │   ├── auth.php             ← Session, rôles, redirect
    │   └── response.php        ← view(), jsonResponse(), flash
    ├── models/
    │   ├── BouteilleModel.php
    │   ├── TransactionModel.php
    │   ├── CommandeModel.php
    │   ├── LivraisonModel.php
    │   ├── StockModel.php
    │   ├── ClientModel.php
    │   ├── RevendeurModel.php
    │   ├── EntrepriseModel.php
    │   ├── LogisticienModel.php
    │   └── LivreurModel.php
    ├── controllers/
    │   ├── AuthController.php
    │   ├── EntrepriseController.php
    │   ├── RevendeurController.php
    │   ├── ClientController.php
    │   ├── LivreurController.php
    │   └── LogisticienController.php
    └── views/
        ├── shared/
        │   ├── header.php
        │   ├── footer.php
        │   └── 404.php
        ├── auth/login.php
        ├── entreprise/dashboard.php
        ├── revendeur/dashboard.php
        ├── client/dashboard.php
        ├── livreur/dashboard.php
        └── logisticien/dashboard.php
```

---

## Installation

### 1. Prérequis

- PHP 8.1+
  A verifier avec php -v
- MySQL 8.0+
  A verifier avec mysql --version
- Apache avec `mod_rewrite` activé
  A verifier avec apache2ctl -M | grep rewrite
  Si c'est afficher rewrite_module(shared) alors c'est ok
  A verifier aussi avec a2query -m rewrite
  Si c'est afficher rewrite (enabled by site administrator) c'est ok
  Sinon No module matches rewrite
  Verifier le lien symbolique de la module
  Apache active les modules via /etc/apache2/mods-enabled/
  ls /etc/apache2/mods-enabled | grep rewrite
  si c'est active il doit y avoir rewrite.load

# A voir plus tard

# vim /var/www/html/consignation/.htaccess

# RewritEngine On

# RewriteRule ^test$ index.html [L]

# Puis assure toi que dans la configuration apache

# <Directory /var/www/html/consignation>

# AllowOverride All

# </Directory>

Pour activer le module en cas de desactivation
sudo a2enmode rewrite
sudo systectml restart apache2
Verification des erreurs apache
sudo tail -f /var/log.apache2/error.log

### 2. Base de données

```sql
-- Dans MySQL :
SOURCE /var/www/html/consigne/database/schema.sql;
Sert a executer toutes les commandes sql contenues dans un fichier
```

### 3. Configuration

Éditez `app/config/database.php` :
Markdown pour indiquer que ce qui suit est du code php donc doit avoir la coloration syntaxique de php

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'consignation');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_mdp');
```

### 4. Serveur web

**Option A — PHP built-in (développement) :**

```bash
cd consignation/public
php -S localhost:8080
# Puis visiter http://localhost:8080/index.php/auth/login
```

**Option B — Apache (production) :**

- Pointez le `DocumentRoot` vers `/var/www/html/consigne/public/`
- Assurez-vous que `AllowOverride All` est actif pour le `.htaccess`
  Dans /etc/apache2/sites-available/00-default.conf
  Tester concretement
  Dans le dossier racine du projet creer le fichier .htaccess avec le contenu suivant
  RewriteEngine On
  RewriteRule ^test$ index.php [L]
  Active ele moteur de reecriture d'apache dans ce dossier
  Declare une regle de reecriture avec un motif a reconnaitre et une destination reelle et un flag indiquant "Last"
  Le motif correspond au debut de chaine avec test comme texte exacte et $ la fin de la chaine donc /test et la flag [L] signifie Last donc se termine par test au point ou il arret toutes les autres regles
  Ensuite creer le fichier index.php
  <?php
  echo "HTACCESS Ok";
  ?>
  Et enfin entrer dans localhost/consigne/

---

## Comptes de démonstration

Tous ont le mot de passe : **`password`**

| Rôle        | Email                |
| ----------- | -------------------- |
| Entreprise  | entreprise@test.com  |
| Revendeur   | revendeur@test.com   |
| Client      | client@test.com      |
| Livreur     | livreur@test.com     |
| Logisticien | logisticien@test.com |

---

## Flux métier complet

```
CLIENT emprunte → REVENDEUR scanne (emprunter + sélection client)
                                    ↓ statut: emprunte
CLIENT rend     → REVENDEUR scanne (rendre)
                                    ↓ statut: rendu
LIVREUR récupère les bouteilles rendues → les livre au STOCK
                                    ↓ statut: en_livraison
LOGISTICIEN reçoit → marque à_laver → lavage_en_cours → disponible
                                    ↓ met à jour stock (propres / à laver)
REVENDEUR commande → ENTREPRISE valide + assigne LIVREUR
                                    ↓ statut commande: en_livraison
LIVREUR livre bouteilles propres → REVENDEUR
                                    ↓ statut: livree
```

---

## Routes

| URL                                | Méthode  | Rôle        | Action                              |
| ---------------------------------- | -------- | ----------- | ----------------------------------- |
| `/auth/login`                      | GET/POST | public      | Page de connexion                   |
| `/auth/logout`                     | GET      | tous        | Déconnexion                         |
| `/entreprise/dashboard`            | GET      | entreprise  | Dashboard statistiques + commandes  |
| `/entreprise/validerCommande/{id}` | POST     | entreprise  | Valider commande + créer livraison  |
| `/entreprise/livreurs`             | GET      | entreprise  | API JSON liste livreurs             |
| `/revendeur/dashboard`             | GET      | revendeur   | Dashboard scanner + historique      |
| `/revendeur/scanner`               | POST     | revendeur   | API scan QR (info/emprunter/rendre) |
| `/revendeur/rechercheClient`       | GET      | revendeur   | API recherche client                |
| `/revendeur/commander`             | POST     | revendeur   | Passer une commande                 |
| `/client/dashboard`                | GET      | client      | Mes emprunts en cours/historique    |
| `/livreur/dashboard`               | GET      | livreur     | Mes livraisons à faire / faites     |
| `/livreur/cocher/{id}`             | POST     | livreur     | Marquer livraison effectuée         |
| `/logisticien/dashboard`           | GET      | logisticien | Gestion stock + statuts             |
| `/logisticien/mettreAJourStock`    | POST     | logisticien | Mise à jour stock                   |
| `/logisticien/changerStatus/{id}`  | POST     | logisticien | Changer statut bouteille            |

---

API interface qui permet a 2 logiciels de communiquer automatiquement entre eux
API recherche client signifie generalement un service permettant de rechercher des informations sur des clients via des requetes programmatiques
Exemple ton application demande donne moi le client ayant l'ID 15 alors l'API repond par un json
{
"id": "15",
"nom": "Rakoto"
}

## Extension possible

- Intégrer **jsQR** (npm) pour décodage QR temps réel via webcam dans la vue revendeur
- Ajouter un système de **notifications** (commande reçue, livraison assignée)
- **Export PDF/CSV** des transactions pour l'entreprise
- **API REST complète** pour une future app mobile
