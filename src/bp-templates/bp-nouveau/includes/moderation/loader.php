<?php
/**
 * BP Nouveau Moderation
 *
 * @since BuddyBoss 1.5.6
 * @version 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Moderation Loader class
 *
 * @since BuddyBoss 1.5.6
 */
#[\AllowDynamicProperties]
class BP_Nouveau_Moderation {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
	 */
	protected function setup_globals() {
		$this->dir = trailingslashit( dirname( __FILE__ ) );
	}

	/**
	 * Include needed files
	 *
	 * @since BuddyBoss 1.5.6
	 */
	protected function includes() {
		require $this->dir . 'functions.php';

		// Test suite requires the AJAX functions early.
		if ( function_exists( 'tests_add_filter' ) ) {
			require $this->dir . 'ajax.php';

			// Load AJAX code only on AJAX requests.
		} else {
			if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && isset( $_REQUEST['action'] ) && 0 === strpos( $_REQUEST['action'], 'moderation_' ) ) {
				require $this->dir . 'ajax.php';
			}
		}
	}

	/**
	 * Register do_action() hooks
	 *
	 * @since BuddyBoss 1.5.6
	 */
	protected function setup_actions() {
		// Enqueue the scripts for the new UI
		add_action( 'bp_nouveau_enqueue_scripts', 'bp_nouveau_moderation_enqueue_scripts' );
	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since BuddyBoss 1.5.6
	 */
	protected function setup_filters() {

		// Register messages scripts
		add_filter( 'bp_nouveau_register_scripts', 'bp_nouveau_moderation_register_scripts', 10, 1 );

		// Localize Scripts
		add_filter( 'bp_core_get_js_strings', 'bp_nouveau_moderation_localize_scripts', 10, 1 );
	}
}

/**
 * Launch the Groups loader class.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_nouveau_moderation( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->moderation = new BP_Nouveau_Moderation();
}

add_action( 'bp_nouveau_includes', 'bp_nouveau_moderation', 10, 1 );
