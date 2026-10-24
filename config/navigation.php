<?php

/*
 * Navigation latérale : un groupe = un module = un accordéon.
 * - 'can'   : permission requise pour voir l'entrée
 * - 'match' : motifs de routes qui rendent l'entrée active
 * - 'soon'  : module prévu mais pas encore livré (affiché grisé)
 */
return [
    [
        'key' => 'dashboard',
        'label' => 'Tableau de bord',
        'icon' => 'home',
        'route' => 'dashboard',
        'match' => ['dashboard'],
    ],
    [
        'key' => 'revenue',
        'label' => 'Recettes & fiscalité',
        'icon' => 'coins',
        'items' => [
            ['label' => 'Encaisser', 'route' => 'revenue.receipts.create', 'can' => 'revenue.create', 'match' => ['revenue.receipts.create']],
            ['label' => 'Reçus émis', 'route' => 'revenue.receipts.index', 'can' => 'revenue.view', 'match' => ['revenue.receipts.index', 'revenue.receipts.show']],
            ['label' => 'Contribuables', 'route' => 'revenue.taxpayers.index', 'can' => 'revenue.taxpayers', 'match' => ['revenue.taxpayers.*']],
            ['label' => 'Catégories & tarifs', 'route' => 'revenue.categories.index', 'can' => 'revenue.configure', 'match' => ['revenue.categories.*']],
            ['label' => 'Clôture de caisse', 'route' => 'revenue.closings.index', 'can' => 'revenue.view', 'match' => ['revenue.closings.*']],
            ['label' => 'Recouvrement', 'route' => 'revenue.recovery.index', 'can' => 'revenue.recovery', 'match' => ['revenue.recovery.*']],
        ],
    ],
    [
        'key' => 'civil',
        'label' => 'État civil',
        'icon' => 'file-text',
        'items' => [
            ['label' => 'Déclarer une naissance', 'route' => 'civil.births.create', 'can' => 'civil.birth.create', 'match' => ['civil.births.create']],
            ['label' => 'Actes de naissance', 'route' => 'civil.births.index', 'can' => 'civil.birth.view', 'match' => ['civil.births.index', 'civil.births.show', 'civil.births.edit']],
            ['label' => 'Gabarits d’actes', 'route' => 'civil.templates.index', 'can' => 'civil.templates', 'match' => ['civil.templates.*']],
            ['label' => 'Mariages', 'soon' => true],
            ['label' => 'Décès', 'soon' => true],
            ['label' => 'Registres', 'soon' => true],
        ],
    ],
    [
        'key' => 'hr',
        'label' => 'Personnel',
        'icon' => 'users',
        'items' => [
            ['label' => 'Agents', 'route' => 'hr.agents.index', 'can' => 'hr.view', 'match' => ['hr.agents.index', 'hr.agents.show', 'hr.agents.edit']],
            ['label' => 'Nouvel agent', 'route' => 'hr.agents.create', 'can' => 'hr.manage', 'match' => ['hr.agents.create']],
            ['label' => 'Congés & permissions', 'route' => 'hr.leaves.index', 'can' => ['hr.leave.request', 'hr.leave.decide', 'hr.manage'], 'match' => ['hr.leaves.*']],
            ['label' => 'Paie', 'soon' => true],
        ],
    ],
    [
        'key' => 'mail',
        'label' => 'Courrier & documents',
        'icon' => 'mail',
        'soon' => true,
        'items' => [
            ['label' => 'Courrier entrant', 'soon' => true],
            ['label' => 'Courrier sortant', 'soon' => true],
            ['label' => 'Archives', 'soon' => true],
        ],
    ],
    [
        'key' => 'land',
        'label' => 'Foncier & urbanisme',
        'icon' => 'map',
        'soon' => true,
        'items' => [
            ['label' => 'Parcelles & lotissements', 'soon' => true],
            ['label' => 'Permis de construire', 'soon' => true],
        ],
    ],
    [
        'key' => 'council',
        'label' => 'Conseil municipal',
        'icon' => 'landmark',
        'soon' => true,
        'items' => [
            ['label' => 'Délibérations', 'soon' => true],
            ['label' => 'Arrêtés municipaux', 'soon' => true],
        ],
    ],
    [
        'key' => 'admin',
        'label' => 'Administration',
        'icon' => 'shield',
        'items' => [
            ['label' => 'Utilisateurs', 'route' => 'admin.users.index', 'can' => 'users.manage', 'match' => ['admin.users.*']],
            ['label' => 'Rôles & droits', 'route' => 'admin.roles.index', 'can' => 'roles.view', 'match' => ['admin.roles.*']],
            ['label' => 'Services', 'route' => 'admin.services.index', 'can' => 'services.manage', 'match' => ['admin.services.*']],
            ['label' => 'Journal d’audit', 'route' => 'admin.audit.index', 'can' => 'audit.view', 'match' => ['admin.audit.*']],
        ],
    ],
];
