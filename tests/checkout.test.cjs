const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../js/paypal.js'), 'utf8');
function setup({sdk = true, valid = true, selected = true, total = '115.00', renderFails = false} = {}) {
    const events = {};
    const classes = new Set();
    const error = {hidden: true, textContent: ''};
    const status = {textContent: 'Loading'};
    const submit = {disabled: false, clicks: 0, click() { this.clicks++; }};
    const radio = {value: selected ? 'ctcl_paypal' : 'ctcl_cash', addEventListener(name, handler) { events.change = handler; }};
    const shipping = [{checked: true}, {checked: false}];
    const form = {reportValidity: () => valid, addEventListener(name, handler) { events[name] = handler; }};
    const panel = {querySelector: selector => selector === '#ctcl-paypal-error' ? error : status, classList: {add: value => classes.add(value)}};
    const container = {closest: selector => selector === 'form' ? form : panel};
    let callbacks;
    const document = {
        readyState: 'complete',
        querySelector(selector) {
            if (selector === '#paypal-button-container') return container;
            if (selector === '.ctcl-checkout-button') return submit;
            if (selector.includes('payment_option')) return radio;
            if (selector === '#ctcl-subtotal-hidden-input') return {value: total};
        },
        querySelectorAll(selector) { return selector.includes('payment_option') ? [radio] : shipping; }
    };
    const config = {loadError: 'Load error', paymentError: 'Payment error', cancelled: 'Cancelled', invalidForm: 'Invalid form', invalidTotal: 'Invalid total', processing: 'Processing', paymentSuccess: 'Paid', paidError: 'Paid, order failed'};
    const window = {ctclPaypalObject: config};
    if (sdk) window.paypal = {Buttons(options) { callbacks = options; return {render: () => renderFails ? Promise.reject(new Error('SDK failure')) : Promise.resolve()}; }};
    vm.runInNewContext(source, {document, window, Promise, Number});
    return {events, error, status, submit, radio, shipping, classes, callbacks, container};
}
async function run() {
    const state = setup();
    assert.equal(state.submit.disabled, true, 'preselected PayPal blocks unpaid submission');
    let prevented = false;
    state.events.submit({preventDefault() { prevented = true; }});
    assert.equal(prevented, true);
    state.radio.value = 'ctcl_cash'; state.events.change();
    assert.equal(state.submit.disabled, false, 'switching to cash restores checkout');
    const missing = setup({sdk: false});
    assert.equal(missing.error.hidden, false); assert.equal(missing.error.textContent, 'Load error');
    const rejected = setup({valid: false});
    assert.equal(rejected.callbacks.onClick({}, {resolve: () => true, reject: () => false}), false);
    assert.equal(rejected.error.textContent, 'Invalid form');
    const badTotal = setup({total: 'NaN'});
    assert.equal(badTotal.callbacks.onClick({}, {resolve: () => true, reject: () => false}), false);
    assert.equal(badTotal.error.textContent, 'Invalid total');
    const failed = setup({renderFails: true});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(failed.error.textContent, 'Load error');
    const cancelled = setup();
    await cancelled.callbacks.createOrder({}, {order: {create: data => { assert.equal(data.purchase_units[0].amount.value, '115.00'); assert.equal(data.payment_source.paypal.experience_context.shipping_preference, 'NO_SHIPPING', 'PayPal must not collect a duplicate shipping address'); return Promise.resolve('test-order'); }}});
    cancelled.callbacks.onCancel();
    assert.equal(cancelled.status.textContent, 'Cancelled'); assert.equal(cancelled.submit.disabled, true);
    const success = setup();
    await success.callbacks.onApprove({}, {order: {capture: () => Promise.resolve({status: 'COMPLETED'})}});
    assert.equal(success.submit.clicks, 1); assert.equal(success.container.hidden, true);
    assert.equal(success.shipping[0].disabled, false); assert.equal(success.shipping[1].disabled, true);
    const incomplete = setup();
    await incomplete.callbacks.onApprove({}, {order: {capture: () => Promise.resolve({status: 'PENDING'})}});
    assert.equal(incomplete.submit.clicks, 0); assert.equal(incomplete.error.textContent, 'Payment error');
    const failedCapture = setup();
    await failedCapture.callbacks.onApprove({}, {order: {capture: () => Promise.reject(new Error('Capture failed'))}});
    assert.equal(failedCapture.submit.clicks, 0); assert.equal(failedCapture.error.textContent, 'Payment error');
    console.log('Passed: payment selection, submission guard, SDK failures, form validation, invalid totals, no duplicate shipping address, cancellation, completed capture, pending capture, and capture failures.');
}
run().catch(error => { console.error(error); process.exitCode = 1; });
