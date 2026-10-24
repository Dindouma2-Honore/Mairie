<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hors ligne — {{ config('mairie.short_name') }}</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<main class="guest-form" style="min-height:100vh">
    <div class="box">
        <h2>Cette page n’est pas disponible hors ligne</h2>
        <p class="muted">La connexion est interrompue et cette page n’a pas encore été ouverte sur cet appareil.</p>
        <p>Vous pouvez continuer à utiliser les écrans déjà ouverts, notamment <b>Encaisser</b> et <b>Déclarer une naissance</b> : les saisies sont gardées sur l’appareil puis envoyées au retour du réseau.</p>
        <div class="form-actions">
            <a class="btn btn-primary" href="/">Retour à l’accueil</a>
            <button class="btn btn-quiet" type="button" onclick="location.reload()">Réessayer</button>
        </div>
    </div>
</main>
</body>
</html>
