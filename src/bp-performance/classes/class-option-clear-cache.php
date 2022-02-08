<?php
/**
 * BuddyBoss Performance clear cache.
 *
 * @package BuddyBoss\Performance\OptionClearCache
 */

namespace BuddyBoss\Performance;

/**
 * Class ClearCache
 *
 * @package BuddyBoss\Performance
 */
class OptionClearCache {

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Class instance.
	 *
	 * @return OptionClearCache
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
			self::$instance->initialize(); // run the hooks.
		}

		return self::$instance;
	}

	/**
	 * Initialization of class.
	 */
	public function initialize() {
		add_action( 'updated_option', array( $this, 'purge_component_cache' ), 10, 3 );

		add_action( 'bb_media_delete_older_symlinks', array( $this, 'purge_symlink_cache' ) );
		add_action( 'bb_document_delete_older_symlinks', array( $this, 'purge_symlink_cache' ) );
		add_action( 'bb_video_delete_older_symlinks', array( $this, 'purge_symlink_cache' ) );
	}

	/**
	 * Purge component cache by component setting enabled or disable.
	 *
	 * @param string $option    Option Name.
	 * @param string $old_value Option Old Value.
	 * @param string $value     Option Updated Value.
	 */
	public function purge_component_cache( $option, $old_value, $value ) {

		if ( ! function_exists( 'bbapp_is_active' ) || ! bbapp_is_active( 'performance' ) ) {
			return;
		}

		$purge_components = array();

		if ( 'bp-active-components' === $option ) {

			$uninstalled_components = array_diff_key( $old_value, $value );
			$uninstalled_components = array_keys( $uninstalled_components );

			$non_cached_component = array(
				'settings',
				'invites',
				'moderation',
				'search',
			);

			if ( ! empty( $uninstalled_components ) ) {
				$can_purge_cache = false;
				foreach ( $uninstalled_components as $component ) {
					if ( in_array( $component, $non_cached_component, true ) ) {
						continue;
					}

					$can_purge_cache = true;

				}
				if ( true === $can_purge_cache ) {
					$purge_components = array_merge( $purge_components, Settings::instance()->get_group_purge_actions( 'bbplatform' ) );
				}
			}
		}

		if ( 'bp_ld_sync_settings' === $option ) {
			if ( ! empty( $value ) && isset( $value['course'] ) ) {
				if ( isset( $value['course']['courses_visibility'] ) && '0' === $value['course']['courses_visibility'] ) {
					$purge_components = array_merge( $purge_components, Settings::instance()->get_group_purge_actions( 'learndash' ) );
				}
			}
		}

		if ( ! empty( $purge_components ) ) {
			$purge_components = array_unique( $purge_components );
			foreach ( $purge_components as $purge_component ) {
				Cache::instance()->purge_by_component( $purge_component );
			}
			Cache::instance()->purge_by_component( 'bbapp-deeplinking' );
		}
	}

	/**
	 * Purge cache while symlink expiered.
	 */
	public function purge_symlink_cache() {
		$purge_components = array(
			'bp-activity',
			'bbp-forums',
			'bbp-topics',
			'bbp-replies',
			'bp-media-photos',
			'bp-media-albums',
			'bp-document',
			'bp-messages',
			'bp-video',
		);

		if ( ! empty( $purge_components ) ) {
			$purge_components = array_unique( $purge_components );
			foreach ( $purge_components as $purge_component ) {
				Cache::instance()->purge_by_component( $purge_component );
			}
			Cache::instance()->purge_by_component( 'bbapp-deeplinking' );
		}
	}
}
