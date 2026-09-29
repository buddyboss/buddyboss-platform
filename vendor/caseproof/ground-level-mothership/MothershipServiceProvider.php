<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership;

use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Container\ServiceProvider;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\LicenseActivations;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Licenses;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\ProductInsights;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\UserAddons;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Users;
use BuddyBossPlatform\GroundLevel\Mothership\Api\RequestFactory;
use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonsManager;
use BuddyBossPlatform\GroundLevel\Mothership\Manager\LicenseManager;
use BuddyBossPlatform\GroundLevel\Mothership\Transients\ActivationTransient;
use BuddyBossPlatform\GroundLevel\Support\AdminNotices;
use BuddyBossPlatform\GroundLevel\Support\View;
/**
 * Service provider for the Mothership component.
 *
 * Registers the Mothership services for license management and API communication.
 */
class MothershipServiceProvider extends ServiceProvider
{
    /**
     * Parameter key for the Mothership cache TTL.
     */
    public const PARAM_CACHE_TTL = 'mothership.cache_ttl';
    /**
     * Parameter key for the Mothership prefix.
     */
    public const PARAM_PREFIX = 'mothership.prefix';
    /**
     * Parameter key for the Mothership API base URL.
     */
    public const PARAM_API_BASE_URL = 'mothership.api_base_url';
    /**
     * Returns service definitions.
     */
    public function services() : array
    {
        return [Util::class, Credentials::class, Request::class, RequestFactory::class, Products::class, ProductInsights::class, Licenses::class, LicenseActivations::class, Users::class, UserAddons::class, AddonsManager::class, LicenseManager::class, UpdateService::class, LegacyUpdateService::class, $this->service(ActivationTransient::class, self::FACTORY), $this->service(View::class, self::SINGLETON, static fn(Container $c) => new View(__DIR__ . '/views', $c->get(AbstractPluginConnection::class)->pluginId)), $this->service(AdminNotices::class, self::SINGLETON, static fn(Container $c) => new AdminNotices($c->get(AbstractPluginConnection::class)->pluginId))];
    }
    /**
     * Returns default parameters.
     */
    public function parameters() : array
    {
        return [self::PARAM_CACHE_TTL => 60, self::PARAM_API_BASE_URL => 'https://licenses.caseproof.com/api/v1/'];
    }
    /**
     * Boot the provider.
     *
     * Sets up license manager events and addon hooks.
     */
    public function boot() : void
    {
        // Load hooks for services.
        $this->container->get(AdminNotices::class)->addHooks();
        $this->container->get(AddonsManager::class)->addHooks();
        $this->container->get(LicenseManager::class)->addHooks();
        $this->container->get(UpdateService::class)->addHooks();
        $this->container->get(LegacyUpdateService::class)->addHooks();
        parent::boot();
    }
}
