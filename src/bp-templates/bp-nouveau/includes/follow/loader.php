<?php
/**
 * BP Nouveau Follow
 *
 * @since BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Follow Loader class
 *
 * @since BuddyBoss 1.0.0
 */
#[\AllowDynamicProperties]
class BP_Nouveau_Follow {
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
		$this->dir = trailingslashit( dirname( __FILE__ ) );
	}

	/**
	 * Include needed files
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function includes() {
		// Test suite requires the AJAX functions early.
		if ( function_exists( 'tests_add_filter' ) ) {
			require $this->dir . 'ajax.php';

		// Load AJAX code only on AJAX requests.
		} else {
			add_action( 'admin_init', function() {
				if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && 0 === strpos( $_REQUEST['action'], 'follow_' ) ) {
					require $this->dir . 'ajax.php';
				}
			} );
		}
	}

	/**
	 * Register do_action() hooks
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function setup_actions() {}

	/**
	 * Register add_filter() hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_filters() {
		$buttons = array(
			'members_member_follow',
		);

		foreach ( $buttons as $button ) {
			add_filter( 'bp_button_' . $button, 'bp_nouveau_ajax_button', 10, 5 );
		}
	}
}

/**
 * Launch the Follow loader class.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_follow( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->follow = new BP_Nouveau_Follow();
}
add_action( 'bp_nouveau_includes', 'bp_nouveau_follow', 10, 1 );
