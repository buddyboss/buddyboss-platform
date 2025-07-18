<?php
/**
 * BuddyBoss Performance Files include.
 *
 * @since BuddyBoss 2.9.11
 * @package BuddyBoss\Performance
 */

namespace BuddyBoss\Performance;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BuddyBoss\Performance\Integration\BB_Activity;
use BuddyBoss\Performance\Integration\BB_Documents;
use BuddyBoss\Performance\Integration\BB_Forums;
use BuddyBoss\Performance\Integration\BB_Friends;
use BuddyBoss\Performance\Integration\BB_Groups;
use BuddyBoss\Performance\Integration\BB_Media_Albums;
use BuddyBoss\Performance\Integration\BB_Media_Photos;
use BuddyBoss\Performance\Integration\BB_Members;
use BuddyBoss\Performance\Integration\BB_Messages;
use BuddyBoss\Performance\Integration\BB_Notifications;
use BuddyBoss\Performance\Integration\BB_Replies;
use BuddyBoss\Performance\Integration\BB_Topics;
use BuddyBoss\Performance\Integration\BB_Videos;
use BuddyBoss\Performance\Integration\BB_Subscriptions;

if ( ! class_exists( 'BuddyBoss\Performance\BP_Performance_Includes' ) ) {
	/**
	 * Class BP_Performance_Includes
	 *
	 * This class is responsible for integrating BuddyBoss platform-specific performance components
	 * into the BuddyBoss Performance system, allowing for caching and purging of various BuddyBoss features.
	 *
	 * @since BuddyBoss 2.9.11
	 */
	class BP_Performance_Includes {
		/**
		 * Constructor.
		 *
		 * @since BuddyBoss 2.9.11
		 */
		public function __construct() {
			add_filter( 'performance_components', array( $this, 'bb_performance_components' ), 10, 3 );
			add_action( 'rest_cache_loaded', array( $this, 'bb_load_cache_components' ) );
			add_action( 'bb_media_delete_older_symlinks', array( $this, 'purge_symlink_cache' ) );
			add_action( 'bb_document_delete_older_symlinks', array( $this, 'purge_symlink_cache' ) );
			add_action( 'bb_video_delete_older_symlinks', array( $this, 'purge_symlink_cache' ) );
			add_filter( 'performance_purge_components', array( $this, 'bb_purge_components' ), 10, 4 );
			add_filter( 'performance_group_purge_actions', array( $this, 'get_group_purge_actions' ), 10, 2 );
			add_filter( 'bbapp_performance_deep_linking_supported_items', array( $this, 'performance_deep_linking_supported_items' ) );
		}

		/**
		 * Adds BuddyBoss platform-specific performance components to the provided components array.
		 * These components facilitate caching and purging operations for various BuddyBoss features.
		 *
		 * @since BuddyBoss 2.9.11
		 *
		 * @param array  $components  Array of performance components.
		 * @param string $purge_url   Base URL used to construct purge URLs for each component.
		 * @param string $purge_nonce Nonce value used for security in purge URLs.
		 *
		 * @return array Modified components array including BuddyBoss platform components.
		 */
		public function bb_performance_components( $components, $purge_url, $purge_nonce ) {
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

		/**
		 * Loads cache components.
		 *
		 * @return void
		 */
		public function bb_load_cache_components() {
			// Load platform or buddyPress related cache integration.
			if ( Performance::mu_is_plugin_active( 'buddyboss-platform/bp-loader.php' ) || Performance::mu_is_plugin_active( 'buddypress/bp-loader.php' ) ) {
				$group_integration = __DIR__ . '/classes/integrations/class-bb-groups.php';
				if ( Performance::mu_is_component_active( 'groups' ) && file_exists( $group_integration ) ) {
					require_once $group_integration;
					BB_Groups::instance();
				}

				$members_integration = __DIR__ . '/classes/integrations/class-bb-members.php';
				if ( Performance::mu_is_component_active( 'members' ) && file_exists( $members_integration ) ) {
					require_once $members_integration;
					BB_Members::instance();
				}

				$activity_integration = __DIR__ . '/classes/integrations/class-bb-activity.php';
				if ( Performance::mu_is_component_active( 'activity' ) && file_exists( $activity_integration ) ) {
					require_once $activity_integration;
					BB_Activity::instance();
				}

				$friends_integration = __DIR__ . '/classes/integrations/class-bb-friends.php';
				if ( Performance::mu_is_component_active( 'friends' ) && file_exists( $friends_integration ) ) {
					require_once $friends_integration;
					BB_Friends::instance();
				}

				$notifications_integration = __DIR__ . '/classes/integrations/class-bb-notifications.php';
				if ( Performance::mu_is_component_active( 'notifications' ) && file_exists( $notifications_integration ) ) {
					require_once $notifications_integration;
					BB_Notifications::instance();
				}

				$messages_integration = __DIR__ . '/classes/integrations/class-bb-messages.php';
				if ( Performance::mu_is_component_active( 'messages' ) && file_exists( $messages_integration ) ) {
					require_once $messages_integration;
					BB_Messages::instance();
				}

				$media_photos_integration = __DIR__ . '/classes/integrations/class-bb-media-photos.php';
				if ( Performance::mu_is_component_active( 'media' ) && file_exists( $media_photos_integration ) ) {
					require_once $media_photos_integration;
					BB_Media_Photos::instance();
				}

				$media_albums_integration = __DIR__ . '/classes/integrations/class-bb-media-albums.php';
				if ( Performance::mu_is_component_active( 'media' ) && file_exists( $media_albums_integration ) ) {
					require_once $media_albums_integration;
					BB_Media_Albums::instance();
				}

				$documents_integration = __DIR__ . '/classes/integrations/class-bb-documents.php';
				if ( Performance::mu_is_component_active( 'document' ) && file_exists( $documents_integration ) ) {
					require_once $documents_integration;
					BB_Documents::instance();
				}

				$videos_integration = __DIR__ . '/classes/integrations/class-bb-videos.php';
				if ( Performance::mu_is_component_active( 'video' ) && file_exists( $videos_integration ) ) {
					require_once $videos_integration;
					BB_Videos::instance();
				}

				$subscriptions_integration = __DIR__ . '/classes/integrations/class-bb-subscriptions.php';
				if ( file_exists( $subscriptions_integration ) ) {
					require_once $subscriptions_integration;
					BB_Subscriptions::instance();
				}
			}

			// Load platform or bbPress related cache integration.
			if ( ( Performance::mu_is_plugin_active( 'buddyboss-platform/bp-loader.php' ) && Performance::mu_is_component_active( 'forums' ) ) || Performance::mu_is_plugin_active( 'bbpress/bbpress.php' ) ) {
				$forum_integration = __DIR__ . '/classes/integrations/class-bb-forums.php';
				$topic_integration = __DIR__ . '/classes/integrations/class-bb-topics.php';
				$reply_integration = __DIR__ . '/classes/integrations/class-bb-replies.php';

				if ( file_exists( $forum_integration ) ) {
					require_once $forum_integration;
					BB_Forums::instance();
				}

				if ( file_exists( $topic_integration ) ) {
					require_once $topic_integration;
					BB_Topics::instance();
				}

				if ( file_exists( $reply_integration ) ) {
					require_once $reply_integration;
					BB_Replies::instance();
				}
			}
		}

		/**
		 * Purge cache while symlink expiered.
		 */
		public function purge_symlink_cache() {
			$purge_components = array(
				'bp-activity',
				'bbp-forums',
				'bbp-topics',
				'bbp-replies',
				'bp-media-photos',
				'bp-media-albums',
				'bp-document',
				'bp-messages',
				'bp-video',
			);

			if ( ! empty( $purge_components ) ) {
				$purge_components = array_unique( $purge_components );

				foreach ( $purge_components as $purge_component ) {
					Cache::instance()->purge_by_component( $purge_component );
				}

				/**
				 * Fires after cache purge for symlink expired.
				 *
				 * @since BuddyBoss 2.9.11
				 */
				do_action( 'bb_purge_symlink_cache_after' );
			}
		}

		/**
		 * Modifies the list of components that should be purged based on specific configuration changes.
		 *
		 * @since BuddyBoss 2.9.11
		 *
		 * @param string $option           The name of the option being updated.
		 * @param mixed  $old_value        The old value of the option.
		 * @param mixed  $value            The new value of the option.
		 *
		 * @param array  $purge_components The default list of components to purge.
		 *
		 * @return array The updated list of components to purge.
		 */
		public function bb_purge_components( $purge_components, $option, $old_value, $value ) {
			$bb_purge_components = array();

			if ( 'bp-active-components' === $option ) {
				$uninstalled_components = array_diff_key( $old_value, $value );
				$uninstalled_components = array_keys( $uninstalled_components );
				$non_cached_component   = array(
					'settings',
					'invites',
					'moderation',
					'search',
				);

				if ( ! empty( $uninstalled_components ) ) {
					$can_purge_cache = false;

					foreach ( $uninstalled_components as $component ) {
						if ( in_array( $component, $non_cached_component, true ) ) {
							continue;
						}

						$can_purge_cache = true;
					}

					if ( true === $can_purge_cache ) {
						$bb_purge_components = array_merge( $purge_components, Settings::instance()->get_group_purge_actions( 'bbplatform' ) );
					}
				}
			}

			if ( 'bp_ld_sync_settings' === $option ) {
				if ( ! empty( $value ) && isset( $value['course'] ) ) {
					if ( isset( $value['course']['courses_visibility'] ) && '0' === $value['course']['courses_visibility'] ) {
						$bb_purge_components = array_merge( $purge_components, Settings::instance()->get_group_purge_actions( 'learndash' ) );
					}
				}
			}

			return array_merge( $purge_components, $bb_purge_components );
		}

		/**
		 * Get Purge actions by group
		 *
		 * @param array  $actions Actions list to perform.
		 * @param string $group   Cache Group name.
		 *
		 * @return array
		 */
		public function get_group_purge_actions( $actions, $group ) {
			if ( 'bbplatform' === $group ) {
				$actions = array(
					'bp-members',
					'bp-notifications',
					'bp-groups',
					'bbp-forums',
					'bbp-topics',
					'bbp-replies',
					'bp-activity',
					'bp-messages',
					'bp-friends',
					'bp-media',
					'bp-document',
					'bp-video',
					'bb-subscription',
				);
			}

			return $actions;
		}

		/**
		 * Check for platform components deeplinking caching.
		 *
		 * @since BuddyBoss 2.9.11
		 *
		 * @param array $deeplinking_cache_args Array of enabled components for deeplinking.
		 *
		 * @return mixed
		 */
		public function performance_deep_linking_supported_items( $deeplinking_cache_args ) {
			$is_component_active           = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
			$bb_groups_settings            = Helper::instance()->get_app_settings( 'cache_bb_social_groups', 'buddyboss-app' );
			$bb_activity_settings          = Helper::instance()->get_app_settings( 'cache_bb_activity_feeds', 'buddyboss-app' );
			$bb_member_settings            = Helper::instance()->get_app_settings( 'cache_bb_members', 'buddyboss-app' );
			$bb_private_messaging          = Helper::instance()->get_app_settings( 'cache_bb_private_messaging', 'buddyboss-app' );
			$bb_forum_discussions_settings = Helper::instance()->get_app_settings( 'cache_bb_forum_discussions', 'buddyboss-app' );

			// Groups.
			if ( ! empty( $is_component_active ) && ! empty( $bb_groups_settings ) ) {
				$deeplinking_cache_args['bp-groups'] = array(
					'action'    => array( 'open_groups', 'open_member_groups' ),
					'namespace' => array( 'buddypress' ),
				);
			}

			// Activity.
			if ( ! empty( $is_component_active ) && ! empty( $bb_activity_settings ) ) {
				$deeplinking_cache_args['bp-activity'] = array(
					'action'    => array( 'open_activity' ),
					'namespace' => array( 'buddypress' ),
				);
			}

			// Members.
			if ( ! empty( $is_component_active ) && ! empty( $bb_member_settings ) ) {
				$deeplinking_cache_args['bp-members'] = array(
					'action'    => array( 'open_member_profile' ),
					'namespace' => array( 'buddypress' ),
				);
			}

			// Message.
			if ( ! empty( $is_component_active ) && ! empty( $bb_private_messaging ) ) {
				$deeplinking_cache_args['bp-messages'] = array(
					'action'    => array( 'open_member_messages' ),
					'namespace' => array( 'buddypress' ),
				);
			}

			// Forums.
			if ( ! empty( $is_component_active ) && ! empty( $bb_forum_discussions_settings ) ) {
				$deeplinking_cache_args['bbp-forums'] = array(
					'action'    => array( 'open_groups_forum', 'open_member_forums', 'open_forum' ),
					'namespace' => array( 'buddypress', 'core' ),
				);

				$deeplinking_cache_args['bbp-replies'] = array(
					'action'    => array( 'open_reply' ),
					'namespace' => array( 'core' ),
				);

				$deeplinking_cache_args['bbp-topics'] = array(
					'action'    => array( 'open_topic' ),
					'namespace' => array( 'core' ),
				);
			}

			return $deeplinking_cache_args;
		}
	}

	new BP_Performance_Includes();
}
