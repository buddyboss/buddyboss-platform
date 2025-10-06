<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Models;

use ValueError;
/**
 * WordPress Hook Model
 */
class Hook
{
    /**
     * Action hook type.
     */
    public const TYPE_ACTION = 'action';
    /**
     * Filter hook type.
     */
    public const TYPE_FILTER = 'filter';
    /**
     * The number of arguments accepted by the callback function.
     *
     * @var integer
     */
    protected int $acceptedArgs;
    /**
     * The callback function.
     *
     * @var callable
     */
    protected $callback;
    /**
     * The name of the hook.
     *
     * @var string
     */
    protected string $hook;
    /**
     * The hook priority.
     *
     * @var integer
     */
    protected int $priority;
    /**
     * The hook type.
     *
     * One of {@see self::TYPE_ACTION} or {@see self::TYPE_HOOK}.
     *
     * @var string
     */
    protected string $type;
    /**
     * Constructs a new hook.
     *
     * @param string   $type         The type of hook.
     * @param string   $hook         The name of the hook.
     * @param callable $callback     The callback function.
     * @param integer  $priority     The hook priority.
     * @param integer  $acceptedArgs The number of arguments accepted by the callback
     *                               function.
     *
     * @throws ValueError Throws a ValueError when an invalid $type is supplied.
     */
    public function __construct(string $type, string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1)
    {
        $validTypes = [self::TYPE_ACTION, self::TYPE_FILTER];
        if (!\in_array($type, $validTypes, \true)) {
            throw new ValueError(\sprintf('The value %1$s is invalid for the $type parameter. Valid types are: %2$s.', $type, \implode('|', $validTypes)));
        }
        $this->type = $type;
        $this->hook = $hook;
        $this->callback = $callback;
        $this->priority = $priority;
        $this->acceptedArgs = $acceptedArgs;
    }
    /**
     * Adds the hook.
     */
    public function add() : void
    {
        $func = "add_{$this->type}";
        $func($this->hook, $this->callback, $this->priority, $this->acceptedArgs);
    }
    /**
     * Retrieves the priority of the registered hook.
     *
     * @return false|integer Returns the priority of the hook if it has been registered
     *                       otherwise returns false.
     */
    public function getRegisteredPriority()
    {
        $func = "has_{$this->type}";
        return $func($this->hook, $this->callback);
    }
    /**
     * Determines whether or not the hook has been registered.
     *
     * @return boolean
     */
    public function isRegistered() : bool
    {
        return $this->getRegisteredPriority() ? \true : \false;
    }
    /**
     * Removes the hook.
     *
     * @return boolean Returns true when the hook existed before it was removed,
     *                 otherwise returns false.
     */
    public function remove() : bool
    {
        $func = "remove_{$this->type}";
        return $func($this->hook, $this->callback, $this->priority);
    }
}
