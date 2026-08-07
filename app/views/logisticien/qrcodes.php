<?php $pageTitle = 'QR Codes générés'; ?>
<?php require APP . '/views/shared/header.php'; ?>
<div class="dashboard logisticien-dash">
    <div class="dash-header">
        <div>
            <h1 class="dash-title">QR Codes générés</h1>
            <p class="dash-subtitle">📦 <?= htmlspecialchars($log['prenom'].' '.$log['nom']) ?></p>
        </div>
        <div>
            <a href="/index.php?url=logisticien/dashboard" class="btn-secondary">← Retour au dashboard</a>
            <button onclick="telechargerTout()" class="btn-secondary">⬇️ Tout télécharger</button>
            <button onclick="window.print()" class="btn-primary">🖨️ Imprimer</button>
        </div>
    </div>
    <?php if (isset($remplacement)): ?>
    <div class="flash flash-success" style="position:relative;transform:none;left:auto;margin-bottom:1.5rem">
        QR code remplacé — Ancien : <code><?= htmlspecialchars($remplacement['ancien']) ?></code>
        → Nouveau : <code><?= htmlspecialchars($remplacement['nouveau']) ?></code>
    </div>
    <?php endif; ?>
    <section class="section">
        <h2 class="section-title"><?= count($images) ?> QR code(s) — prêts à imprimer et coller</h2>
        <p class="text-muted" style="margin-bottom:1.5rem">
            Imprimez cette page et collez chaque étiquette sur la bouteille correspondante, ou téléchargez-les individuellement.
        </p>
        <div class="qr-grid">
            <?php foreach ($images as $qr => $chemin): ?>
            <div class="qr-card">
                <img src="<?= htmlspecialchars($chemin) ?>" alt="QR <?= htmlspecialchars($qr) ?>">
                <p class="qr-label"><?= htmlspecialchars($qr) ?></p>
                <a href="<?= htmlspecialchars($chemin) ?>" download="<?= htmlspecialchars($qr) ?>.png" class="qr-download">⬇️ Télécharger</a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<style>
.qr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1.5rem;
}
.qr-card {
    background: #fff;
    border: 2px dashed var(--border);
    border-radius: var(--radius);
    padding: 1rem;
    text-align: center;
}
.qr-card img {
    width: 140px;
    height: 140px;
    display: block;
    margin: 0 auto 0.5rem;
}
.qr-label {
    font-size: 0.75rem;
    font-family: monospace;
    color: #000;
    word-break: break-all;
}
.qr-download {
    display: inline-block;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    text-decoration: none;
    color: var(--primary, #4f46e5);
    font-weight: 600;
}
.qr-download:hover {
    text-decoration: underline;
}
@media print {
    .navbar, .dash-header a, .dash-header button,
    .section-title, .text-muted, .qr-download { display: none !important; }
    .qr-grid { grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
    .qr-card { border: 1px solid #000; break-inside: avoid; }
    body { background: #fff !important; }
}
</style>
<script>
function telechargerTout() {
    const liens = document.querySelectorAll('.qr-download');
    liens.forEach((lien, i) => {
        setTimeout(() => {
            const a = document.createElement('a');
            a.href = lien.href;
            a.download = lien.download;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }, i * 300);
    });
}
</script>
<?php require APP . '/views/shared/footer.php'; ?>
