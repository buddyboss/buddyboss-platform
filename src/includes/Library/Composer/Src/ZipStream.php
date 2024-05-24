<?php

namespace BuddyBoss\Library\Composer;

class ZipStream
{
	private static $instance;
	/**
	 * Get the instance of the class.
	 *
	 * @return ZipStream
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class          = __CLASS__;
			self::$instance = new $class();
		}

		return self::$instance;
	}

	/**
	 * This Function Is Used To Get Instance From Scoped Vendor
	 *
	 * @return \ZipStream\ZipStream
	 */
	function zipstream( $file_name, $options ) {
		return new \ZipStream\ZipStream( $file_name, $options );
	}

	/**
	 * This Function Is Used To Get Instance From Scoped Vendor
	 *
	 * @return \ZipStream\Option\Archive
	 */
	function archive() {
		return new \ZipStream\Option\Archive();
	}
}
