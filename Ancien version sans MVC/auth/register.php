<?php
/**
 * auth/register.php
 * Traitement de l'inscription d'un client.
 * Reçoit les données POST de register.html et effectue tous les INSERT.
 * Répond en JSON : { "success": true } ou { "success": false, "message": "..." }
 */

// ============================================================
// 0. CONFIGURATION
// ============================================================
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once '../config/db.php';   // fournit $conn (mysqli)

// ============================================================
// 1. VÉRIFICATIONS PRÉLIMINAIRES
// ============================================================

// On n'accepte que les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Fonction utilitaire : nettoyer une valeur
function clean(string $val): string {
    return trim(htmlspecialchars($val, ENT_QUOTES, 'UTF-8'));
}

// Fonction utilitaire : répondre en JSON et stopper
function respond(bool $success, string $message = '', array $extra = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// ============================================================
// 2. RÉCUPÉRATION ET VALIDATION DES DONNÉES
// ============================================================

// --- Champs obligatoires ---
$role     = 'client'; // toujours fixé côté serveur, on n'accepte jamais ce qui vient du client
$email    = clean($_POST['email']    ?? '');
$password = $_POST['password']       ?? '';
$nom      = clean($_POST['nom']      ?? '');
$prenom   = clean($_POST['prenom']   ?? '');
$cin      = clean($_POST['cin']      ?? '');

// --- Validation email ---
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Adresse email invalide.');
}

// --- Validation mot de passe ---
if (strlen($password) < 6) {
    respond(false, 'Le mot de passe doit contenir au moins 6 caractères.');
}

// --- Validation nom / prénom ---
if (empty($nom) || empty($prenom)) {
    respond(false, 'Le nom et le prénom sont obligatoires.');
}

// --- Validation CIN ---
if (empty($cin)) {
    respond(false, 'Le numéro CIN est obligatoire.');
}

// --- Téléphones (JSON) ---
$telephones = [];
if (!empty($_POST['telephones'])) {
    $telephones = json_decode($_POST['telephones'], true);
    if (!is_array($telephones) || count($telephones) === 0) {
        respond(false, 'Veuillez renseigner au moins un numéro de téléphone.');
    }
    foreach ($telephones as $t) {
        if (empty($t['numero'])) {
            respond(false, 'Un numéro de téléphone est vide.');
        }
    }
}

// --- Adresses (JSON) ---
$adresses = [];
if (!empty($_POST['adresses'])) {
    $adresses = json_decode($_POST['adresses'], true);
    if (!is_array($adresses) || count($adresses) === 0) {
        respond(false, 'Veuillez renseigner au moins une adresse.');
    }
    foreach ($adresses as $a) {
        if (empty($a['ville'])) {
            respond(false, 'La ville est obligatoire pour chaque adresse.');
        }
    }
}

// ============================================================
// 3. VÉRIFIER QUE L'EMAIL N'EST PAS DÉJÀ UTILISÉ
// ============================================================
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    respond(false, 'Cette adresse email est déjà utilisée.');
}
mysqli_stmt_close($stmt);

// ============================================================
// 4. VÉRIFIER QUE LE CIN N'EST PAS DÉJÀ UTILISÉ
// ============================================================
$stmt = mysqli_prepare($conn, "SELECT id FROM clients WHERE CIN = ?");
mysqli_stmt_bind_param($stmt, 's', $cin);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    respond(false, 'Ce numéro CIN est déjà enregistré.');
}
mysqli_stmt_close($stmt);

// ============================================================
// 5. HACHAGE DU MOT DE PASSE
// ============================================================
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// ============================================================
// 6. GÉNÉRATION DU QR CODE UNIQUE DU CLIENT
// ============================================================
// Format : CLI-<timestamp>-<random 6 chars>
$qr_code = 'CLI-' . time() . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

// ============================================================
// 7. TRANSACTION : TOUS LES INSERT EN UNE FOIS
// Soit tout réussit, soit tout est annulé.
// ============================================================
mysqli_begin_transaction($conn);

try {

    // ── 7.1 INSERT dans users ──────────────────────────────
    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (email, password, role, created_at)
         VALUES (?, ?, 'client', NOW())"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $email, $password_hash);
    mysqli_stmt_execute($stmt);
    $user_id = mysqli_insert_id($conn);  // ID du user qu'on vient de créer
    mysqli_stmt_close($stmt);

    if (!$user_id) {
        throw new Exception("Erreur lors de la création du compte.");
    }

    // ── 7.2 INSERT dans noms ──────────────────────────────
    $stmt = mysqli_prepare($conn,
        "INSERT INTO nom (user_id, nom, prenom)
         VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'iss', $user_id, $nom, $prenom);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // ── 7.3 INSERT dans clients ───────────────────────────
    $stmt = mysqli_prepare($conn,
        "INSERT INTO clients (user_id, CIN, qr_code)
         VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'iss', $user_id, $cin, $qr_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // ── 7.4 INSERT dans adresses (boucle) ────────────────
    if (!empty($adresses)) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO adresses (user_id, numero_logement, quartier, commune, ville)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($adresses as $a) {
            $num      = clean($a['numero_logement'] ?? '');
            $quartier = clean($a['quartier']        ?? '');
            $commune  = clean($a['commune']         ?? '');
            $ville    = clean($a['ville']           ?? '');

            mysqli_stmt_bind_param($stmt, 'issss',
                $user_id, $num, $quartier, $commune, $ville
            );
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }

    // ── 7.5 INSERT dans telephones (boucle) ──────────────
    if (!empty($telephones)) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO telephones (user_id, indicatif, operateur, numero)
             VALUES (?, ?, ?, ?)"
        );
        foreach ($telephones as $t) {
            $indicatif = clean($t['indicatif'] ?? '+261');
            $operateur = clean($t['operateur'] ?? '');
            $numero    = clean($t['numero']    ?? '');

            mysqli_stmt_bind_param($stmt, 'isss',
                $user_id, $indicatif, $operateur, $numero
            );
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }

    // ── Tout s'est bien passé : on valide ─────────────────
    mysqli_commit($conn);

    respond(true, 'Compte créé avec succès.', ['user_id' => $user_id]);

} catch (Exception $e) {

    // Une erreur est survenue : on annule TOUT
    mysqli_rollback($conn);
    respond(false, 'Erreur serveur : ' . $e->getMessage());

} finally {
    mysqli_close($conn);
}
?>
