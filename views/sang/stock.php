<?php
$totalPoches = array_sum(array_column($stock, 'poches'));
$maxEchelle = max(1, max(array_map(function ($s) { return max((int) $s['poches'], (int) $s['seuil_alerte'] * 2); }, $stock)));
?>
<section class="kpi-grid kpi-grid-3">
    <article class="stat"><span class="stat-label">Poches en stock</span><strong><?= (int) $totalPoches ?></strong></article>
    <article class="stat <?= $critiques ? 'stat-danger' : 'stat-success' ?>"><span class="stat-label">Groupes en stock critique</span><strong><?= count($critiques) ?></strong></article>
    <article class="stat <?= $enAttente ? 'stat-warning' : '' ?>"><span class="stat-label">Demandes en attente</span><strong><?= (int) $enAttente ?></strong></article>
</section>

<section class="blood-grid">
    <?php foreach ($stock as $s):
        $bas = (int) $s['poches'] < (int) $s['seuil_alerte'];
        $pct = min(100, round($s['poches'] / $maxEchelle * 100));
        $seuilPct = min(100, round($s['seuil_alerte'] / $maxEchelle * 100));
    ?>
        <article class="blood-card<?= $bas ? ' low' : '' ?>">
            <div class="blood-head"><span class="blood-group"><?= e($s['groupe_sanguin']) ?></span>
                <?= $bas ? '<span class="badge badge-danger">Critique</span>' : '<span class="badge badge-success">Suffisant</span>' ?></div>
            <div class="blood-count"><strong><?= (int) $s['poches'] ?></strong><span>poche(s)</span></div>
            <div class="gauge"><div class="gauge-fill" style="width: <?= $pct ?>%"></div><span class="gauge-mark" style="left: <?= $seuilPct ?>%" title="Seuil d'alerte"></span></div>
            <small class="muted">Seuil d'alerte : <?= (int) $s['seuil_alerte'] ?> poches</small>
        </article>
    <?php endforeach; ?>
</section>

<section class="grid-2">
    <article class="card">
        <header class="card-head"><h2>Compatibilité transfusionnelle</h2></header>
        <p class="muted small">Groupes dont le receveur peut recevoir du sang. Utile quand le stock du groupe demandé est insuffisant.</p>
        <div class="table-wrap"><table class="table table-compact">
            <thead><tr><th>Receveur</th><th>Peut recevoir de</th></tr></thead>
            <tbody>
            <?php foreach ($compatibilite as $recepteur => $donneurs): ?>
                <tr><td><?= groupe_pastille($recepteur) ?></td>
                    <td><?php foreach ($donneurs as $d): ?><span class="groupe groupe-soft"><?= e($d) ?></span> <?php endforeach; ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </article>

    <article class="card">
        <header class="card-head"><h2>Derniers dons</h2><a class="link" href="<?= e(url('donneurs')) ?>">Voir les donneurs</a></header>
        <?php if (!$derniersDons): ?>
            <div class="empty"><?= icon('drop', 28) ?><p>Aucun don enregistré.</p></div>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($derniersDons as $d): ?>
                    <li><span class="groupe"><?= e($d['groupe_sanguin']) ?></span>
                        <div><strong><?= e($d['donneur']) ?></strong><small class="muted"><?= e(date_fr($d['date_don'])) ?> · <?= (int) $d['volume_ml'] ?> mL</small></div></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>
