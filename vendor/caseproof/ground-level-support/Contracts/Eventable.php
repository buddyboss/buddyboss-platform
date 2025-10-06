<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Contracts;

interface Eventable
{
    /**
     * Emits an event that can be listened to by other classes.
     *
     * @param string $event   The event name.
     * @param mixed  ...$args The arguments to pass to the listener callback.
     */
    public function emit(string $event, ...$args) : void;
    /**
     * Attaches a listener to an event.
     *
     * @param string   $event    The event name.
     * @param callable $callback The callback to call when the event is emitted.
     */
    public function on(string $event, callable $callback) : void;
}
