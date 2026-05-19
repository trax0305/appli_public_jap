<?php
$session = $resultContext['session'];
$wrongAnswers = $resultContext['wrong_answers'];
$objective = $resultContext['objective_context'];
$mission = $resultContext['mission_context'];
$path = $resultContext['path_context'];
$newBadges = $resultContext['unlocked_badges'];

$score = (int) ($session['score_percent'] ?? 0);
$correctAnswers = (int) ($session['correct_answers'] ?? 0);
$totalQuestions = (int) ($session['total_questions'] ?? 0);
$mode = (string) ($session['mode'] ?? 'mission');
$sourceType = (string) ($session['source_type'] ?? 'objective');
$isGuest = !empty($resultContext['is_guest']) || $session['user_id'] === null;
?>


<section class="result-card">
    <p class="eyebrow">Résultat</p>
    <h1><?= e($resultContext['score_label']) ?></h1>

    <p class="result-score <?= $resultContext['is_perfect'] ? 'result-score-perfect' : '' ?>">
        <?= e((string) $correctAnswers) ?> / <?= e((string) $totalQuestions) ?>
        —
        <?= e((string) $score) ?>%
    </p>
</section>

<?php if ($isGuest): ?>
    <section class="card result-context-card">
        <p class="eyebrow">Mode invité</p>
        <h2>Bien joué !</h2>
        <p>Crée un compte gratuit pour sauvegarder ta progression, tes stats et tes badges.</p>

        <div class="result-actions">
            <a class="button button-primary" href="/register">Créer mon compte</a>
            <a class="button button-secondary" href="/guest">Continuer en mode invité</a>
        </div>
    </section>
<?php endif; ?>

<?php if (!$isGuest): ?>
<?php if ($objective !== null): ?>
    <section class="card result-context-card">
        <p class="eyebrow">Objectif</p>
        <h2><?= e($objective['objective_title']) ?></h2>
        <p>
            Progression :
            <?= e((string) $objective['success_count']) ?>
            /
            <?= e((string) $objective['required_success_count']) ?>
        </p>

        <?php if ($objective['objective_completed']): ?>
            <p><strong>Objectif validé</strong></p>
        <?php else: ?>
            <p>
                Encore
                <?= e((string) max(0, $objective['required_success_count'] - $objective['success_count'])) ?>
                réussite(s) à obtenir.
            </p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($objective !== null || $mission !== null || $path !== null): ?>
    <section class="card result-context-card">
        <p class="eyebrow">Suite</p>

        <?php if ($objective !== null && !empty($objective['next_objective_title'])): ?>
            <p><strong>Prochaine étape :</strong> <?= e((string) $objective['next_objective_title']) ?></p>
        <?php endif; ?>

        <?php if ($mission !== null && $mission['mission_completed']): ?>
            <p><strong>Mission réussie :</strong> <?= e((string) $mission['mission_title']) ?></p>
        <?php endif; ?>

        <?php if ($mission !== null && !empty($mission['next_mission_title'])): ?>
            <p><strong>Mission suivante :</strong> <?= e((string) $mission['next_mission_title']) ?></p>
        <?php endif; ?>

        <?php if ($path !== null && $path['path_completed']): ?>
            <p><strong>Parcours terminé :</strong> <?= e((string) $path['path_title']) ?></p>
        <?php endif; ?>

        <?php if ($path !== null && !empty($path['next_path_title'])): ?>
            <p><strong>Nouveau parcours disponible :</strong> <?= e((string) $path['next_path_title']) ?></p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if (!empty($newBadges)): ?>
    <section class="result-badges">
        <p class="eyebrow">Badge débloqué</p>

        <div class="result-badge-list">
            <?php foreach ($newBadges as $badge): ?>
                <article class="result-badge-card">
                    <div class="result-badge-title-row">
                        <strong><?= e((string) ($badge['title'] ?? 'Badge')) ?></strong>
                        <?php if (!empty($badge['icon'])): ?>
                            <span class="result-badge-icon"><?= e((string) $badge['icon']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p><?= e((string) ($badge['description'] ?? '')) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="result-actions">
    <?php if ($sourceType === 'objective' && $objective !== null): ?>
        <a class="button button-primary" href="/missions/<?= e((string) $objective['mission_id']) ?>">
            Continuer la mission
        </a>
    <?php elseif ($mode === 'free'): ?>
        <a class="button button-primary" href="/free-practice">
            Retour mode libre
        </a>
    <?php elseif ($mode === 'review'): ?>
        <a class="button button-primary" href="/review">
            Nouvelle révision
        </a>
    <?php else: ?>
        <a class="button button-primary" href="/dashboard">
            Continuer
        </a>
    <?php endif; ?>

    <?php if ($sourceType === 'objective'): ?>
        <form method="post" action="/quiz/start">
            <?= csrf_field() ?>
            <input type="hidden" name="objective_id" value="<?= e((string) $session['source_id']) ?>">
            <button class="button button-secondary" type="submit">Recommencer ce quiz</button>
        </form>
    <?php elseif ($mode === 'free'): ?>
        <a class="button button-secondary" href="/free-practice">Refaire un entraînement libre</a>
    <?php elseif ($mode === 'review'): ?>
        <a class="button button-secondary" href="/review">Retour révision</a>
    <?php endif; ?>

    <?php if (!empty($wrongAnswers)): ?>
        <form method="post" action="/quiz/<?= e((string) $session['id']) ?>/retry-errors">
            <?= csrf_field() ?>
            <button class="button button-secondary" type="submit">Refaire mes erreurs</button>
        </form>
    <?php endif; ?>

    <a class="button button-secondary" href="/dashboard">Dashboard</a>
</section>
<?php endif; ?>

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
                        <?= e((string) $answer['displayed_value']) ?>
                    </div>

                    <div class="error-details">
                        <p><strong>Ta réponse :</strong> <?= e((string) $answer['user_answer']) ?></p>
                        <p><strong>Bonne réponse :</strong> <?= e((string) $answer['expected_answer']) ?></p>
                        <p class="error-kana-line">
                            Hiragana : <?= e((string) $answer['hira']) ?>
                            — Katakana : <?= e((string) $answer['kata']) ?>
                            — Romaji : <?= e((string) $answer['romaji']) ?>
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
            <span>Tu peux passer à la suite ou consolider avec un autre quiz.</span>
        </div>
    </section>
<?php endif; ?>
