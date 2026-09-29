<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Models;

use BuddyBossPlatform\GroundLevel\Support\Concerns\Serializable;
use BuddyBossPlatform\GroundLevel\Support\Contracts\Arrayable;
use BuddyBossPlatform\GroundLevel\Support\Contracts\Jsonable;
use ReflectionClass;
use ReflectionProperty;
/**
 * WordPress Transient Model
 *
 * Provides an API for WordPress transients with full type safety using public properties.
 * Supports both scalar values and complex objects. Also supports site-wide transients.
 *
 * ## Scalar Transients
 * For single-value transients, pass $isScalar = true (defaults to false):
 *
 * @example
 * class CounterTransient extends Transient {
 * }
 * $counter = new CounterTransient('my_counter', 3600, '', false, true);
 * $counter->setValue(42);
 * $counter->save();
 *
 * ## Complex Transients
 * For multi-property transients, define public properties ($isScalar defaults to false):
 *
 * @example
 * class ActivationTransient extends Transient {
 *     public string $license_key = '';
 *     public string $status = '';
 *     public int $activations_used = 0;
 *
 *     public function isActive(): bool {
 *         return $this->status === 'enabled';
 *     }
 * }
 * $license = new ActivationTransient('activation');
 * $license->license_key = 'ABC-123';
 * $license->status = 'enabled';
 * $license->save();
 *
 * ## Site-wide Transients
 * @example
 * class GlobalSettingsTransient extends Transient {
 *     public array $settings = [];
 * }
 * $settings = new GlobalSettingsTransient('global_settings', DAY_IN_SECONDS, '', true);
 */
abstract class Transient implements Arrayable, Jsonable
{
    use Serializable;
    /**
     * The transient name.
     *
     * @var string
     */
    protected string $transientName;
    /**
     * Optional prefix for the transient name.
     *
     * A trailing underscore is automatically added if not present.
     *
     * @var string
     */
    protected string $prefix;
    /**
     * Whether this transient stores a scalar value.
     *
     * When true, uses the internal $value property for storage.
     * When false, uses public properties for complex object storage.
     *
     * @var boolean
     */
    protected bool $isScalar;
    /**
     * Whether this is a site-wide transient (network transient).
     *
     * @var boolean
     */
    protected bool $isSite;
    /**
     * Transient expiration time in seconds.
     *
     * Set to 0 for no expiration.
     *
     * @var integer
     */
    protected int $expiration;
    /**
     * Whether the transient has been loaded from database.
     *
     * @var boolean
     */
    protected bool $isLoaded = \false;
    /**
     * Stores the value for scalar transients.
     *
     * @var mixed
     */
    protected $value;
    /**
     * Constructs a new transient.
     *
     * @param string  $transientName The transient name.
     * @param integer $expiration    Expiration time in seconds. Defaults to 1 day.
     * @param string  $prefix        Optional prefix for the transient name. Defaults to empty string (no prefix).
     * @param boolean $isSite        Whether this is a site-wide transient. Defaults to false (regular transient).
     * @param boolean $isScalar      Whether this transient stores a scalar value. Defaults to false (complex transient).
     * @param boolean $load          Whether to load the transient data from the database immediately. Defaults to true.
     */
    public function __construct(string $transientName, int $expiration = DAY_IN_SECONDS, string $prefix = '', bool $isSite = \false, bool $isScalar = \false, bool $load = \true)
    {
        $this->transientName = $transientName;
        $this->expiration = $expiration;
        $this->prefix = $prefix;
        $this->isSite = $isSite;
        $this->isScalar = $isScalar;
        $this->validateTransientType();
        if ($load) {
            $this->load();
        }
    }
    /**
     * Loads the transient data from WordPress.
     *
     * @return self
     */
    public function load() : self
    {
        $data = $this->getTransientValue();
        if (\false !== $data) {
            if ($this->isScalar) {
                // Scalar transient: store value directly.
                $this->value = $data;
            } elseif (\is_array($data)) {
                // Complex transient: fill properties from array.
                $this->fillFromArray($data);
            }
            $this->isLoaded = \true;
        } else {
            $this->isLoaded = \false;
        }
        return $this;
    }
    /**
     * Saves the transient to WordPress.
     *
     * @return boolean True on success, false on failure.
     */
    public function save() : bool
    {
        // Determine what to save based on transient type.
        $data = $this->isScalar ? $this->value : $this->toArray();
        $result = $this->isSite ? set_site_transient($this->getTransientName(), $data, $this->expiration) : set_transient($this->getTransientName(), $data, $this->expiration);
        if ($result) {
            $this->isLoaded = \true;
        }
        return $result;
    }
    /**
     * Deletes the transient from WordPress.
     *
     * @return boolean True on success, false on failure.
     */
    public function delete() : bool
    {
        $result = $this->isSite ? delete_site_transient($this->getTransientName()) : delete_transient($this->getTransientName());
        if ($result) {
            $this->isLoaded = \false;
            $this->reset();
        }
        return $result;
    }
    /**
     * Checks if the transient exists in WordPress.
     *
     * @return boolean True if exists, false otherwise.
     */
    public function exists() : bool
    {
        return $this->isLoaded || \false !== $this->getTransientValue();
    }
    /**
     * Gets the transient value.
     *
     * For scalar transients, returns the scalar value.
     * For complex transients, returns the full array of properties.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->isScalar ? $this->value : $this->toArray();
    }
    /**
     * Sets the transient value.
     *
     * For scalar transients, sets the scalar value (with type validation).
     * For complex transients, fills properties from array.
     *
     * @param  mixed $value The value to set.
     * @return self
     * @throws \InvalidArgumentException If value type doesn't match transient type.
     */
    public function setValue($value) : self
    {
        if ($this->isScalar) {
            // Scalar transient:  set value directly.
            $this->value = $value;
        } else {
            // Complex transient:  value must be an array.
            if (!\is_array($value)) {
                throw new \InvalidArgumentException(\sprintf('Complex transient expects array, %s given', \gettype($value)));
            }
            $this->fillFromArray($value);
        }
        return $this;
    }
    /**
     * Updates the transient with a new value and saves it.
     *
     * Convenience method for scalar transients.
     *
     * @param  mixed $value The value to set.
     * @return boolean True on success, false on failure.
     */
    public function update($value) : bool
    {
        $this->setValue($value);
        return $this->save();
    }
    /**
     * Gets the raw transient value from WordPress.
     *
     * @return mixed
     */
    protected function getTransientValue()
    {
        return $this->isSite ? get_site_transient($this->getTransientName()) : get_transient($this->getTransientName());
    }
    /**
     * Gets the prefixed transient name.
     *
     * @return string
     */
    protected function getTransientName() : string
    {
        if (empty($this->prefix)) {
            return $this->transientName;
        }
        // Ensure prefix ends with underscore.
        $prefix = '_' === \substr($this->prefix, -1) ? $this->prefix : $this->prefix . '_';
        return \strtolower($prefix) . $this->transientName;
    }
    /**
     * Determines if this is a site-wide transient.
     *
     * @return boolean
     */
    public function isSite() : bool
    {
        return $this->isSite;
    }
    /**
     * Determines if the transient has been loaded from WordPress.
     *
     * @return boolean
     */
    public function isLoaded() : bool
    {
        return $this->isLoaded;
    }
    /**
     * Fills the transient properties from an array.
     *
     * @param array $data The data to fill from.
     */
    protected function fillFromArray(array $data) : void
    {
        $properties = $this->getPublicProperties();
        foreach ($properties as $property) {
            $name = $property->getName();
            if (\array_key_exists($name, $data)) {
                $this->{$name} = $data[$name];
            }
        }
    }
    /**
     * Converts the transient to an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        $properties = $this->getPublicProperties();
        $data = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $data[$name] = $this->{$name};
        }
        return $data;
    }
    /**
     * Gets all public properties of the transient using reflection.
     *
     * @return ReflectionProperty[]
     */
    protected function getPublicProperties() : array
    {
        $reflection = new ReflectionClass($this);
        return $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
    }
    /**
     * Resets the transient to its default state.
     *
     * For scalar transients, resets the value to null.
     * For complex transients, resets all public properties to their defaults.
     */
    public function reset() : void
    {
        if ($this->isScalar) {
            $this->value = null;
        } else {
            $reflection = new ReflectionClass($this);
            $defaultInstance = $reflection->newInstanceWithoutConstructor();
            $properties = $this->getPublicProperties();
            foreach ($properties as $property) {
                $name = $property->getName();
                if ($property->isInitialized($defaultInstance)) {
                    $this->{$name} = $defaultInstance->{$name};
                }
            }
        }
    }
    /**
     * Validates that the transient type matches its configuration.
     *
     * Ensures that scalar transients don't have public properties,
     * and complex transients do have public properties.
     *
     * @throws \LogicException If configuration is invalid.
     */
    protected function validateTransientType() : void
    {
        $publicProps = $this->getPublicProperties();
        if ($this->isScalar && !empty($publicProps)) {
            throw new \LogicException(\sprintf('%s is marked as scalar ($isScalar = true) but has public properties.' . ' Set $isScalar = false or remove public properties.', static::class));
        }
        if (!$this->isScalar && empty($publicProps)) {
            throw new \LogicException(\sprintf('%s is marked as complex ($isScalar = false) but has no public properties.' . ' Set $isScalar = true or add public properties.', static::class));
        }
    }
}
