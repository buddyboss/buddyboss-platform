<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Services;

use BuddyBossPlatform\GroundLevel\InProductNotifications\Util as IPNUtil;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
use BuddyBossPlatform\GroundLevel\Support\Str;
/**
 * Retriever service for fetching notifications from the Mothership API.
 */
class Retriever extends ScheduledService
{
    /**
     * The store service.
     *
     * @var Store
     */
    protected Store $store;
    /**
     * The products API service.
     *
     * @var Products
     */
    protected Products $products;
    /**
     * The product slug for API requests.
     *
     * @inject \GroundLevel\InProductNotifications\IPNServiceProvider::PARAM_PRODUCT_SLUG
     * @var    string
     */
    protected string $productSlug;
    /**
     * Constructor.
     *
     * @param IPNUtil  $util        The IPN utility service.
     * @param Store    $store       The store service.
     * @param Products $products    The products API service.
     * @param string   $productSlug The product slug for API requests.
     */
    public function __construct(IPNUtil $util, Store $store, Products $products, string $productSlug)
    {
        $this->store = $store;
        $this->products = $products;
        $this->productSlug = $productSlug;
        parent::__construct($util);
    }
    /**
     * Configures the hooks for the service.
     *
     * @return array<int, Hook>
     */
    protected function configureHooks() : array
    {
        return \array_merge(parent::configureHooks(), [new Hook(Hook::TYPE_ACTION, 'admin_init', [$this, 'maybeForceFetch'])]);
    }
    /**
     * Retrieves the hook name for the event action.
     *
     * @return string
     */
    protected function eventName() : string
    {
        return 'remote_fetch';
    }
    /**
     * Watches for the force fetch query parameter and forces a clean fetch if present.
     */
    public function maybeForceFetch() : void
    {
        if (!$this->util->userHasPermission()) {
            return;
        }
        $var = Str::toCamelCase($this->util->prefixId('refresh'));
        // EG: meprIpnRefresh.
        if (1 === (int) \filter_input(\INPUT_GET, $var, \FILTER_VALIDATE_INT)) {
            $this->store->clear()->persist();
            do_action($this->eventHookName());
            if (wp_redirect(remove_query_arg($var))) {
                exit;
            }
        }
    }
    /**
     * Retrieves notifications from the Mothership API and stores them in the database.
     */
    public function performEvent() : void
    {
        $args = ['per_page' => 20];
        $lastId = $this->store->fetch()->lastId();
        if (!empty($lastId)) {
            $args['since'] = $lastId;
        }
        $response = $this->products->getNotifications($this->productSlug, $args);
        if (!$response->isError()) {
            // If there's more notifications, rerun in 1 minute instead of waiting for the next cron.
            if ($response->hasNext()) {
                wp_schedule_single_event(\time() + 60, $this->eventHookName());
            }
            foreach ($response->getData('notifications', []) as $notification) {
                $this->store->add((array) $notification, \true);
            }
            $this->store->persist();
        }
    }
}
