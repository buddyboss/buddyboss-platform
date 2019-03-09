<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

namespace Buddyboss\LearndashIntegration\Library;

/**
 * 
 * 
 * @since BuddyBoss 1.0.0
 */
class ValueLoader
{
	protected $value = [];

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function get($key = null, $default = null)
	{
		$target = $this->value;

		if (is_null($key)) {
			return $target;
		}

        if (isset($target[$key])) {
        	return $target[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($target) || ! array_key_exists($segment, $target)) {
                return $default;
            }

            $target = $target[$segment];
        }

        return $target;
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function set($key = null, $value = null)
	{
		$target =& $this->value;

        if (is_null($key)) {
        	return $target = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (! isset($target[$key]) || ! is_array($target[$key])) {
                $target[$key] = array();
            }

            $target =& $target[$key];
        }

        $target[array_shift($keys)] = $value;

        return $target;
    }
}
