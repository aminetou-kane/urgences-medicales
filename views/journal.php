<?php
$libelles = [
    'connexion' => ['Connexion', 'neutral'], 'deconnexion' => ['Déconnexion', 'neutral'],
    'patient_ajout' => ['Admission', 'primary'], 'patient_modif' => ['Mise à jour', 'info'], 'patient_suppr' => ['Suppression', 'danger'], 'patient_export' => ['Export', 'neutral'],
    'ressource_ajout' => ['Ressource ajoutée', 'primary'], 'ressource_modif' => ['Ressource modifiée', 'info'], 'ressource_suppr' => ['Ressource supprimée', 'danger'], 'ressource_liberation' => ['Libération', 'success'],
    'donneur_ajout' => ['Donneur ajouté', 'primary'], 'donneur_suppr' => ['Donneur supprimé', 'danger'], 'don_enregistre' => ['Don', 'success'],
    'demande_creation' => ['Demande de sang', 'warning'], 'demande_satisfaite' => ['Demande satisfaite', 'success'], 'demande_refus' => ['Demande refusée', 'danger'],
];
$qs = array_filter(['type' => $filtre]);
?>
<div class="toolbar">
    <form class="filters" method="get" action="index.php">
        <input type="hidden" name="action" value="journal">
        <select name="type" aria-label="Type d'action">
            <?php foreach ($familles as $cle => $nom): ?><option value="<?= e($cle) ?>" <?= $filtre === $cle ? 'selected' : '' ?>><?= e($nom) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-secondary" type="submit">Filtrer</button>
    </form>
    <p class="muted small"><?= icon('lock', 14) ?> Consultation réservée aux médecins. Chaque action sensible est tracée.</p>
</div>

<section class="card card-flush">
    <div class="table-wrap">
        <table class="table table-hover">
            <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Détails</th></tr></thead>
            <tbody>
            <?php if (!$entrees): ?>
                <tr><td colspan="4"><div class="empty"><?= icon('file', 28) ?><p>Aucune entrée dans le journal.</p></div></td></tr>
            <?php endif; ?>
            <?php foreach ($entrees as $j): $l = $libelles[$j['action']] ?? [$j['action'], 'neutral']; ?>
                <tr>
                    <td class="nowrap"><?= e(date_fr($j['created_at'], true)) ?></td>
                    <td><?= $j['auteur'] ? '<strong>' . e($j['auteur']) . '</strong><small class="muted block">' . e(Auth::roleLabel($j['role'])) . '</small>' : '<span class="muted">Système</span>' ?></td>
                    <td><span class="badge badge-<?= e($l[1]) ?>"><?= e($l[0]) ?></span></td>
                    <td><?= e($j['details']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <footer class="table-foot">
        <span class="muted"><?= (int) $total ?> entrée(s)</span>
        <?php if ($pages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a class="page<?= $i === $page ? ' current' : '' ?>" href="<?= e(url('journal', $qs + ['page' => $i])) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </footer>
</section>
