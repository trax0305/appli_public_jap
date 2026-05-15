<section class="dashboard-header">
    <div>
        <p class="eyebrow"><?= e($mission['path_title']) ?></p>
        <h1><?= e($mission['title']) ?></h1>
        <p><?= e($mission['description'] ?? '') ?></p>
    </div>

    <a class="button button-secondary" href="/paths">Parcours</a>
</section>

<section class="mission-detail-grid">
    <article class="dashboard-main-card">
        <p class="eyebrow">Kana de la mission</p>

        <div class="kana-preview-grid">
            <?php foreach ($mission['kana'] as $kana): ?>
                <div class="kana-preview-card">
                    <span class="kana-symbol">
                        <?= e($mission['kana_set'] === 'katakana' ? $kana['kata'] : $kana['hira']) ?>
                    </span>
                    <span class="kana-romaji"><?= e($kana['romaji']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <a class="button button-primary button-full" href="/missions/<?= e((string) $mission['id']) ?>/discovery">
            Commencer la découverte
        </a>
    </article>

    <aside class="dashboard-side">
        <article class="mini-card">
            <h2>Étapes</h2>

            <div class="objective-list">
    <?php foreach ($mission['objectives'] as $objective): ?>
        <div class="objective-item objective-item-<?= e($objective['user_status']) ?>">
            <div>
                <strong><?= e($objective['title']) ?></strong>
                <span>
                    <?= e((string) $objective['success_count']) ?>
                    /
                    <?= e((string) $objective['required_success_count']) ?>
                    réussites
                </span>
            </div>

            <?php if ($objective['user_status'] === 'available' && $objective['objective_type'] !== 'discovery'): ?>
                <form method="post" action="/quiz/start">
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