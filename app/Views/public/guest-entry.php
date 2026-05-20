<header class="public-header">
    <a class="brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <a class="header-link" href="/login">Se connecter</a>
</header>

<section class="page-heading">
    <p class="eyebrow">Commencer gratuitement</p>
    <h1>Teste les kana sans compte</h1>
    <p>Commence par un quiz court sur les voyelles hiragana. Tu pourras créer un compte ensuite pour sauvegarder ta progression.</p>
</section>

<section class="choice-grid">
    <article class="choice-card choice-card-primary">
        <div>
            <p class="choice-label">Quiz invité</p>
            <h2>Essayer les voyelles hiragana</h2>
            <p>
                Cinq questions rapides pour reconnaître あ, い, う, え et お.
            </p>
        </div>

        <form method="post" action="/guest/free-practice/start">
            <?= csrf_field() ?>
            <button class="button button-primary" type="submit">
                Lancer le quiz invité
            </button>
        </form>
    </article>

    <article class="choice-card">
        <div>
            <p class="choice-label">Sauvegarde</p>
            <h2>Créer un compte gratuit</h2>
            <p>
                Garde tes stats, tes badges et ta progression dans les parcours.
            </p>
        </div>

        <a class="button button-secondary" href="/register">
            Créer mon compte
        </a>
    </article>
</section>

<p class="center-link">
    Déjà un compte ?
    <a href="/login">Se connecter</a>
</p>
