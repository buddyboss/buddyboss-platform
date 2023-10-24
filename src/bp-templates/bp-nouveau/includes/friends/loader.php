<?php
/**
 * BP Nouveau Connections
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Connections Loader class
 *
 * @since BuddyPress 3.0.0
 */
#[\AllowDynamicProperties]
class BP_Nouveau_Friends {
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
				if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && 0 === strpos( $_REQUEST['action'], 'friends_' ) ) {
					require $this->dir . 'ajax.php';
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
		// Remove BuddyPress action for the members loop
		remove_action( 'bp_directory_members_actions', 'bp_member_add_friend_button' );

		// Register the friends Notifications filters
		add_action( 'bp_nouveau_notifications_init_filters', array( $this, 'notification_filters' ) );
	}

	/**
	 * Register add_filter() hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_filters() {
		$buttons = array(
			'friends_pending',
			'friends_is_friend',
			'friends_not_friends',
			'friends_member_friendship',
			'friends_accept_friendship',
			'friends_reject_friendship',
		);

		foreach ( $buttons as $button ) {
			add_filter( 'bp_button_' . $button, 'bp_nouveau_ajax_button', 10, 5 );
		}
	}

	/**
	 * Register notifications filters for the friends component.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function notification_filters() {

		if ( ! bb_enabled_legacy_email_preference() ) {
			return;
		}

		$notifications = array(
			array(
				'id'       => 'friendship_accepted',
				'label'    => __( 'Accepted connection requests', 'buddyboss' ),
				'position' => 35,
			),
			array(
				'id'       => 'friendship_request',
				'label'    => __( 'Pending connection requests', 'buddyboss' ),
				'position' => 45,
			),
		);

		foreach ( $notifications as $notification ) {
			bp_nouveau_notifications_register_filter( $notification );
		}
	}
}

/**
 * Launch the Connections loader class.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_friends( $bp_nouveau = null ) {
	if ( is_null( $bp_nouveau ) ) {
		return;
	}

	$bp_nouveau->friends = new BP_Nouveau_Friends();
}
add_action( 'bp_nouveau_includes', 'bp_nouveau_friends', 10, 1 );
