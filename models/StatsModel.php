<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

/** Indicateurs du tableau de bord (calculés en SQL). */
class StatsModel
{
    public static function parStatut(): array
    {
        $r = ['En attente' => 0, 'En prise en charge' => 0, 'Traité' => 0];
        foreach (Database::getConnection()->query('SELECT statut, COUNT(*) n FROM patient GROUP BY statut')->fetchAll() as $row) {
            $r[$row['statut']] = (int) $row['n'];
        }
        return $r;
    }

    /** Cas actifs (non traités) par degré d'urgence. */
    public static function parUrgence(): array
    {
        $r = ['Absolue' => 0, 'Moyenne' => 0, 'Faible' => 0];
        $rows = Database::getConnection()->query(
            "SELECT degre_urgence, COUNT(*) n FROM patient WHERE statut <> 'Traité' GROUP BY degre_urgence"
        )->fetchAll();
        foreach ($rows as $row) {
            $r[$row['degre_urgence']] = (int) $row['n'];
        }
        return $r;
    }

    /** Délai moyen d'attente avant prise en charge (7 derniers jours), en minutes. */
    public static function attenteMoyenne(): ?int
    {
        $v = Database::getConnection()->query(
            'SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, pris_en_charge_at)) FROM patient
             WHERE pris_en_charge_at IS NOT NULL AND created_at >= (CURDATE() - INTERVAL 6 DAY)'
        )->fetchColumn();
        return $v === null ? null : (int) round((float) $v);
    }

    /** Admissions des 7 derniers jours, jours sans admission inclus. */
    public static function admissions7Jours(): array
    {
        $db = Database::getConnection();
        $aujourdhui = (string) $db->query('SELECT CURDATE()')->fetchColumn();
        $rows = $db->query(
            'SELECT DATE(created_at) d, COUNT(*) n FROM patient
             WHERE created_at >= (CURDATE() - INTERVAL 6 DAY) GROUP BY DATE(created_at)'
        )->fetchAll();
        $parJour = [];
        foreach ($rows as $row) {
            $parJour[$row['d']] = (int) $row['n'];
        }
        $serie = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime($aujourdhui . " -$i day"));
            $serie[] = ['date' => $date, 'n' => $parJour[$date] ?? 0];
        }
        return $serie;
    }

    /** Patients en attente, par gravité puis ancienneté. */
    public static function fileAttente(int $limite = 6): array
    {
        return Database::getConnection()->query(
            "SELECT id, nom, prenom, age, degre_urgence, groupe_sanguin, TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS attente_min
             FROM patient WHERE statut = 'En attente'
             ORDER BY FIELD(degre_urgence, 'Absolue', 'Moyenne', 'Faible'), created_at ASC LIMIT " . (int) $limite
        )->fetchAll();
    }

    /** Occupation des ressources par type. */
    public static function ressourcesParType(): array
    {
        return Database::getConnection()->query(
            "SELECT type, COUNT(*) total,
                    SUM(statut = 'Occupé') occupe,
                    SUM(statut = 'Disponible') dispo,
                    SUM(statut = 'En maintenance') maintenance
             FROM ressource GROUP BY type ORDER BY FIELD(type, 'Lit', 'Équipement', 'Salle', 'Personnel')"
        )->fetchAll();
    }

    /** Patients Absolue en attente depuis plus de $minutes minutes. */
    public static function urgencesEnRetard(int $minutes): int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM patient WHERE statut = 'En attente' AND degre_urgence = 'Absolue'
             AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) > ?"
        );
        $stmt->execute([$minutes]);
        return (int) $stmt->fetchColumn();
    }
}
