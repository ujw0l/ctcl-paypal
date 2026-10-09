<?php
/**
 * Plugin Name: CT Commerce Lite PayPal
 * Plugin URI: https://github.com/ujw0l/ctcl-paypal
 * Description: PayPal checkout and payment settings for CT Commerce Lite.
 * Version: 1.2.0
 * Author: Ujwol Bastakoti
 * Author URI: https://ujw0l.github.io/
 * Text Domain: ctcl-paypal
 * License: GPLv2 or later
 */
if (!defined('ABSPATH')) { exit; }
define('CTCL_PAYPAL_VERSION', '1.2.0');

// Initialize after all plugins load and WordPress translations are available.
add_action('init', function () {
    if (!class_exists('ctclBillings')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html__('CT Commerce Lite PayPal requires CT Commerce Lite to be installed and activated.', 'ctcl-paypal') . '</p></div>';
        });
        return;
    }

    class ctclPaypal extends ctclBillings {
        public $paymentId = 'ctcl_paypal';
        public $paymentName;
        public $settingFields = 'ctcl_paypal_setting';
        public $paypalFilePath;

        public function __construct() {
            $this->paypalFilePath = plugin_dir_url(__FILE__);
            $this->paymentName = __('PayPal', 'ctcl-paypal');
            $this->displayOptionsUser();
            $this->adminPanelHtml();
            add_action('admin_init', array($this, 'registerOptions'));
            add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
            add_action('wp_enqueue_scripts', array($this, 'enequeFrontendJs'));
            add_filter('ctcl_process_payment_' . $this->paymentId, array($this, 'processPayment'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'settingsLink'));
        }

        public static function sanitizeToggle($value) { return is_scalar($value) && (string) $value === '1' ? '1' : '0'; }
        public static function sanitizeColor($value) {
            return in_array($value, array('gold', 'blue', 'silver', 'white', 'black'), true) ? $value : 'gold';
        }
        public static function sanitizeClientId($value) {
            return is_string($value) ? preg_replace('/[^a-zA-Z0-9_-]/', '', trim($value)) : '';
        }
        public function registerOptions() {
            register_setting($this->settingFields, 'ctcl_activate_paypal', array('sanitize_callback' => array(__CLASS__, 'sanitizeToggle')));
            register_setting($this->settingFields, 'ctcl_paypal_client-id', array('sanitize_callback' => array(__CLASS__, 'sanitizeClientId')));
            register_setting($this->settingFields, 'ctcl_paypal_color_option', array('sanitize_callback' => array(__CLASS__, 'sanitizeColor')));
            // Keep the original option name for existing installations.
            register_setting($this->settingFields, 'ctcl_paypal_enlable_card', array('sanitize_callback' => array(__CLASS__, 'sanitizeToggle')));
        }
        public function settingsLink($links) {
            array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=ctclAdminPanel&tab=billing#ctcl-paypal-settings')) . '">' . esc_html__('Settings', 'ctcl-paypal') . '</a>');
            return $links;
        }
        private function assetVersion($file) {
            return CTCL_PAYPAL_VERSION . '.' . filemtime(__DIR__ . '/' . $file);
        }
        public function enqueueAdminAssets() {
            if (($_GET['page'] ?? '') !== 'ctclAdminPanel' || ($_GET['tab'] ?? '') !== 'billing') { return; }
            wp_enqueue_style('ctcl-paypal-admin', $this->paypalFilePath . 'css/admin.css', array(), $this->assetVersion('css/admin.css'));
            wp_enqueue_script('ctcl-paypal-admin', $this->paypalFilePath . 'js/admin.js', array(), $this->assetVersion('js/admin.js'), true);
        }
        public function displayOptionsUser() {
            if ('1' !== (string) get_option('ctcl_activate_paypal') || !self::sanitizeClientId(get_option('ctcl_paypal_client-id'))) { return; }
            add_filter('ctcl_payment_options', function ($options) {
                $options[] = array('id' => $this->paymentId, 'name' => $this->paymentName, 'html' => $this->frontendHtml());
                return $options;
            });
        }
        public function enequeFrontendJs() {
            if ('1' !== (string) get_option('ctcl_activate_paypal')) { return; }
            $clientId = self::sanitizeClientId(get_option('ctcl_paypal_client-id'));
            if (!$clientId) { return; }
            $currency = strtoupper((string) get_option('ctcl_currency', 'USD'));
            if (!preg_match('/^[A-Z]{3}$/', $currency)) { $currency = 'USD'; }
            $args = array('client-id' => $clientId, 'currency' => $currency, 'intent' => 'capture');
            if ('1' !== (string) get_option('ctcl_paypal_enlable_card')) { $args['disable-funding'] = 'card'; }
            wp_enqueue_style('ctcl-paypal-checkout', $this->paypalFilePath . 'css/checkout.css', array(), $this->assetVersion('css/checkout.css'));
            // Do not make the local controller depend on the remote SDK: it must handle SDK failures.
            wp_enqueue_script('ctclPaypal', add_query_arg($args, 'https://www.paypal.com/sdk/js'), array(), null, true);
            wp_enqueue_script('ctclPaypalJs', $this->paypalFilePath . 'js/paypal.js', array(), $this->assetVersion('js/paypal.js'), true);
            wp_localize_script('ctclPaypalJs', 'ctclPaypalObject', array(
                'buttonColor' => self::sanitizeColor(get_option('ctcl_paypal_color_option')),
                'paymentSuccess' => __('Payment received. Placing your order…', 'ctcl-paypal'),
                'loadError' => __('PayPal could not load. Refresh the page or choose another payment method.', 'ctcl-paypal'),
                'paymentError' => __('Your payment could not be completed. Try again or choose another payment method.', 'ctcl-paypal'),
                'cancelled' => __('Payment cancelled. You can try again when you’re ready.', 'ctcl-paypal'),
                'invalidForm' => __('Complete your contact details and select shipping before paying.', 'ctcl-paypal'),
                'invalidTotal' => __('Your cart total could not be read. Refresh the page and try again.', 'ctcl-paypal'),
                'processing' => __('Confirming your payment…', 'ctcl-paypal'),
                'paidError' => __('Payment received, but your order could not be submitted. Contact the store with your PayPal transaction ID. Do not pay again.', 'ctcl-paypal'),
            ));
        }
        public function adminPanelHtml() {
            add_filter('ctcl_admin_billings_html', function ($options) {
                $active = '1' === (string) get_option('ctcl_activate_paypal');
                $card = '1' === (string) get_option('ctcl_paypal_enlable_card');
                $clientId = (string) get_option('ctcl_paypal_client-id', '');
                $color = self::sanitizeColor(get_option('ctcl_paypal_color_option'));
                $status = !$active ? __('Disabled', 'ctcl-paypal') : ($clientId ? __('Enabled', 'ctcl-paypal') : __('Setup needed', 'ctcl-paypal'));
                ob_start();
                ?>
                <div class="ctcl-paypal-settings" id="ctcl-paypal-settings" data-save-label="<?php esc_attr_e('Save PayPal settings', 'ctcl-paypal'); ?>">
                    <header class="ctcl-pp-hero">
                        <div class="ctcl-pp-brand" aria-label="PayPal"><span>Pay</span><span>Pal</span></div>
                        <div class="ctcl-pp-hero-copy"><p class="ctcl-pp-eyebrow"><?php esc_html_e('PAYMENT INTEGRATION', 'ctcl-paypal'); ?></p><h2><?php esc_html_e('A familiar way to pay.', 'ctcl-paypal'); ?></h2><p><?php esc_html_e('Bring PayPal to your checkout with a look that fits your store.', 'ctcl-paypal'); ?></p></div>
                        <span class="ctcl-pp-status <?php echo $active && $clientId ? 'is-enabled' : ''; ?>"><?php echo esc_html($status); ?></span>
                    </header>
                    <div class="ctcl-pp-layout">
                        <div class="ctcl-pp-fields">
                            <div class="ctcl-pp-section-heading"><span class="ctcl-pp-step">01</span><div><h3><?php esc_html_e('Connect your account', 'ctcl-paypal'); ?></h3><p><?php esc_html_e('Use the client ID from your PayPal developer app.', 'ctcl-paypal'); ?></p></div></div>
                            <div class="ctcl-pp-field"><label for="ctc-paypal-client-id"><?php esc_html_e('PayPal client ID', 'ctcl-paypal'); ?></label><input id="ctc-paypal-client-id" type="text" name="ctcl_paypal_client-id" value="<?php echo esc_attr($clientId); ?>" autocomplete="off" spellcheck="false" aria-describedby="ctcl-pp-client-help"><p id="ctcl-pp-client-help"><?php esc_html_e('Use a sandbox client ID for testing and a live client ID to accept payments.', 'ctcl-paypal'); ?> <a href="https://developer.paypal.com/dashboard/applications" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Find your client ID ↗', 'ctcl-paypal'); ?></a></p></div>
                            <div class="ctcl-pp-toggle-row"><div><label for="ctcl-activate-paypal"><?php esc_html_e('Enable PayPal checkout', 'ctcl-paypal'); ?></label><p><?php esc_html_e('Show PayPal as a payment method in your store.', 'ctcl-paypal'); ?></p></div><input type="hidden" name="ctcl_activate_paypal" value="0"><input class="ctcl-pp-switch" id="ctcl-activate-paypal" type="checkbox" name="ctcl_activate_paypal" value="1" <?php checked($active); ?>></div>
                            <div class="ctcl-pp-section-heading ctcl-pp-heading-spaced"><span class="ctcl-pp-step">02</span><div><h3><?php esc_html_e('Make it yours', 'ctcl-paypal'); ?></h3><p><?php esc_html_e('Choose how PayPal appears at checkout.', 'ctcl-paypal'); ?></p></div></div>
                            <div class="ctcl-pp-field"><label for="ctcl-paypal-color-option"><?php esc_html_e('Button color', 'ctcl-paypal'); ?></label><select id="ctcl-paypal-color-option" name="ctcl_paypal_color_option"><?php foreach (array('gold' => __('Gold', 'ctcl-paypal'), 'blue' => __('Blue', 'ctcl-paypal'), 'silver' => __('Silver', 'ctcl-paypal'), 'white' => __('White', 'ctcl-paypal'), 'black' => __('Black', 'ctcl-paypal')) as $value => $label) { echo '<option value="' . esc_attr($value) . '" ' . selected($color, $value, false) . '>' . esc_html($label) . '</option>'; } ?></select></div>
                            <div class="ctcl-pp-toggle-row"><div><label for="ctcl-paypal-enable-card"><?php esc_html_e('Offer debit and credit cards', 'ctcl-paypal'); ?></label><p><?php esc_html_e('Availability is determined by PayPal for each customer.', 'ctcl-paypal'); ?></p></div><input type="hidden" name="ctcl_paypal_enlable_card" value="0"><input class="ctcl-pp-switch" id="ctcl-paypal-enable-card" type="checkbox" name="ctcl_paypal_enlable_card" value="1" <?php checked($card); ?>></div>
                        </div>
                        <aside class="ctcl-pp-preview-panel" aria-label="<?php esc_attr_e('Checkout appearance preview', 'ctcl-paypal'); ?>">
                            <p class="ctcl-pp-eyebrow"><?php esc_html_e('CHECKOUT PREVIEW', 'ctcl-paypal'); ?></p>
                            <div class="ctcl-pp-preview-card"><span class="ctcl-pp-mini-icon" aria-hidden="true">P</span><h3><?php esc_html_e('Pay with PayPal', 'ctcl-paypal'); ?></h3><p><?php esc_html_e('Complete your payment with your PayPal account.', 'ctcl-paypal'); ?></p><div class="ctcl-pp-preview-button" data-color="<?php echo esc_attr($color); ?>"><span><?php esc_html_e('Pay with', 'ctcl-paypal'); ?></span> <strong>PayPal</strong></div><p class="ctcl-pp-card-note" <?php echo $card ? '' : 'hidden'; ?>><?php esc_html_e('Debit or credit card options may also be shown.', 'ctcl-paypal'); ?></p><div class="ctcl-pp-preview-footer"><span class="dashicons dashicons-lock" aria-hidden="true"></span><?php esc_html_e('Payment handled by PayPal', 'ctcl-paypal'); ?></div></div>
                            <p class="ctcl-pp-preview-caption"><?php esc_html_e('Appearance preview only. PayPal displays the available payment options at checkout.', 'ctcl-paypal'); ?></p>
                            <div class="ctcl-pp-help"><h4><?php esc_html_e('Before you go live', 'ctcl-paypal'); ?></h4><p><?php esc_html_e('Test with a sandbox account, confirm your store currency, then switch to a live client ID.', 'ctcl-paypal'); ?></p><span class="ctcl-pp-currency"><?php echo esc_html(sprintf(__('Store currency: %s', 'ctcl-paypal'), strtoupper((string) get_option('ctcl_currency', 'USD')))); ?></span></div>
                        </aside>
                    </div>
                    <p class="ctcl-pp-save-status" aria-live="polite" data-unsaved="<?php esc_attr_e('You have unsaved changes.', 'ctcl-paypal'); ?>" data-saved="<?php esc_attr_e('Your saved configuration is shown above.', 'ctcl-paypal'); ?>"><?php esc_html_e('Your saved configuration is shown above.', 'ctcl-paypal'); ?></p>
                </div>
                <?php
                $options[] = array('settingFields' => $this->settingFields, 'formHeader' => __('PayPal', 'ctcl-paypal'), 'formSetting' => 'ctcl_payment_setting', 'html' => ob_get_clean());
                return $options;
            }, 40);
        }
        public function frontendHtml() {
            return '<section class="ctcl-pp-checkout" aria-label="' . esc_attr__('PayPal payment', 'ctcl-paypal') . '"><div class="ctcl-pp-checkout-header"><span class="ctcl-pp-checkout-icon" aria-hidden="true">P</span><div><h3>' . esc_html__('Pay with PayPal', 'ctcl-paypal') . '</h3><p>' . esc_html__('Complete your payment with your PayPal account.', 'ctcl-paypal') . '</p></div></div><div id="paypal-button-container"></div><p class="ctcl-pp-checkout-status" role="status" aria-live="polite">' . esc_html__('Loading payment options…', 'ctcl-paypal') . '</p><p id="ctcl-paypal-error" role="alert" hidden></p><div class="ctcl-pp-checkout-footer"><svg aria-hidden="true" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>' . esc_html__('Payment handled by PayPal', 'ctcl-paypal') . '</div></section>';
        }
    }
    new ctclPaypal();
});
