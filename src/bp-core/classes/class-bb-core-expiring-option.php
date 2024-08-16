<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );}

/** This works the way I always anticipate transients to work but forget that
 * they don't work the way I think. They'll always reside in the options table
 * and will be self cleaning (as they're accessed).
 */
class BB_Core_Expiring_Option {

	public static function set( $name, $value, $expire_in_seconds ) {
		list($value_key,$timeout_key) = self::get_keys( $name );
		$timeout                      = self::calc_timeout( $expire_in_seconds );

		bp_update_option( $value_key, $value );
		bp_update_option( $timeout_key, $timeout );
	}

	public static function get( $name ) {
		list($value_key,$timeout_key) = self::get_keys( $name );
		$timeout                      = bp_get_option( $timeout_key );

		// Auto-cleanup if expired.
		if ( time() > (int) $timeout ) {
			bp_delete_option( $timeout_key );
			bp_delete_option( $value_key );
			return '';
		}

		return bp_get_option( $value_key );
	}

	private static function get_keys( $name ) {
		$value_key   = "_bb_expiring_{$name}";
		$timeout_key = "_bb_expiring_timeout_{$name}";

		return array( $value_key, $timeout_key );
	}

	private static function calc_timeout( $expire_in_seconds ) {
		return time() + $expire_in_seconds;
	}
} // End class

