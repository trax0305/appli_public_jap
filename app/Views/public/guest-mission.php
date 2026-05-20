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
            <p class="eyebrow"><?= e((string) $mission['path_title']) ?></p>
            <h1><?= e((string) $mission['title']) ?></h1>
            <p><?= e((string) ($mission['description'] ?? '')) ?></p>
        </div>

        <a class="button button-secondary" href="/guest/guided">Changer</a>
    </section>

    <section class="mission-detail-grid">
        <article class="dashboard-main-card">
            <p class="eyebrow">Kana de la mission</p>

            <div class="kana-preview-grid">
                <?php foreach ($mission['kana'] as $kana): ?>
                    <div class="kana-preview-card">
                        <span class="kana-symbol">
                            <?= e($mission['kana_set'] === 'katakana' ? (string) $kana['kata'] : (string) $kana['hira']) ?>
                        </span>
                        <span class="kana-romaji"><?= e((string) $kana['romaji']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php
            $discoveryCompleted = false;

            foreach ($mission['objectives'] as $objective) {
                if ($objective['objective_type'] === 'discovery' && $objective['user_status'] === 'completed') {
                    $discoveryCompleted = true;
                    break;
                }
            }
            ?>

            <?php if (!$discoveryCompleted): ?>
                <a class="button button-primary button-full" href="/guest/missions/<?= e((string) $mission['id']) ?>/discovery">
                    Commencer la découverte
                </a>
            <?php else: ?>
                <p class="guest-helper-text">Continue les étapes dans l'ordre. Une étape parfaite débloque la suivante.</p>
            <?php endif; ?>
        </article>

        <aside class="dashboard-side">
            <article class="mini-card">
                <h2>Étapes</h2>

                <div class="objective-list">
                    <?php foreach ($mission['objectives'] as $objective): ?>
                        <div class="objective-item objective-item-<?= e((string) $objective['user_status']) ?>">
                            <div>
                                <strong><?= e((string) $objective['title']) ?></strong>
                                <span>
                                    <?= e((string) $objective['success_count']) ?>
                                    /
                                    <?= e((string) $objective['required_success_count']) ?>
                                    réussites
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
