<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Consignation') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="role-<?= htmlspecialchars(currentUser()['role'] ?? 'guest') ?>">

<nav class="navbar">
    <div class="nav-brand">
        <span class="nav-logo">◈</span>
        <span class="nav-title">Consignation</span>
    </div>
    <div class="nav-user">
        <span class="nav-role badge-role"><?= htmlspecialchars(currentUser()['role'] ?? '') ?></span>
        <span class="nav-email"><?= htmlspecialchars(currentUser()['email'] ?? '') ?></span>
        <a href="/index.php?url=auth/logout" class="btn-logout">Déconnexion</a>
    </div>
</nav>

<?php $flash = flashGet(); if ($flash): ?>

<div class="flash flash-<?= $flash['type'] ?>" id="flash-msg">
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<main class="main-content">
