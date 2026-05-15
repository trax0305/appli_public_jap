<?php
$user = $dashboard['user'];
$mode = $dashboard['mode'];
$currentPath = $dashboard['current_path'];
$currentMission = $dashboard['current_mission'];
$progress = $dashboard['progress'];
$lastSession = $dashboard['last_session'];
?>

<section class="dashboard-header">
    <div>
        <p class="eyebrow">Dashboard</p>
        <h1>Bonjour <?= e($user['username']) ?></h1>
    </div>

    <form method="post" action="/logout">
        <?= csrf_field() ?>
        <button class="button button-secondary" type="submit">
            Déconnexion
        </button>
    </form>
</section>

<?php if ($mode === 'guided'): ?>
    <section class="dashboard-grid">
        <article class="dashboard-main-card">
            <p class="eyebrow">Parcours actuel</p>

            <h2><?= e($currentPath['path_title']) ?></h2>

            <?php if ($currentMission): ?>
                <p class="dashboard-muted">
                    Mission actuelle
                </p>

                <h3><?= e($currentMission['title']) ?></h3>

                <p><?= e($currentMission['description'] ?? '') ?></p>

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
            <article class="mini-card">
                <h2>Reprise conseillée</h2>
                <p>
                    Bientôt, l’app te proposera de revoir tes dernières missions
                    et tes erreurs fréquentes.
                </p>
                <a class="button button-secondary button-full" href="/review">
                    Réviser
                </a>
            </article>

            <article class="mini-card">
                <h2>Actions rapides</h2>

                <div class="quick-actions">
                    <a href="/free-practice">Mode libre</a>
                    <a href="/stats">Mes stats</a>
                    <a href="/paths">Parcours</a>
                </div>
            </article>
        </aside>
    </section>
<?php elseif ($mode === 'free'): ?>
    <section class="dashboard-grid">
        <article class="dashboard-main-card">
            <p class="eyebrow">Mode libre</p>
            <h2>Tu es en mode libre.</h2>

            <?php if ($lastSession): ?>
                <p>
                    Dernier entraînement :
                    <?= e($lastSession['kana_set']) ?> —
                    <?= e($lastSession['direction']) ?>
                </p>
            <?php else: ?>
                <p>
                    Lance ton premier entraînement libre quand tu veux.
                </p>
            <?php endif; ?>

            <a class="button button-primary button-full" href="/free-practice">
                Créer un quiz
            </a>
        </article>

        <aside class="dashboard-side">
            <article class="mini-card">
                <h2>Actions rapides</h2>

                <div class="quick-actions">
                    <a href="/free-practice">Mode libre</a>
                    <a href="/stats">Mes stats</a>
                    <a href="/paths">Parcours</a>
                </div>
            </article>
        </aside>
    </section>
<?php else: ?>
    <section class="dashboard-main-card">
        <p class="eyebrow">Configuration incomplète</p>
        <h2>Ton parcours n’est pas encore configuré.</h2>
        <p>Tu peux reprendre l’onboarding pour choisir ton mode d’apprentissage.</p>

        <a class="button button-primary" href="/onboarding">
            Configurer mon apprentissage
        </a>
    </section>
<?php endif; ?>