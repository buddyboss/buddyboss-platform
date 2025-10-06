<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Manager;

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\Mothership\ExtensionType;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonInstallSkin;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MothershipService;
use BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness;
use BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer;
/**
 * The AddonsManager class fetches the available add-ons and integrates with the WP extension installation API.
 */
class AddonsManager implements StaticContainerAwareness
{
    use HasStaticContainer;
    /**
     * Suffix for the cache key for the Products API response.
     *
     * @var string
     */
    protected const CACHE_KEY_PRODUCTS = '-mosh-products';
    /**
     * Suffix for the cache key for the update check.
     *
     * @var string
     */
    protected const CACHE_KEY_UPDATE_CHECK = '-mosh-addons-update-check';
    /**
     * The duration of the cache for the Products API response, default 60 minutes.
     *
     * @var integer
     */
    protected const CACHE_DURATION_MINUTES = 60;
    /**
     * The duration of the cache for the update check, default 30 minutes.
     *
     * @var integer
     */
    protected const UPDATE_CHECK_DURATION_MINUTES = 30;
    /**
     * The products API client. TODO: Reimplement with proper dependency injection.
     *
     * @var callable
     */
    protected static $productsApiClient = [Products::class, 'list'];
    /**
     * The product object for the AJAX request.
     *
     * @var object|null
     */
    protected static ?object $ajaxProduct = null;
    /**
     * Load the hooks for add-on management.
     */
    public static function loadHooks() : void
    {
        add_action('wp_ajax_mosh_addon_activate', [self::class, 'ajaxAddonActivate']);
        add_action('wp_ajax_mosh_addon_deactivate', [self::class, 'ajaxAddonDeactivate']);
        add_action('wp_ajax_mosh_addon_install', [self::class, 'ajaxAddonInstall']);
        add_filter('site_transient_update_themes', [self::class, 'addonsUpdateThemes']);
        add_filter('site_transient_update_plugins', [self::class, 'addonsUpdatePlugins']);
    }
    /**
     * Update the plugins transient with the available add-ons.
     *
     * @param  mixed $transient The update plugins transient.
     * @return mixed            The modified transient.
     */
    public static function addonsUpdatePlugins($transient)
    {
        return self::updateTransient($transient, ExtensionType::PLUGIN());
    }
    /**
     * Update the themes transient with the available add-ons.
     *
     * @param  mixed $transient The update themes transient.
     * @return mixed            The modified transient.
     */
    public static function addonsUpdateThemes($transient)
    {
        return self::updateTransient($transient, ExtensionType::THEME());
    }
    /**
     * Update the transient with the available add-ons.
     *
     * @param  mixed         $transient     The transient to update.
     * @param  ExtensionType $extensionType The extension type being updated.
     * @return mixed                        The modified transient.
     */
    protected static function updateTransient($transient, ExtensionType $extensionType)
    {
        if (!self::hasActiveLicense() || !\is_object($transient)) {
            return $transient;
        }
        if (!isset($transient->response) || !\is_array($transient->response)) {
            $transient->response = [];
        }
        $response = self::getAddons(\true);
        if ($response instanceof Response && $response->isError()) {
            return $transient;
        }
        $products = self::filterProductsByExtensionType($response->products ?? [], $extensionType);
        return !empty($products) ? self::injectUpdates($products, $transient, $extensionType) : $transient;
    }
    /**
     * Returns the modified update transient with add-on updates.
     *
     * @param  array         $products      The products to check.
     * @param  mixed         $transient     The transient to update.
     * @param  ExtensionType $extensionType The extension type to filter by.
     * @return mixed                        The modified transient.
     */
    protected static function injectUpdates(array $products, $transient, ExtensionType $extensionType)
    {
        foreach ($products ?? [] as $product) {
            $mainFile = $product->main_file ?? '';
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
            $versionLatest = $product->_embedded->{'version-latest'}->number ?? '';
            $urlLatest = $product->_embedded->{'version-latest'}->url ?? '';
            $item = null;
            if (empty($mainFile) || empty($versionLatest) || empty($urlLatest) || !isset($transient->checked[$mainFile])) {
                continue;
            }
            if ($extensionType->equals(ExtensionType::PLUGIN(), \false)) {
                $item = (object) ['id' => $mainFile, 'slug' => \dirname($mainFile), 'plugin' => $mainFile, 'new_version' => $versionLatest, 'package' => $urlLatest, 'url' => '', 'tested' => '', 'requires_php' => '', 'icons' => ['2x' => $product->image, '1x' => $product->image]];
            } elseif ($extensionType->equals(ExtensionType::THEME(), \false)) {
                $item = ['theme' => $mainFile, 'new_version' => $versionLatest, 'package' => $urlLatest, 'url' => '', 'requires' => '', 'requires_php' => '', 'icons' => ['2x' => $product->image, '1x' => $product->image]];
            }
            if (!\is_null($item)) {
                if (\version_compare($transient->checked[$mainFile], $versionLatest, '>=')) {
                    $transient->no_update[$mainFile] = $item;
                    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- WordPress data structure.
                } else {
                    $transient->response[$mainFile] = $item;
                }
            }
        }
        return $transient;
    }
    /**
     * Get the add-ons from the API.
     *
     * @param  boolean $cached Whether to use the cached products or not.
     * @return object                     The add-ons.
     */
    public static function getAddons(bool $cached = \false)
    {
        if ($cached && self::cachedResponseIsValid()) {
            $response = self::getCachedApiResponse();
            if (\is_object($response)) {
                return $response;
            }
        }
        $args['_embed'] = 'version-latest';
        $response = \call_user_func(self::$productsApiClient, $args);
        if ($response instanceof Response && !$response->isError()) {
            set_transient(self::getContainer()->get(AbstractPluginConnection::class)->pluginId . self::CACHE_KEY_PRODUCTS, $response, self::CACHE_DURATION_MINUTES * MINUTE_IN_SECONDS);
            self::markCacheRefreshed();
        }
        return $response;
    }
    /**
     * Get the cached API response.
     *
     * @return mixed The cached API response, or false if unavailable.
     */
    protected static function getCachedApiResponse()
    {
        $response = get_transient(self::getContainer()->get(AbstractPluginConnection::class)->pluginId . self::CACHE_KEY_PRODUCTS);
        return $response;
    }
    /**
     * Check if the cached API response is valid.
     *
     * @return boolean
     */
    protected static function cachedResponseIsValid() : bool
    {
        $updateCheckTransient = get_transient(self::getContainer()->get(AbstractPluginConnection::class)->pluginId . self::CACHE_KEY_UPDATE_CHECK);
        return \false !== $updateCheckTransient;
    }
    /**
     * Mark the cached API response valid for a designated period.
     *
     * @param  integer $minutes Optionally set the duration of the cache, default 30 minutes.
     * @return boolean          True if the transient was set, false otherwise.
     */
    protected static function markCacheRefreshed(int $minutes = 30) : bool
    {
        return set_transient(self::getContainer()->get(AbstractPluginConnection::class)->pluginId . self::CACHE_KEY_UPDATE_CHECK, null, $minutes * MINUTE_IN_SECONDS);
    }
    /**
     * Check if the license for the product exists and is active.
     *
     * @return boolean
     */
    protected static function hasActiveLicense() : bool
    {
        return self::getContainer()->get(AbstractPluginConnection::class)->getLicenseKey() && self::getContainer()->get(AbstractPluginConnection::class)->getLicenseActivationStatus();
    }
    /**
     * Filter products by extension type.
     *
     * @param  array         $products      The products to filter.
     * @param  ExtensionType $extensionType The extension type to filter by.
     * @return array
     */
    protected static function filterProductsByExtensionType(array $products, ExtensionType $extensionType) : array
    {
        return \array_values(\array_filter($products, function ($product) use($extensionType) {
            return ($product->extension_type ?? \false) === $extensionType->getValue();
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
        }));
    }
    /**
     * Get a product by slug.
     *
     * @param  string $slug The slug of the product to get.
     * @return object|null The product, or null if not found.
     */
    protected static function getProductBySlug(string $slug) : ?object
    {
        $apiResponse = self::getAddons(\true);
        $result = null;
        foreach ($apiResponse->products ?? [] as $product) {
            if (!empty($product->slug) && $product->slug === $slug) {
                $result = $product;
                break;
            }
        }
        return $result;
    }
    /**
     * Setup an AJAX request. All requests setup the same way: validate the nonce
     * and slug, get a product using the slug, and set the static class property.
     *
     * @return void
     */
    protected static function setupAjaxRequest() : void
    {
        if (!check_ajax_referer('mosh_addons', \false, \false)) {
            wp_send_json_error(new \WP_Error('security_check_failed', esc_html__('Security check failed.', 'caseproof-mothership')));
        }
        if (empty($_POST['slug']) || !\is_string($_POST['slug'])) {
            wp_send_json_error(new \WP_Error('bad_request', esc_html__('Bad request.', 'caseproof-mothership')));
        }
        self::$ajaxProduct = self::getProductBySlug($_POST['slug']);
        if (!self::$ajaxProduct) {
            wp_send_json_error(new \WP_Error('addon_not_found', esc_html__('Add-on not found.', 'caseproof-mothership')));
        }
    }
    /**
     * Activate an add-on.
     *
     * @return void
     */
    public static function ajaxAddonActivate() : void
    {
        self::setupAjaxRequest();
        $extensionType = self::$ajaxProduct->extension_type ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
        $hasPermissions = \false;
        if (ExtensionType::PLUGIN === $extensionType) {
            $hasPermissions = current_user_can('activate_plugins');
        } elseif (ExtensionType::THEME === $extensionType) {
            $hasPermissions = current_user_can('switch_themes');
        } else {
            wp_send_json_error(new \WP_Error('invalid_addon_type', esc_html__('Invalid add-on type.', 'caseproof-mothership')));
        }
        if (!$hasPermissions) {
            wp_send_json_error(new \WP_Error('insufficient_permissions', esc_html__('Sorry, you don\'t have permission activate addons.', 'caseproof-mothership')));
        }
        $mainFile = self::$ajaxProduct->main_file ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
        $activated = \false;
        if ($mainFile) {
            if (ExtensionType::PLUGIN === $extensionType) {
                $activated = activate_plugin($mainFile);
                $activated = \is_null($activated) ? \true : $activated;
            } else {
                switch_theme($mainFile);
                $activated = \true;
            }
        }
        if ($activated && !is_wp_error($activated)) {
            $successMsg = ExtensionType::PLUGIN === $extensionType ? esc_html__('Plugin activated.', 'caseproof-mothership') : esc_html__('Theme activated.', 'caseproof-mothership');
            wp_send_json_success($successMsg);
        } else {
            wp_send_json_error(new \WP_Error('activation_failed', esc_html__('The add-on could not be activated.', 'caseproof-mothership')));
        }
    }
    /**
     * Deactivate an add-on.
     *
     * @return void
     */
    public static function ajaxAddonDeactivate() : void
    {
        self::setupAjaxRequest();
        $extensionType = self::$ajaxProduct->extension_type ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
        $hasPermissions = \false;
        if (ExtensionType::PLUGIN === $extensionType) {
            $hasPermissions = current_user_can('deactivate_plugins');
        } elseif (ExtensionType::THEME === $extensionType) {
            wp_send_json_error(new \WP_Error('invalid_addon_type', esc_html__('Themes cannot be deactivated. Activate a new theme instead.', 'caseproof-mothership')));
        } else {
            wp_send_json_error(new \WP_Error('invalid_addon_type', esc_html__('Invalid add-on type.', 'caseproof-mothership')));
        }
        if (!$hasPermissions) {
            wp_send_json_error(new \WP_Error('insufficient_permissions', esc_html__('Sorry, you don\'t have permission deactivate addons.', 'caseproof-mothership')));
        }
        $mainFile = self::$ajaxProduct->main_file ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
        if (!$mainFile) {
            wp_send_json_error(new \WP_Error('deactivation_failed', esc_html__('The add-on could not be deactivated.', 'caseproof-mothership')));
        }
        deactivate_plugins($mainFile);
        $successMsg = ExtensionType::PLUGIN === $extensionType ? esc_html__('Plugin deactivated.', 'caseproof-mothership') : esc_html__('Add-on deactivated.', 'caseproof-mothership');
        wp_send_json_success($successMsg);
    }
    /**
     * Install an add-on.
     *
     * @return void
     */
    public static function ajaxAddonInstall() : void
    {
        self::setupAjaxRequest();
        $extensionType = self::$ajaxProduct->extension_type ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
        $hasPermissions = \false;
        if (ExtensionType::PLUGIN === $extensionType) {
            $hasPermissions = current_user_can('install_plugins') && current_user_can('activate_plugins');
        } elseif (ExtensionType::THEME === $extensionType) {
            $hasPermissions = current_user_can('install_themes') && current_user_can('switch_themes');
        } else {
            wp_send_json_error(new \WP_Error('invalid_addon_type', esc_html__('Invalid add-on type.', 'caseproof-mothership')));
        }
        if (!$hasPermissions) {
            wp_send_json_error(new \WP_Error('insufficient_permissions', esc_html__('Sorry, you don\'t have permission install addons.', 'caseproof-mothership')));
        }
        set_current_screen();
        $creds = request_filesystem_credentials(admin_url('admin.php'), '', \false, \false, null);
        if (\false === $creds || !\WP_Filesystem($creds)) {
            wp_send_json_error(new \WP_Error('insufficient_permissions', esc_html__('Sorry, you don\'t have permission install addons.', 'caseproof-mothership')));
        }
        require_once \ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        remove_action('upgrader_process_complete', ['Language_Pack_Upgrader', 'async_upgrade'], 20);
        if (ExtensionType::PLUGIN === $extensionType) {
            require_once \ABSPATH . 'wp-admin/includes/plugin.php';
            $installer = new \Plugin_Upgrader(new AddonInstallSkin());
        } else {
            require_once \ABSPATH . 'wp-admin/includes/theme.php';
            $installer = new \Theme_Upgrader(new AddonInstallSkin());
        }
        $addonUrl = self::$ajaxProduct->_embedded->{'version-latest'}->url ?? '';
        if (!\filter_var($addonUrl, \FILTER_VALIDATE_URL)) {
            wp_send_json_error(new \WP_Error('invalid_addon_url', esc_html__('Invalid add-on URL.', 'caseproof-mothership')));
        }
        $installed = $installer->install($addonUrl);
        if (!$installed || is_wp_error($installed)) {
            wp_send_json_error(new \WP_Error('addon_install_failed', esc_html__('The add-on was not installed successfully.', 'caseproof-mothership')));
        }
        wp_cache_flush();
        $activated = \false;
        if (ExtensionType::PLUGIN === $extensionType) {
            $baseName = $installer->plugin_info();
            $activated = $baseName ? \is_null(activate_plugin($baseName)) : $activated;
        } else {
            $themeInfo = $installer->theme_info();
            if ($themeInfo instanceof \WP_Theme) {
                $baseName = $themeInfo->get_stylesheet();
                if ($baseName) {
                    switch_theme($baseName);
                    $activated = get_stylesheet() === $baseName;
                }
            }
        }
        if ($activated) {
            wp_send_json_success(['message' => $extensionType === ExtensionType::PLUGIN ? esc_html__('Plugin installed and activated.', 'caseproof-mothership') : esc_html__('Theme installed and activated.', 'caseproof-mothership'), 'activated' => \true]);
        } else {
            wp_send_json_success(['message' => $extensionType === ExtensionType::PLUGIN ? esc_html__('Plugin installed.', 'caseproof-mothership') : esc_html__('Theme installed.', 'caseproof-mothership'), 'activated' => \false]);
        }
    }
    /**
     * Generates and returns the HTML for the add-ons.
     *
     * @return string The HTML for the add-ons.
     */
    public static function generateAddonsHtml() : string
    {
        if (!self::getContainer()->get(AbstractPluginConnection::class)->getLicenseKey()) {
            return '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Please enter your license key to access add-ons.', 'caseproof-mothership') . '</p></div>';
        }
        // Refresh the add-ons if the button is clicked.
        if (isset($_POST['submit-button-mosh-refresh-addon'])) {
            delete_transient(self::getContainer()->get(AbstractPluginConnection::class)->pluginId . self::CACHE_KEY_PRODUCTS);
        }
        $addons = self::getAddons(\true);
        if ($addons instanceof Response && $addons->isError()) {
            return \sprintf('<div class=""><p>%s <b>%s</b></p></div>', esc_html__('There was an issue connecting with the API.', 'caseproof-mothership'), $addons->error);
        }
        self::enqueueAssets();
        \ob_start();
        $products = self::prepareProductsForDisplay($addons->products ?? []);
        include_once __DIR__ . '/../Views/products.php';
        return \ob_get_clean();
    }
    /**
     * Prepare the addons for display. Remove parent products and any addons that may be missing
     * required data, and add data for installation status, text, and icon class.
     *
     * @param  array $products The products to prepare. Each product is a StdClass object.
     * @return array           The prepared products.
     */
    protected static function prepareProductsForDisplay(array $products) : array
    {
        $products = \array_values(\array_filter($products, function ($product) {
            $isAddon = !empty($product->type) && 'addon' === $product->type;
            $hasMainFile = !empty($product->main_file);
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
            $hasExtensionType = !empty($product->extension_type);
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
            return $isAddon && $hasMainFile && $hasExtensionType;
        }));
        $pluginUpdates = get_site_transient('update_plugins');
        $themeUpdates = get_site_transient('update_themes');
        foreach ($products as $product) {
            $mainFile = $product->main_file ?? \false;
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
            $extensionType = $product->extension_type ?? \false;
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
            if (ExtensionType::PLUGIN === $extensionType) {
                $installed = \is_dir(\WP_PLUGIN_DIR . '/' . \dirname($mainFile));
                $active = is_plugin_active($mainFile);
                // TODO: Add JS for update handling, set this using isset($pluginUpdates->response[$mainFile]).
                $product->updateAvailable = \false;
            } else {
                $theme = wp_get_theme($mainFile);
                $installed = $theme->exists();
                $active = get_stylesheet() === $mainFile;
                // TODO: Add JS for update handling, set this using isset($themeUpdates->response[$mainFile]).
                $product->updateAvailable = \false;
            }
            if ($installed && $active) {
                $product->status = 'active';
                $product->statusLabel = esc_html__('Active', 'caseproof-mothership');
                if (ExtensionType::PLUGIN === $extensionType) {
                    $product->iconClass = 'dashicons dashicons-no-alt';
                    $product->buttonLabel = esc_html__('Deactivate', 'caseproof-mothership');
                } else {
                    $product->iconClass = 'dashicons dashicons-admin-appearance';
                    $product->buttonLabel = esc_html__('Switch Themes', 'caseproof-mothership');
                }
            } elseif (!$installed) {
                $product->status = 'not-installed';
                $product->iconClass = 'dashicons dashicons-download';
                $product->statusLabel = esc_html__('Not Installed', 'caseproof-mothership');
                $product->buttonLabel = esc_html__('Install Add-on', 'caseproof-mothership');
            } else {
                $product->status = 'inactive';
                $product->iconClass = 'dashicons dashicons-yes-alt';
                $product->statusLabel = esc_html__('Inactive', 'caseproof-mothership');
                $product->buttonLabel = esc_html__('Activate', 'caseproof-mothership');
            }
        }
        return $products;
    }
    /**
     * Enqueues the assets for the add-ons display.
     *
     * @return void
     */
    public static function enqueueAssets() : void
    {
        wp_enqueue_style('dashicons');
        wp_enqueue_script('mosh-addons-js', plugin_dir_url(__FILE__) . '../assets/addons.js', [], null, \true);
        wp_enqueue_style('mosh-addons-css', plugin_dir_url(__FILE__) . '../assets/addons.css');
        wp_localize_script('mosh-addons-js', 'MoshAddons', ['ajax_url' => admin_url('admin-ajax.php'), 'themes_url' => admin_url('themes.php'), 'nonce' => wp_create_nonce('mosh_addons'), 'active' => esc_html__('Active', 'caseproof-mothership'), 'inactive' => esc_html__('Inactive', 'caseproof-mothership'), 'activate' => esc_html__('Activate', 'caseproof-mothership'), 'deactivate' => esc_html__('Deactivate', 'caseproof-mothership'), 'switch_themes' => esc_html__('Switch Themes', 'caseproof-mothership'), 'processing' => esc_html__('Processing...', 'caseproof-mothership'), 'install_failed' => esc_html__('Could not install theme. Please download and install manually.', 'caseproof-mothership'), 'plugin_install_failed' => esc_html__('Could not install plugin. Please download and install manually.', 'caseproof-mothership')]);
    }
}
