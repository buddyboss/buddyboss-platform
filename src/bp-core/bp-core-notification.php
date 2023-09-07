<?php
/**
 * Core Notification Default Email install.
 *
 * @package    BuddyBoss\Core
 * @subpackage Core
 *
 * @since BuddyBoss 1.9.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bb_core_before_install', 'bb_core_default_install_emails' );

/**
 * Before install load the notification registration.
 *
 * @since buddyboss 1.9.3
 *
 * @param array $default_components Default component lists.
 */
function bb_core_default_install_emails( $default_components ) {

	// Called members Notification class.
	if ( ! class_exists( 'BP_Members_Notification' ) ) {

		// Load members notification file.
		if ( file_exists( buddypress()->plugin_dir . 'bp-members/classes/class-bp-members-notification.php' ) ) {
			require buddypress()->plugin_dir . 'bp-members/classes/class-bp-members-notification.php';
		}

		BP_Members_Notification::instance();
	}

	// Called members mentions Notification class.
	if ( ! class_exists( 'BP_Members_Mentions_Notification' ) ) {

		// Load members mentions notification file.
		if ( file_exists( buddypress()->plugin_dir . 'bp-members/classes/class-bp-members-mentions-notification.php' ) ) {
			require buddypress()->plugin_dir . 'bp-members/classes/class-bp-members-mentions-notification.php';
		}

		BP_Members_Mentions_Notification::instance();
	}

	// Called Activity Notification class.
	if ( ! class_exists( 'BP_Activity_Notification' ) ) {

		// Load Activity notification file.
		if ( file_exists( buddypress()->plugin_dir . 'bp-activity/classes/class-bp-activity-notification.php' ) ) {
			require buddypress()->plugin_dir . 'bp-activity/classes/class-bp-activity-notification.php';
		}

		BP_Activity_Notification::instance();
	}

	// Called Groups Notification class.
	if ( ! class_exists( 'BP_Groups_Notification' ) ) {

		// Load Groups notification file.
		if ( file_exists( buddypress()->plugin_dir . 'bp-groups/classes/class-bp-groups-notification.php' ) ) {
			require buddypress()->plugin_dir . 'bp-groups/classes/class-bp-groups-notification.php';
		}

		BP_Groups_Notification::instance();
	}

	// Called Friends Notification class.
	if ( ! class_exists( 'BP_Friends_Notification' ) ) {

		// Load Friends notification file.
		if ( file_exists( buddypress()->plugin_dir . 'bp-friends/classes/class-bp-friends-notification.php' ) ) {
			require buddypress()->plugin_dir . 'bp-friends/classes/class-bp-friends-notification.php';
		}

		BP_Friends_Notification::instance();
	}

	// Called Forums Notification class.
	if ( ! class_exists( 'BP_Forums_Notification' ) ) {

		// Load Forums notification file.
		if ( file_exists( buddypress()->plugin_dir . 'bp-forums/classes/class-bp-forums-notification.php' ) ) {
			require buddypress()->plugin_dir . 'bp-forums/classes/class-bp-forums-notification.php';
		}

		BP_Forums_Notification::instance();
	}

	// Called Messages Notification class.
	if ( ! class_exists( 'BP_Messages_Notification' ) ) {

		// Load Messages notification file.
		if ( file_exists( buddypress()->plugin_dir . 'bp-messages/classes/class-bp-messages-notification.php' ) ) {
			require buddypress()->plugin_dir . 'bp-messages/classes/class-bp-messages-notification.php';
		}

		BP_Messages_Notification::instance();
	}

	// Called Posts Notification class.
	if ( ! class_exists( 'BB_Post_Notification' ) ) {

		// Load Messages notification file.
		if ( file_exists( buddypress()->plugin_dir . 'bp-core/classes/class-bb-post-notification.php' ) ) {
			require buddypress()->plugin_dir . 'bp-core/classes/class-bb-post-notification.php';
		}

		BB_Post_Notification::instance();
	}

}
