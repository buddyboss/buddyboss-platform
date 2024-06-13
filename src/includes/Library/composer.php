<?php

namespace BuddyBoss\Library;

/**
 * Composer class.
 *
 * @since BuddyBoss 2.6.30
 */
class Composer {

	/**
	 * @var $instance
	 *
	 * @since BuddyBoss 2.6.30
	 */
	private static $instance;

	/**
	 * Get the instance of the class.
	 *
	 * @since BuddyBoss 2.6.30
	 *
	 * @return Composer
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class          = __CLASS__;
			self::$instance = new $class();
		}

		return self::$instance;
	}

	/**
	 * This function is used to get ZipStream instance from scoped vendor.
	 *
	 * @since BuddyBoss 2.6.30
	 *
	 * @return \BuddyBoss\Library\Composer\ZipStream/\BuddyBossPlatform\BuddyBoss\Library\Composer\ZipStream
	 */
	function zipstream_instance() {
		if ( class_exists( '\BuddyBossPlatform\BuddyBoss\Library\Composer\ZipStream' ) ) {
			return \BuddyBossPlatform\BuddyBoss\Library\Composer\ZipStream::instance();
		}

		return \BuddyBoss\Library\Composer\ZipStream::instance();
	}

	/**
	 * This function is used to get FFMpeg instance from scoped vendor.
	 *
	 * @since BuddyBoss 2.6.30
	 *
	 * @return \BuddyBoss\Library\Composer\FFMpeg/\BuddyBossPlatform\BuddyBoss\Library\Composer\FFMpeg
	 */
	function ffmpeg_instance() {
		if ( class_exists( '\BuddyBossPlatform\BuddyBoss\Library\Composer\FFMpeg' ) ) {
			return \BuddyBossPlatform\BuddyBoss\Library\Composer\FFMpeg::instance();
		}

		return \BuddyBoss\Library\Composer\FFMpeg::instance();
	}
}
