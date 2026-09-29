<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership;

use BuddyBossPlatform\GroundLevel\Support\Str;
/**
 * Utility class for Mothership component.
 */
class Util
{
    /**
     * The API base URL.
     *
     * @inject \GroundLevel\Mothership\MothershipServiceProvider::PARAM_API_BASE_URL
     * @var    string
     */
    private string $apiBaseUrl;
    /**
     * The plugin connection.
     *
     * @var \GroundLevel\Mothership\AbstractPluginConnection
     */
    private AbstractPluginConnection $plugin;
    /**
     * Constructor.
     *
     * @param string                                           $apiBaseUrl The API base URL.
     * @param \GroundLevel\Mothership\AbstractPluginConnection $plugin     The plugin connection.
     */
    public function __construct(string $apiBaseUrl, AbstractPluginConnection $plugin)
    {
        $this->apiBaseUrl = $apiBaseUrl;
        $this->plugin = $plugin;
    }
    /**
     * Composes a constant name by combining a prefix and a name.
     *
     * The prefix is ensured to end with an underscore, and the resulting constant name
     * is converted to uppercase with hyphens, periods, and spaces replaced by underscores.
     *
     * @param  string $name The name to be appended to the prefix.
     * @return string The composed constant name.
     */
    public function composeConstantName(string $name) : string
    {
        $prefix = $this->plugin->pluginPrefix;
        $prefix = '_' === \substr($prefix, -1) ? $prefix : $prefix . '_';
        return Str::toConstantCase($prefix . $name);
    }
    /**
     * Get the API base URL.
     *
     * Checks for a plugin-defined constant first (e.g., MYPLUGIN_MOTHERSHIP_API_BASE_URL),
     * then falls back to the configured API base URL.
     *
     * @return string The API base URL.
     */
    public function getApiBaseUrl() : string
    {
        $apiBaseUrlConstant = $this->composeConstantName('MOTHERSHIP_API_BASE_URL');
        if (\defined($apiBaseUrlConstant)) {
            return \constant($apiBaseUrlConstant);
        }
        return $this->apiBaseUrl;
    }
    /**
     * Checks whether a package download URL is allowed.
     *
     * A URL is allowed when it is well-formed, uses HTTPS, and its host is in the
     * configured allowlist ({@see getAllowedDownloadHosts()}).Use this before
     * passing a URL into WP_Upgrader::install() to guard against poisoned transients
     * or tampered API responses.
     *
     * @param  string $url The package download URL to validate.
     * @return boolean True if the URL is allowed, false otherwise.
     */
    public function isAllowedDownloadUrl(string $url) : bool
    {
        if (\false === \filter_var($url, \FILTER_VALIDATE_URL)) {
            return \false;
        }
        $scheme = \strtolower((string) wp_parse_url($url, \PHP_URL_SCHEME));
        $host = \strtolower((string) wp_parse_url($url, \PHP_URL_HOST));
        if ('https' !== $scheme || empty($host)) {
            return \false;
        }
        return \in_array($host, $this->getAllowedDownloadHosts(), \true);
    }
    /**
     * Returns the URL when it passes {@see self::isAllowedDownloadUrl()}, or an empty string.
     *
     * @param  string $url The package download URL to sanitize.
     * @return string The URL when allowed, or an empty string otherwise.
     */
    public function sanitizeDownloadUrl(string $url) : string
    {
        return $this->isAllowedDownloadUrl($url) ? $url : '';
    }
    /**
     * Returns the allowlist of hostnames permitted as package download sources.
     *
     * Defaults to the Mothership API host plus downloads.wordpress.org. Extend the
     * list via the '{pluginId}_allowed_download_hosts' filter; hosts are matched
     * exactly (no subdomain wildcards) and compared case-insensitively.
     *
     * @return array<string> Lowercased hostnames.
     */
    public function getAllowedDownloadHosts() : array
    {
        $apiHost = (string) wp_parse_url($this->getApiBaseUrl(), \PHP_URL_HOST);
        $defaults = [];
        if ('' !== $apiHost) {
            $defaults[] = $apiHost;
        }
        $defaults[] = 'downloads.wordpress.org';
        /**
         * Filters the list of hostnames allowed as package download sources.
         *
         * @param array<string> $hosts Hostnames; comparison is case-insensitive.
         */
        $hosts = (array) apply_filters("{$this->plugin->pluginId}_allowed_download_hosts", $defaults);
        $hosts = \array_filter($hosts, 'is_string');
        return \array_values(\array_unique(\array_map('strtolower', $hosts)));
    }
}
