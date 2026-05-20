<?php
$isCorrect = (int) $answer['is_correct'] === 1;
?>

<section class="quiz-shell">
    <div class="quiz-progress">
        Question <?= e((string) $answer['question_order']) ?>
        /
        <?= e((string) $session['total_questions']) ?>
    </div>

    <article class="quiz-card quiz-feedback-card">
        <?php if ($isCorrect): ?>
            <div class="quiz-feedback quiz-feedback-success">
                <strong>Bonne réponse</strong>
            </div>
        <?php else: ?>
            <div class="quiz-feedback quiz-feedback-error">
                <strong>Pas encore</strong>
            </div>
        <?php endif; ?>

        <div class="quiz-symbol">
            <?= e((string) $answer['displayed_value']) ?>
        </div>

        <div class="quiz-feedback-lines">
            <p><strong>Tu as répondu :</strong> <?= e((string) $answer['user_answer']) ?></p>
            <p><strong>Bonne réponse :</strong> <?= e((string) $answer['expected_answer']) ?></p>
            <p class="quiz-feedback-kana">
                Hiragana : <?= e((string) $answer['hira']) ?>
                — Katakana : <?= e((string) $answer['kata']) ?>
                — Romaji : <?= e((string) $answer['romaji']) ?>
            </p>
        </div>

        <a class="button button-primary button-full" href="/quiz/<?= e((string) $session['id']) ?>">
            Suivant
        </a>

        <a class="quiz-quit-link" href="/dashboard">Quitter</a>
    </article>
</section>
