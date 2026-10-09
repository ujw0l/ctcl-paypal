# CT Commerce Lite PayPal

PayPal payment add-on for CT Commerce Lite. Version 1.2.0 updates the settings and checkout presentation while keeping the original option names and CT Commerce Lite hooks compatible.

## What's included

- Navy PayPal settings header, saved configuration status, account setup guidance, accessible switches, and store currency information.
- Button color preview and unsaved change feedback. The preview is illustrative; the real SDK determines funding availability.
- Responsive payment card, working loading/error/cancellation states, and form validation before PayPal opens.
- PayPal orders request `NO_SHIPPING` in `payment_source.paypal.experience_context`, so CT Commerce Lite remains responsible for delivery address collection. PayPal may still require billing details for card payments. See [PayPal shipping preferences](https://developer.paypal.com/serversdk/php/models/enumerations/experience-context-shipping-preference).
- Initial payment selection handling and automatic CT Commerce Lite order submission after a completed capture.
- Sanitized options, escaped output, load order compatibility, translated interface strings, and configuration preservation on deactivation.

## Local validation

```sh
php -l ctcl-paypal.php
node --check js/admin.js
node --check js/paypal.js
node tests/checkout.test.cjs
```

The checkout tests use an SDK stub and never charge an account. They cover selection, unpaid submission, SDK load/render errors, invalid fields and totals, cancellation, completed/pending captures, and capture failure. Local browser checks on WordPress 7.1.3 also exercise WordPress settings saves and real PayPal button rendering at desktop and mobile sizes.

## Payment verification limitation

The original integration creates and captures PayPal orders through the browser SDK and inherits `ctclBillings::processPayment()`, which treats a submitted order as successful. This update retains that architecture. A direct submission can bypass browser controls; the browser-supplied total and capture cannot be trusted as proof of payment on the server.

Before production use, implement server-side order creation/capture or verification using PayPal server credentials, authoritative CT Commerce Lite totals, currency/merchant checks, and replay protection linking each capture to one store order. The interface status means that the local option is enabled; it does not verify credentials or payment readiness. Live and sandbox payment transactions have not been executed as part of this interface update.

## WordPress.org directory assets

The `assets/` directory contains the current screenshots and plugin icons. Screenshot numbering matches the `Screenshots` section of `readme.txt`. For a WordPress.org SVN release, copy these files to the top-level SVN `assets/` directory alongside `trunk/` and `tags/`, rather than to `trunk/assets/`. The GitHub push does not publish the WordPress.org listing.

- `screenshot-1.png`: PayPal settings and appearance preview.
- `screenshot-2.png`: desktop PayPal checkout.
- `screenshot-3.png`: mobile PayPal checkout.
- `icon-128x128.png` and `icon-256x256.png`: standard and high-resolution directory icons.

See the [WordPress.org asset requirements](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).
