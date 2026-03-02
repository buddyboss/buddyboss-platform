<?php
/**
 * BuddyBoss Forum Legacy.
 *
 * @package BuddyBoss\Forums
 * @since BuddyBoss 2.2.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Forums_Legacy' ) ) {
	/**
	 * Setup the bp forums legacy class.
	 *
	 * @since 2.2.6
	 */
	class BP_Forums_Legacy {

		/**
		 * The single instance of the class.
		 *
		 * @since 2.2.6
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Forum legacy Constructor.
		 *
		 * @since 2.2.6
		 */
		public function __construct() {

			// Include the code.
			$this->setup_actions();
		}

		/**
		 * Get the instance of this class.
		 *
		 * @since 2.2.6
		 *
		 * @return object Instance.
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Setup actions for Profile Settings.
		 *
		 * @since 2.2.6
		 */
		public function setup_actions() {
			// Create or delete legacy forum and topic subscriptions.
			add_action( 'bb_create_subscription', array( $this, 'bb_create_legacy_forum_subscriptions' ), 10, 1 );
			add_action( 'bb_subscriptions_before_delete_subscription', array( $this, 'bb_delete_legacy_forum_subscriptions' ), 10, 1 );
			add_action( 'bb_subscriptions_after_update_subscription_status', array( $this, 'bb_add_remove_all_legacy_forum_subscriptions' ), 10, 4 );
		}

		/**
		 * Function to check the forums legacy is enabled or not.
		 *
		 * @since 2.2.6
		 *
		 * @return bool True if forums legacy is enabled otherwise false.
		 */
		public function bb_forums_enabled_forums_legacy() {
			return (bool) apply_filters( 'bb_legacy_forums_subscriptions_data_v1', true );
		}

		/**
		 * Create legacy forum and topic subscriptions.
		 *
		 * @since 2.2.6
		 *
		 * @param array $args Array of argument to create a new subscription.
		 *
		 * @return void|bool
		 */
		public function bb_create_legacy_forum_subscriptions( $args ) {
			// Check the legacy forum and topic subscriptions is enabled or not.
			if ( ! $this->bb_forums_enabled_forums_legacy() ) {
				return;
			}

			$r = bp_parse_args(
				$args,
				array(
					'type'    => '',
					'user_id' => bp_loggedin_user_id(),
					'item_id' => 0,
				),
				'bb_create_legacy_subscription'
			);

			if (
				( empty( $r['type'] ) || empty( $r['item_id'] ) ) ||
				( ! in_array( $r['type'], array( 'forum', 'topic' ), true ) )
			) {
				return false;
			}

			return self::bb_add_user_legacy_subscription( $r['user_id'], $r['item_id'] );
		}

		/**
		 * Create legacy forum and topic subscriptions.
		 *
		 * @since 2.2.6
		 *
		 * @param int $subscription_id New modern subscription ID.
		 *
		 * @return void|bool
		 */
		public function bb_delete_legacy_forum_subscriptions( $subscription_id ) {
			// Check the legacy forum and topic subscriptions is enabled or not.
			if ( ! $this->bb_forums_enabled_forums_legacy() ) {
				return;
			}

			if ( ! empty( $subscription_id ) ) {
				// Get the subscription object.
				$subscription = bb_subscriptions_get_subscription( $subscription_id );

				if (
					( empty( $subscription->type ) || empty( $subscription->item_id ) ) ||
					( ! in_array( $subscription->type, array( 'forum', 'topic' ), true ) )
				) {
					return false;
				}

				self::bb_delete_user_legacy_subscription( $subscription->user_id, $subscription->item_id );

				return true;
			}

			return false;
		}

		/**
		 * Create/delete legacy forum and topic subscriptions.
		 *
		 * @since 2.2.6
		 *
		 * @param string $type    Subscription type.
		 * @param int    $item_id Forum/topic ID.
		 * @param int    $status  Status of forum/topic.
		 * @param int    $blog_id The site ID.
		 *
		 * @return void|bool
		 */
		public function bb_add_remove_all_legacy_forum_subscriptions( $type, $item_id, $status, $blog_id ) {
			// Check the legacy forum and topic subscriptions is enabled or not.
			if ( ! $this->bb_forums_enabled_forums_legacy() ) {
				return;
			}

			// Return if not forum/topic subscriptions.
			if ( ! in_array( $type, array( 'forum', 'topic' ), true ) ) {
				return false;
			}

			// Get user meta key for subscriptions.
			$user_meta_key = self::bb_get_user_legacy_subscription_key( $item_id );
			if ( empty( $user_meta_key ) ) {
				return false;
			}

			$get_subscriptions = bb_get_subscription_users(
				array(
					'blog_id' => $blog_id,
					'item_id' => $item_id,
					'type'    => $type,
					'count'   => false,
				),
				true
			);

			$subscribe_user_ids = array();
			if ( ! empty( $get_subscriptions['subscriptions'] ) ) {
				$subscribe_user_ids = array_filter( wp_parse_id_list( $get_subscriptions['subscriptions'] ) );
			}

			// Users exist.
			if ( ! empty( $subscribe_user_ids ) ) {

				// Loop through users.
				if ( 1 === (int) $status ) {
					foreach ( $subscribe_user_ids as $user_id ) {
						// Add each user.
						self::bb_add_user_legacy_subscription( $user_id, $item_id );
					}
				} else {
					foreach ( $subscribe_user_ids as $user_id ) {
						// Remove each user.
						self::bb_delete_user_legacy_subscription( $user_id, $item_id );
					}
				}
			}

			return true;
		}

		/**
		 * Create legacy forum/topic subscriptions.
		 *
		 * @since 2.2.6
		 *
		 * @param int $user_id ID of user.
		 * @param int $item_id ID of forum/topic.
		 *
		 * @return void|bool
		 */
		public static function bb_add_user_legacy_subscription( $user_id, $item_id ) {
			// Get user meta key for subscriptions.
			$user_meta_key = self::bb_get_user_legacy_subscription_key( $item_id );
			if ( empty( $user_meta_key ) ) {
				return false;
			}

			$subscriptions = self::bb_get_user_legacy_subscription( $user_id, $item_id );

			if ( ! in_array( $item_id, $subscriptions, true ) ) {
				$subscriptions[] = $item_id;
				$subscriptions   = implode( ',', wp_parse_id_list( array_filter( $subscriptions ) ) );
				update_user_meta( $user_id, $user_meta_key, $subscriptions );
			}

			return true;
		}

		/**
		 * Delete legacy forum/topic subscriptions.
		 *
		 * @since 2.2.6
		 *
		 * @param int $user_id ID of user.
		 * @param int $item_id ID of forum/topic.
		 *
		 * @return void|bool
		 */
		public static function bb_delete_user_legacy_subscription( $user_id, $item_id ) {
			// Get user meta key for subscriptions.
			$user_meta_key = self::bb_get_user_legacy_subscription_key( $item_id );
			if ( empty( $user_meta_key ) ) {
				return false;
			}

			$subscriptions = self::bb_get_user_legacy_subscription( $user_id, $item_id );

			$pos = array_search( $item_id, $subscriptions, true );
			if ( false === $pos ) {
				return false;
			}

			array_splice( $subscriptions, $pos, 1 );
			$subscriptions = array_filter( $subscriptions );
			if ( ! empty( $subscriptions ) ) {
				$subscriptions = implode( ',', wp_parse_id_list( $subscriptions ) );
				update_user_meta( $user_id, $user_meta_key, $subscriptions );
			} else {
				delete_user_meta( $user_id, $user_meta_key );
			}

			return true;
		}

		/**
		 * Get legacy forum/topic subscriptions.
		 *
		 * @since 2.2.6
		 *
		 * @param int $user_id ID of user.
		 * @param int $item_id ID of forum.
		 *
		 * @return array
		 */
		public static function bb_get_user_legacy_subscription( $user_id, $item_id ) {
			$subscriptions = array();

			// Get user meta key for subscriptions.
			$user_meta_key = self::bb_get_user_legacy_subscription_key( $item_id );
			if ( empty( $user_meta_key ) ) {
				return $subscriptions;
			}

			$subscriptions = get_user_meta( $user_id, $user_meta_key, true );

			return array_filter( wp_parse_id_list( $subscriptions ) );
		}

		/**
		 * Get legacy forum/topic subscriptions key.
		 *
		 * @since 2.2.6
		 *
		 * @param int $item_id ID of forum/topic.
		 *
		 * @return string
		 */
		public static function bb_get_user_legacy_subscription_key( $item_id ) {
			global $wpdb;

			// Get the post type.
			$post_type = get_post_type( $item_id );
			if ( empty( $post_type ) ) {
				return false;
			}

			switch ( $post_type ) {

				// Forum.
				case bbp_get_forum_post_type():
					$user_meta_key = $wpdb->prefix . '_bbp_forum_subscriptions';
					break;

				// Topic.
				case bbp_get_topic_post_type():
				default:
					$user_meta_key = $wpdb->prefix . '_bbp_subscriptions';
					break;
			}

			return $user_meta_key;
		}
	}

	/**
	 * Call forum subscription instance.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @return BP_Forums_Legacy
	 */
	function bb_forum_legacy_subscription() {
		return BP_Forums_Legacy::instance();
	}

	bb_forum_legacy_subscription();
}
