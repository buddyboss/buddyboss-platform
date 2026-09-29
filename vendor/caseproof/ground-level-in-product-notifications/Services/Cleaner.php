<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Services;

use BuddyBossPlatform\GroundLevel\InProductNotifications\Util as IPNUtil;
/**
 * Cleaner service for removing expired notifications.
 */
class Cleaner extends ScheduledService
{
    /**
     * The store service.
     *
     * @var Store
     */
    protected Store $store;
    /**
     * Constructor.
     *
     * @param IPNUtil $util  The IPN utility service.
     * @param Store   $store The store service.
     */
    public function __construct(IPNUtil $util, Store $store)
    {
        $this->store = $store;
        parent::__construct($util);
    }
    /**
     * Retrieves the hook name for the event action.
     *
     * @return string
     */
    protected function eventName() : string
    {
        return 'clean';
    }
    /**
     * Retrieves notifications from the Mothership API and stores them in the database.
     */
    public function performEvent() : void
    {
        $hasChanges = \false;
        foreach ($this->store->fetch(\true)->notifications(\false) as $notification) {
            if ($notification->isExpired() || $notification->isStale()) {
                $this->store->delete($notification->id);
                $hasChanges = \true;
            }
        }
        if ($hasChanges) {
            $this->store->persist();
        }
    }
}
