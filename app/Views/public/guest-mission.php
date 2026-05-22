<header class="public-header guest-header">
    <a class="brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <a class="header-link" href="/login">Se connecter</a>
</header>

<?php
$discoveryCompleted = false;
$nextObjective = null;

foreach ($mission['objectives'] as $objective) {
    if ($objective['objective_type'] === 'discovery' && $objective['user_status'] === 'completed') {
        $discoveryCompleted = true;
    }

    if (
        $nextObjective === null
        && $objective['objective_type'] !== 'discovery'
        && $objective['user_status'] === 'available'
    ) {
        $nextObjective = $objective;
    }
}
?>

<section class="guest-mission-page public-main">
    <section class="dashboard-header guest-mission-header">
        <div class="guest-mission-copy">
            <p class="eyebrow"><?= e((string) $mission['path_title']) ?></p>
            <h1><?= e((string) $mission['title']) ?></h1>
            <p><?= e((string) ($mission['description'] ?? '')) ?></p>
        </div>

        <a class="button button-secondary guest-change-path" href="/guest/guided">Changer de parcours</a>
    </section>

    <section class="mission-detail-grid guest-mission-grid">
        <article class="dashboard-main-card guest-kana-card">
            <p class="eyebrow">Kana de la mission</p>

            <div class="kana-preview-grid guest-kana-grid">
                <?php foreach ($mission['kana'] as $kana): ?>
                    <div class="kana-preview-card guest-kana-mini">
                        <span class="kana-symbol">
                            <?= e($mission['kana_set'] === 'katakana' ? (string) $kana['kata'] : (string) $kana['hira']) ?>
                        </span>
                        <span class="kana-romaji"><?= e((string) $kana['romaji']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$discoveryCompleted): ?>
                <a class="button button-primary button-full" href="/guest/missions/<?= e((string) $mission['id']) ?>/discovery">
                    Commencer la découverte
                </a>
            <?php elseif ($nextObjective !== null): ?>
                <form method="post" action="/guest/quiz/start" class="guest-main-cta-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="objective_id" value="<?= e((string) $nextObjective['id']) ?>">
                    <button class="button button-primary button-full" type="submit">
                        Continuer
                    </button>
                </form>
            <?php else: ?>
                <p class="guest-helper-text">Continue les étapes dans l'ordre. Une étape parfaite débloque la suivante.</p>
            <?php endif; ?>
        </article>

        <aside class="dashboard-side guest-steps-side">
            <article class="mini-card guest-mission-steps">
                <h2>Étapes</h2>

                <div class="objective-list guest-steps-list">
                    <?php foreach ($mission['objectives'] as $objective): ?>
                        <div class="objective-item guest-step-item objective-item-<?= e((string) $objective['user_status']) ?>">
                            <div>
                                <strong><?= e((string) $objective['title']) ?></strong>
                                <span>
                                    <?= e((string) $objective['success_count']) ?>
                                    /
                                    <?= e((string) $objective['required_success_count']) ?>
                                </span>
                            </div>

                            <?php if ($objective['user_status'] === 'available' && $objective['objective_type'] !== 'discovery'): ?>
                                <form method="post" action="/guest/quiz/start">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="objective_id" value="<?= e((string) $objective['id']) ?>">
                                    <button class="button button-secondary button-small" type="submit">
                                        Lancer
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </aside>
    </section>
</section>
