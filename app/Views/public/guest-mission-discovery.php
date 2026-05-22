<header class="public-header guest-header">
    <a class="brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <a class="header-link" href="/login">Se connecter</a>
</header>

<section class="guest-mission-page guest-discovery-page public-main">
    <section class="dashboard-header guest-discovery-header">
        <div>
            <div class="guest-discovery-top-row">
                <p class="eyebrow">Découverte</p>
                <a class="button button-secondary guest-discovery-back" href="/guest/missions/<?= e((string) $mission['id']) ?>">
                    Retour mission
                </a>
            </div>

            <h1><?= e((string) $mission['title']) ?></h1>
            <p>Observe bien les formes avant de commencer.</p>
        </div>
    </section>

    <section class="discovery-grid guest-discovery-grid">
        <?php foreach ($mission['kana'] as $kana): ?>
            <article class="discovery-card guest-discovery-kana">
                <span class="discovery-kana">
                    <?= e($mission['kana_set'] === 'katakana' ? (string) $kana['kata'] : (string) $kana['hira']) ?>
                </span>

                <span class="discovery-romaji">
                    <?= e((string) $kana['romaji']) ?>
                </span>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="card discovery-action-card guest-discovery-action">
        <p>
            Quand tu es prêt, passe au quiz.
        </p>

        <form method="post" action="/guest/missions/<?= e((string) $mission['id']) ?>/discovery/complete">
            <?= csrf_field() ?>

            <button class="button button-primary" type="submit">
                J'ai compris
            </button>
        </form>
    </section>
</section>
