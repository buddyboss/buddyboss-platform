<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Concerns;

/**
 * Serializes the object to an associative array or json string.
 */
trait Serializable
{
    /**
     * Serializes the object to an array that can be serialized by json_encode().
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }
    /**
     * Returns the object as an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        return (array) $this;
    }
    /**
     * Converts the object to a JSON string.
     *
     * @link https://www.php.net/manual/en/function.json-encode.php
     *
     * @param  integer $flags Optional flags passed to {@see json_encode}.
     * @param  integer $depth Maximum depth passed to {@see json_encode}.
     * @return string
     */
    public function toJson(int $flags = 0, int $depth = 512) : string
    {
        return \json_encode($this->jsonSerialize(), $flags, $depth);
    }
}
