<?php
class EntrepriseController {

    private EntrepriseModel $entrepriseM;
    private BouteilleModel $bouteilleM;
    private TransactionModel $transactionM;
    private CommandeModel $commandeM;
    private CollecteModel $collecteM;
    private LivraisonModel $livraisonM;

    public function __construct() {
        requireRole('entreprise');
        foreach (['EntrepriseModel','BouteilleModel','TransactionModel',
                  'CommandeModel','CollecteModel','LivraisonModel'] as $m) {
            require_once APP.'/models/'.$m.'.php';
        }
        $this->entrepriseM = new EntrepriseModel();
        $this->bouteilleM = new BouteilleModel();
        $this->transactionM = new TransactionModel();
        $this->commandeM = new CommandeModel();
        $this->collecteM = new CollecteModel();
        $this->livraisonM = new LivraisonModel();
    }

    // Récupérer les informations de l'entreprise actuellement connectée
    private function getEntreprise(): array {
        $e = $this->entrepriseM->findByEmail(currentUser()['email']);
        if (!$e) die('Entreprise introuvable.');
        return $e;
    }

    // Afficher le tableau de bord de l'entreprise
    public function dashboard(?string $p = null): void {
        $e = $this->getEntreprise();
        $statsStatut = $this->bouteilleM->statsParStatut($e['nom_entreprise']);
        $statsRev = $this->bouteilleM->statsParRevendeur($e['nom_entreprise']);
        $transactions = $this->transactionM->byEntreprise($e['nom_entreprise']);
        $commandes = $this->commandeM->byEntreprise($e['nom_entreprise']);
        $collectes = $this->collecteM->byEntreprise($e['nom_entreprise']);
        $dispo = $this->bouteilleM->allByStatut($e['nom_entreprise'], 'DISPONIBLE_STOCK');
        view('entreprise/dashboard',
            compact('e','statsStatut','statsRev','transactions',
                    'commandes','collectes','dispo'));
                    //compact(...) est une fonction qui renvoie les donnees a la vue 
                    //compact transforme les variables en tableau associatif avec le nom de la variable comme cle et la valeur de la variable comme valeur 
    }

    /* API : bouteilles disponibles pour affectation */
    public function bouteillesDisponibles(?string $p = null): void {
        $e = $this->getEntreprise();
        $rows = $this->bouteilleM->allByStatut($e['nom_entreprise'], 'DISPONIBLE_STOCK');
        jsonResponse($rows);
        //envoyes les donnees au format json au navigateur 
    }
    //Une API est un intermédiaire qui permet à une application de demander un service ou des données à une autre application sans connaître son fonctionnement interne.

    /* Valider commande + affecter bouteilles + créer livraison */
    public function validerCommande(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('entreprise/dashboard');
        $e = $this->getEntreprise();
        $nomBoutique = $_POST['nom_boutique'] ?? '';
        $dateCommande = $_POST['date_commande'] ?? '';
        $cinLivreur = $_POST['CIN_livreur'] ?? '';
        $datePrevue = $_POST['date_prevue'] ?? date('Y-m-d');
        $qrCodes = $_POST['qr_codes'] ?? [];  // tableau de QR codes choisis

        $cmd = $this->commandeM->findOne($nomBoutique, $e['nom_entreprise'], $dateCommande);
        if (!$cmd || $cmd['statut'] !== 'EN_ATTENTE') {
            flashSet('error','Commande introuvable ou déjà traitée.');
            redirect('entreprise/dashboard');
        }

        // Vérifier que le nombre de bouteilles correspond à la quantité commandée
        if (count($qrCodes) !== (int)$cmd['quantite']) {
            flashSet('error',
                'Vous devez sélectionner exactement '.$cmd['quantite'].' bouteille(s). '.
                'Vous en avez sélectionné '.count($qrCodes).'.');
            redirect('entreprise/dashboard');
        }

        // Affecter les bouteilles à la commande
        $this->commandeM->affecterBouteilles(
            $nomBoutique, $e['nom_entreprise'], $dateCommande, $qrCodes);

        // Passer les bouteilles en LIVREE_REVENDEUR
        foreach ($qrCodes as $qr) {
            $this->bouteilleM->updateStatut($qr, 'LIVREE_REVENDEUR', $nomBoutique);
        }

        // Mettre à jour statut commande + créer livraison
        $this->commandeM->updateStatut(
            $nomBoutique, $e['nom_entreprise'], $dateCommande, 'EN_LIVRAISON');
        $this->livraisonM->creer(
            $cinLivreur, $nomBoutique, $e['nom_entreprise'], $dateCommande, $datePrevue);

        flashSet('success', count($qrCodes).' bouteille(s) affectée(s) — livraison créée.');
        redirect('entreprise/dashboard');
    }

    /* Créer une collecte */
    public function creerCollecte(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('entreprise/dashboard');
        $e           = $this->getEntreprise();
        $nomBoutique = $_POST['nom_boutique'] ?? '';
        $cinLivreur  = $_POST['CIN_livreur']  ?? '';
        $datePrevue  = $_POST['date_prevue']  ?? date('Y-m-d');

        // Créer la collecte
        $this->collecteM->creer(
            $nomBoutique, $e['nom_entreprise'], $cinLivreur, $datePrevue);

        // Lier automatiquement toutes les bouteilles RENDUE_REVENDEUR de ce revendeur
        $rendues = $this->bouteilleM->renduesChezRevendeur($nomBoutique);
        foreach ($rendues as $b) {
            $this->bouteilleM->updateStatut($b['qr_code'], 'EN_COLLECTE');
            $this->collecteM->ajouterBouteille(
                $b['qr_code'], $nomBoutique, $e['nom_entreprise'],
                $cinLivreur, $datePrevue);
        }

        flashSet('success', 'Collecte créée — '.count($rendues).' bouteille(s) liées.');
        redirect('entreprise/dashboard');
    }

    /* API JSON */
    public function livreurs(?string $p = null): void {
        jsonResponse($this->entrepriseM->allLivreurs());
    }

    public function revendeurs(?string $p = null): void {
        $e = $this->getEntreprise();
        jsonResponse($this->entrepriseM->allRevendeurs($e['nom_entreprise']));
    }
}
