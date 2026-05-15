<?php
$score = (int) ($session['score_percent'] ?? 0);
$correctAnswers = (int) ($session['correct_answers'] ?? 0);
$totalQuestions = (int) ($session['total_questions'] ?? 0);

$wrongAnswers = array_filter($answers, function (array $answer): bool {
    return (int) $answer['is_correct'] !== 1;
});
?>

<section class="result-card">
    <p class="eyebrow">Résultat</p>

    <?php if ($score === 100): ?>
        <h1>Parfait.</h1>
        <p class="result-score result-score-perfect">
            <?= e((string) $correctAnswers) ?> / <?= e((string) $totalQuestions) ?>
            —
            <?= e((string) $score) ?>%
        </p>
        <p>
            Cette réussite compte pour ton objectif. Continue comme ça.
        </p>
    <?php else: ?>
        <h1>Pas encore.</h1>
        <p class="result-score">
            <?= e((string) $correctAnswers) ?> / <?= e((string) $totalQuestions) ?>
            —
            <?= e((string) $score) ?>%
        </p>
        <p>
            Tu dois faire 100% pour valider cet objectif. C’est normal de rater au début :
            regarde tes erreurs puis recommence.
        </p>
    <?php endif; ?>

    <div class="result-actions">
        <?php if ($missionId): ?>
            <a class="button button-primary" href="/missions/<?= e((string) $missionId) ?>">
                Revoir la mission
            </a>
        <?php endif; ?>

        <form method="post" action="/quiz/start">
            <?= csrf_field() ?>
            <input type="hidden" name="objective_id" value="<?= e((string) $session['source_id']) ?>">
            <button class="button button-secondary" type="submit">
                Recommencer ce quiz
            </button>
        </form>

        <?php if (!empty($wrongAnswers)): ?>
            <form method="post" action="/quiz/<?= e((string) $session['id']) ?>/retry-errors">
                <?= csrf_field() ?>
                <button class="button button-secondary" type="submit">
                    Refaire mes erreurs
                </button>
            </form>
        <?php endif; ?>

        <a class="button button-secondary" href="/dashboard">
            Dashboard
        </a>
    </div>
</section>

<?php if (!empty($wrongAnswers)): ?>
    <section class="result-errors">
        <div class="section-heading">
            <p class="eyebrow">Erreurs</p>
            <h2>À revoir</h2>
        </div>

        <div class="error-list">
            <?php foreach ($wrongAnswers as $answer): ?>
                <article class="error-card">
                    <div class="error-symbol">
                        <?= e($answer['displayed_value']) ?>
                    </div>

                    <div class="error-details">
                        <p>
                            <strong>Ta réponse :</strong>
                            <?= e((string) $answer['user_answer']) ?>
                        </p>

                        <p>
                            <strong>Bonne réponse :</strong>
                            <?= e((string) $answer['expected_answer']) ?>
                        </p>

                        <p class="error-kana-line">
                            Hiragana : <?= e($answer['hira']) ?>
                            —
                            Katakana : <?= e($answer['kata']) ?>
                            —
                            Romaji : <?= e($answer['romaji']) ?>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php else: ?>
    <section class="result-errors">
        <div class="perfect-empty-state">
            <strong>Aucune erreur.</strong>
            <span>Tu peux passer à la suite ou consolider avec un autre 100%.</span>
        </div>
    </section>
<?php endif; ?>