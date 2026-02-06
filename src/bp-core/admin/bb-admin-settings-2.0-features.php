<?php
/**
 * BuddyBoss Admin Settings 2.0 - All Features Registration
 *
 * Registers all BuddyBoss features based on the Figma design:
 * - BUDDYBOSS COMMUNITY SETTINGS (14 features)
 * - BUDDYBOSS ADD-ONS (4 features)
 * - BUDDYBOSS INTEGRATIONS (9 features)
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register all BuddyBoss features.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_2_0_register_all_features() {
	// =============================================================================
	// BUDDYBOSS COMMUNITY SETTINGS
	// =============================================================================

	// 1. Appearance
	bb_register_feature(
		'appearance',
		array(
			'label'              => __( 'Appearance', 'buddyboss' ),
			'description'        => __( 'Customize the look and feel of your community with colors, fonts, and layout options.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-palette',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => '__return_true', // Always available.
			'settings_route'     => '/settings/appearance',
			'order'              => 10,
		)
	);

	// 2. Registration
	bb_register_feature(
		'registration',
		array(
			'label'              => __( 'Registration', 'buddyboss' ),
			'description'        => __( 'Control how members register and join your community.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-square',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => '__return_true',
			'settings_route'     => '/settings/registration',
			'order'              => 15,
		)
	);

	// 3. Account Settings
	bb_register_feature(
		'settings',
		array(
			'label'              => __( 'Account Settings', 'buddyboss' ),
			'description'        => __( 'Allow members to update their account and notification settings directly from their profiles.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-gear',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'settings' );
			},
			'settings_route'     => '/settings/settings',
			'order'              => 20,
		)
	);

	// 4. Moderation
	bb_register_feature(
		'moderation',
		array(
			'label'              => __( 'Moderation', 'buddyboss' ),
			'description'        => __( 'Allow members to block one another and report inappropriate content for review by the site admin.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-flag',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'moderation' );
			},
			'settings_route'     => '/settings/moderation',
			'order'              => 25,
		)
	);

	// 5. Network Search
	bb_register_feature(
		'search',
		array(
			'label'              => __( 'Network Search', 'buddyboss' ),
			'description'        => __( 'Allow members to search the entire network, along with custom post types of your choice.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-magnifying-glass',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'search' );
			},
			'settings_route'     => '/settings/search',
			'order'              => 30,
		)
	);

	// 6. Member Profiles
	bb_register_feature(
		'members',
		array(
			'label'              => __( 'Member Profiles', 'buddyboss' ),
			'description'        => __( 'Everything on a community website revolves around its members. Every user receives a member profile.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-rectangle',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'members' );
			},
			'settings_route'     => '/settings/members',
			'order'              => 35,
		)
	);

	// Note: Activity feature is registered in bb-admin-settings-2.0-activity.php (order: 40)

	// 8. Forum Discussions
	bb_register_feature(
		'forums',
		array(
			'label'              => __( 'Forum Discussions', 'buddyboss' ),
			'description'        => __( 'Allow members to hold discussions in Q&A-style forums, which can operate independently or be linked to social groups.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-chats-teardrop',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'forums' );
			},
			'settings_route'     => '/settings/forums',
			'order'              => 45,
		)
	);

	// Note: Social Groups feature is registered in bb-admin-settings-2.0-groups.php (order: 50)

	// 10. Like & Reactions
	bb_register_feature(
		'reactions',
		array(
			'label'              => __( 'Like & Reactions', 'buddyboss' ),
			'description'        => __( 'Allow community members to interact by liking or selecting from a variety of emotions.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-thumbs-up',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return function_exists( 'bp_is_reactions_active' ) && bp_is_reactions_active();
			},
			'settings_route'     => '/settings/reactions',
			'order'              => 55,
		)
	);

	// 11. Media Uploading
	bb_register_feature(
		'media',
		array(
			'label'              => __( 'Media Uploading', 'buddyboss' ),
			'description'        => __( 'Allow members to upload photos, videos, documents, emojis, and GIFs, and organize them into albums or folders.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-image',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'media' );
			},
			'settings_route'     => '/settings/media',
			'order'              => 60,
		)
	);

	// 12. Private Messaging
	bb_register_feature(
		'messages',
		array(
			'label'              => __( 'Private Messaging', 'buddyboss' ),
			'description'        => __( 'Allow members to send private messages to an individual or to a group.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-chat-teardrop-text',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'messages' );
			},
			'settings_route'     => '/settings/messages',
			'order'              => 65,
		)
	);

	// 13. Notifications
	bb_register_feature(
		'notifications',
		array(
			'label'              => __( 'Notifications', 'buddyboss' ),
			'description'        => __( 'Notify members of relevant activity with a toolbar bubble or email, and let them customize their notification settings.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-bell',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'notifications' );
			},
			'settings_route'     => '/settings/notifications',
			'order'              => 70,
		)
	);

	// 14. Email Invites
	bb_register_feature(
		'invites',
		array(
			'label'              => __( 'Email Invites', 'buddyboss' ),
			'description'        => __( 'Allow members to send email invitations to non-members to join the network.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-paper-plane-tilt',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'invites' );
			},
			'settings_route'     => '/settings/invites',
			'order'              => 75,
		)
	);

	// 15. Connections (Friends)
	bb_register_feature(
		'friends',
		array(
			'label'              => __( 'Connections', 'buddyboss' ),
			'description'        => __( 'Allow members to make connections with each other and build their social network.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-users',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
				return bp_is_active( 'friends' );
			},
			'settings_route'     => '/settings/friends',
			'order'              => 80,
		)
	);

	// =============================================================================
	// BUDDYBOSS ADD-ONS (PRO/PLUS features)
	// =============================================================================

	// 1. BuddyBoss Gamification
	bb_register_feature(
		'gamification',
		array(
			'label'              => __( 'BuddyBoss Gamification', 'buddyboss' ),
			'description'        => __( 'Reward members with points, ranks, and achievements for activity and engagement to encourage participation and retention.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-medal',
			),
			'category'           => 'add-ons',
			'license_tier'       => 'plus',
			'is_available_callback' => function () {
				return class_exists( 'BB_Gamification' );
			},
			'is_active_callback' => function () {
				return class_exists( 'BB_Gamification' ) && BB_Gamification::is_active();
			},
			'settings_route'     => '/settings/gamification',
			'order'              => 100,
		)
	);

	// 2. Offload Media
	bb_register_feature(
		'offload-media',
		array(
			'label'              => __( 'Offload Media', 'buddyboss' ),
			'description'        => __( 'Store media files on external storage to improve site performance, reduce server load, and scale your community with ease.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-cloud',
			),
			'category'           => 'add-ons',
			'license_tier'       => 'plus',
			'is_available_callback' => function () {
				return class_exists( 'BB_Offload_Media' );
			},
			'is_active_callback' => function () {
				return class_exists( 'BB_Offload_Media' ) && BB_Offload_Media::is_active();
			},
			'settings_route'     => '/settings/offload-media',
			'order'              => 105,
		)
	);

	// 3. BuddyBoss Events
	bb_register_feature(
		'events',
		array(
			'label'              => __( 'BuddyBoss Events', 'buddyboss' ),
			'description'        => __( 'Create and manage events with RSVPs so members can discover, join, and engage in community meetups or online sessions.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-calendar-heart',
			),
			'category'           => 'add-ons',
			'license_tier'       => 'plus',
			'is_available_callback' => function () {
				return class_exists( 'BB_Events' );
			},
			'is_active_callback' => function () {
				return class_exists( 'BB_Events' ) && BB_Events::is_active();
			},
			'settings_route'     => '/settings/events',
			'order'              => 110,
		)
	);

	// 4. BuddyBoss Memberships
	bb_register_feature(
		'memberships',
		array(
			'label'              => __( 'BuddyBoss Memberships', 'buddyboss' ),
			'description'        => __( 'Restrict content and features by membership levels to manage access, monetize your community, and control user permissions.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-identification-card',
			),
			'category'           => 'add-ons',
			'license_tier'       => 'plus',
			'is_available_callback' => function () {
				return class_exists( 'BB_Memberships' );
			},
			'is_active_callback' => function () {
				return class_exists( 'BB_Memberships' ) && BB_Memberships::is_active();
			},
			'settings_route'     => '/settings/memberships',
			'order'              => 115,
		)
	);

	// =============================================================================
	// BUDDYBOSS INTEGRATIONS
	// =============================================================================

	// 1. reCAPTCHA
	bb_register_feature(
		'recaptcha',
		array(
			'label'              => __( 'reCAPTCHA', 'buddyboss' ),
			'description'        => __( 'Protect your community from spam and bots with Google reCAPTCHA integration.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-shield-check',
			),
			'category'           => 'integrations',
			'license_tier'       => 'free',
			'is_available_callback' => '__return_true',
			'is_active_callback' => function () {
				return bp_get_option( 'bb_recaptcha_enabled', false );
			},
			'settings_route'     => '/settings/recaptcha',
			'order'              => 200,
		)
	);

	// 2. Lifter LMS
	bb_register_feature(
		'lifter-lms',
		array(
			'label'              => __( 'Lifter LMS', 'buddyboss' ),
			'description'        => __( 'Create and sell courses with lessons, quizzes, and memberships to deliver structured learning inside your community.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-graduation-cap',
			),
			'category'           => 'integrations',
			'license_tier'       => 'free',
			'is_available_callback' => function () {
				return class_exists( 'LifterLMS' );
			},
			'is_active_callback' => function () {
				return class_exists( 'LifterLMS' ) && bp_get_option( 'bb_lifter_lms_enabled', false );
			},
			'settings_route'     => '/settings/lifter-lms',
			'order'              => 205,
		)
	);

	// 3. LearnDash
	bb_register_feature(
		'learndash',
		array(
			'label'              => __( 'LearnDash', 'buddyboss' ),
			'description'        => __( 'Build advanced online courses with quizzes, drip content, and certifications for professional and scalable learning.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-graduation-cap',
			),
			'category'           => 'integrations',
			'license_tier'       => 'free',
			'is_available_callback' => function () {
				return defined( 'LEARNDASH_VERSION' );
			},
			'is_active_callback' => function () {
				return defined( 'LEARNDASH_VERSION' ) && bp_get_option( 'bb_learndash_enabled', false );
			},
			'settings_route'     => '/settings/learndash',
			'order'              => 210,
		)
	);

	// 4. Tutor LMS
	bb_register_feature(
		'tutor-lms',
		array(
			'label'              => __( 'Tutor LMS', 'buddyboss' ),
			'description'        => __( 'Design flexible courses with quizzes and student tracking to deliver engaging learning experiences for your community.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-graduation-cap',
			),
			'category'           => 'integrations',
			'license_tier'       => 'free',
			'is_available_callback' => function () {
				return defined( 'TUTOR_VERSION' );
			},
			'is_active_callback' => function () {
				return defined( 'TUTOR_VERSION' ) && bp_get_option( 'bb_tutor_lms_enabled', false );
			},
			'settings_route'     => '/settings/tutor-lms',
			'order'              => 215,
		)
	);

	// 5. MemberPress Courses
	bb_register_feature(
		'memberpress',
		array(
			'label'              => __( 'MemberPress Courses', 'buddyboss' ),
			'description'        => __( 'Integrate MemberPress courses with your community for seamless membership and learning experiences.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-graduation-cap',
			),
			'category'           => 'integrations',
			'license_tier'       => 'free',
			'is_available_callback' => function () {
				return defined( 'MEPR_VERSION' );
			},
			'is_active_callback' => function () {
				return defined( 'MEPR_VERSION' ) && bp_get_option( 'bb_memberpress_enabled', false );
			},
			'settings_route'     => '/settings/memberpress',
			'order'              => 220,
		)
	);

	// 6. BuddyPress
	bb_register_feature(
		'buddypress',
		array(
			'label'              => __( 'BuddyPress', 'buddyboss' ),
			'description'        => __( 'Compatibility settings for BuddyPress plugins and extensions.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-users-three',
			),
			'category'           => 'integrations',
			'license_tier'       => 'free',
			'is_available_callback' => '__return_true',
			'is_active_callback' => function () {
				return bp_get_option( 'bb_buddypress_compat_enabled', false );
			},
			'settings_route'     => '/settings/buddypress',
			'order'              => 225,
		)
	);

	// 7. Zoom
	bb_register_feature(
		'zoom',
		array(
			'label'              => __( 'Zoom', 'buddyboss' ),
			'description'        => __( 'Enable Zoom video conferencing for meetings and webinars within your community.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-video-camera',
			),
			'category'           => 'integrations',
			'license_tier'       => 'free',
			'is_available_callback' => '__return_true',
			'is_active_callback' => function () {
				return bp_is_active( 'zoom' );
			},
			'settings_route'     => '/settings/zoom',
			'order'              => 230,
		)
	);

	// 8. Pusher
	bb_register_feature(
		'pusher',
		array(
			'label'              => __( 'Pusher', 'buddyboss' ),
			'description'        => __( 'Enable real-time messaging and notifications with Pusher integration.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-chat',
			),
			'category'           => 'integrations',
			'license_tier'       => 'free',
			'is_available_callback' => '__return_true',
			'is_active_callback' => function () {
				return bp_get_option( 'bb_pusher_enabled', false );
			},
			'settings_route'     => '/settings/pusher',
			'order'              => 235,
		)
	);

	// 9. OneSignal
	bb_register_feature(
		'onesignal',
		array(
			'label'              => __( 'OneSignal', 'buddyboss' ),
			'description'        => __( 'Send push notifications to keep members engaged with your community.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-bell-ringing',
			),
			'category'           => 'integrations',
			'license_tier'       => 'free',
			'is_available_callback' => '__return_true',
			'is_active_callback' => function () {
				return bp_get_option( 'bb_onesignal_enabled', false );
			},
			'settings_route'     => '/settings/onesignal',
			'order'              => 240,
		)
	);
}
add_action( 'bb_register_features', 'bb_admin_settings_2_0_register_all_features', 5 );
