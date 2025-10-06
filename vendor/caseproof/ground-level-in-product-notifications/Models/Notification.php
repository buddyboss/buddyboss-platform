<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\InProductNotifications\Models;

use BuddyBossPlatform\GroundLevel\Support\Time;
use InvalidArgumentException;
class Notification
{
    /**
     * Notification database context.
     */
    public const CONTEXT_DB = 'db';
    /**
     * Notification display context.
     */
    public const CONTEXT_DISPLAY = 'display';
    /**
     * The HTML content of the notification.
     *
     * @var string
     */
    public string $content;
    /**
     * Expiration date.
     *
     * @var string
     */
    public string $expiresAt;
    /**
     * The HTML content of the notification icon.
     *
     * @var string
     */
    public string $icon;
    /**
     * The unique ID.
     *
     * @var string
     */
    public string $id;
    /**
     * Publication date.
     *
     * @var string
     */
    public string $publishesAt;
    /**
     * Whether the notification has been read.
     *
     * @var boolean
     */
    public bool $read;
    /**
     * Read date.
     *
     * @var string
     */
    public int $readAt;
    /**
     * The subject of the notification.
     *
     * @var string
     */
    public string $subject;
    /**
     * The current notification context.
     *
     * When preparing a notification for storage in the database, the {@see self::CONTEXT_DB}
     * context should be used and when preparing a notification for display, the
     * {@see self::CONTEXT_DISPLAY} context should be used.
     *
     * @var string
     */
    private string $context;
    /**
     * Notification constructor.
     *
     * @param array  $rawData An associative array of raw notification data.
     * @param string $context The current notification context.
     *
     * @throws InvalidArgumentException When an invalid context is provided.
     */
    public function __construct(array $rawData, string $context = self::CONTEXT_DB)
    {
        $contexts = [self::CONTEXT_DB, self::CONTEXT_DISPLAY];
        if (!\in_array($context, $contexts, \true)) {
            throw new InvalidArgumentException("Invalid context: {$context}. Must be one of: " . \implode('|', $contexts));
        }
        $this->context = $context;
        $this->content = $rawData['content'] ?? '';
        $this->expiresAt = $rawData['expires_at'] ?? $rawData['expiresAt'] ?? '';
        $this->icon = $rawData['icon'] ?? '';
        $this->id = $rawData['id'] ?? '';
        $this->publishesAt = $rawData['publishes_at'] ?? $rawData['publishesAt'] ?? '';
        $this->read = $rawData['read'] ?? \false;
        $this->readAt = $rawData['readAt'] ?? 0;
        $this->subject = $rawData['subject'] ?? '';
    }
    /**
     * Determine if the notification is expired.
     *
     * @return boolean
     */
    public function isExpired() : bool
    {
        return !empty($this->expiresAt) && \strtotime($this->expiresAt) <= Time::now();
    }
    /**
     * Determine if the notification is stale.
     *
     * A stale notification is one that has been read more than two weeks ago.
     *
     * Stale notifications are automatically deleted by the {@see Cleaner} service
     * daily.
     *
     * @return boolean
     */
    public function isStale() : bool
    {
        return $this->read && $this->readAt <= \strtotime('-2 weeks');
    }
    /**
     * Determines if the notification can be displayed on site.
     *
     * A notification should not be displayed if it's expired or if it's scheduled
     * for publication in the future.
     *
     * @return boolean
     */
    public function shouldDisplay() : bool
    {
        return !$this->isExpired() && Time::now() >= \strtotime($this->publishesAt);
    }
    /**
     * Convert the notification to an associative array.
     *
     * @return array
     */
    public function toArray() : array
    {
        $arr = \get_object_vars($this);
        if (self::CONTEXT_DISPLAY === $this->context) {
            $arr['created_at'] = $this->publishesAt;
            unset($arr['expiresAt'], $arr['publishesAt'], $arr['readAt']);
        }
        unset($arr['context']);
        return $arr;
    }
}
