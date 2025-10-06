<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications;

use BuddyBossPlatform\GroundLevel\Container\Concerns\Configurable;
use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Container\Service as BaseService;
use BuddyBossPlatform\GroundLevel\Container\Contracts\LoadableDependency;
use BuddyBossPlatform\GroundLevel\Container\Contracts\ConfiguresParameters;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Ajax;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Cleaner;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Retriever;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Store;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\View;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MothershipService;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
class Service extends BaseService implements ConfiguresParameters, LoadableDependency
{
    use Configurable;
    use Hookable;
    /**
     * The Service ID.
     */
    public const ID = 'GRDLVL.IPN';
    /**
     * Container Parameter: The __FILE__ path for this service file.
     *
     * Read only. This parameter is automatically set when the service is loaded.
     */
    public const FILE = 'GRDLVL.IPN.FILE';
    /**
     * Container Parameter: The slug of the product's main admin menu item
     *
     * Optional.
     *
     * If not set, the service will not render a notification unread count
     * in the main admin menu.
     */
    public const MENU_SLUG = 'GRDLVL.IPN.MENU_SLUG';
    /**
     * Container Parameter: The prefix applied to various strings and IDs used
     * by the service.
     *
     * Default: `grdlvl_`.
     *
     * To customize the prefix, set the parameter in the container prior to loading
     * the service.
     */
    public const PREFIX = 'GRDLVL.IPN.PRODUCT_PREFIX';
    /**
     * Container Parameter: The slug of the product this service is for.
     *
     * Required.
     *
     * If this parameter is not set in the container the service will fail to load
     * and throw a {@see \GroundLevel\Container\NotFoundException}.
     */
    public const PRODUCT_SLUG = 'GRDLVL.IPN.PRODUCT_SLUG';
    /**
     * Container Parameter: A WordPress Action Hook used to render the inbox React component on screen.
     *
     * Required.
     *
     * If this parameter is not set in the container the service will fail to load
     * and throw a {@see \GroundLevel\Container\NotFoundException}.
     */
    public const RENDER_HOOK = 'GRDLVL.IPN.RENDER_HOOK';
    /**
     * An array of theme configuration parameters to style the inbox React component.
     *
     * Default: []
     *
     * To customize the theme, set the parameter in the container prior to loading
     * the service.
     *
     * @link https://github.com/caseproof/ipn-inbox/blob/d84ca9e60b551bd45d969a8a27a57baaa6138193/src/contexts/ThemeContext.tsx#L32-L49
     */
    public const THEME = 'GRDLVL.IPN.THEME';
    /**
     * Container Parameter: The capability required to view the inbox.
     *
     * Default: `manage_options`.
     *
     * To customize the capability, set the parameter in the container prior to
     * loading the service.
     */
    public const USER_CAPABILITY = 'GRDLVL.IPN.USER_CAPABILITY';
    /**
     * Gets the default parameters for the service.
     *
     * @return array
     */
    public function getDefaultParameters() : array
    {
        return [self::MENU_SLUG => '', self::PREFIX => 'grdlvl_', self::THEME => [], self::USER_CAPABILITY => 'manage_options'];
    }
    /**
     * Prefixes the ID with the configured prefix.
     *
     * @param  string $id The ID to prefix.
     * @return string The prefixed ID, eg "grdlvl_ipn_{$id}"
     */
    public function prefixId(string $id = '') : string
    {
        $prefix = $this->container->get(self::PREFIX);
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
     * Loads the dependencies for the service.
     *
     * @param  Container $container The container.
     * @return void
     */
    public function load(Container $container) : void
    {
        /**
         * The Mothership Service is required for the IPN Service to function.
         * This service does not attempt to load the depedent service so if
         * the service is not already loaded into the container the service
         * will fail to load with a {@see \GroundLevel\Container\NotFoundException}.
         */
        $container->get(MothershipService::class);
        $container->addParameter(self::FILE, __FILE__);
        $container->addService(Ajax::class, function (Container $container) : Ajax {
            return new Ajax($container);
        });
        $container->addService(Cleaner::class, function (Container $container) : Cleaner {
            return new Cleaner($container);
        });
        $container->addService(Retriever::class, function (Container $container) : Retriever {
            return new Retriever($container);
        });
        $container->addService(Store::class, function (Container $container) : Store {
            $service = new Store($container);
            $service->setKey($this->prefixId('store'));
            return $service;
        });
        $container->addService(View::class, function (Container $container) : View {
            return new View($container);
        });
        $this->addHooks();
    }
    /**
     * Configures the hooks for the service.
     *
     * @return array<int, Hook>
     */
    protected function configureHooks() : array
    {
        return [
            // This hook is a delayed loader for depedent services.
            new Hook(Hook::TYPE_ACTION, 'init', function () : void {
                $classes = [Ajax::class, Cleaner::class, Retriever::class, View::class];
                foreach ($classes as $class) {
                    $this->container->get($class);
                }
            }, 6),
        ];
    }
    /**
     * Determines if the current user has permission to view the inbox.
     *
     * @return boolean
     */
    public function userHasPermission() : bool
    {
        return current_user_can($this->container->get(self::USER_CAPABILITY));
    }
}
