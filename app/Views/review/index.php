<?php
$recommendedKana = $review['recommended_kana'];
$frequentErrors = $review['frequent_errors'];
$recentSeen = $review['recent_seen'];
$canStartReview = (bool) $review['can_start_review'];

$kanaForSet = static function (array $row): string {
    return $row['kana_set'] === 'katakana' ? $row['kata'] : $row['hira'];
};
?>

<section class="dashboard-header">
    <div>
        <p class="eyebrow">Révision intelligente</p>
        <h1>Révision intelligente</h1>
        <p>On te propose une révision rapide selon tes erreurs et tes kana les moins maîtrisés.</p>
    </div>

    <a class="button button-secondary" href="/free-practice">Aller au mode libre</a>
</section>

<?php if (!$canStartReview): ?>
    <section class="card review-empty-card">
        <h2>Pas encore de révision disponible.</h2>
        <p>Fais quelques quiz pour débloquer la révision intelligente.</p>

        <div class="review-empty-actions">
            <a class="button button-primary" href="/paths">Aller aux parcours</a>
            <a class="button button-secondary" href="/free-practice">Mode libre</a>
        </div>
    </section>
<?php else: ?>
    <section class="review-grid">
        <article class="card">
            <p class="eyebrow">Kana conseillés</p>
            <h2>Priorité révision</h2>

            <div class="review-kana-list">
                <?php foreach ($recommendedKana as $row): ?>
                    <div class="review-kana-item">
                        <span class="review-kana-symbol"><?= e($kanaForSet($row)) ?></span>
                        <div>
                            <strong><?= e($row['romaji']) ?></strong>
                            <p>
                                <?= e($row['kana_set']) ?> · maîtrise <?= e((string) $row['mastery_score']) ?>% · erreurs <?= e((string) $row['wrong_count']) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card">
            <p class="eyebrow">Erreurs fréquentes</p>
            <h2>À corriger en priorité</h2>

            <?php if ($frequentErrors === []): ?>
                <p>Aucune erreur fréquente détectée pour le moment.</p>
            <?php else: ?>
                <div class="review-kana-list">
                    <?php foreach ($frequentErrors as $row): ?>
                        <div class="review-kana-item">
                            <span class="review-kana-symbol"><?= e($kanaForSet($row)) ?></span>
                            <div>
                                <strong><?= e($row['romaji']) ?></strong>
                                <p><?= e((string) $row['wrong_count']) ?> erreurs · maîtrise <?= e((string) $row['mastery_score']) ?>%</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="card">
            <p class="eyebrow">Derniers kana vus</p>
            <h2>Récemment travaillés</h2>

            <?php if ($recentSeen === []): ?>
                <p>Pas encore de kana vus.</p>
            <?php else: ?>
                <div class="review-kana-list">
                    <?php foreach ($recentSeen as $row): ?>
                        <div class="review-kana-item">
                            <span class="review-kana-symbol"><?= e($kanaForSet($row)) ?></span>
                            <div>
                                <strong><?= e($row['romaji']) ?></strong>
                                <p><?= e($row['kana_set']) ?> · vus <?= e((string) $row['seen_count']) ?> fois</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section class="card review-action-card">
        <form method="post" action="/review/start">
            <?= csrf_field() ?>
            <button class="button button-primary" type="submit">Lancer la révision</button>
        </form>
    </section>
<?php endif; ?>
