<section class="dashboard-header">
    <div>
        <p class="eyebrow">Parcours</p>
        <h1>Choisis ton parcours</h1>
    </div>

    <a class="button button-secondary" href="/dashboard">Dashboard</a>
</section>

<section class="path-grid">
    <?php foreach ($paths as $path): ?>
        <?php
        $total = (int) $path['total_missions'];
        $completed = (int) $path['completed_missions'];
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
        ?>

        <article class="path-card">
            <div>
                <p class="path-status"><?= e($path['user_status']) ?></p>
                <h2><?= e($path['title']) ?></h2>
                <p><?= e($path['description'] ?? '') ?></p>
            </div>

            <div class="progress-block">
                <div class="progress-label">
                    <span>Progression</span>
                    <strong><?= e((string) $completed) ?> / <?= e((string) $total) ?></strong>
                </div>

                <div class="progress-bar">
                    <span style="width: <?= e((string) $percent) ?>%"></span>
                </div>
            </div>

            <a class="button button-secondary button-full" href="/paths/<?= e((string) $path['id']) ?>">
                Voir le parcours
            </a>
        </article>
    <?php endforeach; ?>
</section>