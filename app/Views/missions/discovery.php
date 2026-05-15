<section class="dashboard-header">
    <div>
        <p class="eyebrow">Découverte</p>
        <h1><?= e($mission['title']) ?></h1>
        <p>Observe bien les formes avant de commencer le quiz.</p>
    </div>

    <a class="button button-secondary" href="/missions/<?= e((string) $mission['id']) ?>">
        Retour mission
    </a>
</section>

<section class="discovery-grid">
    <?php foreach ($mission['kana'] as $kana): ?>
        <article class="discovery-card">
            <span class="discovery-kana">
                <?= e($mission['kana_set'] === 'katakana' ? $kana['kata'] : $kana['hira']) ?>
            </span>

            <span class="discovery-romaji">
                <?= e($kana['romaji']) ?>
            </span>
        </article>
    <?php endforeach; ?>
</section>

<section class="card discovery-action-card">
    <p>
        Quand tu es prêt, valide la découverte. Le premier quiz de reconnaissance sera débloqué.
    </p>

    <form method="post" action="/missions/<?= e((string) $mission['id']) ?>/discovery/complete">
        <?= csrf_field() ?>

        <button class="button button-primary" type="submit">
            J’ai compris
        </button>
    </form>
</section>