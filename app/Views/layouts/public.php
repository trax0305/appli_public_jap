<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?= e($title ?? 'App Japonais') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Apprends les hiragana et katakana simplement avec des missions guidées, des quiz rapides et une révision intelligente.">
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <main class="public-shell">
        <?php if ($message = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($message = \App\Core\Session::getFlash('success')): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <script src="/assets/js/app.js"></script>
</body>
</html>
