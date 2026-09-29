<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;
use BuddyBossPlatform\GroundLevel\Mothership\Concerns\BuildsIcons;
use BuddyBossPlatform\GroundLevel\Mothership\Util as MothershipUtil;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
/**
 * Owns WordPress update plumbing for the host plugin and its add-ons.
 */
class UpdateService
{
    use BuildsIcons;
    use Hookable;
    /**
     * The plugin connection.
     *
     * @var AbstractPluginConnection
     */
    private AbstractPluginConnection $plugin;
    /**
     * The products API.
     *
     * @var Products
     */
    private Products $products;
    /**
     * The Mothership utility.
     *
     * @var MothershipUtil
     */
    private MothershipUtil $mothershipUtil;
    /**
     * Plugins already registered.
     *
     * @var array<string, true>
     */
    private array $registeredPlugins = [];
    /**
     * Themes already registered.
     *
     * @var array<string, true>
     */
    private array $registeredThemes = [];
    /**
     * Constructor.
     *
     * @param AbstractPluginConnection $plugin         The plugin connection.
     * @param Products                 $products       The products API.
     * @param MothershipUtil           $mothershipUtil The Mothership utility.
     */
    public function __construct(AbstractPluginConnection $plugin, Products $products, MothershipUtil $mothershipUtil)
    {
        $this->plugin = $plugin;
        $this->products = $products;
        $this->mothershipUtil = $mothershipUtil;
    }
    /**
     * Configures WordPress hooks.
     *
     * @return array<Hook>
     */
    protected function configureHooks() : array
    {
        return [new Hook(Hook::TYPE_ACTION, 'init', [$this, 'registerProducts'], 5)];
    }
    /**
     * Registers the host plugin and add-ons for update checks and plugin information.
     *
     * Add-ons opt in by listening to the dispatched action and calling
     * {@see self::plugin()} or {@see self::theme()}:
     *
     *     add_action("{$pluginId}_register_addons", function ($registrar) {
     *         $registrar->plugin('addon-slug');
     *     });
     *
     * @return void
     */
    public function registerProducts() : void
    {
        // Self-register the host plugin.
        $this->plugin($this->plugin->pluginId, $this->plugin->productId);
        /**
         * Allows add-ons to register their own products for updates.
         *
         * @param UpdateService $registrar The service add-ons call register methods on.
         */
        do_action($this->plugin->pluginId . '_register_addons', $this);
    }
    /**
     * Registers a plugin for update checks and plugin info.
     *
     * @param  string      $slug      The plugin slug, matching the `Update URI` header value.
     * @param  string|null $productId Optional. The Mothership product ID. Defaults to $slug.
     * @return void
     */
    public function plugin(string $slug, ?string $productId = null) : void
    {
        if (empty($slug)) {
            return;
        }
        if (isset($this->registeredPlugins[$slug])) {
            return;
        }
        $this->registeredPlugins[$slug] = \true;
        $productId = $productId ?? $slug;
        // Handles update checks for the plugin.
        add_filter("update_plugins_{$slug}", function ($update, array $pluginData) use($slug, $productId) {
            return $this->pluginUpdate($update, $pluginData, $slug, $productId);
        }, 10, 2);
        // Handles plugin information requests for the plugin.
        add_filter('plugins_api', function ($result, string $action, $args) use($slug, $productId) {
            return $this->pluginInformation($result, $action, $args, $slug, $productId);
        }, 10, 3);
        // Applies the automatic update policy to the plugin.
        add_filter('auto_update_plugin', function ($update, $item) use($slug) {
            return $this->pluginAutoUpdate($update, $item, $slug);
        }, 10, 2);
    }
    /**
     * Registers a theme for update checks and theme info.
     *
     * @param  string      $slug      The theme slug, matching the `Update URI:` declaration in `style.css`.
     * @param  string|null $productId Optional. The Mothership product ID. Defaults to $slug.
     * @return void
     */
    public function theme(string $slug, ?string $productId = null) : void
    {
        if (empty($slug)) {
            return;
        }
        if (isset($this->registeredThemes[$slug])) {
            return;
        }
        $this->registeredThemes[$slug] = \true;
        $productId = $productId ?? $slug;
        // Handles update checks for the theme.
        add_filter("update_themes_{$slug}", function ($update, array $themeData, string $themeStylesheet) use($productId) {
            return $this->themeUpdate($update, $themeData, $themeStylesheet, $productId);
        }, 10, 3);
        // Handles theme information requests for the theme.
        add_filter('themes_api', function ($result, string $action, $args) use($slug, $productId) {
            return $this->themeInformation($result, $action, $args, $slug, $productId);
        }, 10, 3);
        // Applies the automatic update policy to the theme.
        add_filter('auto_update_theme', function ($update, $item) use($slug) {
            return $this->themeAutoUpdate($update, $item, $slug);
        }, 10, 2);
    }
    /**
     * Builds the plugin update array for a given product.
     *
     * The shape matches WordPress's expected return for `update_plugins_{$uri}`
     * filters. See {@link https://developer.wordpress.org/reference/hooks/update_plugins_hostname/}.
     *
     * @param false|array $update     Plugin update data, or false if no update.
     * @param array       $pluginData Plugin headers.
     * @param string      $slug       The plugin slug.
     * @param string      $productId  The Mothership product ID.
     *
     * @return array|false The update array, or the original $update on API error.
     */
    private function pluginUpdate($update, array $pluginData, string $slug, string $productId)
    {
        $versionCheck = $this->products->getVersionCheck($productId, ['prerelease' => $this->plugin->allowPrereleaseVersions(), '_embed' => 'version,product']);
        if ($versionCheck->isError()) {
            return $update;
        }
        // The version embed may be absent for unauthenticated/expired licenses.
        // Return the version number anyway so users see an update is available;
        // the package URL will be empty so auto-update can't proceed.
        $versionLatest = $versionCheck->getEmbed('version');
        $response = ['slug' => $slug, 'version' => $versionCheck->getData('number', ''), 'package' => $this->mothershipUtil->sanitizeDownloadUrl($versionLatest->url ?? ''), 'url' => $pluginData['PluginURI'] ?? ''];
        $icons = $this->buildPluginIcons($versionCheck->getEmbed('product'));
        if (!empty($icons)) {
            $response['icons'] = $icons;
        }
        return $response;
    }
    /**
     * Build the theme update array for a given product.
     *
     * The shape matches WordPress's expected return for `update_themes_{$uri}`
     * filters. See {@link https://developer.wordpress.org/reference/hooks/update_themes_hostname/}.
     *
     * @param false|array $update          Theme update data, or false if no update.
     * @param array       $themeData       Theme headers.
     * @param string      $themeStylesheet Theme stylesheet.
     * @param string      $productId       The Mothership product ID.
     *
     * @return array|false The update array, or the original $update on API error.
     */
    private function themeUpdate($update, array $themeData, string $themeStylesheet, string $productId)
    {
        $versionCheck = $this->products->getVersionCheck($productId, ['prerelease' => $this->plugin->allowPrereleaseVersions(), '_embed' => 'version']);
        if ($versionCheck->isError()) {
            return $update;
        }
        // The version embed may be absent for unauthenticated/expired licenses.
        // Return the version number anyway so users see an update is available;
        // the package URL will be empty so auto-update can't proceed.
        $versionLatest = $versionCheck->getEmbed('version');
        return ['theme' => $themeStylesheet, 'version' => $versionCheck->getData('number', ''), 'package' => $this->mothershipUtil->sanitizeDownloadUrl($versionLatest->url ?? ''), 'url' => $themeData['ThemeURI'] ?? ''];
    }
    /**
     * Determines whether a plugin should be auto-updated during WordPress background updates.
     *
     * @param boolean|null $update Whether to auto-update.
     * @param object       $item   The plugin update offer, including `plugin`, `slug` and `new_version`.
     * @param string       $slug   The plugin slug this callback serves.
     *
     * @return boolean|null Whether to auto-update the plugin, or $update if this is not our plugin.
     */
    public function pluginAutoUpdate($update, $item, string $slug) : ?bool
    {
        if (!isset($item->slug) || $item->slug !== $slug) {
            return $update;
        }
        return $this->shouldAutoUpdate($update, $item, ExtensionType::PLUGIN());
    }
    /**
     * Determines whether a theme should be auto-updated during WordPress background updates.
     *
     * @param boolean|null $update Whether to auto-update.
     * @param object       $item   The theme update offer, including `theme`, `id` and `new_version`.
     * @param string       $slug   The theme slug this callback serves.
     *
     * @return boolean|null Whether to auto-update the theme, or $update if this is not our theme.
     */
    public function themeAutoUpdate($update, $item, string $slug) : ?bool
    {
        if (!isset($item->id) || $item->id !== $slug) {
            return $update;
        }
        return $this->shouldAutoUpdate($update, $item, ExtensionType::THEME());
    }
    /**
     * Decides whether a matched plugin or theme should auto-update under the host plugin's policy.
     *
     * @param boolean|null  $update The incoming update decision, returned untouched when the license is inactive.
     * @param object        $item   The matched update offer.
     * @param ExtensionType $type   The extension type of the matched offer.
     *
     * @return boolean|null The auto-update decision, or $update when the license is inactive.
     */
    private function shouldAutoUpdate($update, $item, ExtensionType $type) : ?bool
    {
        if (!$this->plugin->getLicenseActivationStatus()) {
            return $update;
        }
        $policy = $this->plugin->automaticUpdates();
        if (AbstractPluginConnection::AUTOMATIC_UPDATE_NONE === $policy) {
            return \false;
        }
        if (AbstractPluginConnection::AUTOMATIC_UPDATE_ALL === $policy) {
            return \true;
        }
        // AUTOMATIC_UPDATE_MINOR policy: allow minor and patch updates, block major version bumps.
        $installedVersion = $type->equals(ExtensionType::THEME(), \false) ? $this->getInstalledThemeVersion($item) : $this->getInstalledPluginVersion($item);
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $newVersion = $item->new_version ?? '0';
        $currentMajor = (int) \explode('.', $installedVersion ?: '0')[0];
        $newMajor = (int) \explode('.', $newVersion ?: '0')[0];
        return $newMajor === $currentMajor;
    }
    /**
     * Reads the installed version from a plugin's file header.
     *
     * @param object $item The plugin update offer.
     *
     * @return string The installed version or empty string if it cannot be read.
     */
    private function getInstalledPluginVersion($item) : string
    {
        if (!isset($item->plugin)) {
            return '';
        }
        $pluginData = get_file_data(\WP_PLUGIN_DIR . '/' . $item->plugin, ['Version' => 'Version']);
        return $pluginData['Version'] ?? '';
    }
    /**
     * Reads the installed version from a theme's stylesheet header.
     *
     * @param object $item The theme update offer.
     *
     * @return string The installed version, or empty string if it cannot be read.
     */
    private function getInstalledThemeVersion($item) : string
    {
        if (!isset($item->theme)) {
            return '';
        }
        return (string) wp_get_theme($item->theme)->get('Version');
    }
    /**
     * Handles `plugins_api` requests for the plugin.
     *
     * @param false|object $result    The result object. Default false.
     * @param string       $action    The type of information being requested.
     * @param object       $args      Plugin API arguments.
     * @param string       $slug      The plugin slug this callback serves.
     * @param string       $productId The Mothership product ID to look up.
     *
     * @return false|object The plugin information object or the original $result.
     */
    private function pluginInformation($result, string $action, $args, string $slug, string $productId)
    {
        if ('plugin_information' !== $action) {
            return $result;
        }
        if (!isset($args->slug) || $args->slug !== $slug) {
            return $result;
        }
        $pluginInfo = ['slug' => $slug];
        $check = $this->products->getVersionCheck($productId, ['prerelease' => $this->plugin->allowPrereleaseVersions(), '_embed' => 'version,product']);
        $version = $check->getEmbed('version');
        $product = $check->getEmbed('product');
        if ($check->isSuccess()) {
            $pluginInfo['name'] = $product->name ?? '';
            $pluginInfo['version'] = $check->getData('number', '');
            $pluginInfo['last_updated'] = $check->getData('created_at', '');
            $pluginInfo['download_link'] = $this->mothershipUtil->sanitizeDownloadUrl($version->url ?? '');
            $pluginInfo['sections'] = ['description' => $product->description ?? ''];
        }
        /**
         * Filters the plugin information returned for `plugins_api` requests.
         *
         * Each plugin (host or add-on) gets its own slug-prefixed filter.
         *
         * Use this filter to provide additional details such as author, homepage, banners,
         * and changelog that are not available from the Mothership API.
         *
         * @param object      $pluginInfo The plugin information object.
         * @param object|null $product    Product data from the Mothership API.
         * @param object|null $version    Version data from the Mothership API.
         */
        return apply_filters($slug . '_plugin_information', (object) $pluginInfo, $product, $version);
    }
    /**
     * Handles `themes_api` requests for the theme.
     *
     * @param false|object $result    The result object. Default false.
     * @param string       $action    The type of information being requested.
     * @param object       $args      Theme API arguments.
     * @param string       $slug      The theme slug this callback serves.
     * @param string       $productId The Mothership product ID to look up.
     *
     * @return false|object The theme information object or the original $result.
     */
    private function themeInformation($result, string $action, $args, string $slug, string $productId)
    {
        if ('theme_information' !== $action) {
            return $result;
        }
        if (!isset($args->slug) || $args->slug !== $slug) {
            return $result;
        }
        $themeInfo = ['slug' => $slug];
        $check = $this->products->getVersionCheck($productId, ['prerelease' => $this->plugin->allowPrereleaseVersions(), '_embed' => 'version,product']);
        $version = $check->getEmbed('version');
        $product = $check->getEmbed('product');
        if ($check->isSuccess()) {
            $themeInfo['name'] = $product->name ?? '';
            $themeInfo['version'] = $check->getData('number', '');
            $themeInfo['download_link'] = $this->mothershipUtil->sanitizeDownloadUrl($version->url ?? '');
        }
        /**
         * Filters the theme information returned for `themes_api` requests.
         *
         * Each theme gets its own slug-prefixed filter.
         *
         * Use this filter to provide additional details such as author, screenshot_url,
         * preview_url, and description that are not available from the Mothership API.
         *
         * @param object      $themeInfo The theme information object.
         * @param object|null $product   Product data from the Mothership API.
         * @param object|null $version   Version data from the Mothership API.
         */
        return apply_filters($slug . '_theme_information', (object) $themeInfo, $product, $version);
    }
}
