<?php

/**
 * Forums BuddyPress Members Class
 *
 * @package BuddyBoss\Forums
 * @since bbPress (r4395)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBP_Forums_Members' ) ) :
	/**
	 * Member profile modifications
	 *
	 * @since bbPress (r4395)
	 */
	class BBP_BuddyPress_Members {

		/**
		 * Main constructor for modifying Forums profile links
		 *
		 * @since bbPress (r4395)
		 */
		public function __construct() {
			$this->setup_actions();
			$this->setup_filters();
		}

		/**
		 * Setup the actions
		 *
		 * @since bbPress (r4395)
		 *
		 * @access private
		 * @uses add_filter() To add various filters
		 * @uses add_action() To add various actions
		 */
		private function setup_actions() {

			// Allow unsubscribe/unfavorite links to work
			add_action( 'bp_template_redirect', array( $this, 'set_member_forum_query_vars' ) );

			/** Favorites */

			// Move handler to 'bp_actions' - BuddyBoss bypasses template_loader
			remove_action( 'template_redirect', 'bbp_favorites_handler', 1 );
			add_action( 'bp_actions', 'bbp_favorites_handler', 1 );

			/** Subscriptions */

			// Move handler to 'bp_actions' - BuddyBoss bypasses template_loader
			remove_action( 'template_redirect', 'bbp_subscriptions_handler', 1 );
			add_action( 'bp_actions', 'bbp_subscriptions_handler', 1 );
		}

		/**
		 * Setup the filters
		 *
		 * @since bbPress (r4395)
		 *
		 * @access private
		 * @uses add_filter() To add various filters
		 * @uses add_action() To add various actions
		 */
		private function setup_filters() {
			add_filter( 'bbp_pre_get_user_profile_url', array( $this, 'get_user_profile_url' ) );
			add_filter( 'bbp_pre_get_user_topics_created_url', array( $this, 'get_topics_created_url' ) );
			add_filter( 'bbp_pre_get_user_replies_created_url', array( $this, 'get_replies_created_url' ) );
			add_filter( 'bbp_pre_get_favorites_permalink', array( $this, 'get_favorites_permalink' ) );
			add_filter( 'bbp_pre_get_subscriptions_permalink', array( $this, 'get_subscriptions_permalink' ) );
		}

		/** Filters ***************************************************************/

		/**
		 * Override Forums profile URL with BuddyBoss profile URL
		 *
		 * @since bbPress (r3401)
		 * @since bbPress (r6320) Add engagements support
		 *
		 * @param int $user_id
		 * @return string
		 */
		public function get_user_profile_url( $user_id = 0 ) {
			return $this->get_profile_url( $user_id );
		}

		/**
		 * Override Forums topics created URL with BuddyBoss profile URL
		 *
		 * @since bbPress (r3721)
		 * @since bbPress (r6803) Use private method
		 *
		 * @param int $user_id
		 * @return string
		 */
		public function get_topics_created_url( $user_id = 0 ) {
			return $this->get_profile_url( $user_id, bbp_get_topic_archive_slug() );
		}
		/**
		 * Override Forums replies created URL with BuddyBoss profile URL
		 *
		 * @since bbPress (r3721)
		 * @since bbPress (r6803) Use private method
		 *
		 * @param int $user_id
		 * @return string
		 */
		public function get_replies_created_url( $user_id = 0 ) {
			return $this->get_profile_url( $user_id, bbp_get_reply_archive_slug() );
		}

		/**
		 * Override Forums favorites URL with BuddyBoss profile URL
		 *
		 * @since bbPress (r3721)
		 * @since bbPress (r6803) Use private method
		 *
		 * @param int $user_id
		 * @return string
		 */
		public function get_favorites_permalink( $user_id = 0 ) {
			return $this->get_profile_url( $user_id, bbp_get_user_favorites_slug() );
		}

		/**
		 * Override Forums subscriptions URL with BuddyBoss profile URL
		 *
		 * @since bbPress (r3721)
		 * @since bbPress (r6803) Use private method
		 *
		 * @param int $user_id
		 * @return string
		 */
		public function get_subscriptions_permalink( $url, $user_id ) {
			$url = trailingslashit( bp_core_get_user_domain( $user_id ) . bp_get_settings_slug() ) . 'notifications/subscriptions';
			return $url;
		}

		/**
		 * Set favorites and subscriptions query variables if viewing member profile
		 * pages.
		 *
		 * @since bbPress (r4615)
		 * @since bbPress (r6320) Support all profile sections
		 *
		 * @global WP_Query $wp_query
		 * @return If not viewing your own profile
		 */
		public function set_member_forum_query_vars() {

			// Special handling for forum component
			if ( ! bp_is_my_profile() && ! bbp_is_single_user() ) {
				return;
			}

			// Get the main query object
			$wp_query = bbp_get_wp_query();

			// 'topics' action
			if ( bp_is_current_action( bbp_get_topic_archive_slug() ) ) {
				$wp_query->bbp_is_single_user_topics = true;

				// 'replies' action
			} elseif ( bp_is_current_action( bbp_get_reply_archive_slug() ) ) {
				$wp_query->bbp_is_single_user_replies = true;

				// 'favorites' action
			} elseif ( bbp_is_favorites_active() && bp_is_current_action( bbp_get_user_favorites_slug() ) ) {
				$wp_query->bbp_is_single_user_favs = true;
			}
		}

		/** Private Methods *******************************************************/

		/**
		 * Private method used to concatenate user IDs and slugs into URLs.
		 *
		 * @since bbPress (r6803)
		 *
		 * @param int    $user_id User id.
		 * @param string $slug    Slug of the current active page.
		 *
		 * @return string
		 */
		private function get_profile_url( $user_id = 0, $slug = '' ) {

			// Do not filter if not on BuddyPress root blog.
			if ( empty( $user_id ) || ! bp_is_root_blog() ) {
				return false;
			}

			// Setup profile URL.
			$url = array( bp_core_get_user_domain( $user_id ) );

			// Maybe push slug to end of URL array.
			if ( ! empty( $slug ) ) {
				array_push( $url, bbpress()->extend->buddypress->slug );
				array_push( $url, $slug );
			}

			if ( ! empty( array_filter( $url ) ) ) {
				// Return.
				return implode( '', array_map( 'trailingslashit', $url ) );
			}

			return '';
		}
	}
endif;
