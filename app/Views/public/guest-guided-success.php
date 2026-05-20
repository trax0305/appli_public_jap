<header class="public-header guest-header">
    <a class="brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <a class="header-link" href="/login">Se connecter</a>
</header>

<section class="guest-page public-main">
    <article class="choice-card choice-card-primary guest-success-card">
        <div>
            <p class="choice-label"><?= e((string) $mission['path_title']) ?></p>
            <h1>Tu as terminé ta première mission.</h1>
            <p>Crée un compte pour sauvegarder ta progression et continuer ton parcours.</p>
        </div>

        <div class="guest-success-actions">
            <a class="button button-primary" href="/register">
                Créer un compte pour sauvegarder
            </a>

            <a class="button button-secondary" href="/guest/free-practice">
                Tester le mode libre
            </a>
        </div>
    </article>
</section>
