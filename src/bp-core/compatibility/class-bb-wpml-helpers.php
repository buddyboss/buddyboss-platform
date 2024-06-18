<?php
/**
 * Helper class for the third party plugins WPML
 *
 * @package BuddyBoss
 *
 * @since BuddyBoss 2.0.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BB_WPML_Helpers' ) ) {

	/**
	 * BB_WPML_Helpers Class
	 *
	 * This class handles compatibility code for third party plugins used in conjunction with Platform
	 */
	class BB_WPML_Helpers {

		/**
		 * The single instance of the class.
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * BB_WPML_Helpers constructor.
		 */
		public function __construct() {

			$this->compatibility_init();
		}

		/**
		 * Get the instance of this class.
		 *
		 * @return Controller|null
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Register the compatibility hooks for the plugin.
		 */
		public function compatibility_init() {
			add_action( 'wp_loaded', array( $this, 'remove_filter_for_the_content' ) );
			add_action( 'parse_query', array( $this, 'bp_core_fix_wpml_redirection' ), 5 );

			add_filter( 'bp_core_get_root_domain', array( $this, 'bp_core_wpml_fix_get_root_domain' ) );
			add_filter( 'bp_core_get_directory_page_ids', array( $this, 'bb_core_get_wpml_directory_page_ids' ), 10, 1 );

			// Trigger function to delete profile completion data when switch language.
			add_action( 'wpml_language_has_switched', 'bp_core_xprofile_clear_all_user_progress_cache' );

			add_filter( 'bp_profile_search_main_form', array( $this, 'bb_profile_search_main_form' ) );

			// Forum/Topic.
			add_filter( 'bbp_after_has_topics_parse_args', array( $this, 'bb_wpml_member_profile_topic_reply' ) );
			add_filter( 'bbp_after_has_replies_parse_args', array( $this, 'bb_wpml_member_profile_topic_reply' ) );
			
			// Fix incorrect search results for Global Search for translated/non-translated posts.
			add_filter( 'Bp_Search_Posts_sql', array( $this, 'bb_wpml_search_posts_sql' ), 10, 2 );
		}

		/**
		 * Remove the_content filter for WPML. This filter is added inside a class which has no/untraceable instances
		 * So we will loop $wp_filters and remove it from there and only for the Group & Member profile page.
		 * This filter is added inside this class: WPML_Fix_Links_In_Display_As_Translated_Content and mothod name: fix_fallback_links.
		 *
		 * @since BuddyBoss 2.0.6
		 */
		public function remove_filter_for_the_content() {
			global $wp_filter;

			if ( bp_is_user() || bp_is_group() ) {

				if ( isset( $wp_filter['the_content'] ) && isset( $wp_filter['the_content'][99] ) ) {
					// New filters array.
					$new_filters = array();
					// Loop through 'the_content' filters which has priority 99.
					foreach ( $wp_filter['the_content'][99] as $key => $value ) {
						// Find the exact filter and remove it from array.
						if (
							strpos( $key, 'fix_fallback_links' ) !== false &&
							isset( $value['function'][0] ) &&
							$value['function'][0] instanceof WPML_Fix_Links_In_Display_As_Translated_Content
						) {
							continue;
						}
						$new_filters[ $key ] = $value;
					}

					$wp_filter['the_content'][99] = $new_filters;
				}
			}
		}

		/**
		 * Add fix for WPML redirect issue
		 *
		 * @since BuddyBoss 1.4.0
		 *
		 * @param array $q Array of Query Params.
		 *
		 * @return array
		 */
		public function bp_core_fix_wpml_redirection( $q ) {
			if (
				! defined( 'DOING_AJAX' )
				&& ! bp_is_blog_page()
				&& (bool) $q->get( 'page_id' ) === false
				&& (bool) $q->get( 'pagename' ) === true
			) {
				$bp_current_component = bp_current_component();
				$bp_pages             = bp_core_get_directory_pages();

				if ( 'photos' === $bp_current_component && isset( $bp_pages->media->id ) ) {
					$q->set( 'page_id', $bp_pages->media->id );
				} elseif ( 'forums' === $bp_current_component && isset( $bp_pages->members->id ) ) {
					$q->set( 'page_id', $bp_pages->members->id );
				} elseif ( 'groups' === $bp_current_component && isset( $bp_pages->groups->id ) ) {
					$q->set( 'page_id', $bp_pages->groups->id );
				} elseif ( 'documents' === $bp_current_component && isset( $bp_pages->document->id ) ) {
					$q->set( 'page_id', $bp_pages->document->id );
				} elseif ( 'videos' === $bp_current_component && isset( $bp_pages->video->id ) ) {
					$q->set( 'page_id', $bp_pages->video->id );
				} elseif ( 'activity' === $bp_current_component && isset( $bp_pages->activity->id ) ) {
					$q->set( 'page_id', $bp_pages->activity->id );
				} else {
					$page_id = apply_filters( 'bpml_redirection_page_id', null, $bp_current_component, $bp_pages );
					if ( $page_id ) {
						$q->set( 'page_id', $page_id );
					}
				}
			}

			return $q;
		}

		/**
		 * Fix for url with wpml.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param string $url URL.
		 *
		 * @return string
		 */
		public function bp_core_wpml_fix_get_root_domain( $url ) {
			return untrailingslashit( $url );
		}

		/**
		 * Update BP Core pages with WPML if available otherwise return original IDs.
		 *
		 * @since BuddyBoss 2.1.0
		 *
		 * @param array $page_ids BP Core pages ID.
		 *
		 * @return array
		 */
		public function bb_core_get_wpml_directory_page_ids( $page_ids ) {
			// Loop through pages.
			foreach ( $page_ids as $component_name => $page_id ) {
				$page_ids[ $component_name ] = apply_filters( 'wpml_object_id', $page_id, get_post_type( $page_id ), true );
			}

			return $page_ids;
		}

		/**
		 * Added support for the profile search translatable.
		 *
		 * @since BuddyBoss 2.4.60
		 *
		 * @param int $post_id Profile search post id.
		 *
		 * @return int
		 */
		public function bb_profile_search_main_form( $post_id ) {
			global $sitepress;
			if ( $sitepress->is_translated_post_type( 'bp_ps_form' ) ) {
				$args = array(
					'post_type'        => 'bp_ps_form',
					'post_status'      => 'publish',
					'numberposts'      => 1,
					'fields'           => 'ids',
					'suppress_filters' => false,
					'lang'             => ICL_LANGUAGE_CODE, // Get the current language code.
				);

				$profile_query = get_posts( $args );
				if ( ! empty( $profile_query ) ) {
					return current( $profile_query );
				}
			}

			return $post_id;
		}

		/**
		 * Adjusts the query arguments for member profile topic replies when using WPML.
		 * If WPML plugin is active and the query arguments include 'post_parent' set to 'any',
		 * this function adjusts it to an empty string to ensure correct filtering.
		 *
		 * @since BuddyBoss 2.5.70
		 *
		 * @param array $r The query arguments.
		 *
		 * @return array Modified query arguments.
		 */
		public function bb_wpml_member_profile_topic_reply( $r ) {
			if ( class_exists( 'Sitepress' ) && isset( $r['post_parent'] ) && 'any' === $r['post_parent'] ) {
				$r['post_parent'] = '';
			}

			return $r;
		}

		/**
		 * Remove WPML post__in filter to allow parent translated post as well if the post is not translatable.
		 *
		 * @since BuddyBoss 2.6.00
		 *
		 * @param WP_Query $q Query for parsing WP QUERY.
		 *
		 * @return WP_Query $q Returns modified Query.
		 */
		public function bb_remove_wpml_post_parse_query( $q ) {
			if ( isset( $q->query_vars['post__in'] ) ) {
				unset( $q->query_vars['post__in'] );
			}

			return $q;
		}

		/**
		 * Add fix for WPML post count issue in Global Search.
		 *
		 * @since BuddyBoss 2.6.00
		 *
		 * @param string $sql_query String of the SQL query to filter.
		 * @param array  $args      Arguments of the filter to get the post_type.
		 *
		 * @return string $sql_query String of the SQL query to filter.
		 */
		public function bb_wpml_search_posts_sql( $sql_query, $args ) {
			global $sitepress;

			if (
				defined( 'ICL_LANGUAGE_CODE' ) &&
				$sitepress->is_translated_post_type( $args['post_type'] )
			) {
			        global $wpdb;
				$sql_query .= " AND EXISTS (
						SELECT 1 
						FROM {$wpdb->prefix}icl_translations t
						WHERE t.element_type = CONCAT('post_', %s)
						AND t.language_code = %s
						AND t.element_id = p.ID
					)";

				$sql_query = $wpdb->prepare( $sql_query, $args['post_type'], ICL_LANGUAGE_CODE );
			} else {
				add_filter( 'wpml_post_parse_query', array( $this, 'bb_remove_wpml_post_parse_query' ) );
			}

			return $sql_query;
		}

	}

	BB_WPML_Helpers::instance();
}
