<?php
namespace Buddypress\CLI;

use WP_CLI;

// Bail if WP-CLI is not present.
if ( ! defined( '\WP_CLI' ) ) {
	return;
}

WP_CLI::add_hook( 'before_wp_load', function() {
	require_once( __DIR__ . '/component.php' );
	require_once( __DIR__ . '/components/buddypress.php' );
	require_once( __DIR__ . '/components/signup.php' );
	require_once( __DIR__ . '/components/activity.php' );
	require_once( __DIR__ . '/components/activity-favorite.php' );
	require_once( __DIR__ . '/components/component.php' );
	require_once( __DIR__ . '/components/group.php' );
	require_once( __DIR__ . '/components/group-member.php' );
	require_once( __DIR__ . '/components/group-invite.php' );
	require_once( __DIR__ . '/components/member.php' );
	require_once( __DIR__ . '/components/friend.php' );
	require_once( __DIR__ . '/components/xprofile.php' );
	require_once( __DIR__ . '/components/xprofile-group.php' );
	require_once( __DIR__ . '/components/xprofile-field.php' );
	require_once( __DIR__ . '/components/xprofile-data.php' );
	require_once( __DIR__ . '/components/tool.php' );
	require_once( __DIR__ . '/components/message.php' );
	require_once( __DIR__ . '/components/email.php' );

	WP_CLI::add_command( 'bp', __NAMESPACE__ . '\\Command\\Buddypress', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp signup', __NAMESPACE__ . '\\Command\\Signup', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp email', __NAMESPACE__ . '\\Command\\Email', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp activity', __NAMESPACE__ . '\\Command\\Activity', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'activity' ) ) {
				WP_CLI::error( 'The Activity component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp activity favorite', __NAMESPACE__ . '\\Command\\Activity_Favorite', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'activity' ) ) {
				WP_CLI::error( 'The Activity component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp component', __NAMESPACE__ . '\\Command\\Components', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp group', __NAMESPACE__ . '\\Command\\Group', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'groups' ) ) {
				WP_CLI::error( 'The Groups component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp group member', __NAMESPACE__ . '\\Command\\Group_Member', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'groups' ) ) {
				WP_CLI::error( 'The Groups component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp group invite', __NAMESPACE__ . '\\Command\\Group_Invite', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'groups' ) ) {
				WP_CLI::error( 'The Groups component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp member', __NAMESPACE__ . '\\Command\\Member', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp friend', __NAMESPACE__ . '\\Command\\Friend', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'friends' ) ) {
				WP_CLI::error( 'The Friends component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp xprofile', __NAMESPACE__ . '\\Command\\XProfile', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'xprofile' ) ) {
				WP_CLI::error( 'The XProfile component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp xprofile group', __NAMESPACE__ . '\\Command\\XProfile_Group', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'xprofile' ) ) {
				WP_CLI::error( 'The XProfile component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp xprofile field', __NAMESPACE__ . '\\Command\\XProfile_Field', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'xprofile' ) ) {
				WP_CLI::error( 'The XProfile component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp xprofile data', __NAMESPACE__ . '\\Command\\XProfile_Data', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'xprofile' ) ) {
				WP_CLI::error( 'The XProfile component is not active.' );
			}
		},
	) );

	WP_CLI::add_command( 'bp tool', __NAMESPACE__ . '\\Command\\Tool', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			require_once( buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-tools.php' );
		},
	) );

	WP_CLI::add_command( 'bp message', __NAMESPACE__ . '\\Command\\Message', array(
		'before_invoke' => function() {
			if ( ! class_exists( 'Buddypress' ) ) {
				WP_CLI::error( 'The BuddyPress plugin is not active.' );
			}

			if ( ! bp_is_active( 'messages' ) ) {
				WP_CLI::error( 'The Message component is not active.' );
			}
		},
	) );
} );
