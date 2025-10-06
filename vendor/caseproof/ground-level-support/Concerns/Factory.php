<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Concerns;

use TypeError;
/**
 * Instantiates and stores objects and object instances.
 */
trait Factory
{
    /**
     * Holds references to all instantiated class instances.
     *
     * @var object[]
     */
    protected static array $factoryItems = [];
    /**
     * Defines the allowed type of items in the Factory.
     *
     * When adding items to the Factory only items of this type
     * will be added.
     *
     * @return string
     */
    protected static abstract function setItemType() : string;
    /**
     * Adds an item to the Factory.
     *
     * @param integer|string $id   The item ID.
     * @param object         $item The item instance.
     *
     * @throws TypeError Throws a type error when an invalid item is supplied.
     */
    public static function addItem($id, $item) : void
    {
        if (!self::validateItem($item)) {
            $type = \gettype($item);
            throw new TypeError(\sprintf('%1$s::%2$s(): Argument #2 ($item) must be of type %3$s, %4$s given.', __CLASS__, 'addItem', self::setItemType(), 'object' === $type ? \get_class($item) : $type));
        }
        self::$factoryItems[$id] = $item;
    }
    /**
     * Instantiates a new instance of the object.
     *
     * @param  integer|string $id      The object ID.
     * @param  mixed          ...$args Additional initialization arguments.
     * @return object The object instance.
     */
    public static function createItem($id, ...$args)
    {
        $type = self::setItemType();
        return new $type($id, ...$args);
    }
    /**
     * Unsets a Factory item instance by ID.
     *
     * @param string $id The item ID.
     */
    public static function deleteItem(string $id) : void
    {
        unset(self::$factoryItems[$id]);
    }
    /**
     * Retrieves an existing instance for the given object ID and initializes a new
     * object if not found.
     *
     * @param  integer|string $id      The object ID.
     * @param  mixed          ...$args Additional initialization arguments.
     * @return object The object instance.
     */
    public static function getItem($id, ...$args)
    {
        if (!self::itemExists($id)) {
            self::addItem($id, self::createItem($id, ...$args));
        }
        return self::$factoryItems[$id] ?? null;
    }
    /**
     * Determines if an item exists.
     *
     * @param  integer|string $id The item ID.
     * @return boolean
     */
    public static function itemExists($id) : bool
    {
        return \array_key_exists($id, self::$factoryItems);
    }
    /**
     * Lists all items in the Factory.
     *
     * @return object[]
     */
    public static function listItems() : array
    {
        return self::$factoryItems;
    }
    /**
     * Resets the Factory.
     */
    public static function resetItems() : void
    {
        self::$factoryItems = [];
    }
    /**
     * Validates whether or not an item is valid for the Factory.
     *
     * @param  object $item The item instance.
     * @return boolean
     */
    public static function validateItem($item) : bool
    {
        return \is_a($item, self::setItemType());
    }
}
