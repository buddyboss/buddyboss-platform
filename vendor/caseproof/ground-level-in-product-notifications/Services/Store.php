<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Services;

use BuddyBossPlatform\GroundLevel\Container\Service;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Models\Notification;
use BuddyBossPlatform\GroundLevel\Support\Time;
use InvalidArgumentException;
class Store extends Service
{
    /**
     * Filter for unread notifications.
     *
     * @var string
     */
    public const FILTER_UNREAD = 'unread';
    /**
     * The option key/name where notification data will be stored.
     *
     * @var string
     */
    protected string $key;
    /**
     * The data to be stored.
     *
     * @var array
     */
    protected array $data;
    /**
     * Adds a notification to the store.
     *
     * @param  array   $raw        The raw notification data.
     * @param  boolean $withLastId If true, set the last notification ID.
     * @return \GroundLevel\InProductNotifications\Services\Store
     */
    public function add(array $raw, bool $withLastId = \false) : self
    {
        $notification = new Notification($raw);
        $this->data[$notification->id] = $notification->toArray();
        if ($withLastId) {
            $this->setLastId($notification->id);
        }
        return $this;
    }
    /**
     * Clear all stored data.
     *
     * @return \GroundLevel\InProductNotifications\Services\Store
     */
    public function clear() : self
    {
        $this->data = [];
        return $this;
    }
    /**
     * Delete a notification from the store.
     *
     * @param  string $id The notification ID.
     * @return \GroundLevel\InProductNotifications\Services\Store
     */
    public function delete(string $id) : self
    {
        unset($this->data[$id]);
        return $this;
    }
    /**
     * Retrieves stored data from the database.
     *
     * @param  boolean $force If true, force a fetch from the database.
     * @return \GroundLevel\InProductNotifications\Services\Store
     */
    public function fetch(bool $force = \false) : self
    {
        if (empty($this->data) || $force) {
            $data = get_option($this->key, []);
            $this->data = \is_array($data) ? $data : [];
        }
        return $this;
    }
    /**
     * Retrieves a notification by ID.
     *
     * @param  string $id      The notification ID.
     * @param  string $context The notification context.
     * @return \GroundLevel\InProductNotifications\Models\Notification|null
     */
    public function get(string $id, string $context = Notification::CONTEXT_DISPLAY) : ?Notification
    {
        $raw = $this->data[$id] ?? [];
        return empty($raw) ? null : new Notification($raw, $context);
    }
    /**
     * Determines if a notification exists.
     *
     * @param  string $id The notification ID.
     * @return boolean
     */
    public function has(string $id) : bool
    {
        return isset($this->data[$id]);
    }
    /**
     * Retrieves the last notification ID.
     *
     * @return string
     */
    public function lastId() : string
    {
        return $this->data['__lastId'] ?? '';
    }
    /**
     * Mark a notification as read.
     *
     * @param  string $id The notification ID.
     * @return \GroundLevel\InProductNotifications\Services\Store
     */
    public function markRead(string $id) : self
    {
        if (isset($this->data[$id])) {
            $this->data[$id]['read'] = \true;
            $this->data[$id]['readAt'] = Time::now();
        }
        return $this;
    }
    /**
     * Retrieves notifications for display.
     *
     * This method converts notifications to an array of arrays to which can be
     * converted to JSON and passed to the React inbox component.
     *
     * @param  boolean $asArray If true, return an array of arrays instead of
     *                          Notification objects.
     * @param  string  $filter  The filter to apply, must be one of {@see self::FILTER_*}
     *                          constants.
     * @return array<array|Notification>
     * @throws InvalidArgumentException When an invalid $filter is provided.
     */
    public function notifications(bool $asArray = \true, string $filter = '') : array
    {
        if (!empty($filter) && self::FILTER_UNREAD !== $filter) {
            throw new InvalidArgumentException("Invalid filter: {$filter}, must be one of: " . self::FILTER_UNREAD);
        }
        $notifications = $this->data;
        unset($notifications['__lastId']);
        return \array_values(\array_filter(\array_map(function (array $notification) use($asArray, $filter) {
            $notification = new Notification($notification, Notification::CONTEXT_DISPLAY);
            // Remove expired notifications.
            if (!$notification->shouldDisplay()) {
                return null;
            }
            // If filtering by unread only, remove read notifications.
            if (self::FILTER_UNREAD === $filter && $notification->read) {
                return null;
            }
            return $asArray ? $notification->toArray() : $notification;
        }, $notifications)));
    }
    /**
     * Persist data to the database.
     *
     * @return \GroundLevel\InProductNotifications\Services\Store
     */
    public function persist() : self
    {
        update_option($this->key, $this->data, \false);
        return $this;
    }
    /**
     * Sets the option key/name where notification data will be stored.
     *
     * @param string $key The option key/name.
     */
    public function setKey(string $key) : void
    {
        $this->key = $key;
    }
    /**
     * Sets the last notification ID.
     *
     * @param  string $id The notification ID.
     * @return \GroundLevel\InProductNotifications\Services\Store
     */
    public function setLastId(string $id) : self
    {
        $this->data['__lastId'] = $id;
        return $this;
    }
}
