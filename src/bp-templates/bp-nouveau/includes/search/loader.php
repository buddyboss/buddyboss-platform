<?php
/**
 * BP Nouveau Search
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * search Loader class
 *
 * @since BuddyPress 3.0.0
 */
#[\AllowDynamicProperties]
class BBoss_Nouveau_Search {
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
		$dir = trailingslashit( $this->dir );

		require "{$dir}functions.php";
	}

	/**
	 * Register do_action() hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_actions() {
		add_action( 'bp_nouveau_enqueue_scripts', 'bboss_nouveau_search_enqueue_scripts' );

		if ( function_exists( 'bboss_is_messages_component' ) ) {
			// Include the autocomplete JS for composing a message.
			if ( bboss_is_messages_component() && bboss_is_current_action( 'compose' ) ) {
				// After bp_nouveau_search_enqueue_scripts (10), so the bp-nouveau-search handle exists to attach to.
				add_action( 'bp_nouveau_enqueue_scripts', 'bboss_nouveau_search_messages_autocomplete_init_jsblock', 20 );
			}
		}
	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_filters() {
		add_filter( 'bp_nouveau_register_scripts', 'bboss_nouveau_search_register_scripts', 10, 1 );
	}
}

/**
 * Launch the search loader class.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_search( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->search = new BBoss_Nouveau_Search();
}

add_action( 'bp_nouveau_includes', 'bboss_nouveau_search', 10, 1 );
