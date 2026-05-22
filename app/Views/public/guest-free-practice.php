<header class="public-header guest-header">
    <a class="brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <a class="header-link" href="/login">Se connecter</a>
</header>

<section class="guest-page guest-free-page public-main">
    <div class="page-heading guest-heading">
        <h1>Tester le mode libre</h1>
        <p>Choisis quelques kana et lance un quiz sans compte.</p>
    </div>

    <?php if ($limitReached): ?>
        <article class="choice-card choice-card-primary guest-success-card">
            <div>
                <p class="choice-label">Limite atteinte</p>
                <h2>Tu as utilisé tes 3 quiz gratuits en mode libre.</h2>
                <p>Crée un compte gratuit pour continuer, ou teste une mission guidée sans compte.</p>
            </div>

            <div class="guest-success-actions">
                <a class="button button-primary" href="/register">
                    Créer un compte gratuit
                </a>
                <a class="button button-secondary" href="/guest/guided">
                    Tester le parcours guidé
                </a>
            </div>
        </article>
    <?php else: ?>
        <article class="card guest-free-card">
            <p class="guest-limit-text">
                Quiz invités restants : <?= e((string) $remainingCount) ?> / <?= e((string) $limit) ?>
            </p>

            <form method="post" action="/guest/free-practice/start" class="guest-free-form">
                <?= csrf_field() ?>

                <label>
                    Alphabet
                    <select name="kana_set">
                        <option value="hiragana">Hiragana</option>
                        <option value="katakana">Katakana</option>
                    </select>
                </label>

                <label>
                    Groupe
                    <select name="group">
                        <?php foreach ($groups as $value => $label): ?>
                            <option value="<?= e((string) $value) ?>"><?= e((string) $label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Type de quiz
                    <select name="direction">
                        <option value="kana_to_romaji">Kana vers romaji</option>
                        <option value="romaji_to_kana">Romaji vers kana</option>
                        <option value="written">Réponse écrite</option>
                    </select>
                </label>

                <label>
                    Nombre de questions
                    <select name="question_count">
                        <option value="5">5</option>
                        <option value="10">10</option>
                    </select>
                </label>

                <button class="button button-primary" type="submit">
                    Lancer le quiz
                </button>
            </form>
        </article>

        <p class="center-link guest-account-link">
            <a href="/guest/guided">Tester le parcours guidé</a>
            <span class="guest-link-separator">·</span>
            <a href="/register">Créer un compte gratuit</a>
        </p>
    <?php endif; ?>
</section>
