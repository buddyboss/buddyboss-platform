<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );}

/** Helper methods for working with hooks in BuddyBoss Platform */
class BB_Core_Hooks {
	public static function do_action( $tag, $arg = '' ) {
		return self::call( __FUNCTION__, $tag, func_get_args() );
	}

	public static function do_action_ref_array( $tag, $args ) {
		return self::call( __FUNCTION__, $tag, func_get_args() );
	}

	/**
	 * @param string $tag The name of the filter to hook the $value to.
	 * @param mixed  $value The value to filter.
	 * @return mixed
	 */
	public static function apply_filters( $tag, $value ) {
		return self::call( __FUNCTION__, $tag, func_get_args(), 'filter' );
	}

	public static function apply_filters_ref_array( $tag, $args ) {
		return self::call( __FUNCTION__, $tag, func_get_args(), 'filter' );
	}

	public static function add_shortcode( $tag, $callback ) {
		return self::call( __FUNCTION__, $tag, func_get_args(), 'shortcode' );
	}

	private static function call( $fn, $tag, $args, $type = 'action' ) {
		$tags = self::tags( $tag );

		foreach ( $tags as $t ) {
			$args[0] = $t;

			if ( 'filter' === $type ) {
				$args[1] = call_user_func_array( $fn, $args );
			} else {
				call_user_func_array( $fn, $args );
			}
		}

		if ( 'filter' === $type ) {
			return $args[1];
		}
	}

	// We love dashes and underscores ... we just can't choose which we like better :).
	private static function tags( $tag ) {
		// Prepend bb if it doesn't exist already.
		if ( ! preg_match( '/^bb[-_]/i', $tag ) ) {
			$tag = 'bb_' . $tag;
		}

		$tags = array(
			'-' => preg_replace( '/[-_]/', '-', $tag ),
			'_' => preg_replace( '/[-_]/', '_', $tag ),
		);

		// in case, the original tag has mixed dashes and underscores.
		if ( ! in_array( $tag, array_values( $tags ) ) ) {
			$tags['*'] = $tag;
		}

		return $tags;
	}
}

