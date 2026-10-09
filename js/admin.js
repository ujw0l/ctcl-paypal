(() => {
    const init = () => {
        const panel = document.querySelector('.ctcl-paypal-settings');
        if (!panel) return;
        const form = panel.closest('form');
        if (!form) return;
        form.classList.add('ctcl-pp-form');
        const save = form.querySelector('[type="submit"]');
        if (save) save.value = panel.dataset.saveLabel;
        const color = panel.querySelector('#ctcl-paypal-color-option');
        const card = panel.querySelector('#ctcl-paypal-enable-card');
        const status = panel.querySelector('.ctcl-pp-save-status');
        const snapshot = () => JSON.stringify(Array.from(new FormData(form).entries()));
        const saved = snapshot();
        const update = () => {
            panel.querySelector('.ctcl-pp-preview-button').dataset.color = color.value;
            panel.querySelector('.ctcl-pp-card-note').hidden = !card.checked;
            const dirty = snapshot() !== saved;
            status.textContent = dirty ? status.dataset.unsaved : status.dataset.saved;
            status.classList.toggle('is-dirty', dirty);
        };
        form.addEventListener('input', update);
        form.addEventListener('change', update);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
