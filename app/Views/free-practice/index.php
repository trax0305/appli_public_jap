<?php
$alphabetOptions = $page['alphabet_options'];
$quickFilters = $page['quick_filters'];
$trainingTypes = $page['training_types'];
$questionCounts = $page['question_counts'];

$old = $old ?? [
    'alphabet' => 'hiragana',
    'quick_filters' => ['vowels'],
    'direction' => 'kana_to_romaji',
    'question_count' => '20',
    'custom_count' => '20',
    'include_wrong' => '0',
    'options_scope' => 'selected',
];
?>

<section class="dashboard-header">
    <div>
        <p class="eyebrow">Mode libre</p>
        <h1>Créer mon entraînement</h1>
        <p>Personnalise un quiz rapide selon les kana que tu veux travailler.</p>
    </div>

    <a class="button button-secondary" href="/dashboard">Dashboard</a>
</section>

<section class="card free-practice-card">
    <form method="post" action="/free-practice/start" class="free-practice-form">
        <?= csrf_field() ?>

        <fieldset class="free-fieldset">
            <legend>Alphabet</legend>
            <div class="choice-stack">
                <?php foreach ($alphabetOptions as $value => $label): ?>
                    <label class="radio-card">
                        <input type="radio" name="alphabet" value="<?= e($value) ?>" <?= ($old['alphabet'] === $value) ? 'checked' : '' ?>>
                        <span><strong><?= e($label) ?></strong></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="free-fieldset">
            <legend>Sélection rapide</legend>
            <div class="free-checkbox-grid">
                <?php foreach ($quickFilters as $value => $label): ?>
                    <label class="check-card">
                        <input
                            type="checkbox"
                            name="quick_filters[]"
                            value="<?= e($value) ?>"
                            <?= in_array($value, $old['quick_filters'], true) ? 'checked' : '' ?>
                        >
                        <span><?= e($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="free-fieldset">
            <legend>Type d’entraînement</legend>
            <div class="choice-stack">
                <?php foreach ($trainingTypes as $value => $label): ?>
                    <label class="radio-card">
                        <input type="radio" name="direction" value="<?= e($value) ?>" <?= ($old['direction'] === $value) ? 'checked' : '' ?>>
                        <span><strong><?= e($label) ?></strong></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="free-fieldset">
            <legend>Nombre de questions</legend>
            <div class="choice-stack">
                <?php foreach ($questionCounts as $count): ?>
                    <label class="radio-card">
                        <input type="radio" name="question_count" value="<?= e((string) $count) ?>" <?= ($old['question_count'] === (string) $count) ? 'checked' : '' ?>>
                        <span><strong><?= e((string) $count) ?> questions</strong></span>
                    </label>
                <?php endforeach; ?>

                <label class="radio-card">
                    <input type="radio" name="question_count" value="custom" <?= ($old['question_count'] === 'custom') ? 'checked' : '' ?>>
                    <span>
                        <strong>Personnalisé</strong>
                        <small>Entre 5 et 100 (sinon 20 par défaut).</small>
                    </span>
                </label>
            </div>

            <div class="form-group">
                <label for="custom_count">Nombre personnalisé</label>
                <input id="custom_count" type="number" min="5" max="100" name="custom_count" value="<?= e((string) $old['custom_count']) ?>">
            </div>
        </fieldset>

        <details class="free-advanced">
            <summary>Options avancées</summary>

            <div class="free-advanced-content">
                <label class="check-card">
                    <input type="checkbox" name="include_wrong" value="1" <?= ($old['include_wrong'] === '1') ? 'checked' : '' ?>>
                    <span>Inclure mes erreurs fréquentes</span>
                </label>

                <div class="choice-stack">
                    <label class="radio-card">
                        <input type="radio" name="options_scope" value="selected" <?= ($old['options_scope'] === 'selected') ? 'checked' : '' ?>>
                        <span>
                            <strong>Réponses proposées : sélection</strong>
                        </span>
                    </label>

                    <label class="radio-card">
                        <input type="radio" name="options_scope" value="learned" <?= ($old['options_scope'] === 'learned') ? 'checked' : '' ?>>
                        <span>
                            <strong>Réponses proposées : tous les kana appris</strong>
                        </span>
                    </label>
                </div>

                <p class="form-help">Anti-répétition : à venir.</p>
            </div>
        </details>

        <button class="button button-primary button-full" type="submit">
            Lancer le quiz libre
        </button>
    </form>
</section>
