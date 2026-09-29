<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Manager;

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;
use BuddyBossPlatform\GroundLevel\Mothership\Credentials;
use BuddyBossPlatform\GroundLevel\Mothership\ExtensionType;
use BuddyBossPlatform\GroundLevel\Mothership\Util;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
use BuddyBossPlatform\GroundLevel\Support\View;
/**
 * The AddonsManager class fetches the available add-ons and integrates with the WP extension installation API.
 */
class AddonsManager
{
    use Hookable;
    /**
     * Suffix for the cache key for the licensed add-ons response.
     *
     * @var string
     */
    protected const CACHE_KEY_ADDONS = '-mosh-addons';
    /**
     * Cache TTL in minutes for a successful add-ons response.
     *
     * @var integer
     */
    protected const CACHE_TTL_MINUTES = 60;
    /**
     * Cache TTL in minutes for an errored add-ons response (debounces retries).
     *
     * @var integer
     */
    protected const ERROR_TTL_MINUTES = 5;
    /**
     * Page size for the bulk products list.
     *
     * @var integer
     */
    protected const PER_PAGE = 100;
    /**
     * The plugin connection.
     *
     * @var AbstractPluginConnection
     */
    private AbstractPluginConnection $plugin;
    /**
     * The credentials instance.
     *
     * @var Credentials
     */
    private Credentials $credentials;
    /**
     * The products API.
     *
     * @var Products
     */
    private Products $products;
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
     * The product object for the AJAX request.
     *
     * @var object|null
     */
    protected ?object $ajaxProduct = null;
    /**
     * Constructor.
     *
     * @param AbstractPluginConnection $plugin      The plugin connection.
     * @param Credentials              $credentials The credentials instance.
     * @param Products                 $products    The products API.
     * @param View                     $view        The view instance for rendering templates.
     * @param Util                     $util        The Mothership utility instance.
     */
    public function __construct(AbstractPluginConnection $plugin, Credentials $credentials, Products $products, View $view, Util $util)
    {
        $this->plugin = $plugin;
        $this->credentials = $credentials;
        $this->products = $products;
        $this->view = $view;
        $this->util = $util;
    }
    /**
     * Configure WordPress hooks.
     *
     * @return array<Hook>
     */
    protected function configureHooks() : array
    {
        return [new Hook(Hook::TYPE_ACTION, "wp_ajax_{$this->plugin->pluginId}_addon_activate", [$this, 'ajaxAddonActivate']), new Hook(Hook::TYPE_ACTION, "wp_ajax_{$this->plugin->pluginId}_addon_deactivate", [$this, 'ajaxAddonDeactivate']), new Hook(Hook::TYPE_ACTION, "wp_ajax_{$this->plugin->pluginId}_addon_install", [$this, 'ajaxAddonInstall'])];
    }
    /**
     * Returns the connected product's add-ons.
     *
     * Cached for {@see self::CACHE_TTL_MINUTES} minutes on success. On error the last cached
     * list (or an empty list) is kept for {@see self::ERROR_TTL_MINUTES} minutes.
     *
     * @param  boolean $cached Whether to use the cached add-ons list.
     * @return array<object> List of add-on product objects.
     */
    public function getAddons(bool $cached = \false) : array
    {
        $cacheKey = $this->plugin->pluginId . self::CACHE_KEY_ADDONS;
        $cachedAddons = get_transient($cacheKey);
        if ($cached && \is_array($cachedAddons)) {
            return $cachedAddons;
        }
        $addons = $this->fetchAddons();
        if (null === $addons) {
            // On error, keep any previously cached list.
            $addons = \is_array($cachedAddons) ? $cachedAddons : [];
            set_transient($cacheKey, $addons, self::ERROR_TTL_MINUTES * MINUTE_IN_SECONDS);
            return $addons;
        }
        set_transient($cacheKey, $addons, self::CACHE_TTL_MINUTES * MINUTE_IN_SECONDS);
        return $addons;
    }
    /**
     * Fetches the connected product's add-ons from the API.
     *
     * Each add-on object exposes the latest version on a `version` property.
     * Add-ons that require a license upgrade are typed `upgrade-addon` and listed last.
     *
     * @return array<object>|null The add-ons, or null on a fetch error.
     */
    private function fetchAddons() : ?array
    {
        $response = $this->products->getRelations($this->plugin->productId, ['_embed' => 'version-latest', 'per_page' => self::PER_PAGE]);
        $products = [];
        while (\true) {
            if ($response->isError()) {
                return null;
            }
            $products = \array_merge($products, $response->getData('products', []));
            if (!$response->hasNext()) {
                break;
            }
            $next = $response->next();
            if (null === $next) {
                break;
            }
            $response = $next;
        }
        $addons = [];
        $upgrades = [];
        foreach ($products as $product) {
            if ('addon' !== $product->type) {
                continue;
            }
            $version = $product->_embedded->{'version-latest'} ?? null;
            $product->version = isset($version, $version->product) ? $version : null;
            unset($product->_embedded->{'version-latest'});
            if ($product->version) {
                $addons[] = $product;
            } else {
                $product->type = 'upgrade-addon';
                $upgrades[] = $product;
            }
        }
        return \array_merge($addons, $upgrades);
    }
    /**
     * Clears the cached add-ons response.
     *
     * @return void
     */
    public function clearCache() : void
    {
        delete_transient($this->plugin->pluginId . self::CACHE_KEY_ADDONS);
    }
    /**
     * Gets an add-on by slug.
     *
     * @param string  $slug   The slug of the add-on to get.
     * @param boolean $cached Whether to look up in the cached add-ons. Default true.
     *
     * @return object|null The add-on object, or null if not found.
     */
    public function getAddon(string $slug, bool $cached = \true) : ?object
    {
        foreach ($this->getAddons($cached) as $addon) {
            if ($slug === $addon->slug) {
                return $addon;
            }
        }
        return null;
    }
    /**
     * Setup an AJAX request. All requests setup the same way: validate the nonce
     * and slug, get a product using the slug, and set the instance property.
     *
     * @return void
     */
    protected function setupAjaxRequest() : void
    {
        if (!check_ajax_referer('mosh_addons', \false, \false)) {
            wp_send_json_error(new \WP_Error('security_check_failed', esc_html__('Security check failed.', 'ground-level')));
        }
        if (empty($_POST['slug']) || !\is_string($_POST['slug'])) {
            wp_send_json_error(new \WP_Error('bad_request', esc_html__('Bad request.', 'ground-level')));
        }
        $slug = sanitize_text_field(wp_unslash($_POST['slug'] ?? ''));
        $this->ajaxProduct = $this->getAddon($slug);
        if (!$this->ajaxProduct) {
            wp_send_json_error(new \WP_Error('addon_not_found', esc_html__('Add-on not found.', 'ground-level')));
        }
        // Upgrade add-ons cannot be installed or managed here.
        if ('upgrade-addon' === $this->ajaxProduct->type) {
            wp_send_json_error(new \WP_Error('upgrade_required', esc_html__('This add-on requires a license upgrade.', 'ground-level')));
        }
    }
    /**
     * Activate an add-on.
     *
     * @return void
     */
    public function ajaxAddonActivate() : void
    {
        $this->setupAjaxRequest();
        $extensionType = $this->ajaxProduct->extension_type ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
        $hasPermissions = \false;
        if (ExtensionType::PLUGIN === $extensionType) {
            $hasPermissions = current_user_can('activate_plugins');
        } elseif (ExtensionType::THEME === $extensionType) {
            $hasPermissions = current_user_can('switch_themes');
        } else {
            wp_send_json_error(new \WP_Error('invalid_addon_type', esc_html__('Invalid add-on type.', 'ground-level')));
        }
        if (!$hasPermissions) {
            wp_send_json_error(new \WP_Error('insufficient_permissions', esc_html__("Sorry, you don't have the necessary permission to perform this action.", 'ground-level')));
        }
        $mainFile = $this->ajaxProduct->main_file ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
        $activated = \false;
        if ($mainFile) {
            if (ExtensionType::PLUGIN === $extensionType) {
                $activated = activate_plugin($mainFile);
                $activated = \is_null($activated) ? \true : $activated;
            } else {
                switch_theme(\dirname($mainFile));
                $activated = \true;
            }
        }
        if ($activated && !is_wp_error($activated)) {
            $successMsg = ExtensionType::PLUGIN === $extensionType ? esc_html__('Plugin activated.', 'ground-level') : esc_html__('Theme activated.', 'ground-level');
            wp_send_json_success($successMsg);
        } else {
            wp_send_json_error(new \WP_Error('activation_failed', esc_html__('The add-on could not be activated.', 'ground-level')));
        }
    }
    /**
     * Deactivate an add-on.
     *
     * @return void
     */
    public function ajaxAddonDeactivate() : void
    {
        $this->setupAjaxRequest();
        $extensionType = $this->ajaxProduct->extension_type ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
        $hasPermissions = \false;
        if (ExtensionType::PLUGIN === $extensionType) {
            $hasPermissions = current_user_can('deactivate_plugins');
        } elseif (ExtensionType::THEME === $extensionType) {
            wp_send_json_error(new \WP_Error('invalid_addon_type', esc_html__('Themes cannot be deactivated. Activate a new theme instead.', 'ground-level')));
        } else {
            wp_send_json_error(new \WP_Error('invalid_addon_type', esc_html__('Invalid add-on type.', 'ground-level')));
        }
        if (!$hasPermissions) {
            wp_send_json_error(new \WP_Error('insufficient_permissions', esc_html__("Sorry, you don't have permission to deactivate addons.", 'ground-level')));
        }
        $mainFile = $this->ajaxProduct->main_file ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
        if (!$mainFile) {
            wp_send_json_error(new \WP_Error('deactivation_failed', esc_html__('The add-on could not be deactivated.', 'ground-level')));
        }
        deactivate_plugins($mainFile);
        $successMsg = ExtensionType::PLUGIN === $extensionType ? esc_html__('Plugin deactivated.', 'ground-level') : esc_html__('Add-on deactivated.', 'ground-level');
        wp_send_json_success($successMsg);
    }
    /**
     * Install an add-on.
     *
     * @return void
     */
    public function ajaxAddonInstall() : void
    {
        $this->setupAjaxRequest();
        $extensionType = $this->ajaxProduct->extension_type ?? \false;
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
        $hasPermissions = \false;
        if (ExtensionType::PLUGIN === $extensionType) {
            $hasPermissions = current_user_can('install_plugins') && current_user_can('activate_plugins');
        } elseif (ExtensionType::THEME === $extensionType) {
            $hasPermissions = current_user_can('install_themes') && current_user_can('switch_themes');
        } else {
            wp_send_json_error(new \WP_Error('invalid_addon_type', esc_html__('Invalid add-on type.', 'ground-level')));
        }
        if (!$hasPermissions) {
            wp_send_json_error(new \WP_Error('insufficient_permissions', esc_html__("Sorry, you don't have permission to install addons.", 'ground-level')));
        }
        set_current_screen();
        $creds = request_filesystem_credentials(admin_url('admin.php'), '', \false, \false, null);
        if (\false === $creds || !\WP_Filesystem($creds)) {
            wp_send_json_error(new \WP_Error('insufficient_permissions', esc_html__("Sorry, you don't have permission to install addons.", 'ground-level')));
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
        $addonUrl = $this->ajaxProduct->version->url ?? '';
        if (!$this->util->isAllowedDownloadUrl($addonUrl)) {
            wp_send_json_error(new \WP_Error('invalid_addon_url', esc_html__('Invalid add-on URL.', 'ground-level')));
        }
        $installed = $installer->install($addonUrl);
        if (!$installed || is_wp_error($installed)) {
            wp_send_json_error(new \WP_Error('addon_install_failed', esc_html__('The add-on was not installed successfully.', 'ground-level')));
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
            wp_send_json_success(['message' => $extensionType === ExtensionType::PLUGIN ? esc_html__('Plugin installed and activated.', 'ground-level') : esc_html__('Theme installed and activated.', 'ground-level'), 'activated' => \true]);
        } else {
            wp_send_json_success(['message' => $extensionType === ExtensionType::PLUGIN ? esc_html__('Plugin installed.', 'ground-level') : esc_html__('Theme installed.', 'ground-level'), 'activated' => \false]);
        }
    }
    /**
     * Generates and returns the HTML for the add-ons.
     *
     * @return string The HTML for the add-ons.
     */
    public function generateAddonsHtml() : string
    {
        if (!$this->credentials->getLicenseKey()) {
            return '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Please enter your license key to access add-ons.', 'ground-level') . '</p></div>';
        }
        // Refresh the add-ons if the button is clicked.
        if (isset($_POST['submit-button-mosh-refresh-addon']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['grdlvl_mosh_refresh_addons_nonce'] ?? '')), 'grdlvl_mosh_refresh_addons')) {
            $this->clearCache();
        }
        $this->enqueueAssets();
        $products = $this->prepareProductsForDisplay($this->getAddons(\true));
        return $this->view->render('products.php', ['products' => $products]);
    }
    /**
     * Prepare the addons for display. Skip any addons that are missing required data,
     * and add data for installation status, text, and icon class.
     *
     * @param  array $products The products to prepare. Each product is a StdClass object.
     * @return array           The prepared products.
     */
    protected function prepareProductsForDisplay(array $products) : array
    {
        $products = \array_values(\array_filter($products, function ($product) {
            $hasMainFile = !empty($product->main_file);
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
            $hasExtensionType = !empty($product->extension_type);
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response
            return $hasMainFile && $hasExtensionType;
        }));
        $pluginUpdates = get_site_transient('update_plugins');
        $themeUpdates = get_site_transient('update_themes');
        foreach ($products as $product) {
            if ('upgrade-addon' === $product->type) {
                $product->updateAvailable = \false;
                $product->status = 'upgrade';
                $product->statusLabel = esc_html__('Upgrade Required', 'ground-level');
                $product->iconClass = 'dashicons dashicons-unlock';
                $product->buttonLabel = esc_html__('Upgrade', 'ground-level');
                $product->upgradeUrl = $this->plugin->getAccountUrl();
                continue;
            }
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
                $theme = wp_get_theme(\dirname($mainFile));
                $installed = $theme->exists();
                $active = get_stylesheet() === \dirname($mainFile);
                // TODO: Add JS for update handling, set this using isset($themeUpdates->response[$mainFile]).
                $product->updateAvailable = \false;
            }
            if ($installed && $active) {
                $product->status = 'active';
                $product->statusLabel = esc_html__('Active', 'ground-level');
                $product->iconClass = 'dashicons dashicons-no-alt';
                $product->buttonLabel = esc_html__('Deactivate', 'ground-level');
                if (ExtensionType::THEME === $extensionType) {
                    $product->iconClass = 'dashicons dashicons-admin-appearance';
                    $product->buttonLabel = esc_html__('Switch Themes', 'ground-level');
                }
            } elseif (!$installed) {
                $product->status = 'not-installed';
                $product->iconClass = 'dashicons dashicons-download';
                $product->statusLabel = esc_html__('Not Installed', 'ground-level');
                $product->buttonLabel = esc_html__('Install Add-on', 'ground-level');
                if (ExtensionType::THEME === $extensionType) {
                    $product->buttonLabel = esc_html__('Install Theme', 'ground-level');
                }
            } else {
                $product->status = 'inactive';
                $product->iconClass = 'dashicons dashicons-yes-alt';
                $product->statusLabel = esc_html__('Inactive', 'ground-level');
                $product->buttonLabel = esc_html__('Activate', 'ground-level');
            }
        }
        return $products;
    }
    /**
     * Enqueues the assets for the add-ons display.
     *
     * @return void
     */
    public function enqueueAssets() : void
    {
        wp_enqueue_style('dashicons');
        wp_enqueue_script('mosh-addons-js', plugin_dir_url(__FILE__) . '../assets/addons.js', [], \filemtime(__DIR__ . '/../assets/addons.js'), \true);
        wp_enqueue_style('mosh-addons-css', plugin_dir_url(__FILE__) . '../assets/addons.css', [], \filemtime(__DIR__ . '/../assets/addons.css'));
        wp_localize_script('mosh-addons-js', 'MoshAddons', ['ajax_url' => admin_url('admin-ajax.php'), 'actions' => ['activate' => "{$this->plugin->pluginId}_addon_activate", 'deactivate' => "{$this->plugin->pluginId}_addon_deactivate", 'install' => "{$this->plugin->pluginId}_addon_install"], 'themes_url' => admin_url('themes.php'), 'nonce' => wp_create_nonce('mosh_addons'), 'active' => esc_html__('Active', 'ground-level'), 'inactive' => esc_html__('Inactive', 'ground-level'), 'activate' => esc_html__('Activate', 'ground-level'), 'deactivate' => esc_html__('Deactivate', 'ground-level'), 'switch_themes' => esc_html__('Switch Themes', 'ground-level'), 'processing' => esc_html__('Processing...', 'ground-level'), 'install_failed' => esc_html__('Could not install theme. Please download and install manually.', 'ground-level'), 'plugin_install_failed' => esc_html__('Could not install plugin. Please download and install manually.', 'ground-level')]);
    }
}
