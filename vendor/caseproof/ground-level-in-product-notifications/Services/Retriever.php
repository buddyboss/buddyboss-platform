<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Services;

use BuddyBossPlatform\GroundLevel\InProductNotifications\Service as IPNService;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
use BuddyBossPlatform\GroundLevel\Support\Str;
class Retriever extends ScheduledService
{
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
        $var = Str::toCamelCase($this->container->get(IPNService::class)->prefixId('refresh'));
        // EG: meprIpnRefresh.
        if (1 === (int) \filter_input(\INPUT_GET, $var, \FILTER_VALIDATE_INT)) {
            $this->container->get(Store::class)->clear()->persist();
            do_action($this->eventHookName());
        }
    }
    /**
     * Retrieves notifications from the Mothership API and stores them in the database.
     */
    public function performEvent() : void
    {
        /** @var Store $store */
        // phpcs:ignore
        $store = $this->container->get(Store::class);
        $args = ['per_page' => 20];
        $lastId = $store->fetch()->lastId();
        if (!empty($lastId)) {
            $args['since'] = $lastId;
        }
        $req = Products::getNotifications($this->container->get(IPNService::PRODUCT_SLUG), $args);
        if (!$req->isError()) {
            // If there's more notifications, rerun in 1 minute instead of waiting for the next cron.
            if ($req->hasNext()) {
                wp_schedule_single_event(\time() + 60, $this->eventHookName());
            }
            foreach ($req->notifications as $notification) {
                $store->add((array) $notification, \true);
            }
            $store->persist();
        }
    }
}
