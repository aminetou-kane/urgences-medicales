<?php
$lits = ['total' => 0, 'dispo' => 0];
foreach ($ressources as $r) {
    if ($r['type'] === 'Lit') {
        $lits = ['total' => (int) $r['total'], 'dispo' => (int) $r['dispo']];
    }
}
$maxAdm = max(1, max(array_column($admissions, 'n')));
$totalUrg = max(1, array_sum($urgences));
$critiques = array_filter($stock, function ($s) { return (int) $s['poches'] < (int) $s['seuil_alerte']; });
$joursCourts = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
$couleurUrg = ['Absolue' => 'var(--danger)', 'Moyenne' => 'var(--warning)', 'Faible' => 'var(--info)'];
$nbAlertes = ($enRetard > 0 ? 1 : 0) + (count($critiques) > 0 ? 1 : 0) + ($demandesAttente > 0 ? 1 : 0);
?>

<section class="kpi-grid">
    <article class="kpi">
        <span class="kpi-icon tone-primary"><?= icon('users', 22) ?></span>
        <div><span class="kpi-label">Patients en cours</span><strong class="kpi-value"><?= (int) $actifs ?></strong>
        <small class="kpi-sub"><?= (int) $statuts['En attente'] ?> en attente · <?= (int) $statuts['En prise en charge'] ?> pris en charge</small></div>
    </article>
    <article class="kpi">
        <span class="kpi-icon tone-danger"><?= icon('alert', 22) ?></span>
        <div><span class="kpi-label">Urgences absolues</span><strong class="kpi-value"><?= (int) $urgences['Absolue'] ?></strong>
        <small class="kpi-sub<?= $enRetard > 0 ? ' text-danger' : '' ?>"><?= $enRetard > 0 ? (int) $enRetard . ' en attente depuis plus de 15 min' : 'Aucune en retard' ?></small></div>
    </article>
    <article class="kpi">
        <span class="kpi-icon tone-teal"><?= icon('clock', 22) ?></span>
        <div><span class="kpi-label">Attente moyenne</span><strong class="kpi-value"><?= $attenteMoy === null ? '-' : (int) $attenteMoy . '<em> min</em>' ?></strong>
        <small class="kpi-sub">avant prise en charge (7 jours)</small></div>
    </article>
    <article class="kpi">
        <span class="kpi-icon tone-success"><?= icon('bed', 22) ?></span>
        <div><span class="kpi-label">Lits disponibles</span><strong class="kpi-value"><?= (int) $lits['dispo'] ?><em> / <?= (int) $lits['total'] ?></em></strong>
        <small class="kpi-sub"><?= (int) $compteurs['Occupé'] ?> ressource(s) occupée(s) au total</small></div>
    </article>
</section>

<section class="grid-2">
    <article class="card">
        <header class="card-head"><h2>Admissions des 7 derniers jours</h2><span class="muted"><?= array_sum(array_column($admissions, 'n')) ?> patients</span></header>
        <svg class="chart" viewBox="0 0 420 190" role="img" aria-label="Admissions par jour">
            <?php foreach ($admissions as $i => $a):
                $h = (int) round(($a['n'] / $maxAdm) * 120);
                $x = 18 + $i * 57;
                $y = 140 - $h;
                $jour = $joursCourts[(int) date('N', strtotime($a['date'])) - 1];
                $aujourdhui = $i === count($admissions) - 1;
            ?>
                <rect x="<?= $x ?>" y="<?= $y ?>" width="38" height="<?= max($h, 2) ?>" rx="6" fill="<?= $aujourdhui ? 'var(--primary)' : 'var(--primary-soft-2)' ?>"/>
                <text x="<?= $x + 19 ?>" y="<?= $y - 8 ?>" text-anchor="middle" class="chart-val"><?= (int) $a['n'] ?></text>
                <text x="<?= $x + 19 ?>" y="162" text-anchor="middle" class="chart-lbl"><?= e($jour) ?></text>
                <text x="<?= $x + 19 ?>" y="178" text-anchor="middle" class="chart-lbl chart-lbl-sub"><?= e(date('d/m', strtotime($a['date']))) ?></text>
            <?php endforeach; ?>
            <line x1="8" y1="141" x2="412" y2="141" stroke="var(--line)" stroke-width="1"/>
        </svg>
    </article>

    <article class="card">
        <header class="card-head"><h2>Cas actifs par urgence</h2><span class="muted"><?= (int) array_sum($urgences) ?> cas</span></header>
        <div class="bars">
            <?php foreach ($urgences as $nom => $n): ?>
                <div class="bar-row">
                    <span class="bar-label"><?= e($nom) ?></span>
                    <div class="bar-track"><div class="bar-fill" style="width: <?= round($n / $totalUrg * 100) ?>%; background: <?= $couleurUrg[$nom] ?>"></div></div>
                    <strong class="bar-num"><?= (int) $n ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <h3 class="subhead">Occupation des ressources</h3>
        <div class="bars">
            <?php foreach ($ressources as $r):
                $pct = $r['total'] > 0 ? round($r['occupe'] / $r['total'] * 100) : 0; ?>
                <div class="bar-row">
                    <span class="bar-label"><?= e($r['type']) ?></span>
                    <div class="bar-track"><div class="bar-fill" style="width: <?= $pct ?>%; background: <?= $pct >= 80 ? 'var(--danger)' : ($pct >= 50 ? 'var(--warning)' : 'var(--success)') ?>"></div></div>
                    <strong class="bar-num"><?= (int) $r['occupe'] ?>/<?= (int) $r['total'] ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="grid-2 grid-2-wide">
    <article class="card">
        <header class="card-head"><h2>File d'attente prioritaire</h2><a class="link" href="<?= e(url('patients', ['statut' => 'En attente'])) ?>">Voir tout</a></header>
        <?php if (!$file): ?>
            <div class="empty"><?= icon('check', 28) ?><p>Aucun patient en attente.</p></div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Patient</th><th>Âge</th><th>Groupe</th><th>Urgence</th><th class="num">Attente</th></tr></thead>
                <tbody>
                <?php foreach ($file as $p): $retard = $p['degre_urgence'] === 'Absolue' && (int) $p['attente_min'] > 15; ?>
                    <tr>
                        <td><strong><?= e($p['prenom'] . ' ' . $p['nom']) ?></strong></td>
                        <td><?= (int) $p['age'] ?> ans</td>
                        <td><?= groupe_pastille($p['groupe_sanguin']) ?></td>
                        <td><?= badge_urgence($p['degre_urgence']) ?></td>
                        <td class="num <?= $retard ? 'text-danger fw' : '' ?>"><?= e(duree_minutes($p['attente_min'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </article>

    <article class="card">
        <header class="card-head"><h2>Alertes</h2><span class="muted"><?= $nbAlertes ?> active(s)</span></header>
        <ul class="alerts">
            <?php if ($enRetard > 0): ?>
                <li class="alert-item alert-danger"><?= icon('alert', 20) ?><div><strong><?= (int) $enRetard ?> urgence(s) absolue(s)</strong><span>en attente depuis plus de 15 minutes</span></div></li>
            <?php endif; ?>
            <?php if ($critiques): ?>
                <li class="alert-item alert-warning"><?= icon('drop', 20) ?><div><strong>Stock de sang critique</strong><span><?= e(implode(', ', array_column($critiques, 'groupe_sanguin'))) ?></span></div></li>
            <?php endif; ?>
            <?php if ($demandesAttente > 0): ?>
                <li class="alert-item alert-info"><?= icon('pulse', 20) ?><div><strong><?= (int) $demandesAttente ?> demande(s) de sang</strong><span>en attente de validation</span></div></li>
            <?php endif; ?>
            <?php if ($nbAlertes === 0): ?>
                <li class="alert-item alert-success"><?= icon('check', 20) ?><div><strong>Aucune alerte</strong><span>Tout est sous contrôle.</span></div></li>
            <?php endif; ?>
        </ul>

        <?php if ($peutSang && $stock): ?>
            <h3 class="subhead">Stock de sang</h3>
            <div class="stock-mini">
                <?php foreach ($stock as $s): $bas = (int) $s['poches'] < (int) $s['seuil_alerte']; ?>
                    <div class="stock-chip<?= $bas ? ' low' : '' ?>"><span><?= e($s['groupe_sanguin']) ?></span><strong><?= (int) $s['poches'] ?></strong></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>
