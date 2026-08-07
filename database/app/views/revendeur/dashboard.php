<?php $pageTitle = 'Dashboard Revendeur — ' . htmlspecialchars($r['nom_boutique']); ?>
<?php require APP . '/views/shared/header.php'; ?>

<div class="dashboard revendeur-dash">
    <div class="dash-header">
        <div>
            <h1 class="dash-title">Mon espace revendeur</h1>
            <p class="dash-subtitle">🏪 <?= htmlspecialchars($r['nom_boutique']) ?> · <?= htmlspecialchars($r['nom_entreprise']) ?></p>
        </div>
    </div>

    <div class="stats-grid stats-grid--3">
        <div class="stat-card stat--blue">
            <div class="stat-icon">↗</div>
            <div class="stat-value"><?= count($enCours) ?></div>
            <div class="stat-label">En cours</div>
        </div>
        <div class="stat-card stat--green">
            <div class="stat-icon">✓</div>
            <div class="stat-value"><?= count($terminees) ?></div>
            <div class="stat-label">Terminées</div>
        </div>
        <div class="stat-card stat--orange">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= count($commandes) ?></div>
            <div class="stat-label">Commandes</div>
        </div>
    </div>

    <div class="dash-grid-2">
        <!-- ── Scanner QR ─────────────────────────────── -->
        <section class="section card-primary">
            <h2 class="section-title">📷 Scanner une bouteille</h2>

            <!-- Zone caméra -->
            <div class="scanner-area">
                <div id="qr-preview-box">
                    <video id="qr-video" playsinline></video>
                    <canvas id="qr-canvas" hidden></canvas>
                    <div class="scan-overlay">
                        <div class="scan-line"></div>
                        <div class="scan-corners">
                            <span class="corner corner--tl"></span>
                            <span class="corner corner--tr"></span>
                            <span class="corner corner--bl"></span>
                            <span class="corner corner--br"></span>
                        </div>
                    </div>
                    <div id="scan-status" class="scan-status">En attente...</div>
                </div>

                <div class="scanner-controls">
                    <button id="btn-start-scan" class="btn-primary" onclick="startScan()">
                        📷 Activer la caméra
                    </button>
                    <button id="btn-stop-scan" class="btn-secondary hidden" onclick="stopScan()">
                        ⏹ Arrêter
                    </button>
                </div>

                <div class="scanner-manual">
                    <p>— ou saisie manuelle —</p>
                    <div class="input-row">
                        <input type="text" id="qr-input" placeholder="ex: AQUA-1717000000-A3F2">
                        <button class="btn-primary" onclick="lookupQr()">Chercher</button>
                    </div>
                </div>
            </div>

            <!-- Résultat scan -->
            <div id="scan-result" class="scan-result hidden">
                <div class="result-info">
                    <p>QR : <strong id="res-qr"></strong></p>
                    <p>Statut : <span id="res-status" class="badge"></span></p>
                    <p>Entreprise : <strong id="res-entreprise"></strong></p>
                    <div id="res-trans" class="hidden">
                        <p>Emprunté par : <strong id="res-client-name"></strong></p>
                        <p>CIN : <strong id="res-client-cin"></strong></p>
                        <p>Depuis le : <strong id="res-date-emprunt"></strong></p>
                    </div>
                </div>

                <!-- Action emprunter -->
                <div id="action-emprunter" class="action-panel hidden">
                    <h4>Enregistrer un emprunt</h4>
                    <div class="field-group" style="position:relative">
                        <label>Rechercher le client</label>
                        <input type="text" id="client-search"
                               placeholder="Nom, prénom ou CIN…"
                               oninput="rechercheClient(this.value)">
                        <div id="client-results" class="dropdown-results hidden"></div>
                        <input type="hidden" id="selected-client-cin">
                        <p id="selected-client-label" class="selected-label"></p>
                    </div>
                    <button class="btn-primary btn-full" onclick="enregistrerEmprunt()">
                        ✓ Confirmer l'emprunt
                    </button>
                </div>

                <!-- Action rendre -->
                <div id="action-rendre" class="action-panel hidden">
                    <h4>Enregistrer un retour</h4>
                    <button class="btn-secondary btn-full" onclick="enregistrerRetour()">
                        ↩ Confirmer le retour
                    </button>
                </div>

                <!-- Info statut non actionnable -->
                <div id="action-info" class="action-panel hidden">
                    <p id="action-info-text" class="info-msg"></p>
                </div>

                <button class="btn-secondary btn-full" style="margin-top:.8rem"
                        onclick="resetScanner()">
                    🔄 Scanner une autre bouteille
                </button>
            </div>
        </section>

        <!-- ── Commander ──────────────────────────────── -->
        <section class="section">
            <h2 class="section-title">📦 Passer une commande</h2>
            <form method="POST" action="/index.php?url=revendeur/commander" class="order-form">
                <div class="field-group">
                    <label>Nombre de bouteilles</label>
                    <input type="number" name="quantite" min="1" value="10" required>
                </div>
                <button type="submit" class="btn-primary">Envoyer →</button>
            </form>

            <h3 class="subsection-title">Mes commandes</h3>
            <?php if (empty($commandes)): ?>
                <p class="empty-state">Aucune commande.</p>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>Qté</th><th>Date</th><th>Statut</th></tr></thead>
                    <tbody>
                    <?php foreach ($commandes as $cmd): ?>
                    <tr>
                        <td><?= $cmd['quantite'] ?></td>
                        <td><?= substr($cmd['date_commande'],0,10) ?></td>
                        <td><span class="badge badge--<?= strtolower($cmd['statut']) ?>"><?= $cmd['statut'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Emprunts en cours -->
    <?php if (!empty($enCours)): ?>
    <section class="section card-primary">
        <h2 class="section-title">⏳ Emprunts en cours</h2>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Bouteille QR</th><th>Entreprise</th><th>Client</th><th>CIN</th><th>Emprunté le</th></tr>
                </thead>
                <tbody>
                <?php foreach ($enCours as $t): ?>
                <tr class="row-active">
                    <td><code><?= htmlspecialchars($t['qr_code_bouteille']) ?></code></td>
                    <td><?= htmlspecialchars($t['nom_entreprise']) ?></td>
                    <td><?= htmlspecialchars($t['prenom'].' '.$t['nom']) ?></td>
                    <td><?= htmlspecialchars($t['CIN']) ?></td>
                    <td><?= substr($t['date_emprunt'],0,16) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <!-- Historique -->
    <?php if (!empty($terminees)): ?>
    <section class="section">
        <h2 class="section-title">Historique</h2>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Bouteille QR</th><th>Client</th><th>Emprunté le</th><th>Rendu le</th><th>Statut</th></tr>
                </thead>
                <tbody>
                <?php foreach ($terminees as $t): ?>
                <tr>
                    <td><code><?= htmlspecialchars($t['qr_code_bouteille']) ?></code></td>
                    <td><?= htmlspecialchars($t['prenom'].' '.$t['nom'].' ('.$t['CIN'].')') ?></td>
                    <td><?= substr($t['date_emprunt'],0,16) ?></td>
                    <td><?= $t['date_retour'] ? substr($t['date_retour'],0,16) : '—' ?></td>
                    <td><span class="badge badge--<?= strtolower($t['statut']) ?>"><?= $t['statut'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- jsQR chargé en dernier -->
<script src="/js/jsqr.js"></script>
<script>
// ── Variables globales ────────────────────────────────
let currentQr    = null;
let scanStream   = null;
let scanInterval = null;
let scanPaused   = false;

// ── Démarrer la caméra et la détection ───────────────
async function startScan() {
    try {
        scanStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: 640, height: 480 }
        });
        const video = document.getElementById('qr-video');
        video.srcObject = scanStream;
        video.play();

        document.getElementById('btn-start-scan').classList.add('hidden');
        document.getElementById('btn-stop-scan').classList.remove('hidden');
        document.getElementById('scan-status').textContent = '🔍 Recherche d\'un QR code...';

        // Lancer la détection toutes les 300ms
        video.addEventListener('loadedmetadata', () => {
            scanInterval = setInterval(() => detecterQr(video), 300);
        });

    } catch (e) {
        showNotif('Caméra indisponible : ' + e.message, 'error');
    }
}

// ── Détecter le QR code dans chaque frame ────────────
function detecterQr(video) {
    if (scanPaused) return;
    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;

    const canvas  = document.getElementById('qr-canvas');
    const context = canvas.getContext('2d');

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
    const code      = jsQR(imageData.data, imageData.width, imageData.height, {
        inversionAttempts: 'dontInvert'
    });

    if (code) {
        // QR code trouvé — on met en pause et on cherche la bouteille
        scanPaused = true;
        document.getElementById('scan-status').textContent = '✓ QR code détecté !';
        lookupQr(code.data);
    }
}

// ── Arrêter la caméra ─────────────────────────────────
function stopScan() {
    if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
    if (scanStream)   { scanStream.getTracks().forEach(t => t.stop()); scanStream = null; }
    scanPaused = false;
    document.getElementById('btn-start-scan').classList.remove('hidden');
    document.getElementById('btn-stop-scan').classList.add('hidden');
    document.getElementById('scan-status').textContent = 'En attente...';
}

// ── Rechercher la bouteille par QR ───────────────────
async function lookupQr(qr = null) {
    qr = qr || document.getElementById('qr-input').value.trim();
    if (!qr) return;
    currentQr = qr;

    document.getElementById('scan-status').textContent = '⏳ Chargement...';

    const res  = await fetch('/index.php?url=revendeur/scanner', {
        method:  'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body:    'qr_code=' + encodeURIComponent(qr)
    });
    const data = await res.json();

    if (data.error) {
        showNotif(data.error, 'error');
        scanPaused = false;
        document.getElementById('scan-status').textContent = '🔍 Recherche d\'un QR code...';
        return;
    }
    afficherResultat(data);
}

// ── Afficher le résultat du scan ─────────────────────
function afficherResultat(data) {
    const b = data.bouteille;

    document.getElementById('res-qr').textContent         = b.qr_code;
    document.getElementById('res-entreprise').textContent = b.nom_entreprise || '';

    const st = document.getElementById('res-status');
    st.textContent = b.statut;
    st.className   = 'badge badge--' + b.statut.toLowerCase().replace(/_/g, '-');

    // Transaction en cours
    const td = document.getElementById('res-trans');
    if (data.transaction) {
        td.classList.remove('hidden');
        document.getElementById('res-client-name').textContent  = data.transaction.prenom + ' ' + data.transaction.nom;
        document.getElementById('res-client-cin').textContent   = data.transaction.CIN_client;
        document.getElementById('res-date-emprunt').textContent = data.transaction.date_emprunt;
    } else {
        td.classList.add('hidden');
    }

    // Afficher la bonne action
    ['action-emprunter', 'action-rendre', 'action-info'].forEach(id =>
        document.getElementById(id).classList.add('hidden'));

    if (b.statut === 'DISPONIBLE_REVENDEUR') {
        document.getElementById('action-emprunter').classList.remove('hidden');
    } else if (b.statut === 'EMPRUNTEE') {
        document.getElementById('action-rendre').classList.remove('hidden');
    } else {
        document.getElementById('action-info-text').textContent =
            'Statut : ' + b.statut + '. Aucune action disponible ici.';
        document.getElementById('action-info').classList.remove('hidden');
    }

    document.getElementById('scan-result').classList.remove('hidden');
    document.getElementById('scan-status').textContent = '✓ ' + b.qr_code;
}

// ── Réinitialiser pour scanner une autre bouteille ───
function resetScanner() {
    currentQr  = null;
    scanPaused = false;
    document.getElementById('scan-result').classList.add('hidden');
    document.getElementById('qr-input').value        = '';
    document.getElementById('selected-client-cin').value    = '';
    document.getElementById('selected-client-label').textContent = '';
    document.getElementById('client-search').value   = '';
    if (scanStream) {
        document.getElementById('scan-status').textContent = '🔍 Recherche d\'un QR code...';
    }
}

// ── Recherche client ──────────────────────────────────
let searchTO;
function rechercheClient(q) {
    clearTimeout(searchTO);
    if (q.length < 2) {
        document.getElementById('client-results').classList.add('hidden');
        return;
    }
    searchTO = setTimeout(async () => {
        const data = await fetch('/index.php?url=revendeur/rechercheClient?q=' + encodeURIComponent(q))
            .then(r => r.json());
        const box  = document.getElementById('client-results');
        if (!data.length) { box.classList.add('hidden'); return; }
        box.innerHTML = data.map(c =>
            `<div class="dropdown-item"
                  onclick="selectClient('${c.CIN}','${c.prenom} ${c.nom}')">
                ${c.prenom} ${c.nom}
                <small>CIN : ${c.CIN}</small>
             </div>`
        ).join('');
        box.classList.remove('hidden');
    }, 300);
}

function selectClient(cin, label) {
    document.getElementById('selected-client-cin').value        = cin;
    document.getElementById('selected-client-label').textContent = '✓ ' + label + ' (' + cin + ')';
    document.getElementById('client-results').classList.add('hidden');
    document.getElementById('client-search').value = label;
}

// ── Enregistrer emprunt ──────────────────────────────
async function enregistrerEmprunt() {
    const cin = document.getElementById('selected-client-cin').value;
    if (!cin) { showNotif('Veuillez sélectionner un client.', 'error'); return; }

    const res  = await fetch('/index.php?url=revendeur/scanner', {
        method:  'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body:    'qr_code=' + encodeURIComponent(currentQr) +
                 '&action=emprunter&CIN_client=' + encodeURIComponent(cin)
    });
    const data = await res.json();
    if (data.success) {
        showNotif(data.message, 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showNotif(data.error || 'Erreur', 'error');
    }
}

// ── Enregistrer retour ───────────────────────────────
async function enregistrerRetour() {
    const res  = await fetch('/index.php?url=revendeur/scanner', {
        method:  'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body:    'qr_code=' + encodeURIComponent(currentQr) + '&action=rendre'
    });
    const data = await res.json();
    if (data.success) {
        showNotif(data.message, 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showNotif(data.error || 'Erreur', 'error');
    }
}

// ── Notification visuelle ────────────────────────────
function showNotif(msg, type) {
    const n = document.createElement('div');
    n.className = 'flash flash-' + type;
    n.style.cssText = 'position:fixed;top:70px;left:50%;transform:translateX(-50%);z-index:999;pointer-events:none';
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
}
</script>

<!-- Styles scanner -->
<style>
#qr-preview-box {
    position: relative;
    width: 100%;
    max-width: 320px;
    margin: 0 auto 1rem;
    background: #000;
    border-radius: var(--radius-lg);
    overflow: hidden;
    aspect-ratio: 1;
}
#qr-video {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
}
.scan-overlay {
    position: absolute; inset: 0;
    pointer-events: none;
}
.scan-line {
    position: absolute; left: 15%; right: 15%;
    height: 2px;
    background: var(--accent);
    box-shadow: 0 0 8px var(--accent);
    animation: scanMove 2s ease-in-out infinite;
}
@keyframes scanMove { 0%,100%{ top:15%; } 50%{ top:82%; } }

/* Coins du cadre de scan */
.scan-corners { position: absolute; inset: 20px; }
.corner {
    position: absolute;
    width: 20px; height: 20px;
    border-color: var(--accent);
    border-style: solid;
}
.corner--tl { top:0; left:0;  border-width: 3px 0 0 3px; }
.corner--tr { top:0; right:0; border-width: 3px 3px 0 0; }
.corner--bl { bottom:0; left:0;  border-width: 0 0 3px 3px; }
.corner--br { bottom:0; right:0; border-width: 0 3px 3px 0; }

.scan-status {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: rgba(0,0,0,.6);
    color: #fff;
    text-align: center;
    padding: .4rem;
    font-size: .8rem;
}
</style>

<?php require APP . '/views/shared/footer.php'; ?>
