<?php
/**
 * BP Nouveau xProfile
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * xProfile Loader class
 *
 * @since BuddyPress 3.0.0
 */
#[\AllowDynamicProperties]
class BP_Nouveau_xProfile {
	/**
	 * Constructor
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
		$this->setup_filters();
	}

	/**
	 * Globals
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_globals() {
		$this->dir = dirname( __FILE__ );
	}

	/**
	 * Include needed files
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function includes() {
		require( trailingslashit( $this->dir ) . 'functions.php' );
		require( trailingslashit( $this->dir ) . 'template-tags.php' );

		// Test suite requires the AJAX functions early.
		if ( function_exists( 'tests_add_filter' ) ) {
			require trailingslashit( $this->dir ) . 'ajax.php';

			// Load AJAX code only on AJAX requests.
		} else {
			add_action( 'admin_init', function() {
				if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && 0 === strpos( $_REQUEST['action'], 'xprofile_' ) ) {
					require trailingslashit( $this->dir ) . 'ajax.php';
				}
			} );
		}
	}

	/**
	 * Register do_action() hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_actions() {
		add_action( 'bp_nouveau_enqueue_scripts', 'bp_nouveau_xprofile_enqueue_scripts' );
	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_filters() {
		add_filter( 'bp_nouveau_register_scripts', 'bp_nouveau_xprofile_register_scripts', 10, 1 );
	}
}

/**
 * Launch the xProfile loader class.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_xprofile( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->xprofile = new BP_Nouveau_xProfile();
}
add_action( 'bp_nouveau_includes', 'bp_nouveau_xprofile', 10, 1 );
