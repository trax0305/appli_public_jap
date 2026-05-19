<?php
$global = $stats['global'];
$alphabet = $stats['alphabet'];
$frequentErrors = $stats['frequent_errors'];
$weakestKana = $stats['weakest_kana'];
$streakSummary = $stats['streak_summary'];
$badges = $stats['badges'];
$hasAnyStat = (bool) $stats['has_any_stat'];

$kanaForSet = static function (array $row): string {
    return $row['kana_set'] === 'katakana' ? $row['kata'] : $row['hira'];
};
?>

<section class="dashboard-header">
    <div>
        <p class="eyebrow">Stats</p>
        <h1>Mes statistiques</h1>
        <p>Un aperçu simple de ta progression sur les kana.</p>
    </div>

    <a class="button button-secondary" href="/dashboard">Dashboard</a>
</section>

<?php if (!$hasAnyStat): ?>
    <section class="card stats-empty-card">
        <h2>Pas encore de statistiques.</h2>
        <p>Fais quelques quiz pour voir tes statistiques.</p>

        <div class="stats-empty-actions">
            <a class="button button-primary" href="/paths">Commencer un parcours</a>
            <a class="button button-secondary" href="/free-practice">Mode libre</a>
        </div>
    </section>
<?php else: ?>
    <section class="stats-grid stats-grid-top">
        <article class="card stats-metric-card">
            <p class="stats-metric-label">Quiz terminés</p>
            <p class="stats-metric-value"><?= e((string) $global['total_quiz_completed']) ?></p>
        </article>

        <article class="card stats-metric-card">
            <p class="stats-metric-label">Score moyen</p>
            <p class="stats-metric-value"><?= e((string) $global['average_score']) ?>%</p>
        </article>

        <article class="card stats-metric-card">
            <p class="stats-metric-label">Kana vus</p>
            <p class="stats-metric-value"><?= e((string) $global['kana_seen']) ?></p>
        </article>

        <article class="card stats-metric-card">
            <p class="stats-metric-label">Kana maîtrisés</p>
            <p class="stats-metric-value"><?= e((string) $global['kana_mastered']) ?></p>
        </article>
    </section>

    <section class="stats-grid stats-grid-two">
        <article class="card">
            <p class="eyebrow">Par alphabet</p>
            <h2>Maîtrise Hiragana / Katakana</h2>

            <div class="stats-alpha-list">
                <div class="stats-alpha-item">
                    <div>
                        <strong>Hiragana</strong>
                        <p>
                            <?= e((string) $alphabet['hiragana']['mastered']) ?> / <?= e((string) $alphabet['hiragana']['total']) ?> maîtrisés
                        </p>
                    </div>
                    <span class="stats-badge"><?= e((string) $alphabet['hiragana']['mastery_percent']) ?>%</span>
                </div>

                <div class="stats-alpha-item">
                    <div>
                        <strong>Katakana</strong>
                        <p>
                            <?= e((string) $alphabet['katakana']['mastered']) ?> / <?= e((string) $alphabet['katakana']['total']) ?> maîtrisés
                        </p>
                    </div>
                    <span class="stats-badge"><?= e((string) $alphabet['katakana']['mastery_percent']) ?>%</span>
                </div>
            </div>
        </article>

        <article class="card">
            <p class="eyebrow">Séries</p>
            <h2>Résumé des séries</h2>

            <div class="stats-alpha-list">
                <div class="stats-alpha-item">
                    <div>
                        <strong>Meilleure série globale</strong>
                        <p>Maximum observé sur tes kana.</p>
                    </div>
                    <span class="stats-badge"><?= e((string) $streakSummary['best_streak_global']) ?></span>
                </div>

                <div class="stats-alpha-item">
                    <div>
                        <strong>Kana avec série en cours ≥ 3</strong>
                        <p>Nombre de kana actuellement en réussite continue.</p>
                    </div>
                    <span class="stats-badge"><?= e((string) $streakSummary['kana_with_streak_3']) ?></span>
                </div>
            </div>
        </article>
    </section>

    <section class="stats-grid stats-grid-two">
        <article class="card">
            <p class="eyebrow">Erreurs fréquentes</p>
            <h2>Top 10 à revoir</h2>

            <?php if ($frequentErrors === []): ?>
                <p>Aucune erreur enregistrée pour le moment.</p>
            <?php else: ?>
                <div class="stats-table-wrap">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Kana</th>
                                <th>Romaji</th>
                                <th>Set</th>
                                <th>Erreurs</th>
                                <th>Maîtrise</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($frequentErrors as $row): ?>
                                <tr>
                                    <td class="stats-kana-cell"><?= e($kanaForSet($row)) ?></td>
                                    <td><?= e($row['romaji']) ?></td>
                                    <td><?= e($row['kana_set']) ?></td>
                                    <td><?= e((string) $row['wrong_count']) ?></td>
                                    <td><?= e((string) $row['mastery_score']) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>

        <article class="card">
            <p class="eyebrow">Kana faibles</p>
            <h2>Top 10 à consolider</h2>

            <?php if ($weakestKana === []): ?>
                <p>Pas assez de données pour classer les kana faibles.</p>
            <?php else: ?>
                <div class="stats-table-wrap">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Kana</th>
                                <th>Romaji</th>
                                <th>Set</th>
                                <th>Vus</th>
                                <th>Erreurs</th>
                                <th>Maîtrise</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weakestKana as $row): ?>
                                <tr>
                                    <td class="stats-kana-cell"><?= e($kanaForSet($row)) ?></td>
                                    <td><?= e($row['romaji']) ?></td>
                                    <td><?= e($row['kana_set']) ?></td>
                                    <td><?= e((string) $row['seen_count']) ?></td>
                                    <td><?= e((string) $row['wrong_count']) ?></td>
                                    <td><?= e((string) $row['mastery_score']) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section class="card">
        <p class="eyebrow">Badges</p>
        <h2>Mes badges</h2>

        <?php if ($badges === []): ?>
            <p>Les badges seront affichés ici quand ils seront débloqués.</p>
        <?php else: ?>
            <div class="stats-badge-list">
                <?php foreach ($badges as $badge): ?>
                    <article class="stats-badge-item">
                        <strong><?= e($badge['title']) ?></strong>
                        <p><?= e((string) ($badge['description'] ?? '')) ?></p>
                        <small>Débloqué le <?= e((string) $badge['unlocked_at']) ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
