<?php
/**
 * Main BuddyPress Cache Class.
 *
 * @package BuddyBoss\Core\Cache
 * @since BuddyBoss 1.1.8
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Core_Cache' ) ) :

/**
 * @since BuddyPress 1.6.0
 */
class BP_Core_Cache {

	/**
	 * Path to the BuddyPress admin directory.
	 *
	 * @since BuddyPress 1.6.0
	 * @var string $admin_dir
	 */
	public $install_file_object_cache = '';
	public $addin_file_object_cache = '';

	/** Methods ***************************************************************/

	/**
	 * The main BuddyPress admin loader.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Set cache-related globals.
	 *
	 * @since BuddyPress 1.6.0
	 */
	private function setup_globals() {
		$bp = buddypress();

		// Paths and URLs
		$this->install_file_object_cache  = $bp->plugin_dir  . 'bp-core/cache-files/object-cache.php'; // object cache file.
		$this->addin_file_object_cache  = WP_CONTENT_DIR . '/object-cache.php'; // wp content object cache file url.
	}

	/**
	 * Set up the admin hooks, actions, and filters.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 */
	private function setup_actions() {
		$this->init_cache();
	}

	public function init_cache() {
		$addin_required = bp_performance_is_caching_enabled();

		if ( $addin_required ) {
			$this->create_addin();
		} else {
			$this->delete_addin();
		}
    }

	/**
	 * Creates add-in
	 *
	 */
	private function create_addin() {
		$src = $this->install_file_object_cache;
		$dst = $this->addin_file_object_cache;

		if ( $this->objectcache_installed() ) {
			if ( $this->is_objectcache_add_in() ) {
				$script_data = @file_get_contents( $dst );
				if ( $script_data == @file_get_contents( $src ) )
					return;
			} else if ( isset( $_GET['bp_remove_cache_file'] ) && '1' == $_GET['bp_remove_cache_file'] ) {
				// user already manually asked to remove another plugin's add in,
				// we should try to apply ours
				// (in case of missing permissions deletion could fail)
			} else {
				$performance_tab_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-performance', 'bp_remove_cache_file' => '1' ), 'admin.php' ) );
				bp_core_add_admin_notice( sprintf( __( 'The Object Cache add-in file object-cache.php is not a BuddyBoss drop-in. Remove it or disable Object Caching. %s', 'buddyboss' ), sprintf( '<a href="%s" class="button">%s</a>', $performance_tab_url, __( 'Yes, remove it for me', 'buddyboss' ) ) ), 'error' );
				return;
			}
		}

		$contents = @file_get_contents( $src );
		if ( $contents ) {
			@file_put_contents( $dst, $contents );
		}
		if ( @file_exists( $dst ) ) {
			if ( @file_get_contents( $dst ) == $contents ) {
				return;
			}
		}
	}

	/**
	 * Returns true if object-cache.php is installed
	 *
	 * @return boolean
	 */
	public function objectcache_installed() {
		return file_exists( $this->addin_file_object_cache );
	}

	/**
	 * Checks if object-cache.php is latest version
	 *
	 * @return boolean
	 */
	public function is_objectcache_add_in() {
		if ( !$this->objectcache_installed() )
			return false;

		return ( ( $script_data = @file_get_contents( $this->addin_file_object_cache ) )
		         && strstr( $script_data, '//BPObjectCache Version: 1.0' ) !== false );
	}

	/**
	 * Deletes add-in
	 */
	private function delete_addin() {
	    $filename = $this->addin_file_object_cache;
		if ( $this->is_objectcache_add_in() ) {
			if ( !@file_exists( $filename ) )
				return;
			if ( @unlink( $filename ) )
				return;
        }
	}
}

endif; // End class_exists check.

