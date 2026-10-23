<?php
$qs = array_filter(['search' => $recherche, 'statut' => $statut, 'urgence' => $urgence]);

function patient_champs(string $prefixe): void
{ ?>
    <div class="form-grid">
        <label class="field"><span>Nom</span><input type="text" name="nom" maxlength="60" required></label>
        <label class="field"><span>Prénom</span><input type="text" name="prenom" maxlength="60" required></label>
        <label class="field"><span>Âge</span><input type="number" name="age" min="0" max="120" required></label>
        <label class="field"><span>Groupe sanguin</span>
            <select name="groupe_sanguin"><option value="">Non renseigné</option>
                <?php foreach (PatientModel::GROUPES as $g): ?><option><?= e($g) ?></option><?php endforeach; ?>
            </select></label>
        <label class="field"><span>Degré d'urgence</span>
            <select name="degre_urgence"><?php foreach (PatientModel::URGENCES as $u): ?><option><?= e($u) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Statut</span>
            <select name="statut"><?php foreach (PatientModel::STATUTS as $s): ?><option><?= e($s) ?></option><?php endforeach; ?></select></label>
    </div>
<?php }
?>

<div class="toolbar">
    <form class="filters" method="get" action="index.php">
        <input type="hidden" name="action" value="patients">
        <div class="input-icon search"><?= icon('search', 16) ?><input type="search" name="search" placeholder="Rechercher un patient…" value="<?= e($recherche) ?>"></div>
        <select name="statut" aria-label="Statut">
            <option value="">Tous les statuts</option>
            <?php foreach (PatientModel::STATUTS as $s): ?><option <?= $statut === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
        <select name="urgence" aria-label="Urgence">
            <option value="">Toutes les urgences</option>
            <?php foreach (PatientModel::URGENCES as $u): ?><option <?= $urgence === $u ? 'selected' : '' ?>><?= e($u) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-secondary" type="submit">Filtrer</button>
        <?php if ($qs): ?><a class="btn btn-ghost" href="<?= e(url('patients')) ?>">Réinitialiser</a><?php endif; ?>
    </form>
    <div class="toolbar-actions">
        <?php if (Auth::can('patients.exporter')): ?>
            <a class="btn btn-secondary" href="<?= e(url('patients_export', $qs)) ?>"><?= icon('download', 16) ?> Exporter CSV</a>
        <?php endif; ?>
        <?php if (Auth::can('patients.ajouter')): ?>
            <button class="btn btn-primary" type="button" data-open="dlg-ajout"><?= icon('plus', 16) ?> Nouveau patient</button>
        <?php endif; ?>
    </div>
</div>

<section class="card card-flush">
    <div class="table-wrap">
        <table class="table table-hover">
            <thead><tr>
                <th>Patient</th><th>Groupe</th><th>Urgence</th><th>Statut</th><th>Admission</th><th>Attente / prise en charge</th><th class="actions-col"></th>
            </tr></thead>
            <tbody>
            <?php if (!$patients): ?>
                <tr><td colspan="7"><div class="empty"><?= icon('users', 28) ?><p>Aucun patient ne correspond à cette recherche.</p></div></td></tr>
            <?php endif; ?>
            <?php foreach ($patients as $p):
                $retard = $p['statut'] === 'En attente' && $p['degre_urgence'] === 'Absolue' && (int) $p['attente_min'] > 15;
                $fill = json_encode([
                    'id' => (int) $p['id'], 'nom' => $p['nom'], 'prenom' => $p['prenom'], 'age' => (int) $p['age'],
                    'groupe_sanguin' => $p['groupe_sanguin'] ?? '', 'degre_urgence' => $p['degre_urgence'], 'statut' => $p['statut'],
                ], JSON_UNESCAPED_UNICODE);
            ?>
                <tr<?= $retard ? ' class="row-alert"' : '' ?>>
                    <td><div class="person"><span class="avatar avatar-sm"><?= e(initiales($p)) ?></span>
                        <div><strong><?= e($p['prenom'] . ' ' . $p['nom']) ?></strong><small class="muted"><?= (int) $p['age'] ?> ans · #<?= (int) $p['id'] ?></small></div></div></td>
                    <td><?= groupe_pastille($p['groupe_sanguin']) ?></td>
                    <td><?= badge_urgence($p['degre_urgence']) ?></td>
                    <td><?= badge_statut($p['statut']) ?></td>
                    <td><?= e(date_fr($p['created_at'], true)) ?></td>
                    <td>
                        <?php if ($p['statut'] === 'En attente'): ?>
                            <span class="<?= $retard ? 'text-danger fw' : '' ?>"><?= icon('clock', 14) ?> <?= e(duree_minutes($p['attente_min'])) ?></span>
                        <?php elseif ($p['delai_pec_min'] !== null): ?>
                            <span class="muted">Pris en charge après <?= e(duree_minutes($p['delai_pec_min'])) ?></span>
                        <?php else: ?><span class="muted">-</span><?php endif; ?>
                    </td>
                    <td class="actions-col">
                        <?php if (Auth::can('patients.modifier')): ?>
                            <button class="icon-btn" type="button" title="Modifier" aria-label="Modifier" data-open="dlg-modif" data-fill="<?= e($fill) ?>"><?= icon('edit', 16) ?></button>
                        <?php endif; ?>
                        <?php if (Auth::can('patients.supprimer')): ?>
                            <form method="post" action="<?= e(url('patient_supprimer')) ?>" data-confirm="Supprimer définitivement le dossier de <?= e($p['prenom'] . ' ' . $p['nom']) ?> ?">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button class="icon-btn icon-btn-danger" type="submit" title="Supprimer" aria-label="Supprimer"><?= icon('trash', 16) ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <footer class="table-foot">
        <span class="muted"><?= (int) $total ?> patient(s)</span>
        <?php if ($pages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a class="page<?= $i === $page ? ' current' : '' ?>" href="<?= e(url('patients', $qs + ['page' => $i])) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </footer>
</section>

<?php if (Auth::can('patients.ajouter')): ?>
<dialog class="modal" id="dlg-ajout">
    <form method="post" action="<?= e(url('patient_ajouter')) ?>">
        <?= csrf_field() ?>
        <header class="modal-head"><h2>Nouveau patient</h2><button type="button" class="icon-btn" data-close aria-label="Fermer"><?= icon('x', 18) ?></button></header>
        <div class="modal-body"><?php patient_champs('ajout'); ?></div>
        <footer class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuler</button><button class="btn btn-primary" type="submit">Enregistrer l'admission</button></footer>
    </form>
</dialog>
<?php endif; ?>

<?php if (Auth::can('patients.modifier')): ?>
<dialog class="modal" id="dlg-modif">
    <form method="post" action="<?= e(url('patient_modifier')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id">
        <header class="modal-head"><h2>Modifier le patient</h2><button type="button" class="icon-btn" data-close aria-label="Fermer"><?= icon('x', 18) ?></button></header>
        <div class="modal-body">
            <?php patient_champs('modif'); ?>
            <p class="hint"><?= icon('check', 14) ?> Passer le statut à « Traité » libère automatiquement les ressources du patient.</p>
        </div>
        <footer class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuler</button><button class="btn btn-primary" type="submit">Enregistrer</button></footer>
    </form>
</dialog>
<?php endif; ?>
