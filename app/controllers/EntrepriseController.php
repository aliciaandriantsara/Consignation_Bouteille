<?php
class EntrepriseController
{

    private EntrepriseModel $entrepriseM;
    private BouteilleModel $bouteilleM;
    private TransactionModel $transactionM;
    private CommandeModel $commandeM;
    private CollecteModel $collecteM;
    private LivraisonModel $livraisonM;

    public function __construct()
    {
        requireRole('entreprise');
        foreach (
            [
                'EntrepriseModel',
                'BouteilleModel',
                'TransactionModel',
                'CommandeModel',
                'CollecteModel',
                'LivraisonModel'
            ] as $m
        ) {
            require_once APP . '/models/' . $m . '.php';
        }
        $this->entrepriseM = new EntrepriseModel();
        $this->bouteilleM = new BouteilleModel();
        $this->transactionM = new TransactionModel();
        $this->commandeM = new CommandeModel();
        $this->collecteM = new CollecteModel();
        $this->livraisonM = new LivraisonModel();
    }

    // Récupérer les informations de l'entreprise actuellement connectée
    private function getEntreprise(): array
    {
        $e = $this->entrepriseM->findByEmail(currentUser()['email']);
        if (!$e) die('Entreprise introuvable.');
        return $e;
    }

    // Afficher le tableau de bord de l'entreprise
    public function dashboard(?string $p = null): void
    {
        $e = $this->getEntreprise();
        $statsStatut = $this->bouteilleM->statsParStatut($e['nom_entreprise']);
        $statsRev = $this->bouteilleM->statsParRevendeur($e['nom_entreprise']);
        $transactions = $this->transactionM->byEntreprise($e['nom_entreprise']);
        $commandes = $this->commandeM->byEntreprise($e['nom_entreprise']);
        $collectes = $this->collecteM->byEntreprise($e['nom_entreprise']);
        $dispo = $this->bouteilleM->allByStatut($e['nom_entreprise'], 'DISPONIBLE_STOCK');
        view(
            'entreprise/dashboard',
            compact(
                'e',
                'statsStatut',
                'statsRev',
                'transactions',
                'commandes',
                'collectes',
                'dispo'
            )
        );
        //compact(...) est une fonction qui renvoie les donnees a la vue 
        //compact transforme les variables en tableau associatif avec le nom de la variable comme cle et la valeur de la variable comme valeur 
    }

    /* API : bouteilles disponibles pour affectation */
    public function bouteillesDisponibles(?string $p = null): void
    {
        $e = $this->getEntreprise();
        $rows = $this->bouteilleM->allByStatut($e['nom_entreprise'], 'DISPONIBLE_STOCK');
        jsonResponse($rows);
        //envoyes les donnees au format json au navigateur 
    }
    //Une API est un intermédiaire qui permet à une application de demander un service ou des données à une autre application sans connaître son fonctionnement interne.

    /* Valider commande + affecter bouteilles + créer livraison */
    public function validerCommande(?string $p = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('entreprise/dashboard');
        $e = $this->getEntreprise();
        $nomBoutique = $_POST['nom_boutique'] ?? '';
        $dateCommande = $_POST['date_commande'] ?? '';
        $cinLivreur = $_POST['CIN_livreur'] ?? '';
        $datePrevue = $_POST['date_prevue'] ?? date('Y-m-d');
        $qrCodes = $_POST['qr_codes'] ?? [];  // tableau de QR codes choisis

        $cmd = $this->commandeM->findOne($nomBoutique, $e['nom_entreprise'], $dateCommande);
        if (!$cmd || $cmd['statut'] !== 'EN_ATTENTE') {
            flashSet('error', 'Commande introuvable ou déjà traitée.');
            redirect('entreprise/dashboard');
        }

        // Vérifier que le nombre de bouteilles correspond à la quantité commandée
        if (count($qrCodes) !== (int)$cmd['quantite']) {
            flashSet(
                'error',
                'Vous devez sélectionner exactement ' . $cmd['quantite'] . ' bouteille(s). ' .
                    'Vous en avez sélectionné ' . count($qrCodes) . '.'
            );
            redirect('entreprise/dashboard');
        }

        // Affecter les bouteilles à la commande
        $this->commandeM->affecterBouteilles(
            $nomBoutique,
            $e['nom_entreprise'],
            $dateCommande,
            $qrCodes
        );

        // Passer les bouteilles en LIVREE_REVENDEUR
        foreach ($qrCodes as $qr) {
            $this->bouteilleM->updateStatut($qr, 'LIVREE_REVENDEUR', $nomBoutique);
        }

        // Mettre à jour statut commande + créer livraison
        $this->commandeM->updateStatut(
            $nomBoutique,
            $e['nom_entreprise'],
            $dateCommande,
            'EN_LIVRAISON'
        );
        $this->livraisonM->creer(
            $cinLivreur,
            $nomBoutique,
            $e['nom_entreprise'],
            $dateCommande,
            $datePrevue
        );

        flashSet('success', count($qrCodes) . ' bouteille(s) affectée(s) — livraison créée.');
        redirect('entreprise/dashboard');
    }

    /* Créer une collecte */
    public function creerCollecte(?string $p = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('entreprise/dashboard');
        $e           = $this->getEntreprise();
        $nomBoutique = $_POST['nom_boutique'] ?? '';
        $cinLivreur  = $_POST['CIN_livreur']  ?? '';
        $datePrevue  = $_POST['date_prevue']  ?? date('Y-m-d');

        // Créer la collecte
        $this->collecteM->creer(
            $nomBoutique,
            $e['nom_entreprise'],
            $cinLivreur,
            $datePrevue
        );

        // Lier automatiquement toutes les bouteilles RENDUE_REVENDEUR de ce revendeur
        $rendues = $this->bouteilleM->renduesChezRevendeur($nomBoutique);
        foreach ($rendues as $b) {
            $this->bouteilleM->updateStatut($b['qr_code'], 'EN_COLLECTE');
            $this->collecteM->ajouterBouteille(
                $b['qr_code'],
                $nomBoutique,
                $e['nom_entreprise'],
                $cinLivreur,
                $datePrevue
            );
        }

        flashSet('success', 'Collecte créée — ' . count($rendues) . ' bouteille(s) liées.');
        redirect('entreprise/dashboard');
    }

    /* API JSON */
    public function livreurs(?string $p = null): void
    {
        jsonResponse($this->entrepriseM->allLivreurs());
    }

    public function revendeurs(?string $p = null): void
    {
        $e = $this->getEntreprise();
        jsonResponse($this->entrepriseM->allRevendeurs($e['nom_entreprise']));
    }

    public function creerRevendeur(?string $p = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('entreprise/dashboard');
        $e = $this->getEntreprise();

        $nomBoutique = trim($_POST['nom_boutique'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $adresse     = trim($_POST['adresse'] ?? '') ?: null;
        $pass        = $_POST['password'] ?? '';

        require_once APP . '/models/UtilisateurModel.php';
        require_once APP . '/models/RevendeurModel.php';
        $utilisateurM = new UtilisateurModel();
        $revendeurM   = new RevendeurModel();

        $erreurs = [];
        if ($nomBoutique === '') $erreurs[] = 'Le nom de la boutique est obligatoire.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';
        if (strlen($pass) < 8) $erreurs[] = 'Mot de passe : 8 caractères minimum.';

        if (empty($erreurs)) {
            if ($utilisateurM->findByEmail($email))          $erreurs[] = 'Cet email est déjà utilisé.';
            if ($revendeurM->findByNomBoutique($nomBoutique)) $erreurs[] = 'Ce nom de boutique existe déjà.';
        }

        if (!empty($erreurs)) {
            flashSet('error', implode(' ', $erreurs));
            redirect('entreprise/dashboard');
            return;
        }

        $db = getDB();
        try {
            $db->beginTransaction();
            $utilisateurM->creer($email, $pass, 'revendeur');
            // nom_entreprise = celle de l'entreprise CONNECTÉE, jamais lue depuis le formulaire
            $revendeurM->creer($nomBoutique, $email, $e['nom_entreprise'], $adresse);
            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            flashSet('error', 'Erreur lors de la création du compte.');
            redirect('entreprise/dashboard');
            return;
        }

        flashSet('success', 'Compte revendeur créé avec succès.');
        redirect('entreprise/dashboard');
    }

    public function creerLivreur(?string $p = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('entreprise/dashboard');

        $cin    = trim($_POST['cin'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $nom    = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $pass   = $_POST['password'] ?? '';

        require_once APP . '/models/UtilisateurModel.php';
        require_once APP . '/models/LivreurModel.php';
        $utilisateurM = new UtilisateurModel();
        $livreurM     = new LivreurModel();

        $erreurs = [];
        if ($cin === '' || $nom === '' || $prenom === '') $erreurs[] = 'CIN, nom et prénom sont obligatoires.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';
        if (strlen($pass) < 8) $erreurs[] = 'Mot de passe : 8 caractères minimum.';

        if (empty($erreurs)) {
            if ($utilisateurM->findByEmail($email)) $erreurs[] = 'Cet email est déjà utilisé.';
            if ($livreurM->findByCIN($cin))         $erreurs[] = 'Ce CIN est déjà enregistré.';
        }

        if (!empty($erreurs)) {
            flashSet('error', implode(' ', $erreurs));
            redirect('entreprise/dashboard');
            return;
        }

        $db = getDB();
        try {
            $db->beginTransaction();
            $utilisateurM->creer($email, $pass, 'livreur');
            $livreurM->creer($cin, $email, $nom, $prenom);
            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            flashSet('error', 'Erreur lors de la création du compte.');
            redirect('entreprise/dashboard');
            return;
        }

        flashSet('success', 'Compte livreur créé avec succès.');
        redirect('entreprise/dashboard');
    }

    public function creerLogisticien(?string $p = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('entreprise/dashboard');
        $e = $this->getEntreprise();

        $cin    = trim($_POST['cin'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $nom    = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $pass   = $_POST['password'] ?? '';

        require_once APP . '/models/UtilisateurModel.php';
        require_once APP . '/models/LogisticienModel.php';
        $utilisateurM  = new UtilisateurModel();
        $logisticienM  = new LogisticienModel();

        $erreurs = [];
        if ($cin === '' || $nom === '' || $prenom === '') $erreurs[] = 'CIN, nom et prénom sont obligatoires.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';
        if (strlen($pass) < 8) $erreurs[] = 'Mot de passe : 8 caractères minimum.';

        if (empty($erreurs)) {
            if ($utilisateurM->findByEmail($email)) $erreurs[] = 'Cet email est déjà utilisé.';
            if ($logisticienM->findByCIN($cin))     $erreurs[] = 'Ce CIN est déjà enregistré.';
        }

        if (!empty($erreurs)) {
            flashSet('error', implode(' ', $erreurs));
            redirect('entreprise/dashboard');
            return;
        }

        $db = getDB();
        try {
            $db->beginTransaction();
            $utilisateurM->creer($email, $pass, 'logisticien');
            $logisticienM->creer($cin, $email, $e['nom_entreprise'], $nom, $prenom);
            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            flashSet('error', 'Erreur lors de la création du compte.');
            redirect('entreprise/dashboard');
            return;
        }

        flashSet('success', 'Compte logisticien créé avec succès.');
        redirect('entreprise/dashboard');
    }
}
