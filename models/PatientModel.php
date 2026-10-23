<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

class PatientModel
{
    const URGENCES = ['Absolue', 'Moyenne', 'Faible'];
    const STATUTS  = ['En attente', 'En prise en charge', 'Traité'];
    const GROUPES  = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private static function filtres(string $recherche, string $statut, string $urgence): array
    {
        $sql = ' WHERE 1=1';
        $params = [];
        if ($recherche !== '') {
            $sql .= ' AND (nom LIKE ? OR prenom LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $recherche) . '%';
            array_push($params, $like, $like);
        }
        if ($statut !== '') {
            $sql .= ' AND statut = ?';
            $params[] = $statut;
        }
        if ($urgence !== '') {
            $sql .= ' AND degre_urgence = ?';
            $params[] = $urgence;
        }
        return [$sql, $params];
    }

    /** File de prise en charge : statut, puis gravité, puis ordre d'arrivée. */
    public static function getAll(string $recherche, string $statut, string $urgence, int $page, int $parPage): array
    {
        $db = Database::getConnection();
        list($where, $params) = self::filtres($recherche, $statut, $urgence);

        $stmt = $db->prepare('SELECT COUNT(*) FROM patient' . $where);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $offset = max(0, ($page - 1) * $parPage);
        $stmt = $db->prepare(
            'SELECT *, TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS attente_min,
                    TIMESTAMPDIFF(MINUTE, created_at, pris_en_charge_at) AS delai_pec_min
             FROM patient' . $where . "
             ORDER BY FIELD(statut, 'En attente', 'En prise en charge', 'Traité'),
                      FIELD(degre_urgence, 'Absolue', 'Moyenne', 'Faible'),
                      created_at ASC
             LIMIT " . (int) $parPage . ' OFFSET ' . (int) $offset
        );
        $stmt->execute($params);
        return [$stmt->fetchAll(), $total];
    }

    public static function exportRows(string $recherche, string $statut, string $urgence): array
    {
        list($where, $params) = self::filtres($recherche, $statut, $urgence);
        $stmt = Database::getConnection()->prepare('SELECT * FROM patient' . $where . ' ORDER BY id DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id)
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM patient WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function ajouter(array $d): int
    {
        $db = Database::getConnection();
        $pec = $d['statut'] === 'En prise en charge' || $d['statut'] === 'Traité';
        $stmt = $db->prepare(
            'INSERT INTO patient (nom, prenom, age, groupe_sanguin, degre_urgence, statut, pris_en_charge_at, traite_at)
             VALUES (?, ?, ?, ?, ?, ?, ' . ($pec ? 'NOW()' : 'NULL') . ', ' . ($d['statut'] === 'Traité' ? 'NOW()' : 'NULL') . ')'
        );
        $stmt->execute([$d['nom'], $d['prenom'], $d['age'], $d['groupe'], $d['urgence'], $d['statut']]);
        return (int) $db->lastInsertId();
    }

    /** Met à jour le patient et les horodatages du parcours selon le nouveau statut. */
    public static function modifier(int $id, array $d): void
    {
        $ancien = self::find($id);
        if (!$ancien) {
            return;
        }
        $pec = $ancien['pris_en_charge_at'];
        $traite = $ancien['traite_at'];
        $maintenant = (string) Database::getConnection()->query('SELECT NOW()')->fetchColumn();

        if ($d['statut'] === 'En attente') {
            $pec = null;
            $traite = null;
        } elseif ($d['statut'] === 'En prise en charge') {
            $pec = $pec ?: $maintenant;
            $traite = null;
        } else {
            $pec = $pec ?: $maintenant;
            $traite = $traite ?: $maintenant;
        }

        $stmt = Database::getConnection()->prepare(
            'UPDATE patient SET nom = ?, prenom = ?, age = ?, groupe_sanguin = ?, degre_urgence = ?, statut = ?,
                    pris_en_charge_at = ?, traite_at = ? WHERE id = ?'
        );
        $stmt->execute([$d['nom'], $d['prenom'], $d['age'], $d['groupe'], $d['urgence'], $d['statut'], $pec, $traite, $id]);
    }

    public static function supprimer(int $id): void
    {
        Database::getConnection()->prepare('DELETE FROM patient WHERE id = ?')->execute([$id]);
    }

    /** Patients encore suivis, pour les listes déroulantes (ressources, demandes de sang). */
    public static function actifs(): array
    {
        return Database::getConnection()->query(
            "SELECT id, nom, prenom, groupe_sanguin FROM patient WHERE statut <> 'Traité' ORDER BY nom, prenom"
        )->fetchAll();
    }
}
