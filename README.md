# CT Commerce Lite PayPal

PayPal payment add-on for CT Commerce Lite. Version 1.2.0 updates the settings and checkout presentation while keeping the original option names and CT Commerce Lite hooks compatible.

## What's included

- Navy PayPal settings header, saved configuration status, account setup guidance, accessible switches, and store currency information.
- Button color preview and unsaved change feedback. The preview is illustrative; the real SDK determines funding availability.
- Responsive payment card, working loading/error/cancellation states, and form validation before PayPal opens.
- Initial payment selection handling and automatic CT Commerce Lite order submission after a completed capture.
- Sanitized options, escaped output, load order compatibility, translated interface strings, and configuration preservation on deactivation.

## Local validation

```sh
php -l ctcl-paypal.php
node --check js/admin.js
node --check js/paypal.js
node tests/checkout.test.cjs
```

The checkout tests use an SDK stub and never charge an account. They cover selection, unpaid submission, SDK load/render errors, invalid fields and totals, cancellation, completed/pending captures, and capture failure. Browser checks also exercise WordPress settings saves and real PayPal button rendering at desktop and mobile sizes.

## Payment verification limitation

The original integration creates and captures PayPal orders through the browser SDK and inherits `ctclBillings::processPayment()`, which treats a submitted order as successful. This update retains that architecture. A direct submission can bypass browser controls; the browser-supplied total and capture cannot be trusted as proof of payment on the server.

Before production use, implement server-side order creation/capture or verification using PayPal server credentials, authoritative CT Commerce Lite totals, currency/merchant checks, and replay protection linking each capture to one store order. The interface status means that the local option is enabled; it does not verify credentials or payment readiness. Live and sandbox payment transactions have not been executed as part of this interface update.
