# Consignation — Système de gestion des bouteilles réutilisables

------------------------------------------------------------------------------
| 1 | Cycle de vie bouteille complet : 9 statuts formalisés  
| 2 | Entité `lavage` ajoutée avec traçabilité complète  
| 3 | Entité `collecte` corrigée — flux revendeur→entreprise séparé  
| 4 | `bouteille.id_revendeur_actuel` ajouté  
| 5 | `utilisateur.statut_compte` ajouté  
| 6 | `revendeur.adresse` ajouté  
| 7 | `transaction_consignation.statut` formalisé (EN_COURS / TERMINEE / LITIGIEUSE)
| 8 | Transitions bouteille validées côté modèle (`transitionAutorisee()`)  
| 9 | Dashboard livreur : livraisons ET collectes  
| 10 | Dashboard logisticien : cycle lavage en 3 étapes + historique  
| 11 | Stock calculé automatiquement depuis les statuts réels  
| 12 | `session_regenerate_id()` à la connexion (sécurité)

---

## Cycle de vie complet d'une bouteille

```
DISPONIBLE_STOCK
      ↓ (livraison revendeur)
LIVREE_REVENDEUR
      ↓
DISPONIBLE_REVENDEUR   ←──────────────────────────┐
      ↓ (scan revendeur : emprunt)                │
   EMPRUNTEE                                      │
      ↓ (scan revendeur : retour)                 │
RENDUE_REVENDEUR                                  │
      ↓ (collecte assignée)                       │
  EN_COLLECTE                                     │
      ↓ (livreur marque collecte effectuée)       │
EN_STOCK_ENTREPRISE                               │
      ↓ (logisticien démarre lavage)              │
    A_LAVER                                       │
      ↓ (logisticien termine lavage)              │
    PROPRE                                        │
      ↓ (logisticien met en stock)                │
DISPONIBLE_STOCK ─────────────────────────────────┘
```

---

## Architecture MVC

```
consigne/
├── database/
│   └── schema.sql               ← 10 tables + données de test
│
├── public/                      ← Document root Apache
│   ├── index.php                ← Front Controller + routeur
│   ├── .htaccess
│   ├── css/app.css              ← Design system complet
│   └── js/app.js
│
└── app/
    ├── config/database.php
    ├── helpers/
    │   ├── auth.php             ← Session, rôles, statut_compte
    │   └── response.php/
    ├── models/                  ← 12 modèles
    │   ├── BouteilleModel.php   ← 9 statuts + transitions validées
    │   ├── TransactionModel.php ← statut EN_COURS/TERMINEE/LITIGIEUSE
    │   ├── CommandeModel.php    ← 6 statuts
    │   ├── LivraisonModel.php   ← dates prévue + effective
    │   ├── CollecteModel.php    ← flux revendeur→entreprise
    │   ├── LavageModel.php      ← traçabilité lavage
    │   ├── StockModel.php       ← calcul auto depuis statuts
    │   ├── ClientModel.php
    │   ├── RevendeurModel.php
    │   ├── EntrepriseModel.php
    │   ├── LivreurModel.php
    │   └── LogisticienModel.php
    ├── controllers/             ← 6 contrôleurs
    │   ├── AuthController.php
    │   ├── EntrepriseController.php
    │   ├── RevendeurController.php
    │   ├── ClientController.php
    │   ├── LivreurController.php    ← livraisons + collectes
    │   └── LogisticienController.php ← lavage en 3 étapes
    └── views/
        ├── shared/ (header, footer, 404)
        ├── auth/login.php
        ├── entreprise/dashboard.php
        ├── revendeur/dashboard.php
        ├── client/dashboard.php
        ├── livreur/dashboard.php    ← 2 sections : livraisons + collectes
        └── logisticien/dashboard.php ← cycle lavage visuel
```

---

## Installation

### 1. Base de données

```bash
mysql -u root -p < database/schema.sql
```
SOURCE /var/www/html/consigne/database/schema.sql;


### 2. Configuration

Éditez `app/config/database.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'consignation');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_mdp');
```

### 3. Serveur web

**PHP built-in (développement) :**

```bash
cd consignation_v2/public
php -S localhost:8080
# → http://localhost:8080/index.php/auth/login
```

**Apache (production) :**

- `DocumentRoot` → `/chemin/consignation_v2/public/`
- `AllowOverride All` activé pour le `.htaccess`

---

## Comptes de démonstration — mot de passe : `password`

| Rôle        | Email                | Dashboard                                    |
| ----------- | -------------------- | -------------------------------------------- |
| Entreprise  | entreprise@test.com  | Statistiques cycle vie, commandes, collectes |
| Revendeur   | revendeur@test.com   | Scanner QR, emprunts, commandes              |
| Client      | client@test.com      | Bouteilles en cours / historique             |
| Livreur     | livreur@test.com     | Livraisons + Collectes à effectuer           |
| Logisticien | logisticien@test.com | Cycle lavage 3 étapes, stock                 |

---

## Routes complètes

| URL                                | Méthode  | Rôle        | Description                        |
| ---------------------------------- | -------- | ----------- | ---------------------------------- |
| `/auth/login`                      | GET/POST | public      | Connexion                          |
| `/auth/logout`                     | GET      | tous        | Déconnexion                        |
| `/entreprise/dashboard`            | GET      | entreprise  | Dashboard complet                  |
| `/entreprise/validerCommande`      | POST     | entreprise  | Valider commande + créer livraison |
| `/entreprise/creerCollecte`        | POST     | entreprise  | Créer une collecte                 |
| `/entreprise/livreurs`             | GET      | entreprise  | API JSON liste livreurs            |
| `/entreprise/revendeurs`           | GET      | entreprise  | API JSON liste revendeurs          |
| `/revendeur/dashboard`             | GET      | revendeur   | Dashboard                          |
| `/revendeur/scanner`               | POST     | revendeur   | API scan (info/emprunter/rendre)   |
| `/revendeur/rechercheClient`       | GET      | revendeur   | API recherche client               |
| `/revendeur/commander`             | POST     | revendeur   | Passer commande                    |
| `/client/dashboard`                | GET      | client      | Mes emprunts                       |
| `/livreur/dashboard`               | GET      | livreur     | Livraisons + collectes             |
| `/livreur/cocherLivraison/{id}`    | POST     | livreur     | Marquer livraison effectuée        |
| `/livreur/cocherCollecte/{id}`     | POST     | livreur     | Marquer collecte effectuée         |
| `/logisticien/dashboard`           | GET      | logisticien | Stock + lavage                     |
| `/logisticien/demarrerLavage/{id}` | POST     | logisticien | EN_STOCK → A_LAVER                 |
| `/logisticien/terminerLavage/{id}` | POST     | logisticien | A_LAVER → PROPRE                   |
| `/logisticien/mettreEnStock/{id}`  | POST     | logisticien | PROPRE → DISPONIBLE_STOCK          |
| `/logisticien/mettreAJourStock`    | POST     | logisticien | Mise à jour manuelle stock         |

---

## Prochaines évolutions possibles

- Intégrer **jsQR** pour décodage QR temps réel via webcam
- **Notifications** en temps réel (commande reçue, collecte planifiée)
- **Export PDF/CSV** des transactions et statistiques
- **API REST** pour application mobile
- **Historique des lavages** consultable par l'entreprise
