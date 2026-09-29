<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications;

/**
 * Utility class for In-Product Notifications.
 *
 * Provides utility methods for the IPN component.
 */
class Util
{
    /**
     * The prefix for IPN identifiers.
     *
     * @inject \GroundLevel\InProductNotifications\IPNServiceProvider::PARAM_PREFIX
     * @var    string
     */
    protected string $prefix;
    /**
     * The capability required to view the inbox.
     *
     * @inject \GroundLevel\InProductNotifications\IPNServiceProvider::PARAM_USER_CAPABILITY
     * @var    string
     */
    protected string $userCapability;
    /**
     * Constructor.
     *
     * @param string $prefix         The prefix for IPN identifiers.
     * @param string $userCapability The capability required to view the inbox.
     */
    public function __construct(string $prefix, string $userCapability)
    {
        $this->prefix = $prefix;
        $this->userCapability = $userCapability;
    }
    /**
     * Prefixes the ID with the configured prefix.
     *
     * @param  string $id The ID to prefix.
     * @return string The prefixed ID, e.g., "grdlvl_ipn_{$id}"
     */
    public function prefixId(string $id = '') : string
    {
        $prefix = $this->prefix;
        $sep = \substr($prefix, -1);
        if (!\in_array($sep, ['_', '-'], \true)) {
            $sep = '_';
            $prefix .= $sep;
        }
        $prefixedId = $prefix . 'ipn';
        if ($id) {
            $prefixedId .= $sep . $id;
        }
        return $prefixedId;
    }
    /**
     * Determines if the current user has permission to view the inbox.
     *
     * @return boolean
     */
    public function userHasPermission() : bool
    {
        /**
         * Filters whether the current user has permission to view the inbox.
         *
         * Allows plugins to add custom conditions for inbox visibility beyond
         * the user capability check.
         *
         * @param bool   $hasPermission Whether the current user has the required capability.
         * @param string $capability    The capability being checked.
         */
        return (bool) apply_filters($this->prefixId('user_has_permission'), current_user_can($this->userCapability), $this->userCapability);
    }
}
