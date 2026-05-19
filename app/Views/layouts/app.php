<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?= e($title ?? 'App Japonais') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <main class="app-shell app-shell-with-bottom-nav">
        <?php require view_path('partials/flash.php'); ?>
        <?= $content ?>
    </main>

    <?php require view_path('partials/bottom-nav.php'); ?>

    <script src="/assets/js/app.js"></script>
</body>
</html>