<?php
$user = $dashboard['user'];
$mode = $dashboard['mode'];
$currentPath = $dashboard['current_path'];
$currentMission = $dashboard['current_mission'];
$progress = $dashboard['progress'];
$lastSession = $dashboard['last_session'];

$isGuided = $mode === 'guided';
$isFree = $mode === 'free';
$secondaryLearningTitle = $isGuided ? 'Mode libre' : 'Parcours guidé';
$secondaryLearningText = $isGuided
    ? 'Choisis tes kana et lance un quiz.'
    : 'Reprends tes missions étape par étape.';
$secondaryLearningLabel = $isGuided ? 'Ouvrir' : 'Continuer';
$secondaryLearningHref = $isGuided ? '/free-practice' : '/paths';
?>

<section class="dashboard-desktop-shell">
    <header class="dashboard-connected-header">
        <a class="brand dashboard-connected-brand" href="/dashboard">
            <span class="brand-mark">あア</span>
            <span class="brand-name">App Japonais</span>
        </a>

        <nav class="dashboard-connected-nav" aria-label="Navigation connectée">
            <a href="/paths">Apprendre</a>
            <a href="/free-practice">Quiz</a>
            <a href="/stats">Stats</a>
            <a href="/profile">Profil</a>
        </nav>
    </header>

    <section class="dashboard-desktop-intro">
        <h1>Bonjour <?= e($user['username']) ?></h1>
    </section>

    <section class="dashboard-desktop-stack">
        <?php if ($isGuided): ?>
            <article class="dashboard-desktop-main-card">
                <p class="eyebrow">Parcours guidé</p>
                <h2>Continue ton apprentissage</h2>

                <?php if ($currentMission): ?>
                    <p class="dashboard-desktop-main-subtitle"><?= e($currentMission['title']) ?></p>
                    <p class="dashboard-desktop-main-text"><?= e($currentMission['description'] ?? '') ?></p>

                    <div class="dashboard-desktop-progress">
                        <div class="progress-label">
                            <span><?= e($currentPath['path_title']) ?></span>
                            <strong>
                                <?= e((string) $progress['completed']) ?>
                                /
                                <?= e((string) $progress['total']) ?>
                                missions
                            </strong>
                        </div>

                        <div class="progress-bar">
                            <span style="width: <?= e((string) $progress['percent']) ?>%"></span>
                        </div>
                    </div>

                    <a class="button button-primary" href="/missions/<?= e((string) $currentMission['id']) ?>">
                        Continuer
                    </a>
                <?php else: ?>
                    <p class="dashboard-desktop-main-subtitle">Aucune mission actuelle trouvée.</p>
                    <a class="button button-primary" href="/paths">Choisir une mission</a>
                <?php endif; ?>
            </article>
        <?php elseif ($isFree): ?>
            <article class="dashboard-desktop-main-card">
                <p class="eyebrow">Mode libre</p>
                <h2>Crée ton entraînement</h2>
                <p class="dashboard-desktop-main-subtitle">
                    Choisis les kana, le type de quiz et le nombre de questions.
                </p>
                <p class="dashboard-desktop-main-text">
                    Idéal pour revoir ce que tu veux, quand tu veux.
                </p>
                <a class="button button-primary" href="/free-practice">
                    Lancer un quiz libre
                </a>
            </article>
        <?php else: ?>
            <article class="dashboard-desktop-main-card">
                <p class="eyebrow">Configuration incomplète</p>
                <h2>Configure ton apprentissage</h2>
                <p class="dashboard-desktop-main-text">
                    Choisis ton mode d’apprentissage pour commencer dans de bonnes conditions.
                </p>
                <a class="button button-primary" href="/onboarding">
                    Configurer mon apprentissage
                </a>
            </article>
        <?php endif; ?>

        <section class="dashboard-secondary-grid" aria-label="Actions secondaires">
            <article class="dashboard-secondary-card">
                <h2><?= e($secondaryLearningTitle) ?></h2>
                <p><?= e($secondaryLearningText) ?></p>
                <a class="dashboard-secondary-link" href="<?= e($secondaryLearningHref) ?>">
                    <?= e($secondaryLearningLabel) ?>
                </a>
            </article>

            <article class="dashboard-secondary-card">
                <h2>Révision rapide</h2>
                <p>Revois tes derniers acquis et tes erreurs.</p>
                <a class="dashboard-secondary-link" href="/review">Réviser</a>
            </article>

            <article class="dashboard-secondary-card">
                <h2>Mes stats</h2>
                <p>Suis ta progression et tes erreurs.</p>
                <a class="dashboard-secondary-link" href="/stats">Voir</a>
            </article>
        </section>
    </section>
</section>

<section class="dashboard-page dashboard-mobile-dashboard">
    <section class="dashboard-header">
        <div>
            <p class="eyebrow">Dashboard</p>
            <h1>Bonjour <?= e($user['username']) ?></h1>
        </div>

        <a class="dashboard-profile-link" href="/profile">Profil</a>
    </section>

    <?php if ($mode === 'guided'): ?>
        <section class="dashboard-grid">
            <article class="dashboard-main-card dashboard-current-card">
                <p class="eyebrow">Parcours actuel</p>

                <h2><?= e($currentPath['path_title']) ?></h2>

                <?php if ($currentMission): ?>
                    <p class="dashboard-muted">
                        Mission actuelle
                    </p>

                    <h3><?= e($currentMission['title']) ?></h3>

                    <p class="dashboard-mission-description"><?= e($currentMission['description'] ?? '') ?></p>

                    <div class="progress-block">
                        <div class="progress-label">
                            <span>Progression</span>
                            <strong>
                                <?= e((string) $progress['completed']) ?>
                                /
                                <?= e((string) $progress['total']) ?>
                                missions
                            </strong>
                        </div>

                        <div class="progress-bar">
                            <span style="width: <?= e((string) $progress['percent']) ?>%"></span>
                        </div>
                    </div>

                    <a class="button button-primary button-full" href="/missions/<?= e((string) $currentMission['id']) ?>">
                        Continuer
                    </a>
                <?php else: ?>
                    <p>Aucune mission actuelle trouvée.</p>
                <?php endif; ?>
            </article>

            <aside class="dashboard-side">
                <article class="mini-card dashboard-review-card">
                    <h2>Révision rapide</h2>
                    <p>Revois tes erreurs.</p>
                    <a class="button button-secondary button-full" href="/review">
                        Réviser
                    </a>
                </article>

            </aside>
        </section>
    <?php elseif ($mode === 'free'): ?>
        <section class="dashboard-grid">
            <article class="dashboard-main-card dashboard-current-card">
                <p class="eyebrow">Mode libre</p>
                <h2>Créer un entraînement</h2>
                <p>Choisis tes kana et lance un quiz.</p>

                <a class="button button-primary button-full" href="/free-practice">
                    Lancer un quiz
                </a>
            </article>

            <aside class="dashboard-side">
                <article class="mini-card dashboard-review-card">
                    <h2>Révision rapide</h2>
                    <p>Revois tes erreurs.</p>
                    <a class="button button-secondary button-full" href="/review">
                        Réviser
                    </a>
                </article>

            </aside>
        </section>
    <?php else: ?>
        <section class="dashboard-main-card dashboard-current-card">
            <p class="eyebrow">Configuration incomplète</p>
            <h2>Ton parcours n’est pas encore configuré.</h2>
            <p>Tu peux reprendre l’onboarding pour choisir ton mode d’apprentissage.</p>

            <a class="button button-primary" href="/onboarding">
                Configurer mon apprentissage
            </a>
        </section>
    <?php endif; ?>
</section>
