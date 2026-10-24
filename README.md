# Mairie360 — application Laravel (monolithe, MySQL)

Code applicatif à poser sur un Laravel neuf (ce dossier ne contient pas `vendor/`, `artisan` ni `bootstrap/`).
Contenu : config, migrations, modèles, services, contrôleurs, routes, seeders, vues Blade,
CSS/JS, service worker (mode hors ligne), manifest.

**Jamais exécuté dans un vrai Laravel** : l'environnement de développement n'avait pas accès à Packagist.
Premier lancement attendu : `php artisan migrate --seed` ; les erreurs éventuelles sont à corriger.

## Installation

```bash
composer create-project laravel/laravel mairie360
cp -R <ce-dossier>/. mairie360/
cd mairie360
cp .env.example .env
docker compose up -d
php artisan key:generate
php artisan migrate --seed
```

Remarque : `bootstrap/app.php` d'un Laravel 11+ doit charger `routes/web.php` (c'est le cas par défaut) ;
les migrations `users` / `sessions` / `cache` / `jobs` fournies par Laravel passent avant `2026_10_17_*`.
Comptes de démonstration (local uniquement) : `php artisan db:seed --class=DemoSeeder`.

## Points clés

- Numérotation sans doublon : `NumberingService` (`SELECT … FOR UPDATE`, InnoDB).
- Reçus hors ligne : lots de numéros réservés par collecteur, vérifiés côté serveur (`ReceiptService::resolveNumber`).
- Synchronisation idempotente : `SyncService` + table `sync_receipts`.
- Gabarits d'actes : `TemplateController` + `ActRenderer` (jetons `{{nom_enfant}}`…), aperçu avant activation.
- Aucun tarif ni catégorie de recette inventés : seul `DemoSeeder` (jamais en production) en crée de fictifs.

## Acte de naissance (format officiel, Volets N°1 et N°2)

- Gabarit par défaut : `resources/views/civil/templates/birth.blade.php` (A4 paysage, Volet N°2 à gauche, Volet N°1 à droite).
- Textes arabes : `config/acts.php` (à relire par un arabophone). Tant que `ACT_TEMPLATE_VALIDATED=false`, l'impression porte le filigrane « ÉPREUVE — NON VALIDÉ ».
- Emblème (facultatif) : déposer l'image dans `public/images/emblem.png`.
- Dates « en toutes lettres » : `App\Support\FrenchDate`. Un gabarit actif en base (Gabarits d'actes) remplace celui-ci.
