<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') { //isset verifie si la variable session['user_id'] existe et n'est pas non null
    header('Location: ../pages/index.html');
    exit;
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

// ── Nom & prénom
$stmt = mysqli_prepare($conn, "SELECT nom, prenom FROM nom WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$nom = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$prenom_affiche = $nom ? htmlspecialchars($nom['prenom']) : 'Client';
$nom_affiche    = $nom ? htmlspecialchars($nom['nom'])    : ''; //Si nom a une valeur elle retoune le resultat de html blabla sinon elle retourne vide
$initiales      = strtoupper(substr($prenom_affiche,0,1) . substr($nom_affiche,0,1));//final convertit en majuscule, extrait le premier caractere a la position 0 et le point concatened

// ── Infos client
$stmt = mysqli_prepare($conn, "SELECT id, CIN, qr_code FROM clients WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$res    = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$client_id = $client['id']       ?? null;
$cin       = htmlspecialchars($client['CIN']     ?? '');
$qr_code   = htmlspecialchars($client['qr_code'] ?? '');

// ── Stats
$total_emprunts = $en_cours = $total_rendus = 0;
if ($client_id) {
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN date_rendu IS NULL     THEN 1 ELSE 0 END) AS en_cours,
                SUM(CASE WHEN date_rendu IS NOT NULL THEN 1 ELSE 0 END) AS rendus
         FROM transactions WHERE client_id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $client_id);
    mysqli_stmt_execute($stmt);
    $s = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $total_emprunts = $s['total']    ?? 0;
    $en_cours       = $s['en_cours'] ?? 0;
    $total_rendus   = $s['rendus']   ?? 0;
}

// ── Transactions (10 dernières)
$transactions = [];
if ($client_id) {
    $stmt = mysqli_prepare($conn,
        "SELECT t.date_emprrunt, t.date_rendu,
                b.qr_code AS bouteille_qr,
                r.nom_boutique
         FROM transactions t
         JOIN bouteilles b ON t.bouteille_id = b.id
         JOIN revendeur  r ON t.revendeur_id = r.id
         WHERE t.client_id = ?
         ORDER BY t.date_emprrunt DESC LIMIT 10"
    );
    mysqli_stmt_bind_param($stmt, 'i', $client_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) $transactions[] = $row;
    mysqli_stmt_close($stmt);
}

// ── Téléphone & ville
$telephone = $ville = '';
$stmt = mysqli_prepare($conn, "SELECT indicatif, numero FROM telephones WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$tel = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if ($tel) $telephone = htmlspecialchars($tel['indicatif'].' '.$tel['numero']);

$stmt = mysqli_prepare($conn, "SELECT ville FROM adresses WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$adr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if ($adr) $ville = htmlspecialchars($adr['ville']);

mysqli_close($conn);

// ── Salutation selon l'heure
$h = (int)date('H');
$salutation = $h < 12 ? 'Bonjour' : ($h < 18 ? 'Bon après-midi' : 'Bonsoir');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mon espace — AquaCycle</title>
  <link href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --bg:#f5f2ec;--surface:#fff;--surface2:#f0ece3;--border:#e2dcd0;
      --ink:#1a1713;--ink2:#6b6459;
      --accent:#2563c4;--accent-bg:#eef3fd;
      --green:#1a7a4a;--green-bg:#e8f5ee;
      --amber:#b45309;--amber-bg:#fef3e2;
      --radius:16px;--shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.06);
    }
    body{font-family:'Familjen Grotesk',sans-serif;background:var(--bg);color:var(--ink);min-height:100vh;display:flex}

    /* ══ SIDEBAR ══ */
    .sidebar{width:260px;min-height:100vh;background:var(--ink);display:flex;flex-direction:column;padding:32px 0;position:fixed;top:0;left:0;bottom:0;z-index:100}
    .sb-brand{padding:0 28px 28px;border-bottom:1px solid rgba(255,255,255,.08)}
    .sb-brand-name{font-family:'Instrument Serif',serif;font-size:22px;color:#fff}
    .sb-brand-tag{font-size:11px;color:rgba(255,255,255,.3);letter-spacing:.12em;text-transform:uppercase;margin-top:2px}
    .sb-user{padding:20px 28px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:12px}
    .sb-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#2563c4,#60a5fa);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0}
    .sb-uname{font-size:14px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sb-urole{font-size:11px;color:rgba(255,255,255,.35);letter-spacing:.08em;text-transform:uppercase;margin-top:2px}
    .sb-nav{flex:1;padding:20px 16px;display:flex;flex-direction:column;gap:2px}
    .sb-nav-label{font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.22);padding:12px 12px 6px}
    .nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;text-decoration:none;color:rgba(255,255,255,.5);font-size:14px;font-weight:500;cursor:pointer;border:none;background:none;width:100%;text-align:left;transition:background .2s,color .2s;font-family:'Familjen Grotesk',sans-serif}
    .nav-item:hover{background:rgba(255,255,255,.07);color:#fff}
    .nav-item.active{background:rgba(37,99,196,.28);color:#93b8fa}
    .nav-item svg{width:17px;height:17px;flex-shrink:0}
    .sb-bottom{padding:16px;border-top:1px solid rgba(255,255,255,.08)}
    .btn-logout{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:rgba(255,255,255,.38);font-size:14px;text-decoration:none;background:none;border:none;cursor:pointer;width:100%;font-family:'Familjen Grotesk',sans-serif;transition:all .2s}
    .btn-logout:hover{background:rgba(192,57,43,.18);color:#ff8070}
    .btn-logout svg{width:17px;height:17px}

    /* ══ MAIN ══ */
    .main{margin-left:260px;flex:1;padding:40px 48px;min-height:100vh}

    /* ── Topbar ── */
    .topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:36px}
    .greeting-sub{font-size:13px;color:var(--ink2);letter-spacing:.04em;margin-bottom:4px}
    .greeting-title{font-family:'Instrument Serif',serif;font-size:32px;letter-spacing:-.02em;line-height:1}
    .greeting-title em{font-style:italic;color:var(--accent)}
    .topbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .date-chip{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:8px 14px;font-size:13px;color:var(--ink2)}
    .qr-chip{background:var(--accent-bg);border:1px solid rgba(37,99,196,.2);border-radius:10px;padding:8px 14px;font-size:12px;font-weight:600;color:var(--accent);display:flex;align-items:center;gap:6px;letter-spacing:.04em}
    .qr-chip svg{width:14px;height:14px}

    /* ── Stats ── */
    .stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px}
    .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow);position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s}
    .stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 24px rgba(0,0,0,.1)}
    .stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
    .stat-card.c-blue::before{background:var(--accent)}
    .stat-card.c-amber::before{background:var(--amber)}
    .stat-card.c-green::before{background:var(--green)}
    .stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
    .c-blue  .stat-icon{background:var(--accent-bg);color:var(--accent)}
    .c-amber .stat-icon{background:var(--amber-bg);color:var(--amber)}
    .c-green .stat-icon{background:var(--green-bg);color:var(--green)}
    .stat-icon svg{width:20px;height:20px}
    .stat-num{font-family:'Instrument Serif',serif;font-size:44px;line-height:1;letter-spacing:-.02em;margin-bottom:4px}
    .c-blue  .stat-num{color:var(--accent)}
    .c-amber .stat-num{color:var(--amber)}
    .c-green .stat-num{color:var(--green)}
    .stat-lbl{font-size:13px;color:var(--ink2);font-weight:500}

    /* ── Layout 2 colonnes ── */
    .cols{display:grid;grid-template-columns:1fr 300px;gap:24px}

    /* ── Card ── */
    .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
    .card-head{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
    .card-title{font-size:15px;font-weight:600;display:flex;align-items:center;gap:8px}
    .card-title svg{width:16px;height:16px;color:var(--ink2)}
    .chip{font-size:11px;background:var(--surface2);border:1px solid var(--border);border-radius:20px;padding:3px 10px;color:var(--ink2)}

    /* ── Table ── */
    .tbl-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse}
    thead th{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--ink2);padding:11px 20px;text-align:left;background:var(--surface2);border-bottom:1px solid var(--border)}
    tbody tr{border-bottom:1px solid var(--border);transition:background .15s}
    tbody tr:last-child{border-bottom:none}
    tbody tr:hover{background:var(--surface2)}
    tbody td{padding:13px 20px;font-size:13px}
    .badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
    .badge::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
    .badge-green{background:var(--green-bg);color:var(--green)}
    .badge-amber{background:var(--amber-bg);color:var(--amber)}
    .empty{padding:48px 24px;text-align:center;color:var(--ink2)}
    .empty svg{width:36px;height:36px;color:var(--border);margin-bottom:10px;display:block;margin-inline:auto}
    .empty p{font-size:14px}

    /* ── Profil sidebar card ── */
    .prof-body{padding:24px}
    .prof-av{width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#2563c4,#60a5fa);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;margin:0 auto 14px}
    .prof-name{text-align:center;font-family:'Instrument Serif',serif;font-size:19px;margin-bottom:3px}
    .prof-email{text-align:center;font-size:11px;color:var(--ink2);margin-bottom:18px;word-break:break-all}
    .prof-divider{height:1px;background:var(--border);margin-bottom:16px}
    .prof-row{display:flex;justify-content:space-between;align-items:flex-start;padding:8px 0;font-size:13px;border-bottom:1px solid var(--border)}
    .prof-row:last-of-type{border-bottom:none}
    .prof-key{color:var(--ink2);font-weight:500;flex-shrink:0;margin-right:8px}
    .prof-val{color:var(--ink);font-weight:600;text-align:right;word-break:break-all;max-width:60%}
    .qr-box{margin-top:16px;background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center}
    .qr-box-text{font-size:10px;color:var(--ink2);margin-top:8px;word-break:break-all;letter-spacing:.03em}

    /* ── Animations ── */
    .fu{opacity:0;transform:translateY(14px);animation:fu .45s ease forwards}
    .fu:nth-child(1){animation-delay:.04s}.fu:nth-child(2){animation-delay:.09s}
    .fu:nth-child(3){animation-delay:.14s}.fu:nth-child(4){animation-delay:.19s}
    .fu:nth-child(5){animation-delay:.24s}
    @keyframes fu{to{opacity:1;transform:translateY(0)}}

    /* ── Responsive ── */
    @media(max-width:1080px){.cols{grid-template-columns:1fr}}
    @media(max-width:900px){.sidebar{transform:translateX(-100%)}.main{margin-left:0;padding:24px 16px}.stats-grid{grid-template-columns:1fr 1fr}}
    @media(max-width:520px){.stats-grid{grid-template-columns:1fr}.topbar{flex-direction:column;gap:14px}}
  </style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
  <div class="sb-brand">
    <div class="sb-brand-name">AquaCycle</div>
    <div class="sb-brand-tag">Espace client</div>
  </div>

  <div class="sb-user">
    <div class="sb-avatar"><?= $initiales ?></div>
    <div>
      <div class="sb-uname"><?= $prenom_affiche ?> <?= $nom_affiche ?></div>
      <div class="sb-urole">Client</div>
    </div>
  </div>

  <nav class="sb-nav">
    <div class="sb-nav-label">Navigation</div>

    <button class="nav-item active" onclick="show('tableau', this)">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Tableau de bord
    </button>

    <button class="nav-item" onclick="show('historique', this)">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Historique
    </button>

    <button class="nav-item" onclick="show('profil', this)">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Mon profil
    </button>
  </nav>

  <div class="sb-bottom">
    <a href="../auth/logout.php" class="btn-logout">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Déconnexion
    </a>
  </div>
</aside>

<!-- ══ MAIN ══ -->
<main class="main">

  <!-- ═══ TABLEAU DE BORD ═══ -->
  <section id="s-tableau">

    <div class="topbar fu">
      <div>
        <div class="greeting-sub"><?= $salutation ?> 👋</div>
        <div class="greeting-title"><?= $prenom_affiche ?> <em><?= $nom_affiche ?></em></div>
      </div>
      <div class="topbar-right">
        <div class="date-chip"><?= date('d/m/Y') ?></div>
        <?php if($qr_code): ?>
        <div class="qr-chip">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/></svg>
          <?= $qr_code ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card c-blue fu">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
        <div class="stat-num"><?= $total_emprunts ?></div>
        <div class="stat-lbl">Total emprunts</div>
      </div>
      <div class="stat-card c-amber fu">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="stat-num"><?= $en_cours ?></div>
        <div class="stat-lbl">En cours</div>
      </div>
      <div class="stat-card c-green fu">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div class="stat-num"><?= $total_rendus ?></div>
        <div class="stat-lbl">Bouteilles rendues</div>
      </div>
    </div>

    <div class="cols">
      <!-- Transactions récentes -->
      <div class="card fu">
        <div class="card-head">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Transactions récentes
          </div>
          <span class="chip"><?= count($transactions) ?> entrées</span>
        </div>
        <?php if(empty($transactions)): ?>
        <div class="empty">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          <p>Aucune transaction pour le moment.</p>
        </div>
        <?php else: ?>
        <div class="tbl-wrap">
          <table>
            <thead><tr><th>Bouteille</th><th>Revendeur</th><th>Emprunté</th><th>Rendu</th><th>Statut</th></tr></thead>
            <tbody>
              <?php foreach($transactions as $t): ?>
              <tr>
                <td><code style="font-size:11px;background:var(--surface2);padding:2px 7px;border-radius:5px"><?= htmlspecialchars($t['bouteille_qr']) ?></code></td>
                <td><?= htmlspecialchars($t['nom_boutique']) ?></td>
                <td style="color:var(--ink2)"><?= $t['date_emprunt'] ? date('d/m/Y',strtotime($t['date_emprunt'])) : '—' ?></td>
                <td style="color:var(--ink2)"><?= $t['date_rendu']   ? date('d/m/Y',strtotime($t['date_rendu']))   : '—' ?></td>
                <td><?php if($t['date_rendu']): ?><span class="badge badge-green">Rendu</span><?php else: ?><span class="badge badge-amber">En cours</span><?php endif; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Mini profil -->
      <div class="card fu">
        <div class="card-head">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Mon profil
          </div>
        </div>
        <div class="prof-body">
          <div class="prof-av"><?= $initiales ?></div>
          <div class="prof-name"><?= $prenom_affiche ?> <?= $nom_affiche ?></div>
          <div class="prof-email"><?= htmlspecialchars($_SESSION['email']) ?></div>
          <div class="prof-divider"></div>
          <div class="prof-row"><span class="prof-key">CIN</span><span class="prof-val"><?= $cin ?: '—' ?></span></div>
          <?php if($telephone): ?><div class="prof-row"><span class="prof-key">Tél.</span><span class="prof-val"><?= $telephone ?></span></div><?php endif; ?>
          <?php if($ville):     ?><div class="prof-row"><span class="prof-key">Ville</span><span class="prof-val"><?= $ville ?></span></div><?php endif; ?>
          <?php if($qr_code): ?>
          <div class="qr-box">
            <div id="qr1"></div>
            <div class="qr-box-text"><?= $qr_code ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </section>

  <!-- ═══ HISTORIQUE ═══ -->
  <section id="s-historique" style="display:none">
    <div class="topbar fu">
      <div>
        <div class="greeting-sub">Lecture seule</div>
        <div class="greeting-title">Mon <em>historique</em></div>
      </div>
    </div>
    <div class="card fu">
      <div class="card-head">
        <div class="card-title">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Toutes mes transactions
        </div>
        <span class="chip"><?= count($transactions) ?></span>
      </div>
      <?php if(empty($transactions)): ?>
      <div class="empty">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p>Aucune transaction enregistrée.</p>
      </div>
      <?php else: ?>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>#</th><th>Bouteille (QR)</th><th>Revendeur</th><th>Date emprunt</th><th>Date retour</th><th>Statut</th></tr></thead>
          <tbody>
            <?php foreach($transactions as $i => $t): ?>
            <tr>
              <td style="color:var(--ink2);font-size:12px"><?= $i+1 ?></td>
              <td><code style="font-size:11px;background:var(--surface2);padding:2px 7px;border-radius:5px"><?= htmlspecialchars($t['bouteille_qr']) ?></code></td>
              <td><?= htmlspecialchars($t['nom_boutique']) ?></td>
              <td style="color:var(--ink2)"><?= $t['date_emprunt'] ? date('d/m/Y H:i',strtotime($t['date_emprunt'])) : '—' ?></td>
              <td style="color:var(--ink2)"><?= $t['date_rendu']   ? date('d/m/Y H:i',strtotime($t['date_rendu']))   : '—' ?></td>
              <td><?php if($t['date_rendu']): ?><span class="badge badge-green">Rendu</span><?php else: ?><span class="badge badge-amber">En cours</span><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ═══ PROFIL ═══ -->
  <section id="s-profil" style="display:none">
    <div class="topbar fu">
      <div>
        <div class="greeting-sub">Lecture seule</div>
        <div class="greeting-title">Mon <em>profil</em></div>
      </div>
    </div>
    <div class="cols">
      <div class="card fu">
        <div class="card-head">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Informations personnelles
          </div>
        </div>
        <div style="padding:24px">
          <div class="prof-row"><span class="prof-key">Nom complet</span><span class="prof-val"><?= $prenom_affiche ?> <?= $nom_affiche ?></span></div>
          <div class="prof-row"><span class="prof-key">Email</span><span class="prof-val"><?= htmlspecialchars($_SESSION['email']) ?></span></div>
          <div class="prof-row"><span class="prof-key">CIN</span><span class="prof-val"><?= $cin ?: '—' ?></span></div>
          <div class="prof-row"><span class="prof-key">QR Code</span><span class="prof-val" style="font-size:11px"><?= $qr_code ?: '—' ?></span></div>
          <?php if($telephone): ?><div class="prof-row"><span class="prof-key">Téléphone</span><span class="prof-val"><?= $telephone ?></span></div><?php endif; ?>
          <?php if($ville):     ?><div class="prof-row"><span class="prof-key">Ville</span><span class="prof-val"><?= $ville ?></span></div><?php endif; ?>
        </div>
      </div>
      <div class="card fu">
        <div class="card-head">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/></svg>
            Mon QR Code
          </div>
        </div>
        <div style="padding:24px;text-align:center">
          <p style="font-size:13px;color:var(--ink2);margin-bottom:20px">Présentez ce QR code chez un revendeur pour emprunter une bouteille.</p>
          <?php if($qr_code): ?>
          <div class="qr-box"><div id="qr2"></div><div class="qr-box-text"><?= $qr_code ?></div></div>
          <?php else: ?>
          <div style="padding:32px;color:var(--ink2);font-size:13px">QR code non disponible.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  // ── Navigation
  function show(name, btn) {
    document.querySelectorAll('main section').forEach(s => s.style.display = 'none');
    document.getElementById('s-' + name).style.display = 'block';
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    btn.classList.add('active');
  }

  // ── QR Codes
  const qrVal = <?= json_encode($qr_code) ?>;
  const qrOpts = { text: qrVal || ' ', width: 140, height: 140, colorDark: '#1a1713', colorLight: '#f0ece3', correctLevel: QRCode.CorrectLevel.M };

  if (qrVal) {
    new QRCode(document.getElementById('qr1'), qrOpts);
    new QRCode(document.getElementById('qr2'), qrOpts);
  }
</script>
</body>
</html>
