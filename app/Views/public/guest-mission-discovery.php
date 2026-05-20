<header class="public-header guest-header">
    <a class="brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <a class="header-link" href="/login">Se connecter</a>
</header>

<section class="guest-mission-page public-main">
    <section class="dashboard-header">
        <div>
            <p class="eyebrow">Découverte</p>
            <h1><?= e((string) $mission['title']) ?></h1>
            <p>Observe bien les formes avant de commencer les quiz.</p>
        </div>

        <a class="button button-secondary" href="/guest/missions/<?= e((string) $mission['id']) ?>">
            Retour mission
        </a>
    </section>

    <section class="discovery-grid">
        <?php foreach ($mission['kana'] as $kana): ?>
            <article class="discovery-card">
                <span class="discovery-kana">
                    <?= e($mission['kana_set'] === 'katakana' ? (string) $kana['kata'] : (string) $kana['hira']) ?>
                </span>

                <span class="discovery-romaji">
                    <?= e((string) $kana['romaji']) ?>
                </span>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="card discovery-action-card">
        <p>
            Quand tu es prêt, valide la découverte. Le premier quiz sera débloqué.
        </p>

        <form method="post" action="/guest/missions/<?= e((string) $mission['id']) ?>/discovery/complete">
            <?= csrf_field() ?>

            <button class="button button-primary" type="submit">
                J'ai compris
            </button>
        </form>
    </section>
</section>
