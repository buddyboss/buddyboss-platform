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
 * @since BuddyBoss 1.9.3
 *
 * @param array $default_components Default component lists.
 */
function bboss_core_default_install_emails( $default_components ) {

	// Called members Notification class.
	if ( ! class_exists( 'BBoss_Members_Notification' ) ) {

		// Load members notification file.
		if ( file_exists( bboss()->plugin_dir . 'bboss-members/classes/class-bboss-members-notification.php' ) ) {
			require bboss()->plugin_dir . 'bboss-members/classes/class-bboss-members-notification.php';
		}

		BBoss_Members_Notification::instance();
	}

	// Called members mentions Notification class.
	if ( ! class_exists( 'BBoss_Members_Mentions_Notification' ) ) {

		// Load members mentions notification file.
		if ( file_exists( bboss()->plugin_dir . 'bboss-members/classes/class-bboss-members-mentions-notification.php' ) ) {
			require bboss()->plugin_dir . 'bboss-members/classes/class-bboss-members-mentions-notification.php';
		}

		BBoss_Members_Mentions_Notification::instance();
	}

	// Called Activity Notification class.
	if ( ! class_exists( 'BBoss_Activity_Notification' ) ) {

		// Load Activity notification file.
		if ( file_exists( bboss()->plugin_dir . 'bboss-activity/classes/class-bboss-activity-notification.php' ) ) {
			require bboss()->plugin_dir . 'bboss-activity/classes/class-bboss-activity-notification.php';
		}

		BBoss_Activity_Notification::instance();
	}

	// Called Groups Notification class.
	if ( ! class_exists( 'BBoss_Groups_Notification' ) ) {

		// Load Groups notification file.
		if ( file_exists( bboss()->plugin_dir . 'bboss-groups/classes/class-bboss-groups-notification.php' ) ) {
			require bboss()->plugin_dir . 'bboss-groups/classes/class-bboss-groups-notification.php';
		}

		BBoss_Groups_Notification::instance();
	}

	// Called Friends Notification class.
	if ( ! class_exists( 'BBoss_Friends_Notification' ) ) {

		// Load Friends notification file.
		if ( file_exists( bboss()->plugin_dir . 'bboss-friends/classes/class-bboss-friends-notification.php' ) ) {
			require bboss()->plugin_dir . 'bboss-friends/classes/class-bboss-friends-notification.php';
		}

		BBoss_Friends_Notification::instance();
	}

	// Called Forums Notification class.
	if ( ! class_exists( 'BBoss_Forums_Notification' ) ) {

		// Load Forums notification file.
		if ( file_exists( bboss()->plugin_dir . 'bboss-forums/classes/class-bboss-forums-notification.php' ) ) {
			require bboss()->plugin_dir . 'bboss-forums/classes/class-bboss-forums-notification.php';
		}

		BBoss_Forums_Notification::instance();
	}

	// Called Messages Notification class.
	if ( ! class_exists( 'BBoss_Messages_Notification' ) ) {

		// Load Messages notification file.
		if ( file_exists( bboss()->plugin_dir . 'bboss-messages/classes/class-bboss-messages-notification.php' ) ) {
			require bboss()->plugin_dir . 'bboss-messages/classes/class-bboss-messages-notification.php';
		}

		BBoss_Messages_Notification::instance();
	}

	// Called Posts Notification class.
	if ( ! class_exists( 'BBoss_Post_Notification' ) ) {

		// Load Messages notification file.
		if ( file_exists( bboss()->plugin_dir . 'bboss-core/classes/class-bboss-post-notification.php' ) ) {
			require bboss()->plugin_dir . 'bboss-core/classes/class-bboss-post-notification.php';
		}

		BBoss_Post_Notification::instance();
	}

}
