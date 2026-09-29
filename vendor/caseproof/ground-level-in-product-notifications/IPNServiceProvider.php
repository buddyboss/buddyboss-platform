<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications;

use BuddyBossPlatform\GroundLevel\Container\ServiceProvider;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Ajax;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Cleaner;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Retriever;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Store;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\View;
use BuddyBossPlatform\GroundLevel\Mothership\MothershipServiceProvider;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
/**
 * Service provider for the In-Product Notifications (IPN) component.
 *
 * Registers the IPN services for displaying notifications within WordPress admin.
 */
class IPNServiceProvider extends ServiceProvider
{
    /**
     * Parameter: The __FILE__ path for this service file.
     *
     * Read only. This parameter is automatically set when the service is loaded.
     */
    public const PARAM_FILE = 'ipn.file';
    /**
     * Parameter: The slug of the product's main admin menu item.
     *
     * Optional. If not set, the service will not render a notification unread count
     * in the main admin menu.
     */
    public const PARAM_MENU_SLUG = 'ipn.menu_slug';
    /**
     * Parameter: The prefix applied to various strings and IDs used by the service.
     *
     * Default: `grdlvl_`.
     */
    public const PARAM_PREFIX = 'ipn.prefix';
    /**
     * Parameter: The slug of the product this service is for.
     *
     * Required. If this parameter is not set in the container the service will fail to load.
     */
    public const PARAM_PRODUCT_SLUG = 'ipn.product_slug';
    /**
     * Parameter: A WordPress Action Hook used to render the inbox React component on screen.
     *
     * Required. If this parameter is not set in the container the service will fail to load.
     */
    public const PARAM_RENDER_HOOK = 'ipn.render_hook';
    /**
     * Parameter: An array of theme configuration parameters to style the inbox React component.
     *
     * Default: []
     *
     * @link https://github.com/caseproof/ipn-inbox/blob/d84ca9e60b551bd45d969a8a27a57baaa6138193/src/contexts/ThemeContext.tsx#L32-L49
     */
    public const PARAM_THEME = 'ipn.theme';
    /**
     * Parameter: The capability required to view the inbox.
     *
     * Default: `manage_options`.
     */
    public const PARAM_USER_CAPABILITY = 'ipn.user_capability';
    /**
     * Returns service definitions.
     */
    public function services() : array
    {
        return [Util::class, Store::class, Ajax::class, Cleaner::class, Retriever::class, View::class];
    }
    /**
     * Returns provider dependencies.
     */
    public function dependencies() : array
    {
        return [MothershipServiceProvider::class];
    }
    /**
     * Returns default parameters.
     */
    public function parameters() : array
    {
        return [self::PARAM_FILE => __FILE__, self::PARAM_MENU_SLUG => '', self::PARAM_PREFIX => 'grdlvl_', self::PARAM_THEME => [], self::PARAM_USER_CAPABILITY => 'manage_options'];
    }
    /**
     * Configure hooks.
     *
     * @return array<Hook>
     */
    protected function configureHooks() : array
    {
        return [
            // This hook is a delayed loader for dependent services.
            new Hook(Hook::TYPE_ACTION, 'init', function () : void {
                $classes = [Ajax::class, Cleaner::class, Retriever::class, View::class];
                foreach ($classes as $class) {
                    $this->container->get($class);
                }
            }, 6),
        ];
    }
}
