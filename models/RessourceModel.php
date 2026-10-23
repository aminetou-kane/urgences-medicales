<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

class RessourceModel
{
    const TYPES   = ['Lit', 'Équipement', 'Salle', 'Personnel'];
    const STATUTS = ['Disponible', 'Occupé', 'En maintenance'];

    public static function getAll(string $type = '', string $statut = ''): array
    {
        $sql = "SELECT r.*, CONCAT(p.prenom, ' ', p.nom) AS patient_nom
                FROM ressource r LEFT JOIN patient p ON r.patient_id = p.id WHERE 1=1";
        $params = [];
        if ($type !== '') {
            $sql .= ' AND r.type = ?';
            $params[] = $type;
        }
        if ($statut !== '') {
            $sql .= ' AND r.statut = ?';
            $params[] = $statut;
        }
        $stmt = Database::getConnection()->prepare($sql . ' ORDER BY FIELD(r.type, \'Lit\', \'Équipement\', \'Salle\', \'Personnel\'), r.nom');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Totaux par statut pour les cartes de synthèse. */
    public static function compteurs(): array
    {
        $rows = Database::getConnection()->query('SELECT statut, COUNT(*) AS n FROM ressource GROUP BY statut')->fetchAll();
        $c = ['Disponible' => 0, 'Occupé' => 0, 'En maintenance' => 0];
        foreach ($rows as $r) {
            $c[$r['statut']] = (int) $r['n'];
        }
        $c['total'] = array_sum($c);
        return $c;
    }

    /** Règle métier : le patient assigné et le statut restent cohérents. */
    private static function normaliser(string $statut, ?int $patientId): array
    {
        if ($patientId) {
            return ['Occupé', $patientId];
        }
        if ($statut === 'Occupé') {
            $statut = 'Disponible';
        }
        return [$statut, null];
    }

    public static function ajouter(string $nom, string $type, string $statut, ?int $patientId): int
    {
        list($statut, $patientId) = self::normaliser($statut, $patientId);
        $db = Database::getConnection();
        $db->prepare('INSERT INTO ressource (nom, type, statut, patient_id) VALUES (?, ?, ?, ?)')
           ->execute([$nom, $type, $statut, $patientId]);
        return (int) $db->lastInsertId();
    }

    public static function modifier(int $id, string $nom, string $type, string $statut, ?int $patientId): void
    {
        list($statut, $patientId) = self::normaliser($statut, $patientId);
        Database::getConnection()
            ->prepare('UPDATE ressource SET nom = ?, type = ?, statut = ?, patient_id = ? WHERE id = ?')
            ->execute([$nom, $type, $statut, $patientId, $id]);
    }

    public static function supprimer(int $id): void
    {
        Database::getConnection()->prepare('DELETE FROM ressource WHERE id = ?')->execute([$id]);
    }

    public static function liberer(int $id): void
    {
        Database::getConnection()
            ->prepare("UPDATE ressource SET statut = 'Disponible', patient_id = NULL WHERE id = ?")
            ->execute([$id]);
    }

    /** Libère toutes les ressources d'un patient. Retourne le nombre de ressources libérées. */
    public static function libererParPatient(int $patientId): int
    {
        $stmt = Database::getConnection()
            ->prepare("UPDATE ressource SET statut = 'Disponible', patient_id = NULL WHERE patient_id = ?");
        $stmt->execute([$patientId]);
        return $stmt->rowCount();
    }
}
