<?php
/**
 * BP Nouveau Moderation
 *
 * @since   BuddyBoss 1.5.4
 * @version 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Moderation Loader class
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Nouveau_Moderation {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
	}

	/**
	 * Globals
	 *
	 * @since BuddyBoss 1.5.4
	 */
	protected function setup_globals() {
		$this->dir = trailingslashit( dirname( __FILE__ ) );
	}

	/**
	 * Include needed files
	 *
	 * @since BuddyBoss 1.5.4
	 */
	protected function includes() {

		// Test suite requires the AJAX functions early.
		if ( function_exists( 'tests_add_filter' ) ) {
			require $this->dir . 'ajax.php';

			// Load AJAX code only on AJAX requests.
		} else {
			//add_action( 'admin_init', function () {
				if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && 0 === strpos( $_REQUEST['action'], 'moderation_' ) ) {
					require $this->dir . 'ajax.php';
				}
			//} );
		}
	}

}

/**
 * Launch the Groups loader class.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_nouveau_moderation( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->moderation = new BP_Nouveau_Moderation();
}

add_action( 'bp_nouveau_includes', 'bp_nouveau_moderation', 10, 1 );