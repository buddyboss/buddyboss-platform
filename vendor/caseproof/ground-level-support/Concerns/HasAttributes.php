<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Concerns;

use BuddyBossPlatform\GroundLevel\Support\Casts;
use BuddyBossPlatform\GroundLevel\Support\Exceptions\InvalidFormatError;
use BuddyBossPlatform\GroundLevel\Support\Str;
/**
 * Trait enabling attribute access on an object.
 */
trait HasAttributes
{
    use Serializable;
    /**
     * The item's attributes.
     *
     * An array of the item attribute ID mapped to it's current value.
     *
     * @var array
     */
    protected array $attributes = [];
    /**
     * Array of attribute aliases.
     *
     * @var array
     */
    protected array $attributeAliases = [];
    /**
     * The item's changed attributes.
     *
     * @var array
     */
    protected array $changedAttributes = [];
    /**
     * Lists of the item's attribute formats.
     *
     * An array of the item attribute ID mapped to the ID's format.
     *
     * @var array
     */
    protected array $attributeFormats = [];
    /**
     * Casts a value to it's intended format.
     *
     * @param string $key   The keyname of the attribute.
     * @param mixed  $value The value.
     */
    protected function castAttribute(string $key, $value)
    {
        return Casts::cast($this->getAttributeFormat($key), $value);
    }
    /**
     * Sets attributes from a list of key=>value pairs.
     *
     * @param  array $attributes Associative array of attributes to set.
     * @return self
     */
    public function fillAttributes(array $attributes) : self
    {
        foreach ($attributes as $key => $val) {
            $this->setAttribute($key, $val);
        }
        return $this;
    }
    /**
     * Retrieves the value of an object attribute.
     *
     * @param  string $key The key name of the attribute.
     * @return mixed The attribute value.
     */
    public function getAttribute(string $key)
    {
        $possibleGetters = [Str::toCamelCase("get_{$key}"), Str::toCamelCase("get_{$this->getAttributeAlias($key)}")];
        foreach ($possibleGetters as $getter) {
            if (\method_exists($this, $getter)) {
                return $this->{$getter}($key);
            }
        }
        return $this->getAttributeSafe($key);
    }
    /**
     * Retrieves an alias for the given alias.
     *
     * Aliases are used to map a key to another key. This is useful when creating
     * getter/setter functions which have may have different names than the attribute
     * itself. For example getDateCreated may retrieve the configured date created
     * property which might be named something like "created_at".
     *
     * @param string $key The name of the attribute.
     */
    protected function getAttributeAlias(string $key) : string
    {
        return $this->attributeAliases[$key] ?? $key;
    }
    /**
     * Retrieves the format of the specified attribute by keyname.
     *
     * @param  string $key The attribute key.
     * @return Casts
     */
    protected function getAttributeFormat(string $key) : Casts
    {
        return $this->attributeFormats[$key] ?? Casts::STRING();
    }
    /**
     * Retrieves the value of an object attribute directly from the attributes array.
     *
     * @param  string $key The key name of the attribute.
     * @return null|mixed The attribute value or null if not set.
     */
    protected function getAttributeSafe(string $key)
    {
        return $this->attributes[$key] ?? null;
    }
    /**
     * Determines if the attribute has been changed.
     *
     * @param  string $key The attribute key.
     * @return boolean Returns true if the attribute has changed.
     */
    protected function hasAttributeChanged(string $key) : bool
    {
        return \in_array($key, $this->changedAttributes, \true);
    }
    /**
     * Determines if the model has any changed attributes.
     *
     * @return boolean
     */
    protected function hasChangedAttributes() : bool
    {
        return !empty($this->changedAttributes);
    }
    /**
     * Resets the changed attributes list.
     *
     * This does not act as an undo function, it only resets the list of changed
     * attributes.
     *
     * @return self
     */
    protected function resetChangedAttributes() : self
    {
        $this->changedAttributes = [];
        return $this;
    }
    /**
     * Set the value of a attribute.
     *
     * If custom set behavior is required, a setter method can be added to the class.
     * The setters name should be in camelCase starting with "set" and using the
     * attribute's keyname as the rest of the method. For example to add a custom
     * setter for the "foo-bar" attribute, add a method "setFooBar" or for an "id"
     * attribute, add "setId".
     *
     * @param  string $key   The attribute's key.
     * @param  mixed  $value The value of the attribute to set.
     * @return self
     */
    public function setAttribute(string $key, $value) : self
    {
        $possibleSetters = [Str::toCamelCase("set_{$key}"), Str::toCamelCase("set_{$this->getAttributeAlias($key)}")];
        foreach ($possibleSetters as $setter) {
            if (\method_exists($this, $setter)) {
                return $this->{$setter}($value);
            }
        }
        $this->setAttributeSafe($key, $value);
        return $this;
    }
    /**
     * Sets the value of the specified attribute and automatically casts it to
     * its specified format.
     *
     * @param string $key   The attribute's key.
     * @param mixed  $value The supplied value.
     */
    protected function setAttributeSafe(string $key, $value) : void
    {
        $castedVal = $this->castAttribute($key, $value);
        $currVal = $this->getAttributeSafe($key);
        if ($currVal !== $castedVal) {
            if (!$this->hasAttributeChanged($key)) {
                $this->changedAttributes[] = $key;
            }
            $this->attributes[$key] = $castedVal;
        }
    }
    /**
     * Returns the object as an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        return $this->attributes;
    }
    /**
     * Unsets an object attribute.
     *
     * @param  string $key The attribute name.
     * @return self
     */
    public function unsetAttribute(string $key) : self
    {
        unset($this->attributes[$key]);
        return $this;
    }
}
