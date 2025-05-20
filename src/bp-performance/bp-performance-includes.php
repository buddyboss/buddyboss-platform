<?php
/**
 * BuddyBoss Performance Files include.
 *
 * @package BuddyBoss\Performance
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BP_Performance_Includes {
	/**
	 * Constructor.
	 *
	 * @since [BBVERSION]
	 */
	public function __construct() {
		// BB Cache Components.
		add_filter( 'performance_components', array( $this, 'bb_performance_components' ), 10, 3 );
	}

	/**
	 * Adds BuddyBoss platform-specific performance components to the provided components array.
	 * These components facilitate caching and purging operations for various BuddyBoss features.
	 *
	 * @param array $components Array of performance components.
	 * @param string $purge_url Base URL used to construct purge URLs for each component.
	 * @param string $purge_nonce Nonce value used for security in purge URLs.
	 *
	 * @since [BBVERSION]
	 *
	 * @return array Modified components array including BuddyBoss platform components.
	 */
	function bb_performance_components( $components, $purge_url, $purge_nonce ) {
		$components['buddyboss'] = array(
			'title'     => __( 'BuddyBoss Platform', 'buddyboss' ),
			'purge_url' => $purge_url . '&group=bbplatform&component=all&nonce=' . $purge_nonce,
			'settings'  => array(
				'cache_bb_activity_feeds'     => array(
					'label'          => __( 'Activity Feeds', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Activity Feeds', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bp-activity&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_members'            => array(
					'label'          => __( 'Member Profiles', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Member Profiles', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bp-members&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_member_connections' => array(
					'label'          => __( 'Member Connections', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Member Connections', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bp-friends&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_social_groups'      => array(
					'label'          => __( 'Social Groups', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Social Groups', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bp-groups&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_private_messaging'  => array(
					'label'          => __( 'Private Messaging', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Private Messaging', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bp-messages&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_forum_discussions'  => array(
					'label'          => __( 'Forum Discussions', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Forum Discussions', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bbp-forums,bbp-topics,bbp-replies&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_notifications'      => array(
					'label'          => __( 'Notifications', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Notifications', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bp-notifications&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_media'              => array(
					'label'          => __( 'Photos', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Photos/Albums', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bp-media&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_document'           => array(
					'label'          => __( 'Documents', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Document Files/Folders', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bp-document&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_video'              => array(
					'label'          => __( 'Videos', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Videos', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bp-video&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
				'cache_bb_subscription'       => array(
					'label'          => __( 'Subscriptions', 'buddyboss' ),
					'label_checkbox' => __( 'Cache Subscriptions', 'buddyboss' ),
					'purge_url'      => $purge_url . '&group=bbplatform&component=bb-subscription&nonce=' . $purge_nonce,
					'type'           => 'checkbox',
					'value'          => true,
				),
			),
		);

		return $components;
	}
}

new BP_Performance_Includes();
