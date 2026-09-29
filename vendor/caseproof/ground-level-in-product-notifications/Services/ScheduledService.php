<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Services;

use BuddyBossPlatform\GroundLevel\InProductNotifications\Util as IPNUtil;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;
/**
 * Abstract base class for scheduled IPN services.
 */
abstract class ScheduledService
{
    use Hookable;
    /**
     * The IPN utility service.
     *
     * @var IPNUtil
     */
    protected IPNUtil $util;
    /**
     * The cron recurrence interval.
     *
     * @var string
     */
    protected string $recurrence = 'daily';
    /**
     * Constructor.
     *
     * @param IPNUtil $util The IPN utility service.
     */
    public function __construct(IPNUtil $util)
    {
        $this->util = $util;
        $this->addHooks();
    }
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
        return $this->util->prefixId($this->eventName());
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
