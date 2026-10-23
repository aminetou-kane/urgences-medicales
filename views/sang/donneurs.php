<?php $qs = array_filter(['search' => $recherche, 'groupe' => $groupe]); ?>

<div class="toolbar">
    <form class="filters" method="get" action="index.php">
        <input type="hidden" name="action" value="donneurs">
        <div class="input-icon search"><?= icon('search', 16) ?><input type="search" name="search" placeholder="Rechercher un donneur…" value="<?= e($recherche) ?>"></div>
        <select name="groupe" aria-label="Groupe sanguin">
            <option value="">Tous les groupes</option>
            <?php foreach (SangModel::GROUPES as $g): ?><option <?= $groupe === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-secondary" type="submit">Filtrer</button>
        <?php if ($qs): ?><a class="btn btn-ghost" href="<?= e(url('donneurs')) ?>">Réinitialiser</a><?php endif; ?>
    </form>
    <div class="toolbar-actions">
        <?php if (Auth::can('sang.gerer')): ?>
            <button class="btn btn-primary" type="button" data-open="dlg-donneur"><?= icon('plus', 16) ?> Nouveau donneur</button>
        <?php endif; ?>
    </div>
</div>

<p class="muted small note-line"><?= icon('clock', 14) ?> Délai minimal entre deux dons : <?= (int) $delai ?> jours. Un don est refusé automatiquement avant ce délai.</p>

<section class="card card-flush">
    <div class="table-wrap">
        <table class="table table-hover">
            <thead><tr><th>Donneur</th><th>Groupe</th><th>Téléphone</th><th>Dernier don</th><th>Éligibilité</th><th class="num">Dons</th><th class="actions-col"></th></tr></thead>
            <tbody>
            <?php if (!$donneurs): ?>
                <tr><td colspan="7"><div class="empty"><?= icon('heart', 28) ?><p>Aucun donneur ne correspond à cette recherche.</p></div></td></tr>
            <?php endif; ?>
            <?php foreach ($donneurs as $d): ?>
                <tr>
                    <td><div class="person"><span class="avatar avatar-sm"><?= e(initiales($d)) ?></span><strong><?= e($d['prenom'] . ' ' . $d['nom']) ?></strong></div></td>
                    <td><?= groupe_pastille($d['groupe_sanguin']) ?></td>
                    <td><?= $d['telephone'] ? e($d['telephone']) : '<span class="muted">-</span>' ?></td>
                    <td><?= $d['dernier_don'] ? e(date_fr($d['dernier_don'])) . ' <small class="muted">(il y a ' . (int) $d['jours_depuis'] . ' j)</small>' : '<span class="muted">Jamais</span>' ?></td>
                    <td><?= $d['eligible']
                        ? '<span class="badge badge-success">Éligible</span>'
                        : '<span class="badge badge-warning">Dès le ' . e(date_fr($d['prochain_don'])) . '</span>' ?></td>
                    <td class="num"><?= (int) $d['nb_dons'] ?></td>
                    <td class="actions-col">
                        <?php if (Auth::can('sang.gerer')): ?>
                            <form method="post" action="<?= e(url('don_enregistrer')) ?>" data-confirm="Enregistrer un don de <?= e($d['prenom'] . ' ' . $d['nom']) ?> (<?= e($d['groupe_sanguin']) ?>) ?">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                <button class="btn btn-sm <?= $d['eligible'] ? 'btn-primary' : 'btn-secondary' ?>" type="submit" <?= $d['eligible'] ? '' : 'disabled title="Délai minimal non écoulé"' ?>><?= icon('drop', 14) ?> Enregistrer un don</button>
                            </form>
                            <form method="post" action="<?= e(url('donneur_supprimer')) ?>" data-confirm="Supprimer le donneur <?= e($d['prenom'] . ' ' . $d['nom']) ?> et son historique ?">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                <button class="icon-btn icon-btn-danger" type="submit" title="Supprimer" aria-label="Supprimer"><?= icon('trash', 16) ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <footer class="table-foot"><span class="muted"><?= count($donneurs) ?> donneur(s)</span></footer>
</section>

<?php if (Auth::can('sang.gerer')): ?>
<dialog class="modal" id="dlg-donneur">
    <form method="post" action="<?= e(url('donneur_ajouter')) ?>">
        <?= csrf_field() ?>
        <header class="modal-head"><h2>Nouveau donneur</h2><button type="button" class="icon-btn" data-close aria-label="Fermer"><?= icon('x', 18) ?></button></header>
        <div class="modal-body">
            <div class="form-grid">
                <label class="field"><span>Nom</span><input type="text" name="nom" maxlength="60" required></label>
                <label class="field"><span>Prénom</span><input type="text" name="prenom" maxlength="60" required></label>
                <label class="field"><span>Groupe sanguin</span>
                    <select name="groupe_sanguin" required><?php foreach (SangModel::GROUPES as $g): ?><option><?= e($g) ?></option><?php endforeach; ?></select></label>
                <label class="field"><span>Téléphone <small class="muted">(facultatif)</small></span><input type="tel" name="telephone" maxlength="20" placeholder="77 000 00 00"></label>
            </div>
        </div>
        <footer class="modal-foot"><button type="button" class="btn btn-ghost" data-close>Annuler</button><button class="btn btn-primary" type="submit">Enregistrer</button></footer>
    </form>
</dialog>
<?php endif; ?>
