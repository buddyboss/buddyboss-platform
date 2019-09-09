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
	 * @since BuddyBoss 1.1.9
	 */
	private function setup_globals() {
		$bp = buddypress();

		// Paths and URLs
		$this->install_file_object_cache  = $bp->plugin_dir  . 'bp-core/cache/files/object-cache.php'; // object cache file.
		$this->addin_file_object_cache  = WP_CONTENT_DIR . '/object-cache.php'; // wp content object cache file url.
	}

	/**
	 * Set up the admin hooks, actions, and filters.
	 *
	 * @since BuddyBoss 1.1.9
	 *
	 */
	private function setup_actions() {
		add_action( 'admin_bar_menu', array( $this, 'flush_cache_button' ), 100 );
		add_action( 'admin_init', array( $this, 'flush_cache' ) );
		add_action( 'upgrader_process_complete', 'bp_core_performance_clear_cache' );
		register_activation_hook( __FILE__, 'bp_core_performance_clear_cache' );
		add_action( 'deactivate_plugin', array( $this, 'on_deactivation' ) );
		add_action( 'bp_admin_performance_data_save', array( $this, 'init_cache' ) );
	}

	public function init_cache() {

	    //clear cache first
		bp_core_performance_clear_cache();

		$addin_required = bp_performance_is_object_caching_enabled();

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
		global $wp_filesystem;

		$performance_tab_url = wp_nonce_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-performance', 'bp_remove_cache_file' => '1' ), 'admin.php' ) ) );

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
				bp_core_add_admin_notice( sprintf( __( 'The Object Cache add-in file object-cache.php is not a BuddyBoss drop-in. Remove it or disable Object Caching. %s', 'buddyboss' ), sprintf( '<a href="%s" class="button">%s</a>', $performance_tab_url, __( 'Yes, remove it for me', 'buddyboss' ) ) ), 'error' );
				return;
			}
		}

		$result = false;
		// do we have filesystem credentials?
		if ( $this->initialize_filesystem( $performance_tab_url, true ) ) {
			$result = $wp_filesystem->copy( $src, $dst, true );
        }

		if ( ! $result ) {
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
		global $wp_filesystem;

		$performance_tab_url = wp_nonce_url( bp_get_admin_url( add_query_arg( array( 'page' => 'bp-performance', 'bp_remove_cache_file' => '1' ), 'admin.php' ) ) );
		$filename = $this->addin_file_object_cache;

		if ( $this->is_objectcache_add_in() ) {

			$result = false;
			// do we have filesystem credentials?
			if ( $this->initialize_filesystem( $performance_tab_url, true ) ) {
				$result = $wp_filesystem->delete( $filename );
			}

			if ( ! $result ) {
				if ( ! @file_exists( $filename ) ) {
					return;
				}
				if ( @unlink( $filename ) ) {
					return;
				}
			}

			bp_core_performance_clear_cache();
        }
	}

	public function flush_cache_button() {
		global $wp_admin_bar;

		if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
			return false;
		}

		// User verification
		if ( ! is_admin() ) {
			return false;
		}

		// Check if user wants button in admin bar or not
		if ( ! bp_performance_is_object_caching_enabled() && ! bp_performance_is_opcode_caching_enabled() ) {
			return false;
		}

		// Button parameters
		$flush_url = add_query_arg( array( 'bp_flush_opcache_action' => 'bpflushopcacheall' ) );
		$nonced_url = wp_nonce_url( $flush_url, 'bp_flush_opcache_all' );

		// Admin button only on main site in MS edition or admin bar if normal edition
		if ( ( is_multisite() && is_super_admin() && is_main_site() ) || ! is_multisite() ) {
			$wp_admin_bar->add_menu( array(
					'parent' => '',
					'id' => 'bp_flush_opcache',
					'title' => __( 'Flush Cache', 'buddyboss' ),
					'meta' => array( 'title' => __( 'Flush Cache', 'buddyboss' ) ),
					'href' => $nonced_url
				)
			);
		}
	}

	public function flush_cache() {
		if ( ! isset( $_REQUEST['bp_flush_opcache_action'] ) ) {
			return;
		}

		// User's verification
		if ( ! is_admin() ) {
			wp_die( __( 'Sorry, you can\'t flush OPcache.', 'buddyboss' ) );
		}

		// Show notice when flush is done
		$action = sanitize_key( $_REQUEST['bp_flush_opcache_action'] );
		if ( $action == 'done' ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'show_opcache_notice' ) );
				add_action( 'admin_notices', array( $this, 'show_opcache_notice' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'show_opcache_notice' ) );
			}
			return;
		}

		// Check for nonce and admin
		check_admin_referer( 'bp_flush_opcache_all' );

		// OPcache reset
		if ( $action == 'bpflushopcacheall' ) {
			bp_core_performance_clear_cache();
		}

		wp_redirect( esc_url_raw( add_query_arg( array( 'bp_flush_opcache_action' => 'done' ) ) ) );
		exit;
	}

	public function show_opcache_notice() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><strong><?php _e( 'OPcache was successfully flushed.', 'buddyboss' ); ?></strong></p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'buddyboss' ); ?></span>
			</button>
		</div>
		<?php
	}

	public function initialize_filesystem( $url, $silent = false ) {
	    if ( ! function_exists( 'request_filesystem_credentials' ) ) {
		    require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

		if ( $silent ) {
			ob_start();
		}

		if ( ( $credentials = request_filesystem_credentials( $url ) ) === false ) {
			if ( $silent ) {
				ob_end_clean();
			}

			return false;
		}

		if ( ! WP_Filesystem( $credentials ) ) {
			request_filesystem_credentials( $url );

			if ( $silent ) {
				ob_end_clean();
			}

			return false;
		}

		return true;
	}

	public function on_deactivation( $plugin ) {
		if ( $plugin === plugin_basename( __FILE__ ) ) {
			$this->delete_addin();
		}
	}
}

endif; // End class_exists check.

