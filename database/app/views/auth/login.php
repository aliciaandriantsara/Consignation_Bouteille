<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Consignation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="login-page">

<div class="login-wrapper">
    <div class="login-art">
        <div class="art-orb art-orb--1"></div>
        <div class="art-orb art-orb--2"></div>
        <div class="art-orb art-orb--3"></div>
        <div class="login-art-text">
            <span class="art-logo">◈</span>
            <h1 class="art-headline">Consignation</h1>
            <p class="art-sub">Gestion intelligente du cycle de vie des bouteilles réutilisables</p>
            <div class="art-cycle">
                <span class="cycle-item">Disponible</span>
                <span class="cycle-arrow">→</span>
                <span class="cycle-item">Emprunté</span>
                <span class="cycle-arrow">→</span>
                <span class="cycle-item">Rendu</span>
                <span class="cycle-arrow">→</span>
                <span class="cycle-item">Collecté</span>
                <span class="cycle-arrow">→</span>
                <span class="cycle-item">Lavé</span>
                <span class="cycle-arrow">→</span>
                <span class="cycle-item cycle-item--accent">Prêt</span>
            </div>
        </div>
    </div>

    <div class="login-form-panel">
        <div class="login-form-inner">
            <h2 class="form-title">Connexion</h2>
            <p class="form-sub">Accédez à votre espace de travail</p>

            <?php $flash = flashGet(); if ($flash): ?>
            <div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
            <?php endif; ?>

            <form method="POST" action="/index.php?url=auth/login" class="auth-form">
                <div class="field-group">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" required
                           placeholder="vous@exemple.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="field-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required
                           placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary btn-full">Se connecter →</button>
            </form>

            <div class="login-hint">
                <p>Comptes de démonstration <code>mot de passe : password</code></p>
                <div class="demo-accounts">
                    <button class="demo-btn" data-email="entreprise@test.com">🏭 Entreprise</button>
                    <button class="demo-btn" data-email="revendeur@test.com">🏪 Revendeur</button>
                    <button class="demo-btn" data-email="client@test.com">👤 Client</button>
                    <button class="demo-btn" data-email="livreur@test.com">🚚 Livreur</button>
                    <button class="demo-btn" data-email="logisticien@test.com">📦 Logisticien</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/app.js"></script>
</body>
</html>
