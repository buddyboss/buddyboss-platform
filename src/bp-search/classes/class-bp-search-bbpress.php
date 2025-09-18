<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_bbPress' ) ) :

	/**
	 * BuddyPress Global Search  - search bbPress class
	 */
	abstract class Bp_Search_bbPress extends Bp_Search_Type {
		public $type;

		function sql( $search_term, $only_totalrow_count = false ) {
			global $wpdb;
			$query_placeholder = array();

			$sql = ' SELECT ';

			if ( $only_totalrow_count ) {
				$sql .= ' COUNT( DISTINCT id ) ';
			} else {
				$sql                .= " DISTINCT id , '{$this->type}' as type, post_title LIKE %s AS relevance, post_date as entry_date  ";
				$query_placeholder[] = '%' . $search_term . '%';
			}

			$sql                .= " FROM
						{$wpdb->posts}
					WHERE
						1=1
						AND (
								(
										(post_title LIKE %s)
									OR 	(post_content LIKE %s)
								)
							)
						AND post_type = '{$this->type}'
						AND post_status = 'publish'
				";
			$query_placeholder[] = '%' . $search_term . '%';
			$query_placeholder[] = '%' . $search_term . '%';
			$sql                 = $wpdb->prepare( $sql, $query_placeholder );

			return apply_filters(
				'Bp_Search_Forums_sql',
				$sql,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		/**
		 * Get user accessible groups with static caching to avoid code duplication.
		 *
		 * @since BuddyBoss 2.12.0
		 *
		 * @return array Array containing user_group_ids and excluded_group_ids
		 */
		protected function get_user_accessible_groups() {
			static $cache = null;

			if ( null !== $cache ) {
				return $cache;
			}

			$user_group_ids     = array();
			$excluded_group_ids = array();

			if ( bp_is_active( 'groups' ) ) {
				$current_user_id = get_current_user_id();

				// Get user's group memberships.
				$user_groups    = bp_get_user_groups(
					$current_user_id,
					array(
						'is_admin' => null,
						'is_mod'   => null,
					)
				);
				$user_group_ids = wp_list_pluck( $user_groups, 'group_id' );

				// Use static cache for restricted groups to avoid multiple database queries.
				static $restricted_groups_cache = null;

				if ( null === $restricted_groups_cache ) {
					// Get all private and hidden groups.
					$restricted_groups       = groups_get_groups(
						array(
							'fields'   => 'ids',
							'status'   => array( 'private', 'hidden' ),
							'per_page' => - 1,
						)
					);
					$restricted_groups_cache = ! empty( $restricted_groups['groups'] ) ? $restricted_groups['groups'] : array();
				}

				$restricted_group_ids = $restricted_groups_cache;

				// Groups that user cannot access.
				$excluded_group_ids = array_diff( $restricted_group_ids, $user_group_ids );
			}

			$cache = array(
				'user_group_ids'     => $user_group_ids,
				'excluded_group_ids' => $excluded_group_ids,
			);

			return $cache;
		}

		protected function generate_html( $template_type = '' ) {
			$post_ids = array();
			foreach ( $this->search_results['items'] as $item_id => $item_html ) {
				$post_ids[] = $item_id;
			}

			remove_action( 'pre_get_posts', 'bbp_pre_get_posts_normalize_forum_visibility', 4 );

			// now we have all the posts
			// lets do a wp_query and generate html for all posts
			$qry = new WP_Query(
				array(
					'post_type'     => $this->type,
					'post__in'      => $post_ids,
					'post_status'   => array( 'publish', 'private', 'hidden', 'closed' ),
					'no_found_rows' => true,
					'nopaging'      => true,
				)
			);

			add_action( 'pre_get_posts', 'bbp_pre_get_posts_normalize_forum_visibility', 4 );

			if ( $qry->have_posts() ) {
				while ( $qry->have_posts() ) {
					$qry->the_post();

					$post_id = get_the_ID();
					/**
					 * The following will try to load loop/forum.php, loop/topic.php loop/reply.php(if reply is included).
					 */
					$result_item = array(
						'id'    => $post_id,
						'type'  => $this->type,
						'title' => get_the_title(),
						'html'  => bp_search_buffer_template_part( 'loop/' . $this->type, $template_type, false ),
					);

					$this->search_results['items'][ $post_id ] = $result_item;
				}
			}
			wp_reset_postdata();
		}

		/**
		 * Get all nested child forum ids.
		 *
		 * @since BuddyBoss 1.6.3
		 *
		 * @uses bbp_get_forum_post_type() Get forum post type.
		 *
		 * @param int $forum_id Forum ID to get nested child forum ids for.
		 *
		 * @return array
		 */
		public function nested_child_forum_ids( $forum_id ) {
			static $bp_nested_child_forum_ids = array();
			global $wpdb;

			$cache_key = 'nested_child_forum_ids_' . bbp_get_forum_post_type() . '_' . $forum_id;
			if ( ! isset( $bp_nested_child_forum_ids[ $cache_key ] ) ) {
				// SQL query for getting all nested child forum id from parent forum id.
				$sql = "SELECT ID
				FROM  ( SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ( 'publish', 'private', 'hidden' ) ) forum_sorted,
					  ( SELECT @pv := %d, @pvv := %d ) initialisation
				WHERE FIND_IN_SET( post_parent, @pvv )
				AND   LENGTH( @pvv := CONCAT(@pv, ',', ID ) )";

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$child_forum_ids = $wpdb->get_col( $wpdb->prepare( $sql, bbp_get_forum_post_type(), $forum_id, $forum_id ) );

				$bp_nested_child_forum_ids[ $cache_key ] = $child_forum_ids;
			} else {
				$child_forum_ids = $bp_nested_child_forum_ids[ $cache_key ];
			}

			return $child_forum_ids;
		}

	}

	// End class Bp_Search_bbPress

endif;

