<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Request;
/**
 * Deprecated trait for paginated responses.
 *
 * @deprecated Use methods from {@see \GroundLevel\Mothership\Api\Response} instead.
 */
trait PaginatedResponse
{
    /**
     * Check if there is a next page of data.
     *
     * @return boolean
     */
    public function hasNext() : bool
    {
        return isset($this->data->_links->next);
    }
    /**
     * Get the next page of data.
     *
     * @return object|null
     */
    public function next() : ?object
    {
        if (!$this->hasNext()) {
            return null;
        }
        $endpoint = \basename(wp_parse_url($this->data->_links->next, \PHP_URL_PATH));
        $query = [];
        \parse_str(wp_parse_url($this->data->_links->next, \PHP_URL_QUERY), $query);
        return Request::get($endpoint, $query);
    }
    /**
     * Check if there is a previous page of data.
     *
     * @return boolean
     */
    public function hasPrevious() : bool
    {
        return isset($this->data->_links->prev);
    }
    /**
     * Get the previous page of data.
     *
     * @return object|null
     */
    public function previous() : ?object
    {
        if (!$this->hasPrevious()) {
            return null;
        }
        $endpoint = \basename(wp_parse_url($this->data->_links->prev, \PHP_URL_PATH));
        $query = [];
        \parse_str(wp_parse_url($this->data->_links->prev, \PHP_URL_QUERY), $query);
        return Request::get($endpoint, $query);
    }
}
