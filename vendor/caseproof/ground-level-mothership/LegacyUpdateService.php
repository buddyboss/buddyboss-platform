<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\Concerns\BuildsIcons;
use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonsManager;
use BuddyBossPlatform\GroundLevel\Mothership\Util as MothershipUtil;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
/**
 * Bridge that injects updates for add-ons that have not yet migrated to
 * {@see UpdateService}'s per-plugin contract.
 *
 * Reads the licensed add-ons list via {@see AddonsManager::getAddons()} and writes
 * update entries directly into the WP update transients for any installed add-on
 * whose main file does not declare an `Update URI` header. Once an add-on ships
 * the header and registers via the `{$pluginId}_register_addons` action,
 * {@see UpdateService} takes over and this service skips it.
 *
 * @deprecated Intended to be removed once all add-ons have migrated.
 * @see        UpdateService for the new contract.
 */
class LegacyUpdateService
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
     * The add-ons manager instance.
     *
     * @var AddonsManager
     */
    private AddonsManager $addonsManager;
    /**
     * The Mothership utility.
     *
     * @var MothershipUtil
     */
    private MothershipUtil $mothershipUtil;
    /**
     * Constructor.
     *
     * @param AbstractPluginConnection $plugin         The plugin connection.
     * @param AddonsManager            $addonsManager  The add-ons manager.
     * @param MothershipUtil           $mothershipUtil The Mothership utility.
     */
    public function __construct(AbstractPluginConnection $plugin, AddonsManager $addonsManager, MothershipUtil $mothershipUtil)
    {
        $this->plugin = $plugin;
        $this->addonsManager = $addonsManager;
        $this->mothershipUtil = $mothershipUtil;
    }
    /**
     * Configures WordPress hooks.
     *
     * @return array<Hook>
     */
    protected function configureHooks() : array
    {
        return [new Hook(Hook::TYPE_FILTER, 'site_transient_update_plugins', [$this, 'addonsUpdatePlugins']), new Hook(Hook::TYPE_FILTER, 'site_transient_update_themes', [$this, 'addonsUpdateThemes'])];
    }
    /**
     * Injects add-on plugin updates into the plugins update transient.
     *
     * @param  mixed $transient The update plugins transient.
     * @return mixed            The modified transient.
     */
    public function addonsUpdatePlugins($transient)
    {
        return $this->updateTransient($transient, ExtensionType::PLUGIN());
    }
    /**
     * Injects add-on theme updates into the themes update transient.
     *
     * @param  mixed $transient The update themes transient.
     * @return mixed            The modified transient.
     */
    public function addonsUpdateThemes($transient)
    {
        return $this->updateTransient($transient, ExtensionType::THEME());
    }
    /**
     * Update the transient with available add-on updates for the given extension type.
     *
     * @param  mixed         $transient     The transient to update.
     * @param  ExtensionType $extensionType The extension type being updated.
     * @return mixed                        The modified transient.
     */
    private function updateTransient($transient, ExtensionType $extensionType)
    {
        if (!\is_object($transient) || !$this->plugin->getLicenseActivationStatus()) {
            return $transient;
        }
        if (!isset($transient->response) || !\is_array($transient->response)) {
            $transient->response = [];
        }
        if (!isset($transient->no_update) || !\is_array($transient->no_update)) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- WordPress data structure.
            $transient->no_update = [];
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- WordPress data structure.
        }
        $products = $this->filterProductsByExtensionType($this->addonsManager->getAddons(\true), $extensionType);
        return !empty($products) ? $this->injectUpdates($products, $transient, $extensionType) : $transient;
    }
    /**
     * Injects update entries into the transient for products that have not migrated to
     * the {@see UpdateService} per-plugin filter contract.
     *
     * @param  array         $products      The products to inject.
     * @param  object        $transient     The transient to update.
     * @param  ExtensionType $extensionType The extension type.
     * @return object                       The modified transient.
     */
    private function injectUpdates(array $products, object $transient, ExtensionType $extensionType) : object
    {
        $isPlugin = $extensionType->equals(ExtensionType::PLUGIN(), \false);
        foreach ($products as $product) {
            $mainFile = $product->main_file ?? '';
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
            $versionLatest = $product->version->number ?? '';
            if (empty($mainFile) || empty($versionLatest)) {
                continue;
            }
            $urlLatest = $this->mothershipUtil->sanitizeDownloadUrl($product->version->url ?? '');
            $transientKey = $isPlugin ? $mainFile : \dirname($mainFile);
            if (!isset($transient->checked[$transientKey])) {
                continue;
            }
            if ($this->hasUpdateUri($transientKey, $extensionType)) {
                continue;
            }
            $item = $isPlugin ? $this->buildPluginItem($product, $mainFile, $versionLatest, $urlLatest) : $this->buildThemeItem($transientKey, $versionLatest, $urlLatest);
            if (\version_compare($transient->checked[$transientKey], $versionLatest, '>=')) {
                $transient->no_update[$transientKey] = $item;
                // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- WordPress data structure.
            } else {
                $transient->response[$transientKey] = $item;
            }
        }
        return $transient;
    }
    /**
     * Filters products by extension type.
     *
     * @param  array         $products      The products to filter.
     * @param  ExtensionType $extensionType The extension type to filter by.
     * @return array
     */
    private function filterProductsByExtensionType(array $products, ExtensionType $extensionType) : array
    {
        return \array_values(\array_filter(
            $products,
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- API response.
            static fn($product) => ($product->extension_type ?? '') === $extensionType->getValue()
        ));
    }
    /**
     * Whether the installed plugin/theme at the given transient key declares an
     * `Update URI` header. Empty header => legacy injection applies.
     *
     * @param  string        $transientKey  Plugin file path (plugins) or stylesheet (themes).
     * @param  ExtensionType $extensionType The extension type.
     * @return boolean
     */
    private function hasUpdateUri(string $transientKey, ExtensionType $extensionType) : bool
    {
        if ($extensionType->equals(ExtensionType::PLUGIN(), \false)) {
            $plugins = $this->getPlugins();
            return !empty($plugins[$transientKey]['UpdateURI'] ?? '');
        }
        $theme = wp_get_theme($transientKey);
        return $theme->exists() && !empty($theme->get('UpdateURI'));
    }
    /**
     * Returns {@see get_plugins()}, loading the admin plugin helpers if needed.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getPlugins() : array
    {
        if (!\function_exists('BuddyBossPlatform\\get_plugins')) {
            require_once \ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return get_plugins();
    }
    /**
     * Builds the plugin transient entry. Object cast matches WP's storage shape for
     * `$transient->response`/`$transient->no_update` plugin entries.
     *
     * @param  object $product       The product object.
     * @param  string $mainFile      The plugin main file.
     * @param  string $versionLatest The latest version number.
     * @param  string $urlLatest     The package URL.
     * @return object
     */
    private function buildPluginItem(object $product, string $mainFile, string $versionLatest, string $urlLatest) : object
    {
        $plugins = $this->getPlugins();
        $pluginUri = $plugins[$mainFile]['PluginURI'] ?? '';
        $item = ['id' => $mainFile, 'slug' => \dirname($mainFile), 'plugin' => $mainFile, 'new_version' => $versionLatest, 'url' => $pluginUri, 'package' => $urlLatest];
        $icons = $this->buildPluginIcons($product);
        if (!empty($icons)) {
            $item['icons'] = $icons;
        }
        return (object) $item;
    }
    /**
     * Builds the theme transient entry. Returned as array since WP stores theme
     * transient entries as arrays in `$transient->response`/`$transient->no_update`.
     *
     * @param  string $stylesheet    The theme stylesheet.
     * @param  string $versionLatest The latest version number.
     * @param  string $urlLatest     The package URL.
     * @return array
     */
    private function buildThemeItem(string $stylesheet, string $versionLatest, string $urlLatest) : array
    {
        $theme = wp_get_theme($stylesheet);
        $themeUri = $theme->exists() ? (string) $theme->get('ThemeURI') : '';
        return ['theme' => $stylesheet, 'new_version' => $versionLatest, 'url' => $themeUri, 'package' => $urlLatest];
    }
}
