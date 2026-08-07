<?php
// app/controllers/ClientController.php

class ClientController {
    private ClientModel      $clientM;
    private TransactionModel $transactionM;

    public function __construct() {
        requireRole('client');
        require_once APP.'/models/ClientModel.php';
        require_once APP.'/models/TransactionModel.php';
        $this->clientM      = new ClientModel();
        $this->transactionM = new TransactionModel();
    }

    public function dashboard(?string $p = null): void {
        $client = $this->clientM->findByEmail(currentUser()['email']);
        if (!$client) die('Client introuvable.');
        $trans      = $this->transactionM->byClient($client['CIN']);
        $enCours    = array_filter($trans, fn($t) => $t['statut'] === 'EN_COURS');
        $historique = array_filter($trans, fn($t) => $t['statut'] !== 'EN_COURS');
        view('client/dashboard', compact('client','enCours','historique'));
    }
}
