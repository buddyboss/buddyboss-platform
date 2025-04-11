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

			add_action( 'bb_get_the_profile_field_options_select_html', array( $this, 'bb_wpml_profile_field_options_order' ), 10, 2 );

			add_filter( 'bp_groups_get_where_conditions', array( $this, 'bb_wpml_groups_dir_search_where_conditions' ), 10, 2 );
			add_filter( 'Bp_Search_Groups_sql', array( $this, 'bb_wpml_groups_search_global_sql' ), 10, 2 );

			// Prevent activity creation for WPML translated posts.
			add_filter( 'bp_init', array( $this, 'bb_prevent_translated_post_activities' ) );
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

		/**
		 * Sort the xprofile field options alphabetically according to the current language.
		 * Reference: https://wpml.org/errata/buddyboss-alphabetical-sorting-issue-for-translated-taxonomy-fields-in-frontend/
		 *
		 * @since 2.7.40
		 *
		 * @param string $html      The HTML output of the field.
		 * @param object $field_obj The field object.
		 *
		 * @return string $html The sorted HTML output of the field.
		 */
		public function bb_wpml_profile_field_options_order( $html, $field_obj ) {
			if ( class_exists( 'Sitepress' ) && 'en' !== apply_filters( 'wpml_current_language', null ) ) {
				$order_by = ! empty( $field_obj->order_by ) ? $field_obj->order_by : 'asc';

				if ( 'custom' === $order_by ) {
					return $html;
				}

				preg_match_all( '/<option(.*?)>(.*?)<\/option>/s', $html, $matches, PREG_SET_ORDER );

				$options = array();
				foreach ( $matches as $match ) {
					$options[] = array(
						'full'       => $match[0],
						'attributes' => $match[1],
						'text'       => $match[2],
					);
				}

				// Sort the array by the 'text' element.
				usort(
					$options,
					function ( $a, $b ) {
						return strcmp( $a['text'], $b['text'] );
					}
				);

				if ( 'desc' === $order_by ) {
					$first   = array_shift( $options );
					$options = array_reverse( $options );
					array_unshift( $options, $first );
				}

				// Rebuild the HTML string.
				$sorted_options = '';
				foreach ( $options as $option ) {
					$sorted_options .= sprintf( '<option%s>%s</option>', $option['attributes'], $option['text'] );
				}

				$html = $sorted_options;
			}

			return $html;
		}

		/**
		 * Retrieves the translated group name from WPML.
		 *
		 * @since BuddyBoss 2.8.10
		 *
		 * @param string $search_term The original search term.
		 *
		 * @return string|false The translated group name if found, otherwise false.
		 */
		public static function bb_get_wpml_translated_group_name( $search_term ) {
			if ( ! class_exists( 'Sitepress' ) || empty( $search_term ) ) {
				return false;
			}

			global $wpdb;

			return $wpdb->get_var(
				$wpdb->prepare(
					"SELECT s.value
		                 FROM {$wpdb->prefix}icl_string_translations st
		                 JOIN {$wpdb->prefix}icl_strings s ON st.string_id = s.id
		                 WHERE s.context = %s
		                 AND st.value LIKE %s
		                 AND st.language = %s",
					'Buddypress Multilingual',
					'%' . $wpdb->esc_like( $search_term ) . '%',
					ICL_LANGUAGE_CODE
				)
			);
		}

		/**
		 * Modifies the WHERE conditions in BuddyPress group search to
		 * include translated names.
		 *
		 * @param array $where The existing WHERE conditions.
		 * @param array $r     The search query arguments.
		 *
		 * @return array Modified WHERE conditions.
		 */
		public function bb_wpml_groups_dir_search_where_conditions( $where, $r ) {
			if ( isset( $r['search_terms'] ) && $r['search_terms'] ) {
				$translated_name = self::bb_get_wpml_translated_group_name( $r['search_terms'] );

				if ( ! empty( $translated_name ) ) {
					$where['search'] = str_replace( $r['search_terms'], $translated_name, $where['search'] );
				}
			}

			return $where;
		}

		/**
		 * Modifies the SQL query for searching BuddyPress groups by replacing
		 * the search term with its translated version.
		 *
		 * @param string $sql_query The original SQL query.
		 * @param array  $args      The search query arguments.
		 *
		 * @return string Modified SQL query.
		 */
		public function bb_wpml_groups_search_global_sql( $sql_query, $args ) {
			if ( isset( $args['search_term'] ) && $args['search_term'] ) {
				$translated_name = self::bb_get_wpml_translated_group_name( $args['search_term'] );

				if ( ! empty( $translated_name ) ) {
					$sql_query = str_replace( $args['search_term'], $translated_name, $sql_query );
				}
			}

			return $sql_query;
		}

		/**
		 * Prevents activity creation for WPML translated posts.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_prevent_translated_post_activities() {
			if ( ! bp_is_active( 'activity' ) ) {
				return;
			}

			// Get all post types.
			$post_types = get_post_types( array( 'public' => true ), 'names' );
			if ( ! empty( $post_types ) ) {
				foreach ( $post_types as $post_type ) {
					add_filter( "bp_activity_{$post_type}_pre_publish", array( $this, 'bb_check_if_wpml_translation' ), 10, 4 );
				}
			}

			// For WPML translation approvals via AJAX.
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				$this->bb_hook_wp_send_json();
			}
		}

		/**
		 * Override WordPress's wp_send_json_success function to intercept WPML translation approvals.
		 * This is a hacky but effective approach since WPML doesn't provide a hook before sending the response.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		private function bb_hook_wp_send_json() {

			if ( empty( $_POST['nonce'] ) || empty( $_POST['endpoint'] ) || empty( $_POST['action'] ) ) {
				return;
			}

			$nonce    = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
			$endpoint = sanitize_text_field( wp_unslash( $_POST['endpoint'] ) );
			$action   = sanitize_text_field( wp_unslash( $_POST['action'] ) );

			if (
				! wp_verify_nonce( $nonce, $endpoint ) ||
				'WPML\TM\ATE\Review\AcceptTranslation' !== $endpoint ||
				'wpml_action' !== $action
			) {
				return;
			}

			// Extract the post ID from the request.
			$post_id = null;
			if ( isset( $_POST['data'] ) ) {
				$data = json_decode( sanitize_text_field( wp_unslash( $_POST['data'] ) ), true );
				if ( isset( $data['postId'] ) ) {
					$post_id = intval( $data['postId'] );
				}
			}

			// If we don't have a post ID, there's nothing to do.
			if ( ! $post_id ) {
				return;
			}

			// Define a function that will run before wp_send_json_success is called.
			// The function will be removed immediately after it runs once.
			add_filter(
				'wp_die_ajax_handler',
				function( $function ) use ( $post_id ) {
					// Get the post to pass to bp_activity_post_type_publish.
					$post = get_post( $post_id );
					if ( $post && 'publish' === $post->post_status ) {
						// Before sending the response, explicitly check if this is a translation and prevent activity.
						if ( function_exists( 'bp_activity_post_type_publish' ) ) {

							// Now call the function directly to process this post.
							bp_activity_post_type_publish( $post_id, $post );
						}
					}

					// Return the original function to ensure normal execution.
					return $function;
				},
				1
			);
		}

		/**
		 * Check if the post is a WPML translation and prevent activity creation if it is.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param bool $return  Whether the post should be published to activity.
		 * @param int  $blog_id ID of the current site.
		 * @param int  $post_id ID of the current post being published.
		 * @param int  $user_id ID of the current user or post author.
		 *
		 * @return bool False if it's a translation, otherwise the original value.
		 */
		public function bb_check_if_wpml_translation( $return, $blog_id, $post_id, $user_id ) {
			global $wpdb, $sitepress;

			// Continue only if WPML is active and we have the necessary components.
			if ( ! defined( 'ICL_SITEPRESS_VERSION' ) || ! $sitepress || empty( $post_id ) ) {
				return $return;
			}

			$default_lang = apply_filters( 'wpml_default_language', null );
			if ( empty( $default_lang ) ) {
				return $return;
			}

			// Get current language - if it's not the default language, we don't want to create an activity.
			$current_language = apply_filters( 'wpml_current_language', null );
			if ( ! empty( $current_language ) && $current_language !== $default_lang ) {
				return false;
			}

			// Not identified as a translation.
			return $return;
		}
	}

	BB_WPML_Helpers::instance();
}
