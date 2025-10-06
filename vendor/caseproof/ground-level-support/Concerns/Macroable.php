<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support\Concerns;

use BadMethodCallException;
use Closure;
/**
 * Add "macro" support to a class.
 *
 * Macros can serve as a mechanism to configure commonly used configurations
 * of the specified class.
 */
trait Macroable
{
    /**
     * Array of registered macros.
     *
     * @var array
     */
    protected static array $macros = [];
    /**
     * Dynamically invokes calls to the macro.
     *
     * @param  string $method The called method.
     * @param  array  $args   Arguments passed to the method.
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return static::invokeMacro($method, $args);
    }
    /**
     * Dynamically invokes static calls to the macro.
     *
     * @param  string $method The called method.
     * @param  array  $args   Arguments passed to the method.
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        return static::invokeMacro($method, $args);
    }
    /**
     * Deregisters an existing macro.
     *
     * @param string $id The macro ID.
     */
    public static function deregisterMacro(string $id) : void
    {
        if (static::hasMacro($id)) {
            unset(static::$macros[$id]);
        }
    }
    /**
     * Determines if a macro is registered
     *
     * @param  string $id The macro ID.
     * @return boolean
     */
    public static function hasMacro(string $id) : bool
    {
        return isset(static::$macros[$id]);
    }
    /**
     * Invokes a macro.
     *
     * @param  string $id   The macro ID.
     * @param  array  $args Arguments to pass to the macro.
     * @return mixed
     *
     * @throws BadMethodCallException Throws an exception when the macro isn't
     *                                registered.
     */
    protected static function invokeMacro(string $id, array $args)
    {
        if (!static::hasMacro($id)) {
            throw new BadMethodCallException(\sprintf('Method %1$s::%2$s does not exist', static::class, $id));
        }
        $macro = static::$macros[$id];
        if ($macro instanceof Closure) {
            $macro = $macro->bindTo(null, static::class);
        }
        return $macro(...$args);
    }
    /**
     * Registers a new macro
     *
     * @param string   $id       The macro ID.
     * @param callable $callback The macro callback.
     */
    public static function registerMacro(string $id, callable $callback) : void
    {
        static::$macros[$id] = $callback;
    }
    /**
     * Deregisters all existing macros.
     */
    public static function resetMacros() : void
    {
        static::$macros = [];
    }
}
