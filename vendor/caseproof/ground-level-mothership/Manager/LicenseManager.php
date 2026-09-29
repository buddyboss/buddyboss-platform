<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Manager;

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\LicenseActivations;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Licenses;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
use BuddyBossPlatform\GroundLevel\Mothership\Credentials;
use BuddyBossPlatform\GroundLevel\Mothership\Transients\ActivationTransient;
use BuddyBossPlatform\GroundLevel\Mothership\Util;
use BuddyBossPlatform\GroundLevel\Support\AdminNotices;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
use BuddyBossPlatform\GroundLevel\Support\Result;
use BuddyBossPlatform\GroundLevel\Support\View;
/**
 * Manages license activation, deactivation, validation, and plugin updates via the Mothership API.
 */
class LicenseManager
{
    use Hookable;
    /**
     * The plugin connection.
     *
     * @var AbstractPluginConnection
     */
    private AbstractPluginConnection $plugin;
    /**
     * The addons manager instance.
     *
     * @var AddonsManager
     */
    private AddonsManager $addonsManager;
    /**
     * The credentials instance.
     *
     * @var Credentials
     */
    private Credentials $credentials;
    /**
     * The license activations API.
     *
     * @var LicenseActivations
     */
    private LicenseActivations $licenseActivations;
    /**
     * The products API.
     *
     * @var Products
     */
    private Products $products;
    /**
     * The licenses API.
     *
     * @var Licenses
     */
    private Licenses $licenses;
    /**
     * The activation transient.
     *
     * @var ActivationTransient
     */
    private ActivationTransient $activationTransient;
    /**
     * The admin notices service.
     *
     * @var AdminNotices
     */
    private AdminNotices $adminNotices;
    /**
     * The view instance for rendering templates.
     *
     * @var View
     */
    private View $view;
    /**
     * The Mothership utility instance.
     *
     * @var Util
     */
    private Util $util;
    /**
     * The result of the most recent form submission handled by {@see self::controller()}.
     *
     * @var Result|null
     */
    private ?Result $lastFormResult = null;
    /**
     * Constructor.
     *
     * @param AbstractPluginConnection $plugin              The plugin connection.
     * @param AddonsManager            $addonsManager       The addons manager instance.
     * @param Credentials              $credentials         The credentials instance.
     * @param LicenseActivations       $licenseActivations  The license activations API.
     * @param Products                 $products            The products API.
     * @param Licenses                 $licenses            The licenses API.
     * @param ActivationTransient      $activationTransient The activation transient.
     * @param AdminNotices             $adminNotices        The admin notices service.
     * @param View                     $view                The view instance for rendering templates.
     * @param Util                     $util                The Mothership utility instance.
     */
    public function __construct(AbstractPluginConnection $plugin, AddonsManager $addonsManager, Credentials $credentials, LicenseActivations $licenseActivations, Products $products, Licenses $licenses, ActivationTransient $activationTransient, AdminNotices $adminNotices, View $view, Util $util)
    {
        $this->plugin = $plugin;
        $this->addonsManager = $addonsManager;
        $this->credentials = $credentials;
        $this->licenseActivations = $licenseActivations;
        $this->products = $products;
        $this->licenses = $licenses;
        $this->activationTransient = $activationTransient;
        $this->adminNotices = $adminNotices;
        $this->view = $view;
        $this->util = $util;
        // Add WP Cron event to check the license status every 12 hours.
        $cronName = $this->plugin->pluginId . '_check_license_activation_status_event';
        if (!wp_next_scheduled($cronName)) {
            wp_schedule_event(\time(), 'twicedaily', $cronName);
        }
    }
    /**
     * Configure WordPress hooks.
     *
     * @return array<Hook>
     */
    protected function configureHooks() : array
    {
        return [new Hook(Hook::TYPE_ACTION, $this->plugin->pluginId . '_check_license_activation_status_event', [$this, 'checkLicenseActivationStatus']), new Hook(Hook::TYPE_ACTION, $this->plugin->pluginId . '_active_license_invalidated', [$this, 'onLicenseRevoked']), new Hook(Hook::TYPE_ACTION, $this->plugin->pluginId . '_active_license_expired', [$this, 'onLicenseRevoked']), new Hook(Hook::TYPE_ACTION, 'admin_init', [$this, 'onLicenseKeyOverwritten']), new Hook(Hook::TYPE_FILTER, 'upgrader_pre_install', [$this, 'preventUpdatesInDevelopment'], 10, 2), new Hook(Hook::TYPE_ACTION, 'admin_enqueue_scripts', [$this, 'enqueueAssets']), new Hook(Hook::TYPE_ACTION, 'in_plugin_update_message-' . $this->plugin->pluginFile, [$this, 'appendLicenseUpdateMessage'], 10, 2)];
    }
    /**
     * Enqueues the stylesheet and dashicons used by the license views.
     *
     * @return void
     */
    public function enqueueAssets() : void
    {
        wp_enqueue_style("{$this->plugin->pluginId}-grdlvl-mosh-licenses", plugin_dir_url(__FILE__) . '../assets/license.css', ['dashicons'], null);
    }
    /**
     * Prevents plugin updates in development environments by checking for a .git directory.
     *
     * This hooks into WordPress's upgrader_pre_install filter and blocks the update if the
     * plugin directory contains a .git directory. This covers both manual "Update Now" clicks
     * from the plugins page and programmatic calls via {@see installPluginSilently()}.
     *
     * @param boolean|\WP_Error $abort Whether to abort the update. A WP_Error will abort.
     * @param array             $extra Extra arguments passed by the upgrader, including 'plugin'.
     *
     * @return boolean|\WP_Error The original $abort value, or a WP_Error to block the update.
     */
    public function preventUpdatesInDevelopment($abort, $extra)
    {
        if (!isset($extra['plugin']) || $extra['plugin'] !== $this->plugin->pluginFile) {
            return $abort;
        }
        $pluginDir = \WP_PLUGIN_DIR . '/' . \dirname($this->plugin->pluginFile);
        if (@\is_dir($pluginDir . '/.git')) {
            return new \WP_Error('dev_environment', __('Plugin update is prevented in development environment to avoid accidental overwrites.', 'ground-level'));
        }
        return $abort;
    }
    /**
     * Appends a license-aware message to the plugin's update row when the download is unavailable.
     *
     * @param array  $pluginData The plugin header data. Unused.
     * @param object $response   The update response object. `package` is empty when the download is blocked.
     *
     * @return void
     */
    public function appendLicenseUpdateMessage(array $pluginData, $response) : void
    {
        if (!empty($response->package)) {
            return;
        }
        $message = $this->licenseUpdateMessage();
        if ('' === $message) {
            return;
        }
        \printf(' <span class="gl-mosh-license-update-message">%s</span>', wp_kses_post($message));
    }
    /**
     * Builds the license-specific message explaining why an update cannot be downloaded.
     *
     * @return string The message HTML, or an empty string when no message applies.
     */
    private function licenseUpdateMessage() : string
    {
        $accountLink = '';
        $accountUrl = $this->plugin->getAccountUrl();
        if ('' !== $accountUrl) {
            $accountLink = \sprintf(' <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url($accountUrl), esc_html__('Visit your account dashboard', 'ground-level'));
        }
        if (!$this->plugin->getLicenseActivationStatus()) {
            return __('Please activate your license to download this update.', 'ground-level') . $accountLink;
        }
        if ($this->isLicenseExpired()) {
            return __('Your license has expired. Please renew your license to download this update.', 'ground-level') . $accountLink;
        }
        return __('Your license does not allow downloading this update. Please check your license status.', 'ground-level') . $accountLink;
    }
    /**
     * Determines whether the cached license has an expiration date in the past.
     *
     * @return boolean
     */
    private function isLicenseExpired() : bool
    {
        $expiresAt = $this->activationTransient->licenseExpiresAt;
        if ('' === $expiresAt) {
            return \false;
        }
        $timestamp = \strtotime($expiresAt);
        return \false !== $timestamp && $timestamp < \time();
    }
    /**
     * Cleans up after a license is revoked (invalidated or expired).
     */
    public function onLicenseRevoked() : void
    {
        // Update license status.
        $this->plugin->setLicenseActivationStatus(\false);
        // Notify users.
        $this->notifyLicenseRevoked();
        // Clean up.
        $this->addonsManager->clearCache();
        $this->activationTransient->delete();
    }
    /**
     * Marks the license as inactive when the current license key is no longer the one
     * the site was activated with, and asks the user to activate again.
     *
     * This runs on every admin request so we catch the change as soon as an admin user
     * loads any admin page - for example after a developer adds, edits, or removes a
     * {$pluginPrefix}_LICENSE_KEY environment variable or constant, overwriting the license
     * key stored in the database.
     *
     * We do not call the Mothership "deactivate" API here. The server still treats the
     * old key as activated on this site, and we do not want to give up that activation
     * just because someone toggled a local config value.
     *
     * @return void
     */
    public function onLicenseKeyOverwritten() : void
    {
        if (!$this->plugin->getLicenseActivationStatus()) {
            return;
        }
        $cachedKey = $this->activationTransient->licenseKey;
        // Nothing to compare against yet (fresh install or transient not synced).
        if ('' === $cachedKey) {
            return;
        }
        if ($this->credentials->getLicenseKey() === $cachedKey) {
            return;
        }
        $this->plugin->setLicenseActivationStatus(\false);
        $this->addonsManager->clearCache();
        $this->activationTransient->delete();
        $this->adminNotices->flash('license_key_overwritten', __(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'Your license key was overwritten by an environment variable or constant. Please activate your license again.',
            'ground-level'
        ), AdminNotices::WARNING);
    }
    /**
     * Sends license revocation notifications via admin notice and email.
     */
    private function notifyLicenseRevoked() : void
    {
        $isExpired = $this->plugin->pluginId . '_active_license_expired' === current_action();
        $productName = $this->activationTransient->productName ?: $this->plugin->productId;
        $licenseKey = $this->activationTransient->licenseKey ?: '';
        $accountUrl = $this->plugin->getAccountUrl();
        if ($isExpired) {
            $error = \sprintf(
                // Translators: %1$s license key, %2$s product name.
                __('The license key %1$s has expired. Please renew your license to continue using %2$s.', 'ground-level'),
                '<code>' . esc_html($licenseKey) . '</code>',
                $productName
            );
        } else {
            $error = __(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                "This issue could have been caused by a change in the license's status or the site may have been disabled from your account dashboard.",
                'ground-level'
            );
        }
        $this->notifyLicenseRevokedNotice($error, $productName, $accountUrl);
        $this->notifyLicenseRevokedEmail($error, $productName, $accountUrl, $licenseKey, $isExpired);
    }
    /**
     * Displays an admin notice for a revoked or expired license.
     *
     * @param string $error       The error detail message.
     * @param string $productName The product name.
     * @param string $accountUrl  The account dashboard URL.
     */
    private function notifyLicenseRevokedNotice(string $error, string $productName, string $accountUrl) : void
    {
        $message = \sprintf(
            // Translators: %s product name.
            __('During a recent %s license status check an error was encountered.', 'ground-level'),
            $productName
        );
        $actions = [];
        if (!empty($accountUrl)) {
            $actions[] = ['label' => __('Visit Account Dashboard', 'ground-level'), 'url' => $accountUrl];
        }
        $notice = \implode(' ', [$message, $error]);
        $this->adminNotices->notice('license_revoked', $notice, AdminNotices::WARNING, $actions);
    }
    /**
     * Sends an email notification for a revoked or expired license.
     *
     * Throttled to once per 24h per (plugin, license key, revocation type).
     *
     * @param string  $error       The error detail message.
     * @param string  $productName The product name.
     * @param string  $accountUrl  The account dashboard URL.
     * @param string  $licenseKey  The license key the email is about.
     * @param boolean $isExpired   True if the license expired, false if it was invalidated.
     */
    private function notifyLicenseRevokedEmail(string $error, string $productName, string $accountUrl, string $licenseKey, bool $isExpired) : void
    {
        $cooldownKey = \sprintf('%s_license_%s_email_sent_%s', $this->plugin->pluginId, $isExpired ? 'expired' : 'invalidated', \md5($licenseKey));
        if (\false !== get_site_transient($cooldownKey)) {
            return;
        }
        $siteName = get_bloginfo('name');
        $siteUrl = home_url();
        $subject = \sprintf(
            // Translators: %1$s site name, %2$s product name.
            __('[WARNING] Error with your %1$s license for %2$s', 'ground-level'),
            $siteName,
            $productName
        );
        $body = [\sprintf(
            // Translators: %1$s product name, %2$s site name, %3$s site URL.
            __('A recent %1$s license status check on %2$s (%3$s) has encountered an error.', 'ground-level'),
            $productName,
            $siteName,
            $siteUrl
        ), wp_strip_all_tags($error)];
        if (!empty($accountUrl)) {
            // Translators: %s account dashboard URL.
            $body[] = \sprintf(__('Visit Account Dashboard: %s', 'ground-level'), $accountUrl);
        }
        if (wp_mail(get_option('admin_email'), $subject, \implode("\n\n", $body))) {
            set_site_transient($cooldownKey, 1, DAY_IN_SECONDS);
        }
    }
    /**
     * Checks and validates the license activation with the license server.
     *
     * Triggers actions if the active license is expired or invalidated.
     *
     * @return boolean false if the license activation could not be verified, true otherwise.
     */
    public function checkLicenseActivationStatus() : bool
    {
        if (!$this->plugin->getLicenseActivationStatus()) {
            return \false;
        }
        $licenseKey = $this->credentials->getLicenseKey();
        $activationDomain = $this->credentials->getDomain();
        if (empty($licenseKey) || empty($activationDomain)) {
            return \false;
        }
        $activation = $this->licenseActivations->retrieveLicenseActivation($licenseKey, $activationDomain);
        if ($activation->isError()) {
            if (\in_array($activation->statusCode, [401, 403, 404], \true)) {
                do_action_deprecated($this->plugin->pluginId . '_license_status_changed', [\false, $activation], '4.0.0', 'Use the {$pluginId}_active_license_invalidated or {$pluginId}_active_license_expired ' . 'actions instead.');
                if ('license-expired' === $activation->getErrorCode()) {
                    /**
                     * Fires when an active license is detected as expired during a status check.
                     *
                     * @param Response $activation The response from the license activation retrieval API call.
                     */
                    do_action($this->plugin->pluginId . '_active_license_expired', $activation);
                } else {
                    /**
                     * Fires when an active license is detected as invalid during a status check.
                     *
                     * @param Response $activation The response from the license activation retrieval API call.
                     */
                    do_action($this->plugin->pluginId . '_active_license_invalidated', $activation);
                }
            }
            return \false;
        }
        $this->syncActivationTransient();
        return \true;
    }
    /**
     * Handles admin actions and notices.
     */
    public function controller() : void
    {
        $licenseButton = $this->plugin->pluginId . '_license_button';
        if (!isset($_POST[$licenseButton])) {
            return;
        }
        $action = sanitize_text_field(wp_unslash($_POST[$licenseButton]));
        if (!\in_array($action, ['activate', 'deactivate', 'check_status'], \true)) {
            return;
        }
        if ('activate' === $action) {
            $result = $this->handleActivation();
        } elseif ('deactivate' === $action) {
            $result = $this->handleDeactivation();
        } elseif ('check_status' === $action) {
            $result = $this->handleCheckStatus();
        }
        $this->lastFormResult = $result;
        if (!\filter_var($_POST['hide_notice'] ?? \false, \FILTER_VALIDATE_BOOLEAN)) {
            $type = '';
            if ('deactivate' === $action && $result->isSuccess()) {
                $data = $result->getData();
                if (\is_array($data) && isset($data['freed']) && \false === $data['freed']) {
                    $type = AdminNotices::WARNING;
                }
            }
            $this->renderNotice($result, $type);
        }
    }
    /**
     * Returns the result of the most recent form submission handled by {@see self::controller()}.
     *
     * @return Result|null
     */
    public function getLastFormResult() : ?Result
    {
        return $this->lastFormResult;
    }
    /**
     * Handles license activation with validation.
     *
     * @return Result
     */
    private function handleActivation() : Result
    {
        if (!current_user_can('manage_options')) {
            return Result::failure(__('Insufficient permissions', 'ground-level'));
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? ''));
        if (empty($nonce) || !wp_verify_nonce($nonce, 'mothership_activate_license')) {
            return Result::failure(__('Invalid nonce', 'ground-level'));
        }
        if (empty($_POST['license_key']) || empty($_POST['activation_domain'])) {
            return Result::failure(__('License key and domain are required', 'ground-level'));
        }
        $licenseKey = sanitize_text_field(wp_unslash($_POST['license_key']));
        $domain = sanitize_text_field(wp_unslash($_POST['activation_domain']));
        return $this->activateLicense($licenseKey, $domain, \true);
    }
    /**
     * Handles license deactivation with validation.
     *
     * @return Result
     */
    private function handleDeactivation() : Result
    {
        if (!current_user_can('manage_options')) {
            return Result::failure(__('Insufficient permissions', 'ground-level'));
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? ''));
        if (empty($nonce) || !wp_verify_nonce($nonce, 'mothership_deactivate_license')) {
            return Result::failure(__('Invalid nonce', 'ground-level'));
        }
        $licenseKey = $this->credentials->getLicenseKey();
        $domain = $this->credentials->getDomain();
        if ('' === $licenseKey || '' === $domain) {
            return Result::failure(__('License key and domain are required for deactivation', 'ground-level'));
        }
        return $this->deactivateLicense($licenseKey, $domain);
    }
    /**
     * Handles the on-demand license status check with validation.
     *
     * @return Result
     */
    private function handleCheckStatus() : Result
    {
        if (!current_user_can('manage_options')) {
            return Result::failure(__('Insufficient permissions', 'ground-level'));
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? ''));
        if (empty($nonce) || !wp_verify_nonce($nonce, 'mothership_check_license_status')) {
            return Result::failure(__('Invalid nonce', 'ground-level'));
        }
        if ($this->checkLicenseActivationStatus()) {
            return Result::success(__('License status verified successfully', 'ground-level'));
        }
        return Result::failure(__('License could not be verified', 'ground-level'));
    }
    /**
     * Renders an admin notice based on the result.
     *
     * @param Result $result The result to render.
     * @param string $type   Override notice type. Optional. Default ''.
     */
    private function renderNotice(Result $result, string $type = '') : void
    {
        $message = $result->isSuccess() ? $result->getMessage() ?: __('Operation completed successfully', 'ground-level') : ($result->getMessage() ?: __('An error occurred during the operation', 'ground-level'));
        if (empty($type)) {
            $type = $result->isSuccess() ? AdminNotices::SUCCESS : AdminNotices::ERROR;
        }
        $this->adminNotices->flash('license_action_result', $message, $type);
    }
    /**
     * Returns the appropriate license form based on the current activation status.
     *
     * @return string The HTML for the form.
     */
    public function generateLicenseForm() : string
    {
        if ($this->plugin->getLicenseActivationStatus()) {
            return $this->generateDeactivationForm();
        } else {
            return $this->generateActivationForm();
        }
    }
    /**
     * Generates the HTML for the activation form.
     *
     * @return string The HTML for the form.
     */
    public function generateActivationForm() : string
    {
        return $this->view->render('license-activation-form.php', ['pluginId' => $this->plugin->pluginId, 'licenseKey' => $this->credentials->getLicenseKey(), 'activationDomain' => $this->credentials->getDomain(), 'lockLicenseKey' => $this->isLicenseKeyOverridden()]);
    }
    /**
     * Generates the HTML for the deactivation form.
     *
     * Includes the active license information box.
     *
     * @return string The HTML for the form.
     */
    public function generateDeactivationForm() : string
    {
        return $this->view->render('license-deactivation-form.php', ['pluginId' => $this->plugin->pluginId, 'licenseKeyMasked' => $this->maskLicenseKey($this->activationTransient->licenseKey), 'activationDomain' => $this->credentials->getDomain(), 'lockLicenseKey' => $this->isLicenseKeyOverridden(), 'activation' => $this->activationTransient]);
    }
    /**
     * Whether the license key is overridden by an environment variable or constant.
     *
     * @return boolean
     */
    private function isLicenseKeyOverridden() : bool
    {
        return \false !== $this->credentials->isCredentialSetInEnvironmentOrConstants(Credentials::LICENSE_KEY_BASENAME);
    }
    /**
     * Masks the license key, preserving dashes and underscores and revealing the last third of the string.
     *
     * @param  string $licenseKey The license key to mask.
     * @return string
     */
    private function maskLicenseKey(string $licenseKey) : string
    {
        if ('' === $licenseKey) {
            return '';
        }
        $len = \strlen($licenseKey);
        $toShow = (int) \floor($len / 3);
        $visible = $toShow > 0 ? \substr($licenseKey, -$toShow) : '';
        $masked = \preg_replace('/[^-_]/', '*', \substr($licenseKey, 0, $len - $toShow));
        return $masked . $visible;
    }
    /**
     * Generates the HTML for the on-demand license status check form.
     *
     * @return string The HTML for the form.
     */
    public function generateCheckStatusForm() : string
    {
        return $this->view->render('check-status-form.php', ['pluginId' => $this->plugin->pluginId]);
    }
    /**
     * Activates the license.
     *
     * @param  string  $licenseKey            The license key.
     * @param  string  $domain                The domain.
     * @param  boolean $installCorrectEdition Whether to check and install the correct product edition after activation.
     * @return Result The result indicating success or failure.
     */
    public function activateLicense(string $licenseKey, string $domain, bool $installCorrectEdition = \false) : Result
    {
        $activation = $this->licenseActivations->activate($this->plugin->productId, $licenseKey, $domain);
        if ($activation->isError()) {
            return Result::failure($activation->getErrorMessage());
        }
        $this->credentials->setLicenseKey($licenseKey);
        $this->plugin->setLicenseActivationStatus(\true);
        $this->syncActivationTransient();
        wp_clean_update_cache();
        /**
         * Fires after a license is successfully activated.
         *
         * @param Response $activation The response from the license activation API call.
         * @param string   $licenseKey The license key that was activated.
         * @param string   $domain     The domain the license was activated for.
         */
        do_action($this->plugin->pluginId . '_license_activated', $activation, $licenseKey, $domain);
        if ($installCorrectEdition) {
            $result = $this->maybeInstallCorrectEdition();
            if (\false !== $result) {
                return $result;
            }
        }
        return Result::success(__('License activated successfully', 'ground-level'));
    }
    /**
     * Deactivates the license.
     *
     * Deactivating the license on the server may fail for various reasons, however we still want to clear
     * the local activation data so the user is not blocked from activating a different license on the site.
     *
     * @param  string $licenseKey The license key.
     * @param  string $domain     The domain.
     * @return Result The result object.
     */
    public function deactivateLicense(string $licenseKey, string $domain) : Result
    {
        if (!$this->plugin->setLicenseActivationStatus(\false)) {
            return Result::failure(__('Failed to deactivate the license', 'ground-level'));
        }
        $this->credentials->setLicenseKey('');
        $this->addonsManager->clearCache();
        $this->activationTransient->delete();
        wp_clean_update_cache();
        $response = $this->licenseActivations->deactivate($licenseKey, $domain);
        $freed = !$response->isError();
        $data = \compact('freed');
        /**
         * Fires after a license is deactivated.
         *
         * When $freed is false the license was only cleared locally (e.g. an expired license
         * the API refused to deactivate) and the activation is still in use remotely.
         *
         * @param Response $response   The response from the license deactivation API call.
         * @param string   $licenseKey The license key that was deactivated.
         * @param string   $domain     The domain the license was deactivated for.
         * @param boolean  $freed      Whether the activation was freed on the licensing server.
         */
        do_action($this->plugin->pluginId . '_license_deactivated', $response, $licenseKey, $domain, $freed);
        if ($response->isError()) {
            $message = \sprintf(
                // Translators: %1$s opening anchor tag, %2$s closing anchor tag.
                __(
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    'The license has been removed from this site, but we could not confirm the activation was released on our licensing server. If this site still appears under your license, you can remove it from your %1$saccount dashboard%2$s.',
                    'ground-level'
                ),
                '<a href="' . esc_url($this->plugin->getAccountUrl()) . '" target="_blank" rel="noopener noreferrer">',
                '</a>'
            );
            return Result::success($message, $data);
        }
        return Result::success(__('License deactivated successfully', 'ground-level'), $data);
    }
    /**
     * Retrieves the latest version for a product.
     *
     * This method is a replacement for {@see GroundLevel\Mothership\Api\Request\Products::getVersionLatest()}
     * which currently does not support filtering by type.
     *
     * @todo Use order_by=number in the query args once
     * {@see https://github.com/caseproof/licenses.caseproof.com/issues/589} is resolved.
     *
     * @param  string $slug The product slug.
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function getVersionLatest(string $slug) : Response
    {
        $args = ['order' => 'desc', 'order_by' => 'created_at', 'per_page' => 1];
        if (!$this->plugin->allowPrereleaseVersions()) {
            $args['type'] = 'release';
        }
        $versions = $this->products->getVersions($slug, $args);
        if ($versions->isError()) {
            return $versions;
        }
        $versionList = $versions->getData('versions');
        if (empty($versionList)) {
            return new Response((object) ['message' => 'Not found'], 404);
        }
        // We are only interested in the first result (latest version).
        return new Response($versionList[0]);
    }
    /**
     * Fetches activation details from the API and saves them to {@see GroundLevel\Mothership\Transients\ActivationTransient}.
     */
    public function syncActivationTransient() : void
    {
        $licenseKey = $this->credentials->getLicenseKey();
        if (empty($licenseKey)) {
            return;
        }
        $response = $this->licenseActivations->retrieveLicenseActivationsMeta($licenseKey, ['_embed' => 'license,license.product,license.user']);
        if ($response->isError()) {
            return;
        }
        $licenseMeta = $response->data;
        $license = $response->getEmbed('license');
        $product = $response->getEmbed('license.product');
        $user = $response->getEmbed('license.user');
        // License activation counts.
        $this->activationTransient->prodActivationsAllowed = (int) ($licenseMeta->prod->allowed ?? 0);
        $this->activationTransient->prodActivationsUsed = (int) ($licenseMeta->prod->used ?? 0);
        $this->activationTransient->prodActivationsFree = (int) ($licenseMeta->prod->free ?? 0);
        $this->activationTransient->testActivationsAllowed = (int) ($licenseMeta->test->allowed ?? 0);
        $this->activationTransient->testActivationsUsed = (int) ($licenseMeta->test->used ?? 0);
        $this->activationTransient->testActivationsFree = (int) ($licenseMeta->test->free ?? 0);
        // Product data.
        $this->activationTransient->productSlug = $product->slug ?? '';
        $this->activationTransient->productName = $product->name ?? '';
        // License data.
        $this->activationTransient->licenseKey = $licenseKey;
        $this->activationTransient->licenseStatus = $license->status ?? '';
        $this->activationTransient->licenseExpiresAt = $license->expires_at ?? '';
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        // User data.
        $this->activationTransient->userEmail = $user->email ?? '';
        if (!empty($product->slug)) {
            $version = $this->getVersionLatest($product->slug);
            if (!$version->isError()) {
                // Version data.
                $this->activationTransient->downloadUrl = $version->getData('url', '');
                $this->activationTransient->versionNumber = $version->getData('number', '');
            }
        }
        $this->activationTransient->save();
    }
    /**
     * Retrieves the product and its sibling editions, keyed by slug.
     *
     * @param  string $slug The product slug.
     * @return array<string, object> Products keyed by slug.
     */
    public function getEditions(string $slug) : array
    {
        // This could be reduced to one API request if the relations endpoint supports
        // embedding "self".
        $product = $this->products->get($slug);
        $siblings = $this->products->getRelations($slug, ['type' => 'sibling']);
        if ($product->isError() || $siblings->isError()) {
            return [];
        }
        $productData = $product->data;
        $siblingProducts = $siblings->getData('products', []);
        if (null === $productData) {
            return [];
        }
        $editions = [];
        $editions[$productData->slug] = $productData;
        foreach ($siblingProducts as $sibling) {
            $editions[$sibling->slug] = $sibling;
        }
        return $editions;
    }
    /**
     * Checks whether the license entitles a higher-priority edition than the one currently installed.
     *
     * @return array{installed: object, license: object}|false false if the installed edition is correct, otherwise an array of both editions.
     */
    public function isIncorrectEditionInstalled()
    {
        $installedProduct = $this->plugin->productId;
        $licenseProduct = $this->activationTransient->productSlug;
        if (empty($installedProduct) || empty($licenseProduct)) {
            return \false;
        }
        // Same edition.
        if ($installedProduct === $licenseProduct) {
            return \false;
        }
        $editions = $this->getEditions($licenseProduct);
        $installedEdition = $editions[$installedProduct] ?? null;
        $licenseEdition = $editions[$licenseProduct] ?? null;
        // Not enough data to compare.
        if (\is_null($installedEdition) || \is_null($licenseEdition)) {
            return \false;
        }
        if ($licenseEdition->priority > $installedEdition->priority) {
            return ['installed' => $installedEdition, 'license' => $licenseEdition];
        }
        return \false;
    }
    /**
     * Silently installs a plugin from a download URL.
     *
     * @param  string $downloadUrl The URL to download the plugin from.
     * @return Result The result indicating success or failure.
     */
    public function installPluginSilently(string $downloadUrl) : Result
    {
        if (!current_user_can('update_plugins')) {
            return Result::failure(__('Insufficient permissions to install plugin', 'ground-level'));
        }
        if (!$this->util->isAllowedDownloadUrl($downloadUrl)) {
            return Result::failure(__('Invalid download URL', 'ground-level'));
        }
        require_once \ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader($skin);
        $result = $upgrader->install($downloadUrl, ['overwrite_package' => \true]);
        if (is_wp_error($result)) {
            return Result::failure($result->get_error_message());
        }
        if (is_wp_error($skin->result)) {
            return Result::failure($skin->result->get_error_message());
        }
        if ($skin->get_errors()->has_errors()) {
            return Result::failure($skin->get_error_messages());
        }
        // Plugin_Upgrader::install() returns null or an empty array when the install never ran.
        if (\true !== $result) {
            return Result::failure(__('Plugin installation failed', 'ground-level'));
        }
        return Result::success(__('Plugin installed successfully', 'ground-level'));
    }
    /**
     * Checks if the edition associated with the activated license is different from the currently installed edition,
     * and if so, attempts to silently install the correct edition based on the license.
     *
     * @return Result|false The installation result, or false if no edition change was needed.
     */
    protected function maybeInstallCorrectEdition()
    {
        $editions = $this->isIncorrectEditionInstalled();
        if (\false === $editions) {
            return \false;
        }
        $downloadUrl = $this->activationTransient->downloadUrl;
        if (empty($downloadUrl)) {
            return \false;
        }
        $result = $this->installPluginSilently($downloadUrl);
        if ($result->isSuccess()) {
            /**
             * Fires when the currently installed edition is changed.
             *
             * @param array $editions {
             *     @type  object $installed The previously installed edition.
             *     @type  object $license   The edition associated with the activated license.
             * }
             */
            do_action($this->plugin->pluginId . '_edition_changed', $editions);
            return Result::success(__('License activated and plugin updated successfully', 'ground-level'), $editions);
        }
        return \false;
    }
}
