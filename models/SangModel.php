<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

/** Gestion du sang : stock, donneurs, dons et demandes pour les patients. */
class SangModel
{
    const GROUPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    /** Groupes dont un receveur peut recevoir le sang (compatibilité ABO / Rhésus). */
    const COMPATIBILITE = [
        'O-'  => ['O-'],
        'O+'  => ['O-', 'O+'],
        'A-'  => ['O-', 'A-'],
        'A+'  => ['O-', 'O+', 'A-', 'A+'],
        'B-'  => ['O-', 'B-'],
        'B+'  => ['O-', 'O+', 'B-', 'B+'],
        'AB-' => ['O-', 'A-', 'B-', 'AB-'],
        'AB+' => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'],
    ];

    /* ---------- Stock ---------- */
    public static function stock(): array
    {
        return Database::getConnection()->query(
            "SELECT * FROM stock_sang ORDER BY FIELD(groupe_sanguin, 'O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-')"
        )->fetchAll();
    }

    public static function stocksCritiques(): array
    {
        return Database::getConnection()->query(
            'SELECT * FROM stock_sang WHERE poches < seuil_alerte ORDER BY poches ASC'
        )->fetchAll();
    }

    /* ---------- Donneurs ---------- */
    public static function donneurs(string $recherche, string $groupe, int $delaiJours): array
    {
        $sql = 'SELECT d.*, DATEDIFF(CURDATE(), d.dernier_don) AS jours_depuis,
                       DATE_ADD(d.dernier_don, INTERVAL ' . (int) $delaiJours . ' DAY) AS prochain_don,
                       (SELECT COUNT(*) FROM don WHERE donneur_id = d.id) AS nb_dons
                FROM donneur d WHERE 1=1';
        $params = [];
        if ($recherche !== '') {
            $sql .= ' AND (d.nom LIKE ? OR d.prenom LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $recherche) . '%';
            array_push($params, $like, $like);
        }
        if ($groupe !== '') {
            $sql .= ' AND d.groupe_sanguin = ?';
            $params[] = $groupe;
        }
        $stmt = Database::getConnection()->prepare($sql . ' ORDER BY d.nom, d.prenom');
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['eligible'] = $r['dernier_don'] === null || (int) $r['jours_depuis'] >= $delaiJours;
        }
        unset($r);
        return $rows;
    }

    public static function ajouterDonneur(string $nom, string $prenom, string $groupe, string $telephone): int
    {
        $db = Database::getConnection();
        $db->prepare('INSERT INTO donneur (nom, prenom, groupe_sanguin, telephone) VALUES (?, ?, ?, ?)')
           ->execute([$nom, $prenom, $groupe, $telephone !== '' ? $telephone : null]);
        return (int) $db->lastInsertId();
    }

    public static function supprimerDonneur(int $id): void
    {
        Database::getConnection()->prepare('DELETE FROM donneur WHERE id = ?')->execute([$id]);
    }

    public static function nomDonneur(int $id): string
    {
        $stmt = Database::getConnection()->prepare("SELECT CONCAT(prenom, ' ', nom, ' (', groupe_sanguin, ')') FROM donneur WHERE id = ?");
        $stmt->execute([$id]);
        return (string) $stmt->fetchColumn();
    }

    /**
     * Enregistre un don : vérifie le délai entre deux dons, ajoute une poche au stock.
     * Retourne [succès, message].
     */
    public static function enregistrerDon(int $donneurId, int $delaiJours): array
    {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'SELECT *, DATEDIFF(CURDATE(), dernier_don) AS jours_depuis,
                        DATE_ADD(dernier_don, INTERVAL ' . (int) $delaiJours . ' DAY) AS prochain_don
                 FROM donneur WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$donneurId]);
            $d = $stmt->fetch();
            if (!$d) {
                $db->rollBack();
                return [false, 'Donneur introuvable.'];
            }
            if ($d['dernier_don'] !== null && (int) $d['jours_depuis'] < $delaiJours) {
                $db->rollBack();
                return [false, 'Don impossible : dernier don il y a ' . (int) $d['jours_depuis'] . ' jour(s). '
                    . 'Prochain don possible le ' . date_fr($d['prochain_don']) . '.'];
            }
            $db->prepare('INSERT INTO don (donneur_id, date_don, volume_ml) VALUES (?, CURDATE(), 450)')->execute([$donneurId]);
            $db->prepare('UPDATE donneur SET dernier_don = CURDATE() WHERE id = ?')->execute([$donneurId]);
            $db->prepare('UPDATE stock_sang SET poches = poches + 1 WHERE groupe_sanguin = ?')->execute([$d['groupe_sanguin']]);
            $db->commit();
            return [true, 'Don enregistré : +1 poche ' . $d['groupe_sanguin'] . ' au stock.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Don : ' . $e->getMessage());
            return [false, "Erreur lors de l'enregistrement du don."];
        }
    }

    public static function derniersDons(int $limite = 6): array
    {
        return Database::getConnection()->query(
            "SELECT dn.date_don, dn.volume_ml, CONCAT(d.prenom, ' ', d.nom) AS donneur, d.groupe_sanguin
             FROM don dn JOIN donneur d ON d.id = dn.donneur_id ORDER BY dn.date_don DESC, dn.id DESC LIMIT " . (int) $limite
        )->fetchAll();
    }

    /* ---------- Demandes de sang ---------- */
    public static function demandes(string $statut = ''): array
    {
        $sql = "SELECT s.*, CONCAT(p.prenom, ' ', p.nom) AS patient_nom, p.degre_urgence,
                       CONCAT(u.prenom, ' ', u.nom) AS demandeur,
                       TIMESTAMPDIFF(MINUTE, s.created_at, NOW()) AS attente_min,
                       (SELECT poches FROM stock_sang WHERE groupe_sanguin = s.groupe_sanguin) AS stock_dispo
                FROM demande_sang s
                JOIN patient p ON p.id = s.patient_id
                LEFT JOIN utilisateur u ON u.id = s.demandeur_id WHERE 1=1";
        $params = [];
        if ($statut !== '') {
            $sql .= ' AND s.statut = ?';
            $params[] = $statut;
        }
        $stmt = Database::getConnection()->prepare(
            $sql . " ORDER BY FIELD(s.statut, 'En attente', 'Satisfaite', 'Refusée'), FIELD(p.degre_urgence, 'Absolue', 'Moyenne', 'Faible'), s.created_at ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function demandesEnAttente(): int
    {
        return (int) Database::getConnection()->query("SELECT COUNT(*) FROM demande_sang WHERE statut = 'En attente'")->fetchColumn();
    }

    public static function ajouterDemande(int $patientId, string $groupe, int $poches, string $commentaire, int $demandeurId): int
    {
        $db = Database::getConnection();
        $db->prepare('INSERT INTO demande_sang (patient_id, groupe_sanguin, poches, commentaire, demandeur_id) VALUES (?, ?, ?, ?, ?)')
           ->execute([$patientId, $groupe, $poches, $commentaire !== '' ? $commentaire : null, $demandeurId]);
        return (int) $db->lastInsertId();
    }

    /** Satisfait (retire du stock) ou refuse une demande. Retourne [succès, message]. */
    public static function traiterDemande(int $id, string $decision, int $userId): array
    {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM demande_sang WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $demande = $stmt->fetch();
            if (!$demande || $demande['statut'] !== 'En attente') {
                $db->rollBack();
                return [false, 'Cette demande a déjà été traitée ou n\'existe pas.'];
            }

            if ($decision === 'refuser') {
                $db->prepare("UPDATE demande_sang SET statut = 'Refusée', traite_par = ?, traite_at = NOW() WHERE id = ?")->execute([$userId, $id]);
                $db->commit();
                return [true, 'Demande refusée.'];
            }

            $stmt = $db->prepare('SELECT poches FROM stock_sang WHERE groupe_sanguin = ? FOR UPDATE');
            $stmt->execute([$demande['groupe_sanguin']]);
            $dispo = (int) $stmt->fetchColumn();
            if ($dispo < (int) $demande['poches']) {
                $db->rollBack();
                $compat = array_filter(self::COMPATIBILITE[$demande['groupe_sanguin']] ?? [], function ($g) use ($demande) {
                    return $g !== $demande['groupe_sanguin'];
                });
                return [false, 'Stock insuffisant en ' . $demande['groupe_sanguin'] . ' (' . $dispo . ' poche(s) disponible(s), '
                    . $demande['poches'] . ' demandée(s)). Groupes compatibles : ' . implode(', ', $compat) . '.'];
            }
            $db->prepare('UPDATE stock_sang SET poches = poches - ? WHERE groupe_sanguin = ?')->execute([$demande['poches'], $demande['groupe_sanguin']]);
            $db->prepare("UPDATE demande_sang SET statut = 'Satisfaite', traite_par = ?, traite_at = NOW() WHERE id = ?")->execute([$userId, $id]);
            $db->commit();
            return [true, 'Demande satisfaite : ' . $demande['poches'] . ' poche(s) ' . $demande['groupe_sanguin'] . ' retirée(s) du stock.'];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Demande de sang : ' . $e->getMessage());
            return [false, 'Erreur lors du traitement de la demande.'];
        }
    }
}
