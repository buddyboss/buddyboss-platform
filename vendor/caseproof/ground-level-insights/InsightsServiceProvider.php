<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Insights;

use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Container\ServiceProvider;
use BuddyBossPlatform\GroundLevel\Insights\Services\NetPromoterScore;
use BuddyBossPlatform\GroundLevel\Insights\Services\RestApi;
use BuddyBossPlatform\GroundLevel\InProductNotifications\IPNServiceProvider;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\View;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\ProductInsights;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
/**
 * Service provider for the Insights component.
 *
 * Registers the Insights services for gathering product feedback and NPS data.
 */
class InsightsServiceProvider extends ServiceProvider
{
    /**
     * Parameter: The __FILE__ path for this service file.
     *
     * Read only. This parameter is automatically set when the service is loaded.
     */
    public const PARAM_FILE = 'insights.file';
    /**
     * Parameter: The grace period in days after installation to show the NPS notification.
     *
     * Default: 14.
     */
    public const PARAM_GRACE_PERIOD_DAYS = 'insights.grace_period_days';
    /**
     * Parameter: The prefix applied to various strings and IDs used by the service.
     *
     * Default: `grdlvl_insights_`.
     */
    public const PARAM_PREFIX = 'insights.prefix';
    /**
     * Parameter: The name of the product.
     *
     * Required. If this parameter is not set in the container the service will fail to load.
     */
    public const PARAM_PRODUCT_NAME = 'insights.product_name';
    /**
     * Parameter: The recurrence interval in days for the NPS notification.
     *
     * Default: 90.
     */
    public const PARAM_RECURRENCE_DAYS = 'insights.recurrence_days';
    /**
     * Parameter: The REST API namespace.
     *
     * Default: `grdlvl/insights`.
     */
    public const PARAM_REST_NAMESPACE = 'insights.rest_namespace';
    /**
     * Returns service definitions.
     */
    public function services() : array
    {
        return [NetPromoterScore::class, $this->service(RestApi::class, self::SINGLETON, static fn(Container $c): RestApi => new RestApi($c->get(NetPromoterScore::class), $c->get(ProductInsights::class), static fn(): View => $c->get(View::class), $c->get(IPNServiceProvider::PARAM_PRODUCT_SLUG), $c->get(self::PARAM_REST_NAMESPACE), $c->get(IPNServiceProvider::PARAM_USER_CAPABILITY), $c->get(self::PARAM_PREFIX), $c->get(self::PARAM_FILE)))];
    }
    /**
     * Returns provider dependencies.
     */
    public function dependencies() : array
    {
        return [IPNServiceProvider::class];
    }
    /**
     * Returns default parameters.
     */
    public function parameters() : array
    {
        return [self::PARAM_FILE => __FILE__, self::PARAM_PREFIX => 'grdlvl_insights_', self::PARAM_GRACE_PERIOD_DAYS => 14, self::PARAM_RECURRENCE_DAYS => 90, self::PARAM_REST_NAMESPACE => 'grdlvl/insights'];
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
                $classes = [NetPromoterScore::class, RestApi::class];
                foreach ($classes as $class) {
                    $service = $this->container->get($class);
                    $service->addHooks();
                }
            }, 7),
        ];
    }
}
