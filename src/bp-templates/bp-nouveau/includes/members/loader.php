<?php
/**
 * BP Nouveau Members
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Members Loader class
 *
 * @since BuddyPress 3.0.0
 */
#[\AllowDynamicProperties]
class BP_Nouveau_Members {
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
		$this->dir                  = dirname( __FILE__ );
		$this->is_user_home_sidebar = false;
	}

	/**
	 * Include needed files
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function includes() {
		require trailingslashit( $this->dir ) . 'functions.php';
		require trailingslashit( $this->dir ) . 'template-tags.php';
	}

	/**
	 * Register do_action() hooks
	 *
	 * @since BuddyPress 3.0.0
	 * @since BuddyBoss 1.0.0 Hooked bp_nouveau_admin_bar_member_dashboard to bp_setup_admin_bar action
	 */
	protected function setup_actions() {
		$ajax_actions = array(
			array(
				'members_filter' => array(
					'function' => 'bp_nouveau_ajax_object_template_loader',
					'nopriv'   => true,
				),
			),
		);

		foreach ( $ajax_actions as $ajax_action ) {
			$action = key( $ajax_action );

			add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

			if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
				add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
			}
		}

		// Actions to check whether we are in the member's default front page sidebar.
		add_action( 'dynamic_sidebar_before', array( $this, 'user_home_sidebar_set' ), 10, 1 );
		add_action( 'dynamic_sidebar_after', array( $this, 'user_home_sidebar_unset' ), 10, 1 );

		// Add 'Dashboard' link to admin bar.
		add_action( 'bp_setup_admin_bar', 'bp_nouveau_admin_bar_member_dashboard', 11 );

		// Enqueue the scripts for the new UI.
		add_action( 'bp_nouveau_enqueue_scripts', 'bp_nouveau_member_enqueue_scripts' );

	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_filters() {
		// Register member scripts.
		add_filter( 'bp_nouveau_register_scripts', 'bp_nouveau_member_register_scripts', 10, 1 );

		// Localize Scripts.
		add_filter( 'bp_core_get_js_strings', 'bp_nouveau_member_localize_scripts', 10, 1 );

		// Add the default-front to User's front hierarchy if user enabled it (Enabled by default).
	}

	/**
	 * Add filters to be sure the (BuddyBoss) widgets display will be consistent
	 * with the displayed user's default front page.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $sidebar_index The Sidebar identifier.
	 */
	public function user_home_sidebar_set( $sidebar_index = '' ) {
		if ( 'sidebar-buddypress-members' !== $sidebar_index ) {
			return;
		}

		$this->is_user_home_sidebar = true;

		// Add needed filters.
		bp_nouveau_members_add_home_widget_filters();
	}

	/**
	 * Remove filters to be sure the (BuddyBoss) widgets display will no more take
	 * the displayed user in account.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param  string $sidebar_index The Sidebar identifier.
	 */
	public function user_home_sidebar_unset( $sidebar_index = '' ) {
		if ( 'sidebar-buddypress-members' !== $sidebar_index ) {
			return;
		}

		$this->is_user_home_sidebar = false;

		// Remove no more needed filters.
		bp_nouveau_members_remove_home_widget_filters();
	}
}

/**
 * Launch the Members loader class.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_members( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->members = new BP_Nouveau_Members();
}
add_action( 'bp_nouveau_includes', 'bp_nouveau_members', 5, 1 );
