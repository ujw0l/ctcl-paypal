(() => {
    'use strict';
    const init = () => {
        const container = document.querySelector('#paypal-button-container');
        if (!container) return;
        const panel = container.closest('.ctcl-pp-checkout');
        const form = container.closest('form');
        const submit = document.querySelector('.ctcl-checkout-button');
        const error = panel.querySelector('#ctcl-paypal-error');
        const status = panel.querySelector('.ctcl-pp-checkout-status');
        const config = window.ctclPaypalObject || {};
        let paid = false;
        let busy = false;
        const selected = () => document.querySelector('input[name="payment_option"]:checked')?.value === 'ctcl_paypal';
        const sync = () => { if (submit) submit.disabled = busy || (selected() && !paid); };
        const message = text => { error.textContent = text; error.hidden = !text; };
        const reset = () => { busy = false; status.textContent = ''; sync(); };
        document.querySelectorAll('input[name="payment_option"]').forEach(input => input.addEventListener('change', sync));
        // Handle PayPal being the only method or already selected on page load.
        sync();
        if (form) form.addEventListener('submit', event => {
            if (busy || (selected() && !paid)) event.preventDefault();
        });
        if (!window.paypal || typeof window.paypal.Buttons !== 'function') {
            status.textContent = '';
            message(config.loadError);
            return;
        }
        const valid = () => {
            if (!form || !submit || !form.reportValidity()) { message(config.invalidForm); return false; }
            const amount = document.querySelector('#ctcl-subtotal-hidden-input')?.value;
            if (!amount || !Number.isFinite(Number(amount)) || Number(amount) <= 0) { message(config.invalidTotal); return false; }
            message('');
            return true;
        };
        try {
            const buttons = window.paypal.Buttons({
                onClick(data, actions) {
                    return !paid && selected() && valid() ? actions.resolve() : actions.reject();
                },
                createOrder(data, actions) {
                    if (!valid() || paid) return Promise.reject(new Error('Invalid checkout'));
                    busy = true;
                    status.textContent = config.processing;
                    sync();
                    return actions.order.create({
                        purchase_units: [{ amount: { value: document.querySelector('#ctcl-subtotal-hidden-input').value } }]
                    });
                },
                onApprove(data, actions) {
                    return actions.order.capture().then(details => {
                        if (details.status !== 'COMPLETED') throw new Error('Payment capture incomplete');
                        paid = true;
                        busy = false;
                        panel.classList.add('is-paid');
                        container.hidden = true;
                        status.textContent = config.paymentSuccess;
                        message('');
                        // Preserve the existing integration's automatic order submission.
                        document.querySelectorAll('input[name="shipping_option"]').forEach(input => { input.disabled = !input.checked; });
                        sync();
                        if (form && form.reportValidity()) submit.click();
                        else message(config.paidError);
                    }).catch(() => {
                        reset();
                        message(paid ? config.paidError : config.paymentError);
                    });
                },
                onCancel() { reset(); status.textContent = config.cancelled; },
                onError() { reset(); message(paid ? config.paidError : config.paymentError); },
                style: {
                    layout: 'vertical', color: ['gold', 'blue', 'silver', 'white', 'black'].includes(config.buttonColor) ? config.buttonColor : 'gold',
                    shape: 'rect', label: 'pay', tagline: false, height: 46
                }
            });
            Promise.resolve(buttons.render('#paypal-button-container')).then(() => {
                status.textContent = '';
            }).catch(() => { reset(); message(config.loadError); });
        } catch (_) { reset(); message(config.loadError); }
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
