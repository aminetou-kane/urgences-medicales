<?php
$qs = array_filter(['type' => $type, 'statut' => $statut]);

function ressource_champs(array $patients): void
{ ?>
    <div class="form-grid">
        <label class="field span-2"><span>Nom</span><input type="text" name="nom" maxlength="80" required></label>
        <label class="field"><span>Type</span>
            <select name="type"><?php foreach (RessourceModel::TYPES as $t): ?><option><?= e($t) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Statut</span>
            <select name="statut"><?php foreach (RessourceModel::STATUTS as $s): ?><option><?= e($s) ?></option><?php endforeach; ?></select></label>
        <label class="field span-2"><span>Patient assigné <small class="muted">(rend la ressource « Occupé »)</small></span>
            <select name="patient_id"><option value="">Aucun</option>
                <?php foreach ($patients as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['prenom'] . ' ' . $p['nom']) ?></option><?php endforeach; ?>
            </select></label>
    </div>
<?php }
?>

<section class="kpi-grid kpi-grid-4">
    <article class="stat"><span class="stat-label">Total</span><strong><?= (int) $compteurs['total'] ?></strong></article>
    <article class="stat stat-success"><span class="stat-label">Disponibles</span><strong><?= (int) $compteurs['Disponible'] ?></strong></article>
    <article class="stat stat-danger"><span class="stat-label">Occupées</span><strong><?= (int) $compteurs['Occupé'] ?></strong></article>
    <article class="stat stat-warning"><span class="stat-label">En maintenance</span><strong><?= (int) $compteurs['En maintenance'] ?></strong></article>
</section>

<div class="toolbar">
    <form class="filters" method="get" action="index.php">
        <input type="hidden" name="action" value="ressources">
        <select name="type" aria-label="Type">
            <option value="">Tous les types</option>
            <?php foreach (RessourceModel::TYPES as $t): ?><option <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select>
        <select name="statut" aria-label="Statut">
            <option value="">Tous les statuts</option>
            <?php foreach (RessourceModel::STATUTS as $s): ?><option <?= $statut === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-secondary" type="submit">Filtrer</button>
        <?php if ($qs): ?><a class="btn btn-ghost" href="<?= e(url('ressources')) ?>">Réinitialiser</a><?php endif; ?>
    </form>
    <div class="toolbar-actions">
        <button class="btn btn-primary" type="button" data-open="dlg-r-ajout"><?= icon('plus', 16) ?> Nouvelle ressource</button>
    </div>
</div>

<section class="card card-flush">
    <div class="table-wrap">
        <table class="table table-hover">
            <thead><tr><th>Ressource</th><th>Type</th><th>Statut</th><th>Patient assigné</th><th class="actions-col"></th></tr></thead>
            <tbody>
            <?php if (!$ressources): ?>
                <tr><td colspan="5"><div class="empty"><?= icon('bed', 28) ?><p>Aucune ressource ne correspond à ce filtre.</p></div></td></tr>
            <?php endif; ?>
            <?php foreach ($ressources as $r):
                $fill = json_encode([
                    'id' => (int) $r['id'], 'nom' => $r['nom'], 'type' => $r['type'], 'statut' => $r['statut'], 'patient_id' => (string) ($r['patient_id'] ?? ''),
                ], JSON_UNESCAPED_UNICODE);
            ?>
                <tr>
                    <td><strong><?= e($r['nom']) ?></strong></td>
                    <td><span class="muted"><?= e($r['type']) ?></span></td>
                    <td><?= badge_ressource($r['statut']) ?></td>
                    <td><?= $r['patient_nom'] ? e($r['patient_nom']) : '<span class="muted">-</span>' ?></td>
                    <td class="actions-col">
                        <?php if ($r['statut'] === 'Occupé'): ?>
                            <form method="post" action="<?= e(url('ressource_liberer')) ?>" data-confirm="Libérer « <?= e($r['nom']) ?> » ?">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button class="btn btn-sm btn-secondary" type="submit"><?= icon('unlock', 14) ?> Libérer</button>
                            </form>
                        <?php endif; ?>
                        <button class="icon-btn" type="button" title="Modifier" aria-label="Modifier" data-open="dlg-r-modif" data-fill="<?= e($fill) ?>"><?= icon('edit', 16) ?></button>
                        <form method="post" action="<?= e(url('ressource_supprimer')) ?>" data-confirm="Supprimer la ressource « <?= e($r['nom']) ?> » ?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button class="icon-btn icon-btn-danger" type="submit" title="Supprimer" aria-label="Supprimer"><?= icon('trash', 16) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <footer class="table-foot"><span class="muted"><?= count($ressources) ?> ressource(s)</span></footer>
</section>

<dialog class="modal" id="dlg-r-ajout">
    <form method="post" action="<?= e(url('ressource_ajouter')) ?>">
        <?= csrf_field() ?>
        <header class="modal-head"><h2>Nouvelle ressource</h2><button type="button" class="icon-btn" data-close aria-label="Fermer"><?= icon('x', 18) ?></button></header>
        <div class="modal-body"><?php ressource_champs($patients); ?></div>
        <footer class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuler</button><button class="btn btn-primary" type="submit">Ajouter</button></footer>
    </form>
</dialog>

<dialog class="modal" id="dlg-r-modif">
    <form method="post" action="<?= e(url('ressource_modifier')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id">
        <header class="modal-head"><h2>Modifier la ressource</h2><button type="button" class="icon-btn" data-close aria-label="Fermer"><?= icon('x', 18) ?></button></header>
        <div class="modal-body"><?php ressource_champs($patients); ?></div>
        <footer class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuler</button><button class="btn btn-primary" type="submit">Enregistrer</button></footer>
    </form>
</dialog>
