<?php
/**
 * BP Nouveau Document
 *
 * @since BuddyBoss 1.4.0
 * @version 1.0.0
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Document Loader class
 *
 * @since BuddyBoss 1.4.0
 */
#[\AllowDynamicProperties]
class BP_Nouveau_Document {
	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
	 */
	protected function setup_globals() {
		$this->dir = trailingslashit( dirname( __FILE__ ) );
	}

	/**
	 * Include needed files
	 *
	 * @since BuddyBoss 1.4.0
	 */
	protected function includes() {
		require $this->dir . 'functions.php';
		require $this->dir . 'template-tags.php';

		// Test suite requires the AJAX functions early.
		if ( function_exists( 'tests_add_filter' ) ) {
			require $this->dir . 'ajax.php';

			// Load AJAX code only on AJAX requests.
		} else {
			add_action(
				'admin_init',
				function() {
					if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && isset(  $_REQUEST['action'] ) && 0 === strpos( $_REQUEST['action'], 'document_' ) ) {
						require $this->dir . 'ajax.php';
					}
				}
			);
		}
	}

	/**
	 * Register do_action() hooks
	 *
	 * @since BuddyBoss 1.4.0
	 */
	protected function setup_actions() {
		// Enqueue the scripts for the new UI.
		add_action( 'bp_nouveau_enqueue_scripts', 'bp_nouveau_document_enqueue_scripts' );
	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since BuddyBoss 1.4.0
	 */
	protected function setup_filters() {

		// Localize Scripts.
		add_filter( 'bp_core_get_js_strings', 'bp_nouveau_document_localize_scripts', 11, 1 );

		// Redirect edit button document popup activity to parent activity edit.
		add_filter( 'bb_nouveau_get_activity_entry_bubble_buttons', 'bp_nouveau_document_activity_edit_button', 10, 2 );
	}
}

/**
 * Launch the Media loader class.
 *
 * @param null $bp_nouveau
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_document( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->document = new BP_Nouveau_Document();
}
add_action( 'bp_nouveau_includes', 'bp_nouveau_document', 10, 1 );
