<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?= e($title ?? 'App Japonais') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Apprends les hiragana et katakana simplement avec des missions guidées, des quiz rapides et une révision intelligente.">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <main class="public-shell">
        <?= $content ?>
    </main>

    <script src="/assets/js/app.js"></script>
</body>
</html>