<?php $stocks = []; foreach ($stock as $s) { $stocks[$s['groupe_sanguin']] = (int) $s['poches']; } ?>

<div class="toolbar">
    <form class="filters" method="get" action="index.php">
        <input type="hidden" name="action" value="demandes">
        <select name="statut" aria-label="Statut">
            <option value="">Tous les statuts</option>
            <?php foreach (['En attente', 'Satisfaite', 'Refusée'] as $s): ?><option <?= $statut === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-secondary" type="submit">Filtrer</button>
        <?php if ($statut !== ''): ?><a class="btn btn-ghost" href="<?= e(url('demandes')) ?>">Réinitialiser</a><?php endif; ?>
    </form>
    <div class="toolbar-actions">
        <?php if (Auth::can('sang.gerer')): ?>
            <button class="btn btn-primary" type="button" data-open="dlg-demande"><?= icon('plus', 16) ?> Nouvelle demande</button>
        <?php endif; ?>
    </div>
</div>

<section class="card card-flush">
    <div class="table-wrap">
        <table class="table table-hover">
            <thead><tr><th>Patient</th><th>Groupe</th><th class="num">Poches</th><th class="num">Stock</th><th>Commentaire</th><th>Demande</th><th>Statut</th><th class="actions-col"></th></tr></thead>
            <tbody>
            <?php if (!$demandes): ?>
                <tr><td colspan="8"><div class="empty"><?= icon('pulse', 28) ?><p>Aucune demande de sang.</p></div></td></tr>
            <?php endif; ?>
            <?php foreach ($demandes as $d):
                $dispo = (int) $d['stock_dispo'];
                $suffisant = $dispo >= (int) $d['poches'];
            ?>
                <tr>
                    <td><strong><?= e($d['patient_nom']) ?></strong><small class="muted block"><?= badge_urgence($d['degre_urgence']) ?></small></td>
                    <td><?= groupe_pastille($d['groupe_sanguin']) ?></td>
                    <td class="num"><strong><?= (int) $d['poches'] ?></strong></td>
                    <td class="num <?= $d['statut'] === 'En attente' ? ($suffisant ? 'text-success fw' : 'text-danger fw') : 'muted' ?>"><?= $dispo ?></td>
                    <td><?= $d['commentaire'] ? e($d['commentaire']) : '<span class="muted">-</span>' ?></td>
                    <td><small><?= e(date_fr($d['created_at'], true)) ?></small><small class="muted block"><?= e($d['demandeur'] ?? '-') ?></small></td>
                    <td><?= badge_demande($d['statut']) ?></td>
                    <td class="actions-col">
                        <?php if ($d['statut'] === 'En attente'): ?>
                            <?php if (Auth::can('sang.valider')): ?>
                                <form method="post" action="<?= e(url('demande_traiter')) ?>" data-confirm="Satisfaire cette demande ? <?= (int) $d['poches'] ?> poche(s) <?= e($d['groupe_sanguin']) ?> seront retirées du stock.">
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>"><input type="hidden" name="decision" value="satisfaire">
                                    <button class="btn btn-sm btn-primary" type="submit" <?= $suffisant ? '' : 'disabled title="Stock insuffisant"' ?>><?= icon('check', 14) ?> Satisfaire</button>
                                </form>
                                <form method="post" action="<?= e(url('demande_traiter')) ?>" data-confirm="Refuser cette demande ?">
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>"><input type="hidden" name="decision" value="refuser">
                                    <button class="btn btn-sm btn-secondary" type="submit"><?= icon('x', 14) ?> Refuser</button>
                                </form>
                            <?php else: ?>
                                <span class="muted small">En attente du médecin</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <footer class="table-foot"><span class="muted"><?= count($demandes) ?> demande(s)</span></footer>
</section>

<?php if (Auth::can('sang.gerer')): ?>
<dialog class="modal" id="dlg-demande">
    <form method="post" action="<?= e(url('demande_ajouter')) ?>">
        <?= csrf_field() ?>
        <header class="modal-head"><h2>Nouvelle demande de sang</h2><button type="button" class="icon-btn" data-close aria-label="Fermer"><?= icon('x', 18) ?></button></header>
        <div class="modal-body">
            <div class="form-grid">
                <label class="field span-2"><span>Patient</span>
                    <select name="patient_id" id="demande-patient" required>
                        <option value="">Sélectionner un patient…</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" data-groupe="<?= e($p['groupe_sanguin'] ?? '') ?>"><?= e($p['prenom'] . ' ' . $p['nom']) ?><?= $p['groupe_sanguin'] ? ' (' . e($p['groupe_sanguin']) . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select></label>
                <label class="field"><span>Groupe sanguin demandé</span>
                    <select name="groupe_sanguin" id="demande-groupe" required><?php foreach (SangModel::GROUPES as $g): ?><option><?= e($g) ?></option><?php endforeach; ?></select></label>
                <label class="field"><span>Nombre de poches</span><input type="number" name="poches" min="1" max="10" value="1" required></label>
                <label class="field span-2"><span>Commentaire <small class="muted">(facultatif)</small></span><input type="text" name="commentaire" maxlength="200" placeholder="Motif, contexte clinique…"></label>
            </div>
        </div>
        <footer class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuler</button><button class="btn btn-primary" type="submit">Envoyer la demande</button></footer>
    </form>
</dialog>
<?php endif; ?>
