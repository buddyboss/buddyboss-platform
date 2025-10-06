<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Concerns;

use BuddyBossPlatform\GroundLevel\Support\Models\User;
/**
 * Trait which defines an objects relationship to a user model.
 *
 * This trait assumes your user ID attribute is configured as an integer and will
 * result in type errors if it is not.
 */
trait HasUserRelationship
{
    /**
     * The attribute key where the user ID is stored.
     *
     * @var string
     */
    protected string $userIdKey = 'user_id';
    /**
     * Retrieves the value of an object attribute directly from the attributes array.
     *
     * @param  string $key The key name of the attribute.
     * @return null|mixed The attribute value or null if not set.
     */
    protected abstract function getAttributeSafe(string $key);
    /**
     * Retrieves the user ID key.
     *
     * @return string
     */
    protected function getUserIdKey() : string
    {
        return $this->userIdKey;
    }
    /**
     * Retrieves the user ID value.
     *
     * @return integer|null The user ID value or null if not set.
     */
    public function getUserId() : ?int
    {
        return $this->getAttributeSafe($this->getUserIdKey());
    }
    /**
     * Retrieves a user object.
     *
     * @return User|null
     */
    public function getUser() : ?User
    {
        $id = $this->getUserId();
        return $id ? new User($id) : null;
    }
}
