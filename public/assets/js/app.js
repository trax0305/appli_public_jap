document.addEventListener('DOMContentLoaded', () => {
    const guidedOptions = document.querySelector('#guidedOptions');
    const learningModeInputs = document.querySelectorAll('input[name="learning_mode"]');

    if (guidedOptions && learningModeInputs.length > 0) {
        const refreshGuidedOptions = () => {
            const selected = document.querySelector('input[name="learning_mode"]:checked');

            guidedOptions.hidden = selected?.value !== 'guided';
        };

        learningModeInputs.forEach((input) => {
            input.addEventListener('change', refreshGuidedOptions);
        });

        refreshGuidedOptions();
    }

});
