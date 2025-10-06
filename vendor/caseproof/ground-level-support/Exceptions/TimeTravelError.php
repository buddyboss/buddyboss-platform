<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Exceptions;

/**
 * An exception with support for arbitrary error data.
 */
class TimeTravelError extends Exception
{
    /**
     * Error code used when time travel is disabled.
     */
    public const E_TIME_TRAVEL_DISABLED = 100;
}
