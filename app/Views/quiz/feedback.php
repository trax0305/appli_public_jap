<?php
$isCorrect = (int) $answer['is_correct'] === 1;
$isGuest = $session['user_id'] === null;
$settings = json_decode((string) ($session['settings_json'] ?? '{}'), true);
$guestMissionId = is_array($settings) ? (int) ($settings['guest_mission_id'] ?? 0) : 0;
$guestType = is_array($settings) ? (string) ($settings['guest_type'] ?? '') : '';
$quitUrl = '/dashboard';

if ($isGuest && $guestMissionId > 0) {
    $quitUrl = '/guest/missions/' . $guestMissionId;
} elseif ($isGuest && $guestType === 'free_practice') {
    $quitUrl = '/guest/free-practice';
} elseif ($isGuest) {
    $quitUrl = '/guest';
}
?>

<section class="quiz-shell quiz-feedback-shell">
    <div class="quiz-progress">
        Question <?= e((string) $answer['question_order']) ?>
        /
        <?= e((string) $session['total_questions']) ?>
    </div>

    <article class="quiz-card quiz-feedback-card <?= $isCorrect ? 'quiz-feedback-card-success' : 'quiz-feedback-card-error' ?>">
        <div class="quiz-symbol">
            <?= e((string) $answer['displayed_value']) ?>
        </div>

        <?php if (!$isCorrect): ?>
            <div class="quiz-feedback-lines">
                <p><strong>La bonne réponse était : <?= e((string) $answer['expected_answer']) ?></strong></p>
            </div>
        <?php endif; ?>

        <a class="button button-primary button-full" href="/quiz/<?= e((string) $session['id']) ?>">
            Suivant
        </a>

        <a class="quiz-quit-link" href="<?= e($quitUrl) ?>">Quitter</a>
    </article>
</section>
