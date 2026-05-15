<section class="auth-card onboarding-card">
    <a class="brand auth-brand" href="/">
        <span class="brand-mark">あア</span>
        <span class="brand-name">App Japonais</span>
    </a>

    <p class="eyebrow">Premiers réglages</p>
    <h1>Comment veux-tu apprendre ?</h1>

    <form method="post" action="/onboarding" class="form">
        <?= csrf_field() ?>

        <div class="choice-stack">
            <label class="radio-card">
                <input type="radio" name="learning_mode" value="guided" checked>
                <span>
                    <strong>Me guider étape par étape</strong>
                    <small>L’app te propose les missions dans le bon ordre.</small>
                </span>
            </label>

            <label class="radio-card">
                <input type="radio" name="learning_mode" value="free">
                <span>
                    <strong>M’entraîner librement</strong>
                    <small>Tu choisis toi-même les kana, les groupes et les quiz.</small>
                </span>
            </label>
        </div>

        <div class="guided-options" id="guidedOptions">
            <p class="form-section-title">Par quoi veux-tu commencer ?</p>

            <div class="choice-stack">
                <label class="radio-card">
                    <input type="radio" name="path_code" value="hiragana_base" checked>
                    <span>
                        <strong>Hiragana</strong>
                        <small>Recommandé pour débuter.</small>
                    </span>
                </label>

                <label class="radio-card">
                    <input type="radio" name="path_code" value="katakana_base">
                    <span>
                        <strong>Katakana</strong>
                        <small>Utile pour les mots étrangers et noms propres.</small>
                    </span>
                </label>
            </div>
        </div>

        <button class="button button-primary button-full" type="submit">
            Continuer
        </button>
    </form>
</section>