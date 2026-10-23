<?php
$user = Auth::user();
$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$dateJour = $jours[(int) date('N') - 1] . ' ' . date('j') . ' ' . $mois[(int) date('n') - 1] . ' ' . date('Y');

$menu = [
    ['Principal', [
        ['dashboard', 'dashboard', 'Tableau de bord', 'dashboard', 'dashboard'],
        ['patients', 'users', 'Patients', 'patients', 'patients.voir'],
        ['ressources', 'bed', 'Ressources', 'ressources', 'ressources.voir'],
    ]],
    ['Banque de sang', [
        ['sang', 'drop', 'Stock de sang', 'sang', 'sang.voir'],
        ['donneurs', 'heart', 'Donneurs', 'donneurs', 'sang.voir'],
        ['demandes', 'pulse', 'Demandes de sang', 'demandes', 'sang.voir'],
    ]],
    ['Administration', [
        ['journal', 'file', "Journal d'activité", 'journal', 'journal.voir'],
    ]],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titre) ?> · Urgences Médicales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span class="brand-logo"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/></svg></span>
            <span class="brand-text"><strong>Urgences</strong><small>Médicales</small></span>
        </div>

        <nav class="nav" aria-label="Navigation principale">
            <?php foreach ($menu as $section): ?>
                <?php
                $items = array_filter($section[1], function ($i) { return Auth::can($i[4]); });
                if (!$items) { continue; }
                ?>
                <div class="nav-section"><?= e($section[0]) ?></div>
                <?php foreach ($items as $i): ?>
                    <a class="nav-link<?= $actif === $i[3] ? ' active' : '' ?>" href="<?= e(url($i[0])) ?>">
                        <?= icon($i[1], 18) ?><span><?= e($i[2]) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-user">
            <span class="avatar"><?= e(initiales($user)) ?></span>
            <div class="user-info">
                <strong><?= e($user['prenom'] . ' ' . $user['nom']) ?></strong>
                <small><?= e(Auth::roleLabel($user['role'])) ?></small>
            </div>
            <a class="icon-btn" href="<?= e(url('logout')) ?>" title="Se déconnecter" aria-label="Se déconnecter"><?= icon('logout', 18) ?></a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn menu-toggle" id="menu-toggle" type="button" aria-label="Ouvrir le menu"><?= icon('menu', 20) ?></button>
            <h1 class="page-title"><?= e($titre) ?></h1>
            <div class="topbar-date"><?= icon('clock', 16) ?> <?= e($dateJour) ?></div>
        </header>

        <main class="content">
            <?php foreach (flashes() as $f): ?>
                <div class="flash flash-<?= e($f['type']) ?>" role="status">
                    <?= icon($f['type'] === 'success' ? 'check' : 'alert', 18) ?>
                    <span><?= e($f['msg']) ?></span>
                    <button type="button" class="flash-close" data-dismiss aria-label="Fermer"><?= icon('x', 16) ?></button>
                </div>
            <?php endforeach; ?>

            <?= $contenu ?>
        </main>
    </div>
</div>
<div class="scrim" id="scrim"></div>
<script src="assets/js/app.js"></script>
</body>
</html>
