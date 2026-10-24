<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Matrice des habilitations. Relancer ce seeder est sans danger (idempotent) :
 * c'est la source de vérité versionnée des droits.
 * La matrice est une proposition de départ : à faire valider par la mairie.
 */
class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'revenue.view' => ['revenue', 'Consulter les reçus'],
        'revenue.create' => ['revenue', 'Encaisser et émettre des reçus'],
        'revenue.cancel' => ['revenue', 'Annuler un reçu'],
        'revenue.taxpayers' => ['revenue', 'Gérer les contribuables'],
        'revenue.configure' => ['revenue', 'Configurer catégories et tarifs'],
        'revenue.closing.create' => ['revenue', 'Effectuer une clôture de caisse'],
        'revenue.closing.audit' => ['revenue', 'Contrôler et viser les clôtures de caisse'],
        'revenue.recovery' => ['revenue', 'Gérer le recouvrement et les impayés'],
        'civil.birth.view' => ['civil', 'Consulter les actes de naissance'],
        'civil.birth.create' => ['civil', 'Saisir une déclaration de naissance'],
        'civil.birth.validate' => ['civil', 'Valider un acte (officier d’état civil)'],
        'civil.birth.self_validate' => ['civil', 'Valider son propre acte (exceptionnel)'],
        'civil.birth.print' => ['civil', 'Délivrer et imprimer des actes'],
        'civil.templates' => ['civil', 'Gérer les gabarits d’actes'],
        'hr.view' => ['hr', 'Consulter les dossiers des agents'],
        'hr.manage' => ['hr', 'Créer et modifier les dossiers des agents'],
        'hr.leave.request' => ['hr', 'Demander un congé'],
        'hr.leave.decide' => ['hr', 'Approuver ou refuser un congé'],
        'users.manage' => ['admin', 'Gérer les comptes utilisateurs'],
        'roles.view' => ['admin', 'Consulter la matrice des droits'],
        'services.manage' => ['admin', 'Gérer les services'],
        'audit.view' => ['admin', 'Consulter le journal d’audit'],
        'reports.view' => ['reports', 'Consulter les rapports'],
    ];

    public const ROLES = [
        'admin_technique' => ['Administrateur technique', 'Comptes et supervision ; pas d’accès métier par défaut.', [
            'users.manage', 'roles.view', 'services.manage', 'audit.view',
        ]],
        'admin_municipal' => ['Administrateur municipal', 'Paramétrage métier et habilitations.', [
            'users.manage', 'roles.view', 'services.manage', 'revenue.view', 'revenue.taxpayers', 'revenue.configure', 'revenue.closing.create', 'revenue.closing.audit', 'revenue.recovery', 'hr.view', 'reports.view',
        ]],
        'direction' => ['Direction (maire, secrétaire général)', 'Pilotage et décisions.', [
            'revenue.view', 'revenue.closing.audit', 'revenue.recovery', 'civil.birth.view', 'hr.view', 'hr.leave.decide', 'reports.view', 'audit.view',
        ]],
        'rh' => ['Responsable RH', 'Dossiers agents et congés.', [
            'hr.view', 'hr.manage', 'hr.leave.request', 'hr.leave.decide',
        ]],
        'regisseur' => ['Régisseur / collecteur', 'Encaissement et reçus.', [
            'revenue.view', 'revenue.create', 'revenue.taxpayers', 'revenue.closing.create',
        ]],
        'resp_financier' => ['Responsable financier', 'Contrôle des recettes, annulations, tarifs.', [
            'revenue.view', 'revenue.create', 'revenue.cancel', 'revenue.taxpayers', 'revenue.configure', 'revenue.closing.create', 'revenue.closing.audit', 'revenue.recovery', 'reports.view',
        ]],
        'officier_etat_civil' => ['Officier d’état civil', 'Validation des actes.', [
            'civil.birth.view', 'civil.birth.create', 'civil.birth.validate', 'civil.birth.print', 'civil.templates',
        ]],
        'agent_etat_civil' => ['Agent d’état civil', 'Saisie et délivrance des actes validés.', [
            'civil.birth.view', 'civil.birth.create', 'civil.birth.print',
        ]],
        'resp_service' => ['Responsable de service', 'Suivi de son service.', [
            'hr.view', 'hr.leave.decide', 'hr.leave.request',
        ]],
        'agent' => ['Agent municipal', 'Ses propres demandes.', [
            'hr.leave.request',
        ]],
        'consultation' => ['Consultation', 'Lecture seule.', [
            'revenue.view', 'civil.birth.view', 'reports.view',
        ]],
    ];

    public function run(): void
    {
        $ids = [];
        foreach (self::PERMISSIONS as $slug => [$module, $label]) {
            $ids[$slug] = Permission::updateOrCreate(['slug' => $slug], ['module' => $module, 'label' => $label])->id;
        }

        foreach (self::ROLES as $slug => [$name, $description, $permissions]) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description]);
            $role->permissions()->sync(array_map(fn ($p) => $ids[$p], $permissions));
        }
    }
}
