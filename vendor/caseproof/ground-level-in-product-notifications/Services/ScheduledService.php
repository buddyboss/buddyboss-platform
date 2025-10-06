<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Services;

use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Container\Contracts\LoadableDependency;
use BuddyBossPlatform\GroundLevel\Container\Service;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Service as IPNService;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
abstract class ScheduledService extends Service implements LoadableDependency
{
    use Hookable;
    /**
     * The cron recurrence interval.
     *
     * @var string
     */
    protected string $recurrence = 'daily';
    /**
     * Retrieves the hook name for the event action.
     *
     * @return string
     */
    protected abstract function eventName() : string;
    /**
     * Performs the event.
     */
    protected abstract function performEvent() : void;
    /**
     * Configures the hooks for the service.
     *
     * @return array<int, Hook>
     */
    protected function configureHooks() : array
    {
        return [new Hook(Hook::TYPE_ACTION, 'init', [$this, 'schedule']), new Hook(Hook::TYPE_ACTION, $this->eventHookName(), [$this, 'performEvent'])];
    }
    /**
     * Retrieves the hook name for the fetch action.
     *
     * @return string The hook name, eg mepr_ipn_remote_fetch.
     */
    protected function eventHookName() : string
    {
        return $this->container->get(IPNService::class)->prefixId($this->eventName());
    }
    /**
     * Load service dependencies.
     *
     * @param \GroundLevel\Container\Container $container The container.
     */
    public function load(Container $container) : void
    {
        $this->addHooks();
    }
    /**
     * Schedules the fetch cron job.
     */
    public function schedule() : void
    {
        $hook = $this->eventHookName();
        if (!wp_next_scheduled($hook)) {
            wp_schedule_event(\time(), $this->recurrence, $hook);
        }
    }
}
