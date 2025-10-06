<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Manager;

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MothershipService;
use BuddyBossPlatform\GroundLevel\Mothership\Credentials;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
use BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\LicenseActivations;
use BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness;
/**
 * The LicenseManager class manages site activation.
 */
class LicenseManager implements StaticContainerAwareness
{
    use HasStaticContainer;
    /**
     * Schedules the events for the license manager.
     *
     * @param string $pluginId The plugin ID.
     */
    public static function scheduleEvents(string $pluginId) : void
    {
        $cronName = $pluginId . '_check_license_status_event';
        add_action($cronName, [self::class, 'checkLicenseStatus']);
        // Add WP Cron event to check the license status every 12 hours.
        if (!wp_next_scheduled($cronName)) {
            wp_schedule_event(\time(), 'twicedaily', $cronName);
        }
    }
    /**
     * The checkLicenseStatus function checks the license status and triggers the license status changed action.
     *
     * @return boolean false if the license status is false or license key and activation domain are empty, otherwise true.
     */
    public static function checkLicenseStatus() : bool
    {
        // Only run this if license status is true.
        if (!self::getContainer()->get(AbstractPluginConnection::class)->getLicenseActivationStatus()) {
            return \false;
        }
        $licenseKey = Credentials::getLicenseKey();
        $activationDomain = Credentials::getActivationDomain();
        if (empty($licenseKey) || empty($activationDomain)) {
            return \false;
        }
        $status = LicenseActivations::retrieveLicenseActivation($licenseKey, $activationDomain);
        $pluginId = self::getContainer()->get(AbstractPluginConnection::class)->pluginId;
        if ($status instanceof Response && $status->isError()) {
            if ($status->errorCode === 401) {
                do_action($pluginId . '_license_status_changed', \false, $status);
                return \false;
            }
        }
        do_action($pluginId . '_license_status_changed', \true, $status);
        return \true;
    }
    /**
     * The controller function handles the license activation and deactivation.
     */
    public static function controller() : void
    {
        $pluginId = self::getContainer()->get(AbstractPluginConnection::class)->pluginId;
        if (isset($_POST[$pluginId . '_license_button'])) {
            if ($_POST[$pluginId . '_license_button'] === 'activate') {
                try {
                    self::activateLicense($_POST['license_key'], $_POST['activation_domain']);
                    \printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__('License activated successfully', 'caseproof-mothership'));
                } catch (\Exception $e) {
                    \printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html($e->getMessage()));
                }
            } elseif ($_POST[$pluginId . '_license_button'] === 'deactivate') {
                try {
                    self::deactivateLicense($_POST['license_key'], $_POST['activation_domain']);
                    \printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__('License deactivated successfully', 'caseproof-mothership'));
                } catch (\Exception $e) {
                    \printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html($e->getMessage()));
                }
            }
        }
    }
    /**
     * Generates the HTML for the activation form.
     *
     * @return string The HTML for the activation form.
     */
    public function generateLicenseActivationForm() : string
    {
        if (self::getContainer()->get(AbstractPluginConnection::class)->getLicenseActivationStatus()) {
            return $this->generateDisconnectForm();
        } else {
            return $this->generateActivationForm();
        }
    }
    /**
     * Generates the HTML for the activation form.
     */
    public function generateActivationForm() : string
    {
        \ob_start();
        $licenseIsStored = Credentials::isCredentialSetInEnvironmentOrConstants(MothershipService::LICENSE_KEY_BASENAME);
        $pluginId = self::getContainer()->get(AbstractPluginConnection::class)->pluginId;
        ?>
        <form method="post" action="" name="<?php 
        echo esc_attr($pluginId);
        ?>_activate_license_form">
            <div class="<?php 
        echo esc_attr($pluginId);
        ?>-licence-div-form">
                <label for="license_key"><?php 
        esc_html_e('License Key: ', 'caseproof-mothership');
        ?></label>
                <input name="license_key"
                    type="text"
                    id="license_key"
                    value="<?php 
        echo esc_attr(Credentials::getLicenseKey());
        ?>"
                    class="regular-text"
                    <?php 
        if ($licenseIsStored) {
            ?>readonly <?php 
        }
        ?>
                >
                <input type="hidden"
                    name="activation_domain"
                    value="<?php 
        echo esc_attr(Credentials::getActivationDomain());
        ?>"
                >
                <?php 
        wp_nonce_field('mothership_activate_license', '_wpnonce');
        ?>
                <input type="hidden" name="<?php 
        echo esc_attr($pluginId);
        ?>_license_button" value="activate">
                <input type="submit"
                    value="<?php 
        esc_html_e('Activate License', 'caseproof-mothership');
        ?>"
                    class="button button-primary <?php 
        echo esc_attr($pluginId);
        ?>-button-activate"
                    <?php 
        if ($licenseIsStored) {
            ?>disabled<?php 
        }
        ?>
                >
            </div>
        </form>
        <?php 
        return \ob_get_clean();
    }
    /**
     * Generates the HTML for the disconnect form.
     */
    public function generateDisconnectForm() : string
    {
        $pluginId = self::getContainer()->get(AbstractPluginConnection::class)->pluginId;
        \ob_start();
        ?>
        <form method="post" action="" name="<?php 
        echo esc_attr($pluginId);
        ?>_deactivate_license_form">
            <div class="<?php 
        echo esc_attr($pluginId);
        ?>-licence-div-form">
                <label for="license_key"><?php 
        esc_html_e('License Key: ', 'caseproof-mothership');
        ?></label>
                <input name="license_key"
                    type="text"
                    readonly
                    id="license_key"
                    value="<?php 
        echo esc_attr(Credentials::getLicenseKey());
        ?>"
                    class="regular-text"
                >
                <input type="hidden"
                    name="activation_domain"
                    value="<?php 
        echo esc_attr(Credentials::getActivationDomain());
        ?>"
                >
                <?php 
        wp_nonce_field('mothership_deactivate_license', '_wpnonce');
        ?>
                <input type="hidden" name="<?php 
        echo esc_attr($pluginId);
        ?>_license_button" value="deactivate">
                <input
                    type="submit"
                    value="<?php 
        esc_html_e('Deactivate License', 'caseproof-mothership');
        ?>"
                    class="button button-secondary <?php 
        echo esc_attr($pluginId);
        ?>-button-deactivate"
                >
            </div>
        </form>
        <?php 
        return \ob_get_clean();
    }
    /**
     * Activates the license.
     *
     * @param  string $licenseKey The license key.
     * @param  string $domain     The domain.
     * @throws \Exception If the license activation fails.
     */
    public static function activateLicense(string $licenseKey, string $domain) : void
    {
        // Check if the user has the necessary capabilities.
        if (!current_user_can('manage_options')) {
            throw new \Exception(esc_html__('Insufficient permissions', 'caseproof-mothership'));
        }
        // Check if the nonce is valid.
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'mothership_activate_license')) {
            throw new \Exception(esc_html__('Invalid nonce', 'caseproof-mothership'));
        }
        // Translators: %s is the response error message.
        $errorHtml = esc_html__('License activation failed: %s', 'caseproof-mothership');
        try {
            $product = self::getContainer()->get(AbstractPluginConnection::class)->productId;
            $response = LicenseActivations::activate($product, $licenseKey, $domain);
        } catch (\Exception $e) {
            throw new \Exception(\sprintf($errorHtml, esc_html($e->getMessage())));
        }
        if ($response instanceof Response && $response->isError()) {
            throw new \Exception(\sprintf($errorHtml, esc_html($response->error)));
        }
        if ($response instanceof Response && !$response->isError()) {
            try {
                Credentials::storeLicenseKey($licenseKey);
                self::getContainer()->get(AbstractPluginConnection::class)->updateLicenseActivationStatus(\true);
            } catch (\Exception $e) {
                throw new \Exception(\sprintf($errorHtml, esc_html($e->getMessage())));
            }
        }
    }
    /**
     * Deactivates the license.
     *
     * @param  string $licenseKey The license key.
     * @param  string $domain     The domain.
     * @throws \Exception If the license deactivation fails.
     */
    public static function deactivateLicense(string $licenseKey, string $domain) : void
    {
        // Translators: %s is the error message.
        $errorHtml = esc_html__('License deactivation failed: %s', 'caseproof-mothership');
        $pluginId = self::getContainer()->get(AbstractPluginConnection::class)->pluginId;
        // Check if the user has the necessary capabilities.
        if (!current_user_can('manage_options')) {
            throw new \Exception(\sprintf($errorHtml, esc_html__('Insufficient permissions', 'caseproof-mothership')));
        }
        // Check if the nonce is valid.
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'mothership_deactivate_license')) {
            throw new \Exception(\sprintf($errorHtml, esc_html__('Invalid nonce', 'caseproof-mothership')));
        }
        try {
            $response = LicenseActivations::deactivate($licenseKey, $domain);
        } catch (\Exception $e) {
            throw new \Exception(\sprintf($errorHtml, esc_html($e->getMessage())));
        }
        if ($response->isError()) {
            throw new \Exception(\sprintf($errorHtml, esc_html($response->error)));
        }
        try {
            Credentials::storeLicenseKey('');
            // Delete the add-ons transient.
            delete_transient($pluginId . '-mosh-products');
            self::getContainer()->get(AbstractPluginConnection::class)->updateLicenseActivationStatus(\false);
        } catch (\Exception $e) {
            throw new \Exception(\sprintf($errorHtml, esc_html($e->getMessage())));
        }
    }
}
