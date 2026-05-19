<?php
$user = $profile['user'];
$currentPath = $profile['current_path'];
$badges = $profile['badges'];
?>

<section class="dashboard-header">
    <div>
        <p class="eyebrow">Profil</p>
        <h1>Mon profil</h1>
        <p>Gère tes préférences et retrouve ta progression.</p>
    </div>

    <a class="button button-secondary" href="/dashboard">Dashboard</a>
</section>

<section class="profile-grid">
    <article class="card">
        <p class="eyebrow">Informations</p>
        <h2><?= e($user['username']) ?></h2>
        <p>Email : <?= e($profile['masked_email']) ?></p>
        <p>Compte créé le : <?= e((string) ($user['created_at'] ?? '—')) ?></p>
    </article>

    <article class="card">
        <p class="eyebrow">Préférences</p>
        <h2>Personnalisation</h2>

        <form method="post" action="/profile" class="form">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username">Pseudo</label>
                <input id="username" name="username" type="text" minlength="3" maxlength="80" value="<?= e($user['username']) ?>" required>
            </div>

            <div class="form-group">
                <label for="learning_mode">Mode d’apprentissage</label>
                <select id="learning_mode" name="learning_mode">
                    <option value="guided" <?= $user['learning_mode'] === 'guided' ? 'selected' : '' ?>>Guidé</option>
                    <option value="free" <?= $user['learning_mode'] === 'free' ? 'selected' : '' ?>>Libre</option>
                </select>
            </div>

            <div class="form-group">
                <label for="theme_preference">Thème</label>
                <select id="theme_preference" name="theme_preference">
                    <option value="system" <?= $user['theme_preference'] === 'system' ? 'selected' : '' ?>>Système</option>
                    <option value="light" <?= $user['theme_preference'] === 'light' ? 'selected' : '' ?>>Clair</option>
                    <option value="dark" <?= $user['theme_preference'] === 'dark' ? 'selected' : '' ?>>Sombre</option>
                </select>
            </div>

            <button class="button button-primary button-full" type="submit">
                Enregistrer les préférences
            </button>
        </form>
    </article>

    <article class="card">
        <p class="eyebrow">Parcours actuel</p>
        <h2>Progression guidée</h2>

        <?php if ($currentPath): ?>
            <p><strong>Parcours :</strong> <?= e((string) $currentPath['path_title']) ?></p>
            <p><strong>Mission actuelle :</strong> <?= e((string) ($currentPath['mission_title'] ?? '—')) ?></p>
            <p><strong>Statut :</strong> <?= e((string) $currentPath['path_status']) ?></p>
            <div class="actions">
                <a class="button button-secondary" href="/paths">Voir les parcours</a>
            </div>
        <?php else: ?>
            <p>Aucun parcours en cours pour le moment.</p>
            <div class="actions">
                <a class="button button-secondary" href="/paths">Choisir un parcours</a>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="card profile-badges-card">
    <p class="eyebrow">Badges obtenus</p>
    <h2>Mes badges</h2>

    <?php if ($badges === []): ?>
        <p>Tes badges apparaîtront ici quand tu les débloqueras.</p>
    <?php else: ?>
        <div class="stats-badge-list">
            <?php foreach ($badges as $badge): ?>
                <article class="stats-badge-item">
                    <div class="result-badge-title-row">
                        <strong><?= e($badge['title']) ?></strong>
                        <?php if (!empty($badge['icon'])): ?>
                            <span class="result-badge-icon"><?= e((string) $badge['icon']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p><?= e((string) ($badge['description'] ?? '')) ?></p>
                    <small>Obtenu le <?= e((string) $badge['unlocked_at']) ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card profile-account-card">
    <p class="eyebrow">Compte</p>
    <h2>Actions du compte</h2>

    <div class="profile-account-actions">
        <form method="post" action="/logout">
            <?= csrf_field() ?>
            <button class="button button-secondary button-full" type="submit">Déconnexion</button>
        </form>

        <button class="button button-disabled button-full" type="button" disabled>
            Changer le mot de passe (à venir)
        </button>

        <button class="button button-disabled button-full" type="button" disabled>
            Supprimer mon compte (à venir)
        </button>
    </div>
</section>
