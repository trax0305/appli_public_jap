<header class="public-header guest-header">
    <a class="brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <a class="header-link" href="/login">Se connecter</a>
</header>

<section class="guest-page public-main">
    <div class="page-heading guest-heading">
        <h1>Par quoi veux-tu commencer ?</h1>
        <p>Teste une première mission sans compte.</p>
    </div>

    <section class="choice-grid guest-choice-grid">
        <article class="choice-card choice-card-primary guest-card">
            <div>
                <p class="choice-label">Hiragana</p>
                <h2>Commencer les hiragana</h2>
                <p>Le meilleur point de départ pour débuter.</p>
            </div>

            <form method="post" action="/guest/guided/start">
                <?= csrf_field() ?>
                <input type="hidden" name="path_code" value="hiragana_base">
                <button class="button button-primary" type="submit">
                    Commencer par les hiragana
                </button>
            </form>
        </article>

        <article class="choice-card guest-card">
            <div>
                <p class="choice-label">Katakana</p>
                <h2>Commencer les katakana</h2>
                <p>Utile pour les mots étrangers et les noms propres.</p>
            </div>

            <form method="post" action="/guest/guided/start">
                <?= csrf_field() ?>
                <input type="hidden" name="path_code" value="katakana_base">
                <button class="button button-secondary" type="submit">
                    Commencer par les katakana
                </button>
            </form>
        </article>
    </section>

    <p class="center-link guest-account-link">
        <a href="/register">Créer un compte gratuit</a>
    </p>
</section>
