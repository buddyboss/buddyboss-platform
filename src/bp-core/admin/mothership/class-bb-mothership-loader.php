<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use GroundLevel\Container\Container;
use GroundLevel\Mothership\Service as MothershipService;
use GroundLevel\Mothership\Manager\LicenseManager;
use GroundLevel\Mothership\Manager\AddonsManager;
use GroundLevel\Mothership\Credentials;
use GroundLevel\Mothership\Api\Request;
use GroundLevel\Mothership\AbstractPluginConnection;

/**
 * Main loader class for BuddyBoss Mothership functionality.
 *
 * This class follows the GroundLevel framework patterns for service registration,
 * container awareness, and hook configuration.
 */
class BB_Mothership_Loader
{
    /**
     * Container for dependency injection.
     *
     * @var Container
     */
    private $container;

    /**
     * Plugin connector instance.
     *
     * @var \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector
     */
    private $pluginConnector;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the mothership functionality.
     */
    private function init(): void
    {
        // Create the container.
        $this->container = new Container();

        // Create the plugin connector.
        $this->pluginConnector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();

        // Initialize the mothership service.
        $this->initMothershipService();

        // Set up hooks.
        $this->setupHooks();
    }

    /**
     * Initialize the mothership service.
     */
    private function initMothershipService(): void
    {
        // Create the mothership service.
        $mothershipService = new MothershipService($this->container, $this->pluginConnector);

        // Load the mothership service dependencies.
        $mothershipService->load($this->container);

        // Register the mothership service in the container.
        $this->container->addService(
            MothershipService::class,
            function () use ($mothershipService) {
                return $mothershipService;
            },
            true // Singleton
        );
    }

    /**
     * Setup WordPress hooks.
     */
    private function setupHooks(): void
    {
        // Register admin pages.
        add_action('admin_menu', [$this, 'registerAdminPages'], 99);

        // Register license controller.
        add_action('admin_init', [LicenseManager::class, 'controller'], 20);

        // Register addons functionality.
        AddonsManager::loadHooks();

        // Handle license status changes.
        add_action($this->pluginConnector->pluginId . '_license_status_changed', [$this, 'handleLicenseStatusChange'], 10, 2);

        // For local development - disable SSL verification if needed.
        if (defined('BUDDYBOSS_DISABLE_SSL_VERIFY') && constant('BUDDYBOSS_DISABLE_SSL_VERIFY')) {
            add_filter('https_ssl_verify', '__return_false');
        }
    }

    /**
     * Register admin pages.
     */
    public function registerAdminPages(): void
    {
        // Only show to users with manage_options capability.
        if (!current_user_can('manage_options')) {
            return;
        }

        // Register License page.
        \BuddyBoss\Core\Admin\Mothership\BB_License_Page::register();

        // Register Addons page.
        \BuddyBoss\Core\Admin\Mothership\BB_Addons_Page::register();
    }

    /**
     * Handle license status changes.
     *
     * @param bool  $isActive License active status.
     * @param mixed $response API response.
     */
    public function handleLicenseStatusChange(bool $isActive, $response): void
    {
        if (!$isActive) {
            // License is no longer active.
            $this->pluginConnector->updateLicenseActivationStatus(false);

            // Clear cached data.
            delete_transient($this->pluginConnector->pluginId . '-mosh-products');
            delete_transient($this->pluginConnector->pluginId . '-mosh-addons-update-check');

            // Log the deactivation.
            error_log('BuddyBoss license deactivated: ' . print_r($response, true));
        } else {
            // License is active - ensure status is updated.
            $this->pluginConnector->updateLicenseActivationStatus(true);
        }
    }

    /**
     * Get the container.
     *
     * @return Container The container instance.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}
