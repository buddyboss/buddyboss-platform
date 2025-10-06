<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

use DateTime;
use DateTimeZone;
use BuddyBossPlatform\GroundLevel\Support\Exceptions\TimeTravelError;
use Exception;
use InvalidArgumentException;
class Time
{
    /**
     * Format alias for MySQL datetime format.
     *
     * @see Time::FORMAT_MYSQL
     */
    public const ALIAS_MYSQL = 'mysql';
    /**
     * Format alias for Unix timestamp format.
     *
     * @see Time::FORMAT_TIMESTAMP
     */
    public const ALIAS_TIMESTAMP = 'timestamp';
    /**
     * Datetime format for MySQL.
     */
    public const FORMAT_MYSQL = 'Y-m-d H:i:s';
    /**
     * Datetime format for a Unix timestamp.
     */
    public const FORMAT_TIMESTAMP = 'U';
    /**
     * Datetime format for a ISO 8601 date.
     *
     * E.g. 2004-02-12T15:19:21+00:00
     */
    public const FORMAT_ISO_8601 = 'c';
    /**
     * Time unit: seconds
     */
    public const UNIT_SECONDS = 'seconds';
    /**
     * Time unit: minutes
     */
    public const UNIT_MINUTES = 'minutes';
    /**
     * Time unit: hours
     */
    public const UNIT_HOURS = 'hours';
    /**
     * Time unit: days
     */
    public const UNIT_DAYS = 'days';
    /**
     * Time unit: weeks
     */
    public const UNIT_WEEKS = 'weeks';
    /**
     * Time unit: months
     */
    public const UNIT_MONTHS = 'months';
    /**
     * Time unit: years
     */
    public const UNIT_YEARS = 'years';
    /**
     * One minute in seconds.
     */
    public const MINUTE_IN_SECONDS = 60;
    /**
     * One hour in seconds.
     */
    public const HOUR_IN_SECONDS = 3600;
    /**
     * One day in seconds.
     */
    public const DAY_IN_SECONDS = 86400;
    /**
     * One week in seconds.
     */
    public const WEEK_IN_SECONDS = 7 * self::DAY_IN_SECONDS;
    /**
     * One month in seconds.
     */
    public const MONTH_IN_SECONDS = 30 * self::DAY_IN_SECONDS;
    /**
     * One year in seconds.
     */
    public const YEAR_IN_SECONDS = 365 * self::DAY_IN_SECONDS;
    /**
     * The current time when time travelling.
     *
     * @var null|integer
     */
    protected static ?int $currentTime = null;
    /**
     * Maps datetime format aliases to their PHP equivalents.
     *
     * @var string[]
     */
    protected static $formatAliases = [self::ALIAS_MYSQL => self::FORMAT_MYSQL, self::ALIAS_TIMESTAMP => self::FORMAT_TIMESTAMP];
    /**
     * Advances time by the specified amount of ticks, if time isn't frozen, freezes
     * time at the current timestamp.
     *
     * @param  integer       $ticks    The number of ticks to advance time by.
     * @param  string        $units    The unit of time to advance by, see Time::UNIT_* constants.
     * @param  callable|null $callback Optional callback function, if supplied time
     *                                will be unfrozen after the callback is executed.
     *                                The frozen time is provided to the callback
     *                                as a unix timestamp.
     * @return mixed Returns the result of the callback if supplied.
     * @throws TimeTravelError Throws exception when time traveling is disabled.
     */
    public static function advance(int $ticks, string $units = self::UNIT_SECONDS, ?callable $callback = null)
    {
        if (!self::isTraveling()) {
            self::freezeTime();
        }
        return self::advanceFrom(self::$currentTime, $ticks, $units, $callback);
    }
    /**
     * Advances time from the specified timestamp.
     *
     * @param  string|integer|DateTime $startTimestamp A unix timestamp, a DateTime object,
     *                                                or a string parseable by {@see strtotime}.
     * @param  integer                 $ticks          The number of ticks to advance time by.
     * @param  string                  $units          The unit of time to advance by, see Time::UNIT_* constants.
     * @param  callable|null           $callback       Optional callback function, if supplied time
     *                                                will be unfrozen after the callback is executed.
     *                                                The frozen time is provided to the callback
     *                                                as a unix timestamp.
     * @return mixed Returns the result of the callback if supplied.
     * @throws TimeTravelError Throws exception when time traveling is disabled.
     */
    public static function advanceFrom($startTimestamp, int $ticks, string $units = self::UNIT_SECONDS, ?callable $callback = null)
    {
        return self::travelTo(\strtotime("+{$ticks} {$units}", self::toTimestamp($startTimestamp)), $callback);
    }
    /**
     * Determines if time travel is allowed.
     *
     * By default time travel is only allowed when running unit and integration tests.
     *
     * @return boolean
     */
    protected static function canTravel() : bool
    {
        $disabled = \defined('BuddyBossPlatform\\GRDLVL_CLOCK_DISABLE_TIME_TRAVEL') ? GRDLVL_CLOCK_DISABLE_TIME_TRAVEL : \false;
        return !$disabled && (\defined('BuddyBossPlatform\\GRDLVL_TESTING') && GRDLVL_TESTING);
    }
    /**
     * Freezes time.
     *
     * @param  callable|null $callback Optional callback function, if supplied time
     *                                will be unfrozen after the callback is executed.
     *                                The frozen time is provided to the callback
     *                                as a unix timestamp.
     * @return mixed Returns the result of the callback if supplied.
     * @throws TimeTravelError Throws exception when time traveling is disabled.
     */
    public static function freezeTime(?callable $callback = null)
    {
        if (self::isTraveling()) {
            self::reset();
        }
        return self::travelTo(self::now('U', \true), $callback);
    }
    /**
     * Retrieves the default callable used by {@see Time::now}.
     *
     * @return null|callable
     */
    protected static function getDefaultNow() : ?callable
    {
        $func = \defined('BuddyBossPlatform\\GRDLVL_CLOCK_NOW_FUNCTION') ? GRDLVL_CLOCK_NOW_FUNCTION : 'current_time';
        return \is_callable($func) ? $func : null;
    }
    /**
     * Handles time travel function callbacks, executes the callback function and
     * then travels back to the current time.
     *
     * @param  callable|null $callback The callback function.
     * @return mixed Returns the result of the callback if supplied.
     */
    protected static function handleCallback(?callable $callback = null)
    {
        if ($callback !== null) {
            $res = $callback(self::$currentTime);
            self::reset();
            return $res;
        }
        return null;
    }
    /**
     * Determines if the clock is currently time traveling.
     *
     * @return boolean
     */
    public static function isTraveling() : bool
    {
        return self::$currentTime !== null;
    }
    /**
     * Retrieves the current time in the specified format.
     *
     * This function is a wrapper around the WordPress core {@see current_time}
     * function and falls back to a re-implementation which does not rely on any
     * WordPress functions. When using the fallback, the default timezone is
     * specified using the server's default timezone as retrieved by {@see date_default_timezone_get}.
     *
     * Plugins using this function should use the constant GRDLVL_CLOCK_NOW_FUNCTION to define
     * a callable to be used in place of {@see current_time}. This will enabled time travel
     * when running unit and integration tests.
     *
     * @param  string|null  $format The desired date format. Accepts any valid PHP date
     *                             format string as specfied by {@see DateTime::format}
     *                             additionally accepts shorthands 'mysql' to automatically
     *                             format in MySQL date format (Y-m-d H:i:s). Additionally
     *                             accepts 'timestamp' to return the date as a Unix timestamp.
     *                             If not supplied, a timestamp is automatically returned.
     * @param  integer|null $gmt    Whether to return the date in GMT. If false adjusts the
     *                             current time to use the site's timezone. When
     *                             both $gmt and $format are null, the default value is
     *                             true, otherwise the default value is false.
     * @return string|integer Current timestamp as an integer if $format is 'timestamp'
     *                    or 'U', otherwise returns the current time as a string
     *                    formatted by $format.
     */
    public static function now(?string $format = null, ?bool $gmt = null)
    {
        $gmt = \is_null($format) && \is_null($gmt) ? \true : $gmt;
        $format = \is_null($format) ? self::FORMAT_TIMESTAMP : $format;
        // Convert format aliases to the PHP format string.
        $format = self::$formatAliases[$format] ?? $format;
        // If we can and are time traveling, return the time travel date.
        if (self::canTravel() && self::isTraveling()) {
            $datetime = $gmt ? \gmdate($format, self::$currentTime) : \date($format, self::$currentTime);
            return self::FORMAT_TIMESTAMP === $format ? (int) $datetime : $datetime;
        }
        /*
         * Use the default now function if one is defined.
         *
         * If not defined and WordPress is loaded, current_time is used.
         */
        $func = self::getDefaultNow();
        if (\is_callable($func)) {
            return \call_user_func($func, $format, $gmt);
        }
        // Fallback to this WordPress-independent implementation of current_time().
        $tz = $gmt ? 'UTC' : \date_default_timezone_get();
        $datetime = new DateTime('now', new DateTimeZone($tz));
        if (self::FORMAT_TIMESTAMP === $format) {
            $ts = (int) $datetime->format($format);
            return $gmt ? $ts : $ts + (int) $datetime->format('Z');
        }
        return $datetime->format($format);
    }
    /**
     * Resets time travel.
     *
     * Future requests to {@see Time::now} will return the actual time.
     */
    public static function reset() : void
    {
        self::$currentTime = null;
    }
    /**
     * Rewinds time by the specified amount of ticks, if time isn't already frozen
     * it will be frozen at the current timestamp.
     *
     * @param  integer       $ticks    The number of ticks to adance time by.
     * @param  string        $units    The unit of time to advance by, see Time::UNIT_* constants.
     * @param  callable|null $callback Optional callback function, if supplied time
     *                                will be unfrozen after the callback is executed.
     *                                The frozen time is provided to the callback
     *                                as a unix timestamp.
     * @return mixed Returns the result of the callback if supplied.
     * @throws TimeTravelError Throws exception when time traveling is disabled.
     */
    public static function rewind(int $ticks, string $units = self::UNIT_SECONDS, ?callable $callback = null)
    {
        if (!self::isTraveling()) {
            self::freezeTime();
        }
        return self::rewindFrom(self::$currentTime, $ticks, $units, $callback);
    }
    /**
     * Rewinds time from the specified specified date.
     *
     * @param  string|integer|DateTime $startTimestamp A unix timestamp, a DateTime object,
     *                                                or a string parseable by {@see strtotime}.
     * @param  integer                 $ticks          The number of ticks to adance time by.
     * @param  string                  $units          The unit of time to advance by, see Time::UNIT_* constants.
     * @param  callable|null           $callback       Optional callback function, if supplied time
     *                                                will be unfrozen after the callback is executed.
     *                                                The frozen time is provided to the callback
     *                                                as a unix timestamp.
     * @return mixed Returns the result of the callback if supplied.
     * @throws TimeTravelError Throws exception when time traveling is disabled.
     */
    public static function rewindFrom($startTimestamp, int $ticks, string $units = self::UNIT_SECONDS, ?callable $callback = null)
    {
        return self::travelTo(\strtotime("-{$ticks} {$units}", self::toTimestamp($startTimestamp)), $callback);
    }
    /**
     * Normalizes a datimetime string or unix timestamp to a unix timestamp.
     *
     * @param  string|integer|DateTime $timestamp A unix timestamp, a DateTime object,
     *                                           or a string parseable by {@see strtotime}.
     * @return integer
     */
    public static function toTimestamp($timestamp) : int
    {
        if (\is_numeric($timestamp)) {
            return (int) $timestamp;
        } elseif (\is_a($timestamp, DateTime::class)) {
            return $timestamp->getTimestamp();
        }
        return \strtotime($timestamp);
    }
    /**
     * Time travel to the specific date and time.
     *
     * @param  string|integer|DateTime $timestamp A unix timestamp, a DateTime object,
     *                                           or a string parseable by {@see strtotime}.
     * @param  callable|null           $callback  Optional callback function, if supplied
     *                                           time will be unfrozen after the callback
     *                                           is executed. The frozen time is provided
     *                                           to the callback as a unix timestamp.
     * @throws TimeTravelError Throws exception when time traveling is disabled.
     */
    public static function travelTo($timestamp, ?callable $callback = null)
    {
        if (!self::canTravel()) {
            throw new TimeTravelError('Time travel is disabled.', TimeTravelError::E_TIME_TRAVEL_DISABLED);
        }
        self::$currentTime = self::toTimestamp($timestamp);
        return self::handleCallback($callback);
    }
    /**
     * Turn intervals into seconds.
     *
     * @param  integer $interval Interval's number of units.
     * @param  string  $unit     The unit of time of the interval, see `Time::UNIT_*` constants.
     * @return integer
     * @throws \InvalidArgumentException When the passed `$unit` is not one of the `Time::UNIT_*` constants.
     */
    public static function intervalToSeconds(int $interval, string $unit = Time::UNIT_MINUTES) : int
    {
        if (!\in_array($unit, [self::UNIT_SECONDS, self::UNIT_MINUTES, self::UNIT_HOURS, self::UNIT_DAYS, self::UNIT_WEEKS, self::UNIT_MONTHS, self::UNIT_YEARS], \true)) {
            throw new InvalidArgumentException('The unit parameter must be one of the GroundLevel\\Support\\Time::UNIT_* constants');
        }
        return $unit === self::UNIT_SECONDS ? $interval : self::$unit($interval);
    }
    /**
     * Converts minutes into seconds.
     *
     * @param  integer $n Number of minutes.
     * @return integer
     */
    public static function minutes(int $n = 1) : int
    {
        return $n * self::MINUTE_IN_SECONDS;
    }
    /**
     * Converts hours into seconds.
     *
     * @param  integer $n Number of hours.
     * @return integer
     */
    public static function hours(int $n = 1) : int
    {
        return $n * self::HOUR_IN_SECONDS;
    }
    /**
     * Converts days into seconds.
     *
     * @param  integer $n Number of days.
     * @return integer
     */
    public static function days(int $n = 1) : int
    {
        return $n * self::DAY_IN_SECONDS;
    }
    /**
     * Converts weeks into seconds.
     *
     * @param  integer $n Number of weeks.
     * @return integer
     */
    public static function weeks(int $n = 1) : int
    {
        return $n * self::WEEK_IN_SECONDS;
    }
    /**
     * Converts months into seconds elapsed since either now or a base time stamp.
     *
     * @param  integer         $n         Number of months.
     * @param  boolean|integer $baseTs    Base time stamp.
     * @param  boolean         $backwards Whether or not calculating months backwards.
     * @param  boolean|integer $dayNum    The day of the month.
     * @return integer
     */
    public static function months(int $n, $baseTs = \false, bool $backwards = \false, $dayNum = \false) : int
    {
        $baseTs = empty($baseTs) ? self::now() : $baseTs;
        $monthNum = \gmdate('n', $baseTs);
        $dayNum = (int) $dayNum < 1 || (int) $dayNum > 31 ? \gmdate('j', $baseTs) : $dayNum;
        $yearNum = \gmdate('Y', $baseTs);
        $hourNum = \gmdate('H', $baseTs);
        $minuteNum = \gmdate('i', $baseTs);
        $secondNum = \gmdate('s', $baseTs);
        /*
         * We're going to use the FIRST DAY of month for our calc date,
         * then adjust the day of month when we're done
         * This allows us to get the correct target month first,
         * then set the right day of month afterwards.
         */
        try {
            $calcDate = new DateTime("{$yearNum}-{$monthNum}-1 {$hourNum}:{$minuteNum}:{$secondNum}", new DateTimeZone('UTC'));
        } catch (Exception $e) {
            return 0;
        }
        if ($backwards) {
            $calcDate->modify("-{$n} month");
        } else {
            $calcDate->modify("+{$n} month");
        }
        $daysInNewMonth = $calcDate->format('t');
        // Now that we have the right month, let's get the right day of month.
        if ($daysInNewMonth < $dayNum) {
            $calcDate->modify('last day of this month');
        } elseif ($dayNum > 1) {
            $addDays = $dayNum - 1;
            // $calcDate is already at the first day of the month, so we'll minus one day here.
            $calcDate->modify("+{$addDays} day");
        }
        // If $backwards is true, this will most likely be a negative number so we'll use abs().
        return \abs($calcDate->getTimestamp() - $baseTs);
    }
    /**
     * Converts years into seconds elapsed since either now or a base time stamp.
     *
     * @param  integer         $n         Number of years.
     * @param  boolean|integer $baseTs    Base time stamp.
     * @param  boolean         $backwards Whether or not calculating years backwards.
     * @param  boolean|integer $dayNum    The day of the month.
     * @param  boolean|integer $monthNum  The month of the year.
     * @return integer
     */
    public static function years(int $n, $baseTs = \false, bool $backwards = \false, $dayNum = \false, $monthNum = \false) : int
    {
        $baseTs = empty($baseTs) ? self::now() : $baseTs;
        $dayNum = (int) $dayNum < 1 || (int) $dayNum > 31 ? \gmdate('j', $baseTs) : $dayNum;
        $monthNum = (int) $monthNum < 1 || (int) $monthNum > 12 ? \gmdate('n', $baseTs) : $monthNum;
        $yearNum = \gmdate('Y', $baseTs);
        $hourNum = \gmdate('H', $baseTs);
        $minuteNum = \gmdate('i', $baseTs);
        $secondNum = \gmdate('s', $baseTs);
        try {
            $calcDate = new DateTime("{$yearNum}-{$monthNum}-{$dayNum} {$hourNum}:{$minuteNum}:{$secondNum}", new DateTimeZone('UTC'));
        } catch (Exception $e) {
            return 0;
        }
        if ($backwards) {
            $calcDate->modify("-{$n} year");
        } else {
            $calcDate->modify("+{$n} year");
        }
        /*
         * If we're counting from Feb 29th on a Leap Year to a non-leap year we need to minus 1 day
         * or we'll end up with a March 1st date.
         */
        if ($dayNum === 29 && $monthNum === 2 && $calcDate->format('L') === 0) {
            $calcDate->modify('-1 day');
        }
        // If $backwards is true, this will most likely be a negative number so we'll use abs().
        return \abs($calcDate->getTimestamp() - $baseTs);
    }
}
