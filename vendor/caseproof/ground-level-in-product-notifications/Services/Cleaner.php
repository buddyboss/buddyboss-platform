<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Services;

class Cleaner extends ScheduledService
{
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
        /** @var Store $store */
        // phpcs:ignore
        $store = $this->container->get(Store::class);
        foreach ($store->fetch(\true)->notifications(\false) as $notification) {
            if ($notification->isExpired() || $notification->isStale()) {
                $store->delete($notification->id);
                $hasChanges = \true;
            }
        }
        if ($hasChanges) {
            $store->persist();
        }
    }
}
