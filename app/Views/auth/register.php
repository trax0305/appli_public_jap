<section class="auth-card">
    <a class="brand auth-brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <p class="eyebrow">Créer un compte</p>
    <h1>Commence gratuitement.</h1>

    <form method="post" action="/register" class="form">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="username">Pseudo</label>
            <input
                id="username"
                name="username"
                type="text"
                value="<?= e($old['username'] ?? '') ?>"
                required
            >
            <?php if (!empty($errors['username'])): ?>
                <p class="form-error"><?= e($errors['username']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                value="<?= e($old['email'] ?? '') ?>"
                required
            >
            <?php if (!empty($errors['email'])): ?>
                <p class="form-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input
                id="password"
                name="password"
                type="password"
                minlength="12"
                required
            >

            <p class="form-help">
                Minimum 12 caractères, avec une majuscule, une minuscule, un chiffre et un caractère spécial.
            </p>

            <?php if (!empty($errors['password'])): ?>
                <p class="form-error"><?= e($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <button class="button button-primary button-full" type="submit">
            Créer mon compte
        </button>
    </form>

    <p class="center-link">
        Déjà inscrit ?
        <a href="/login">Se connecter</a>
    </p>
</section>