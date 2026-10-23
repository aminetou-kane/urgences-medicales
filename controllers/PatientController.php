<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/RessourceModel.php';
require_once __DIR__ . '/../models/JournalModel.php';

class PatientController
{
    private static function filtres(): array
    {
        $statut = get_param('statut');
        $urgence = get_param('urgence');
        return [
            get_param('search'),
            in_array($statut, PatientModel::STATUTS, true) ? $statut : '',
            in_array($urgence, PatientModel::URGENCES, true) ? $urgence : '',
        ];
    }

    public static function index(): void
    {
        list($recherche, $statut, $urgence) = self::filtres();
        $page = max(1, (int) get_param('page', '1'));
        $parPage = (int) Config::get('par_page', 10);
        list($patients, $total) = PatientModel::getAll($recherche, $statut, $urgence, $page, $parPage);

        View::render('patients/liste', [
            'patients' => $patients,
            'total'    => $total,
            'page'     => $page,
            'pages'    => max(1, (int) ceil($total / $parPage)),
            'recherche' => $recherche,
            'statut'   => $statut,
            'urgence'  => $urgence,
        ], 'Patients', 'patients');
    }

    /** Valide la saisie : retourne [données, erreurs]. */
    private static function valider(): array
    {
        $d = [
            'nom'     => input('nom'),
            'prenom'  => input('prenom'),
            'age'     => input('age'),
            'groupe'  => input('groupe_sanguin'),
            'urgence' => input('degre_urgence', 'Faible'),
            'statut'  => input('statut', 'En attente'),
        ];
        $erreurs = [];
        if ($d['nom'] === '' || mb_strlen($d['nom']) > 60) {
            $erreurs[] = 'Le nom est obligatoire (60 caractères maximum).';
        }
        if ($d['prenom'] === '' || mb_strlen($d['prenom']) > 60) {
            $erreurs[] = 'Le prénom est obligatoire (60 caractères maximum).';
        }
        if (!ctype_digit($d['age']) || (int) $d['age'] > 120) {
            $erreurs[] = "L'âge doit être un nombre entre 0 et 120.";
        }
        if ($d['groupe'] !== '' && !in_array($d['groupe'], PatientModel::GROUPES, true)) {
            $erreurs[] = 'Groupe sanguin invalide.';
        }
        if (!in_array($d['urgence'], PatientModel::URGENCES, true)) {
            $erreurs[] = "Degré d'urgence invalide.";
        }
        if (!in_array($d['statut'], PatientModel::STATUTS, true)) {
            $erreurs[] = 'Statut invalide.';
        }
        $d['age'] = (int) $d['age'];
        $d['groupe'] = $d['groupe'] !== '' ? $d['groupe'] : null;
        return [$d, $erreurs];
    }

    public static function ajouter(): void
    {
        list($d, $erreurs) = self::valider();
        if ($erreurs) {
            flash('error', implode(' ', $erreurs));
            redirect('patients');
        }
        $id = PatientModel::ajouter($d);
        JournalModel::log('patient_ajout', "Admission de {$d['prenom']} {$d['nom']} (#$id), urgence {$d['urgence']}");
        flash('success', "Patient {$d['prenom']} {$d['nom']} admis.");
        redirect('patients');
    }

    public static function modifier(): void
    {
        $id = (int) input('id');
        list($d, $erreurs) = self::valider();
        $ancien = $id ? PatientModel::find($id) : null;
        if (!$ancien) {
            flash('error', 'Patient introuvable.');
            redirect('patients');
        }
        if ($erreurs) {
            flash('error', implode(' ', $erreurs));
            redirect('patients');
        }
        PatientModel::modifier($id, $d);
        JournalModel::log('patient_modif', "Mise à jour de {$d['prenom']} {$d['nom']} (#$id) : {$d['statut']}, urgence {$d['urgence']}");

        $message = 'Patient mis à jour.';
        if ($d['statut'] === 'Traité' && $ancien['statut'] !== 'Traité') {
            $n = RessourceModel::libererParPatient($id);
            if ($n > 0) {
                JournalModel::log('ressource_liberation', "$n ressource(s) libérée(s) après la fin de prise en charge de #$id");
                $message .= " $n ressource(s) libérée(s) automatiquement.";
            }
        }
        flash('success', $message);
        redirect('patients');
    }

    public static function supprimer(): void
    {
        $id = (int) input('id');
        $patient = $id ? PatientModel::find($id) : null;
        if ($patient) {
            RessourceModel::libererParPatient($id);
            PatientModel::supprimer($id);
            JournalModel::log('patient_suppr', "Suppression du dossier de {$patient['prenom']} {$patient['nom']} (#$id)");
            flash('success', 'Dossier supprimé.');
        }
        redirect('patients');
    }

    /** Export CSV (compatible Excel français : séparateur « ; », encodage UTF-8 avec BOM). */
    public static function exporter(): void
    {
        list($recherche, $statut, $urgence) = self::filtres();
        $rows = PatientModel::exportRows($recherche, $statut, $urgence);
        JournalModel::log('patient_export', count($rows) . ' dossier(s) exporté(s) en CSV');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="patients_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Nom', 'Prénom', 'Âge', 'Groupe sanguin', "Degré d'urgence", 'Statut', 'Admis le', 'Pris en charge le', 'Traité le'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], csv_safe($r['nom']), csv_safe($r['prenom']), $r['age'], $r['groupe_sanguin'],
                $r['degre_urgence'], $r['statut'], $r['created_at'], $r['pris_en_charge_at'], $r['traite_at'],
            ], ';');
        }
        fclose($out);
        exit;
    }
}
