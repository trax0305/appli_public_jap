<section class="quiz-shell">
    <div class="quiz-progress">
        Question <?= e((string) $question['question_order']) ?>
        /
        <?= e((string) $session['total_questions']) ?>
    </div>

    <article class="quiz-card">
        <?php if ($session['direction'] === 'romaji_to_kana'): ?>
            <p class="eyebrow">Quel kana correspond à :</p>
        <?php elseif ($session['direction'] === 'written'): ?>
            <p class="eyebrow">Écris le romaji de :</p>
        <?php else: ?>
            <p class="eyebrow">Quel est ce kana ?</p>
        <?php endif; ?>

        <div class="quiz-symbol">
            <?= e($question['displayed_value']) ?>
        </div>

        <form method="post" action="/quiz/<?= e((string) $session['id']) ?>/answer" class="quiz-options <?= $session['direction'] === 'written' ? 'quiz-written-form' : '' ?>">
            <?= csrf_field() ?>

            <input type="hidden" name="answer_id" value="<?= e((string) $question['id']) ?>">

            <?php if ($session['direction'] === 'written'): ?>
                <input
                    class="quiz-written-input"
                    type="text"
                    name="answer"
                    autocomplete="off"
                    autocapitalize="off"
                    spellcheck="false"
                    autofocus
                    required
                >

                <button class="button button-primary button-full" type="submit">
                    Valider
                </button>
            <?php else: ?>
                <?php foreach ($options as $option): ?>
                    <button class="quiz-option" type="submit" name="answer" value="<?= e((string) $option) ?>">
                        <?= e((string) $option) ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </form>
    </article>
</section>
