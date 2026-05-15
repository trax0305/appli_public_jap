<section class="dashboard-header">
    <div>
        <p class="eyebrow">Parcours</p>
        <h1><?= e($path['title']) ?></h1>
        <p><?= e($path['description'] ?? '') ?></p>
    </div>

    <a class="button button-secondary" href="/paths">Tous les parcours</a>
</section>

<section class="mission-list">
    <?php foreach ($path['missions'] as $mission): ?>
        <article class="mission-row mission-row-<?= e($mission['user_status']) ?>">
            <div>
                <p class="mission-status"><?= e($mission['user_status']) ?></p>
                <h2><?= e($mission['title']) ?></h2>
                <p><?= e($mission['description'] ?? '') ?></p>
                <p class="mission-kana-count">
                    <?= e((string) $mission['kana_count']) ?> caractères
                </p>
            </div>

            <?php if ($mission['user_status'] !== 'locked'): ?>
                <a class="button button-primary" href="/missions/<?= e((string) $mission['id']) ?>">
                    Ouvrir
                </a>
            <?php else: ?>
                <span class="button button-disabled">Verrouillé</span>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>