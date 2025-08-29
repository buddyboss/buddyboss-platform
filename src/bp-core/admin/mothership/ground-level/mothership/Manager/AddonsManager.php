<?php

declare(strict_types=1);

namespace GroundLevel\Mothership\Manager;

use GroundLevel\Mothership\Api\Request\Products;
use GroundLevel\Mothership\Api\Response;
use GroundLevel\Mothership\Manager\AddonInstallSkin;
use GroundLevel\Mothership\Service as MothershipService;
use GroundLevel\Container\Contracts\StaticContainerAwareness;
use GroundLevel\Container\Concerns\HasStaticContainer;

/**
 * The Addons class fetches the available add-ons and integrates with the WP plugin installation API.
 */
class AddonsManager implements StaticContainerAwareness
{
    use HasStaticContainer;

    /**
     * Load the hooks for the add-ons.
     *
     * @return void
     */
    public static function loadHooks(): void
    {
        add_action('wp_ajax_mosh_addon_activate', [self::class, 'ajaxAddonActivate']);
        add_action('wp_ajax_mosh_addon_deactivate', [self::class, 'ajaxAddonDeactivate']);
        add_action('wp_ajax_mosh_addon_install', [self::class, 'ajaxAddonInstall']);
        add_filter('site_transient_update_plugins', [self::class, 'addonsUpdatePlugins']);
    }

    /**
     * Update the plugins transient with the available add-ons.
     *
     * @param  object $transient The plugins transient.
     * @return object The plugins transient.
     */
    public static function addonsUpdatePlugins($transient)
    {
        // If the license key is not set, return the transient.
        if (! self::getContainer()->get(MothershipService::CONNECTION_PLUGIN_SERVICE_ID)->getLicenseKey()) {
            return $transient;
        }

        // If the license is not active, return the transient.
        if (! self::getContainer()->get(MothershipService::CONNECTION_PLUGIN_SERVICE_ID)->getLicenseActivationStatus()) {
            return $transient;
        }

        $transientCheck = self::checkAddonsUpdateTransient();
        if ($transientCheck !== false) {
            $productsTransient = get_transient(self::getContainer()->get(MothershipService::CONNECTION_PLUGIN_SERVICE_ID)->pluginId . '-mosh-products');
            return ($productsTransient->products ?? false) ? self::getTransientWithAddonsUpdates($productsTransient->products, $transient) : $transient;
        }

        if (! is_object($transient)) {
            return $transient;
        }

        if (! isset($transient->response) || ! is_array($transient->response)) {
            $transient->response = [];
        }

        $products = self::getAddons(false);

        if ($products instanceof Response && $products->isError()) {
            // Set transient to expire in 30 minutes so we don't keep checking..
            set_transient(self::getContainer()->get(MothershipService::CONNECTION_PLUGIN_SERVICE_ID)->pluginId . '-mosh-addons-update-check', null, 30 * MINUTE_IN_SECONDS);
            return $transient;
        }

        if (! is_array($products->products ?? null)) {
            return $transient;
        }

        $transient = self::getTransientWithAddonsUpdates($products->products, $transient);

        // Create a transient that expires every 30 minutes. We only want this to run once every 30 minutes.
        set_transient(
            self::getContainer()->get(MothershipService::CONNECTION_PLUGIN_SERVICE_ID)->pluginId . '-mosh-addons-update-check',
            null,
            30 * MINUTE_IN_SECONDS
        );

        return $transient;
    }

    /**
     * Check if the add-ons update transient is available which expires every 30 minutes.
     *
     * @return boolean True if the transient is available, false otherwise.
     */
    public static function checkAddonsUpdateTransient(): bool
    {
        $updateCheck = get_transient(self::getContainer()->get(MothershipService::CONNECTION_PLUGIN_SERVICE_ID)->pluginId . '-mosh-addons-update-check');
        if ($updateCheck !== false) {
            return true;
        }
        return false;
    }

    /**
     * Returns the modified transient with add-ons updates.
     *
     * @param  array  $products  The products to check.
     * @param  object $transient The transient to update.
     * @return object The modified transient.
     */
    public static function getTransientWithAddonsUpdates(array $products, object $transient)
    {
        foreach ($products ?? [] as $product) {
            if (! isset($transient->checked[$product->main_file])) {
                continue;
            }

            $versionLatest = $product->_embedded->{'version-latest'}->number ?? '';
            $urlLatest     = $product->_embedded->{'version-latest'}->url ?? '';

            $item = (object) [
                'id'          => $product->main_file,
                'slug'        => $product->slug,
                'plugin'      => $product->main_file,
                'new_version' => $versionLatest,
                'package'     => $urlLatest,
                'icons'       => [
                    'png' => $product->image,
                ],
            ];
            if (
                version_compare(
                    $transient->checked[$product->main_file],
                    $versionLatest,
                    '>='
                )
            ) {
                $transient->no_update[$product->main_file] = $item;
            } else {
                $transient->response[$product->main_file] = $item;
            }
        }
        return $transient;
    }

    /**
     * Activate an add-on.
     *
     * @return void
     */
    public static function ajaxAddonActivate(): void
    {
        if (! isset($_POST['plugin'])) {
            wp_send_json_error(esc_html__('Bad request.', 'caseproof-mothership'));
        }

        if (! current_user_can('activate_plugins')) {
            wp_send_json_error(esc_html__('Sorry, you don\'t have permission to do this.', 'caseproof-mothership'));
        }

        if (! check_ajax_referer('mosh_addons', false, false)) {
            wp_send_json_error(esc_html__('Security check failed.', 'caseproof-mothership'));
        }

        $result = activate_plugins(wp_unslash($_POST['plugin']));
        $type   = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'add-on';

        if (is_wp_error($result)) {
            if ($type === 'plugin') {
                wp_send_json_error(
                    esc_html__(
                        'Could not activate plugin. Please activate from the Plugins page manually.',
                        'caseproof-mothership'
                    )
                );
            } else {
                wp_send_json_error(
                    esc_html__(
                        'Could not activate add-on. Please activate from the Plugins page manually.',
                        'caseproof-mothership'
                    )
                );
            }
        }

        if ($type === 'plugin') {
            wp_send_json_success(esc_html__('Plugin activated.', 'caseproof-mothership'));
        } else {
            wp_send_json_success(esc_html__('Add-on activated.', 'caseproof-mothership'));
        }
    }

    /**
     * Deactivate an add-on.
     *
     * @return void
     */
    public static function ajaxAddonDeactivate(): void
    {
        if (! isset($_POST['plugin'])) {
            wp_send_json_error(esc_html__('Bad request.', 'caseproof-mothership'));
        }
        if (! current_user_can('deactivate_plugins')) {
            wp_send_json_error(esc_html__('Sorry, you don\'t have permission to do this.', 'caseproof-mothership'));
        }
        if (! check_ajax_referer('mosh_addons', false, false)) {
            wp_send_json_error(esc_html__('Security check failed.', 'caseproof-mothership'));
        }

        deactivate_plugins(wp_unslash($_POST['plugin']));
        $type = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'add-on';

        if ($type === 'plugin') {
            wp_send_json_success(esc_html__('Plugin deactivated.', 'caseproof-mothership'));
        } else {
            wp_send_json_success(esc_html__('Add-on deactivated.', 'caseproof-mothership'));
        }
    }

    /**
     * Install an add-on.
     *
     * @return void
     */
    public static function ajaxAddonInstall(): void
    {
        if (! isset($_POST['plugin'])) {
            wp_send_json_error(esc_html__('Bad request.', 'caseproof-mothership'));
        }

        if (! current_user_can('install_plugins') || ! current_user_can('activate_plugins')) {
            wp_send_json_error(esc_html__('Sorry, you don\'t have permission to do this.', 'caseproof-mothership'));
        }

        if (! check_ajax_referer('mosh_addons', false, false)) {
            wp_send_json_error(esc_html__('Security check failed.', 'caseproof-mothership'));
        }

        $type = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'add-on';

        if ($type === 'plugin') {
            $error = esc_html__(
                'Could not install plugin. Please download and install manually.',
                'caseproof-mothership'
            );
        } else {
            $error = esc_html__('Could not install add-on.', 'caseproof-mothership');
        }

        // Set the current screen to avoid undefined notices.
        set_current_screen();

        $url = esc_url_raw(
            add_query_arg(
                [],
                admin_url('admin.php')
            )
        );

        $creds = request_filesystem_credentials($url, '', false, false, null);

        // Check for file system permissions.
        if (false === $creds) {
            wp_send_json_error($error);
        }

        if (! WP_Filesystem($creds)) {
            wp_send_json_error($error);
        }

        // We do not need any extra credentials if we have gotten this far, so let's install the plugin.
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        // Do not allow WordPress to search/download translations, as this will break JS output.
        remove_action('upgrader_process_complete', ['Language_Pack_Upgrader', 'async_upgrade'], 20);

        $installer = new \Plugin_Upgrader(new AddonInstallSkin());
        $plugin    = wp_unslash($_POST['plugin']);
        $installer->install($plugin);

        // Flush the cache and return the newly installed plugin basename.
        wp_cache_flush();

        if ($installer->plugin_info()) {
            $pluginBaseName = $installer->plugin_info();

            // Activate the plugin silently.
            $activated = activate_plugin($pluginBaseName);

            if (! is_wp_error($activated)) {
                wp_send_json_success(
                    [
                        'message'   => $type === 'plugin'
                                        ? esc_html__('Plugin installed & activated.', 'caseproof-mothership')
                                        : esc_html__('Add-on installed & activated.', 'caseproof-mothership'),
                        'activated' => true,
                        'basename'  => $pluginBaseName,
                    ]
                );
            } else {
                wp_send_json_success(
                    [
                        'message'   => $type === 'plugin'
                                        ? esc_html__('Plugin installed.', 'caseproof-mothership')
                                        : esc_html__('Add-on installed.', 'caseproof-mothership'),
                        'activated' => false,
                        'basename'  => $pluginBaseName,
                    ]
                );
            }
        }

        wp_send_json_error($error);
    }

    /**
     * Get the add-ons from the API.
     *
     * @param  boolean $cached Whether to use the cached products or not.
     * @return object The add-ons.
     */
    public static function getAddons(bool $cached = false)
    {
        $pluginConnector = self::getContainer()->get(MothershipService::CONNECTION_PLUGIN_SERVICE_ID);
        
        if ($cached) {
            $addons = get_transient($pluginConnector->pluginId . '-mosh-products');
            if (false !== $addons) {
                return $addons;
            }
        }


        $args['_embed'] = 'version-latest';
        $resp           = Products::list($args);

        // Add debugging for response
        if ($resp instanceof Response) {
            if ($resp->isError()) {
                error_log('BuddyBoss Mothership: API Error: ' . $resp->error);
            } else {
                error_log('BuddyBoss Mothership: API Success - Products count: ' . (isset($resp->products) ? count($resp->products) : 0));
            }
        }

        // If the response is not an error, set the transient.
        if ($resp instanceof Response && ! $resp->isError()) {
            set_transient($pluginConnector->pluginId . '-mosh-products', $resp, HOUR_IN_SECONDS);
        }
        return $resp;
    }

    /**
     * Generates and returns the HTML for the add-ons.
     *
     * @return string The HTML for the add-ons.
     */
    public static function generateAddonsHtml(): string
    {
        $pluginConnector = self::getContainer()->get(MothershipService::CONNECTION_PLUGIN_SERVICE_ID);
        
        // Check if license key exists
        if (! $pluginConnector->getLicenseKey()) {
            return '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Please enter your license key to access add-ons.', 'buddyboss') . '</p></div>';
        }

        // Check if license is activated
        if (! $pluginConnector->getLicenseActivationStatus()) {
            return '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Please activate your license to access add-ons.', 'buddyboss') . '</p></div>';
        }

        // Refresh the add-ons if the button is clicked.
        if (isset($_POST['submit-button-mosh-refresh-addon'])) {
            delete_transient($pluginConnector->pluginId . '-mosh-products');
        }

        $addons = self::getAddons(true);
        if ($addons instanceof Response && $addons->isError()) {
            return sprintf(
                '<div class="notice notice-error is-dismissible"><p>%s <b>%s</b></p></div>',
                esc_html__('There was an issue connecting with the API.', 'buddyboss'),
                $addons->error
            );
        }

        // Check if we have products data
        if (! $addons || ! isset($addons->products) || empty($addons->products)) {
            return '<div class="notice notice-info is-dismissible"><p>' . esc_html__('No add-ons available at this time.', 'buddyboss') . '</p></div>';
        }

        self::enqueueAssets();
        ob_start();
        $products = $addons->products;
        include_once __DIR__ . '/../Views/products.php';
        return ob_get_clean();
    }

    /**
     * Enqueues the assets for the add-ons display.
     *
     * @return void
     */
    public static function enqueueAssets(): void
    {
        wp_enqueue_style('dashicons');
        wp_enqueue_script('mosh-addons-js', plugin_dir_url(__FILE__) . '../Assets/addons.js', [], null, true);
        wp_enqueue_style('mosh-addons-css', plugin_dir_url(__FILE__) . '../Assets/addons.css');
        wp_localize_script('mosh-addons-js', 'MoshAddons', [
            'ajax_url'              => admin_url('admin-ajax.php'),
            'nonce'                 => wp_create_nonce('mosh_addons'),
            'active'                => esc_html__('Active', 'caseproof-mothership'),
            'inactive'              => esc_html__('Inactive', 'caseproof-mothership'),
            'activate'              => esc_html__('Activate', 'caseproof-mothership'),
            'deactivate'            => esc_html__('Deactivate', 'caseproof-mothership'),
            'install_failed'        => esc_html__(
                'Could not install add-on. Please download and install manually.',
                'caseproof-mothership'
            ),
            'plugin_install_failed' => esc_html__(
                'Could not install plugin. Please download and install manually.',
                'caseproof-mothership'
            ),
        ]);
    }
}
