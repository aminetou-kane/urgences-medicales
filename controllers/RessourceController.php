<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/RessourceModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/JournalModel.php';

class RessourceController
{
    public static function index(): void
    {
        $type = get_param('type');
        $statut = get_param('statut');
        $type = in_array($type, RessourceModel::TYPES, true) ? $type : '';
        $statut = in_array($statut, RessourceModel::STATUTS, true) ? $statut : '';

        View::render('ressources/liste', [
            'ressources' => RessourceModel::getAll($type, $statut),
            'compteurs'  => RessourceModel::compteurs(),
            'patients'   => PatientModel::actifs(),
            'type'       => $type,
            'statut'     => $statut,
        ], 'Ressources', 'ressources');
    }

    private static function valider(): array
    {
        $nom = input('nom');
        $type = input('type');
        $statut = input('statut', 'Disponible');
        $patientId = (int) input('patient_id') ?: null;
        $erreurs = [];
        if ($nom === '' || mb_strlen($nom) > 80) {
            $erreurs[] = 'Le nom est obligatoire (80 caractères maximum).';
        }
        if (!in_array($type, RessourceModel::TYPES, true)) {
            $erreurs[] = 'Type invalide.';
        }
        if (!in_array($statut, RessourceModel::STATUTS, true)) {
            $erreurs[] = 'Statut invalide.';
        }
        if ($patientId !== null && !PatientModel::find($patientId)) {
            $erreurs[] = 'Patient introuvable.';
        }
        return [[$nom, $type, $statut, $patientId], $erreurs];
    }

    public static function ajouter(): void
    {
        list($d, $erreurs) = self::valider();
        if ($erreurs) {
            flash('error', implode(' ', $erreurs));
            redirect('ressources');
        }
        $id = RessourceModel::ajouter($d[0], $d[1], $d[2], $d[3]);
        JournalModel::log('ressource_ajout', "Ressource « {$d[0]} » ({$d[1]}) ajoutée (#$id)");
        flash('success', "Ressource « {$d[0]} » ajoutée.");
        redirect('ressources');
    }

    public static function modifier(): void
    {
        $id = (int) input('id');
        list($d, $erreurs) = self::valider();
        if (!$id || $erreurs) {
            flash('error', $erreurs ? implode(' ', $erreurs) : 'Ressource introuvable.');
            redirect('ressources');
        }
        RessourceModel::modifier($id, $d[0], $d[1], $d[2], $d[3]);
        JournalModel::log('ressource_modif', "Ressource « {$d[0]} » modifiée (#$id)");
        flash('success', 'Ressource mise à jour.');
        redirect('ressources');
    }

    public static function liberer(): void
    {
        $id = (int) input('id');
        if ($id) {
            RessourceModel::liberer($id);
            JournalModel::log('ressource_liberation', "Ressource #$id libérée manuellement");
            flash('success', 'Ressource libérée.');
        }
        redirect('ressources');
    }

    public static function supprimer(): void
    {
        $id = (int) input('id');
        if ($id) {
            RessourceModel::supprimer($id);
            JournalModel::log('ressource_suppr', "Ressource #$id supprimée");
            flash('success', 'Ressource supprimée.');
        }
        redirect('ressources');
    }
}
