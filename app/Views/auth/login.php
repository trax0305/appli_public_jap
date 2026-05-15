<section class="auth-card">
    <a class="brand auth-brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <p class="eyebrow">Connexion</p>
    <h1>Content de te revoir.</h1>

    <form method="post" action="/login" class="form">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="email">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input
                id="password"
                name="password"
                type="password"
                required
            >
        </div>

        <button class="button button-primary button-full" type="submit">
            Me connecter
        </button>
    </form>

    <p class="center-link">
        Pas encore de compte ?
        <a href="/register">Créer un compte</a>
    </p>
</section>