<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_bbPress_Replies' ) ) :

	/**
	 * BuddyPress Global Search  - search bbpress forums replies class
	 */
	class Bp_Search_bbPress_Replies extends Bp_Search_bbPress {
		public $type = 'reply';

		function sql( $search_term, $only_totalrow_count = false ) {
			global $wpdb;

			$bp_prefix = bp_core_get_table_prefix();

			$query_placeholder = array();

			if ( $only_totalrow_count ) {
				$columns = ' COUNT( DISTINCT p.id ) ';
			} else {
				$columns             = " DISTINCT p.id , '{$this->type}' as type, p.post_title LIKE %s AS relevance, p.post_date as entry_date  ";
				$query_placeholder[] = '%' . $search_term . '%';
			}

			$from = "{$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_bbp_forum_id'";

			/**
			 * Filter the MySQL JOIN clause for the forum's reply Search query.
			 *
             * @since BuddyBoss 1.5.6
			 *
			 * @param string $join_sql JOIN clause.
			 */
			$from = apply_filters( 'bp_forum_reply_search_join_sql', $from );

			// Initialize forum access variables.
			$excluded_forum_ids                  = array();
			$enrolled_hidden_group_forum_ids_str = '';

			if ( bp_is_active( 'groups' ) && ! current_user_can( 'administrator' ) ) {
				// Get user accessible groups using shared method from parent class.
				$groups_data        = $this->get_user_accessible_groups();
				$excluded_group_ids = $groups_data['excluded_group_ids'];

				// Get forums from excluded groups.
				if ( ! empty( $excluded_group_ids ) ) {
					$excluded_forum_args = array(
						'fields'      => 'ids',
						'post_status' => array( 'publish', 'private', 'hidden' ),
						'post_type'   => bbp_get_forum_post_type(),
						'numberposts' => -1,
						'meta_query'  => array(
							'relation' => 'OR',
						),
					);

					// Add meta queries for each excluded group.
					foreach ( $excluded_group_ids as $group_id ) {
						$excluded_forum_args['meta_query'][] = array(
							'key'     => '_bbp_group_ids',
							'value'   => maybe_serialize( array( $group_id ) ),
							'compare' => '=',
						);
					}

					$excluded_forum_ids = get_posts( $excluded_forum_args );
				}

				// Get hidden groups.
				$hidden_groups = groups_get_groups(
					array(
						'fields'      => 'ids',
						'status'      => array( 'hidden' ),
						'show_hidden' => true,
						'user_id'     => get_current_user_id(),
						'per_page'    => - 1,
					)
				);

				$user_hidden_group_ids = $hidden_groups['groups'];

				// Get hidden group forums where the user is enrolled.
				if ( ! empty( $user_hidden_group_ids ) ) {
					// Get forums that are associated with hidden groups where the user is enrolled.

					$included_forum_args = array(
						'fields'      => 'ids',
						'post_status' => array( 'hidden' ),
						'post_type'   => bbp_get_forum_post_type(),
						'numberposts' => -1,
						'meta_query'  => array(
							'relation' => 'OR',
						),
					);

					foreach ( $user_hidden_group_ids as $group_id ) {
						$included_forum_args['meta_query'][] = array(
							'key'     => '_bbp_group_ids',
							'value'   => maybe_serialize( array( $group_id ) ),
							'compare' => '=',
						);
					}

					$included_hidden_group_forum_ids = get_posts( $included_forum_args );
					$included_hidden_group_forum_ids = array_map( 'intval', $included_hidden_group_forum_ids );

					$enrolled_hidden_group_forum_ids = array();

					// Get child forum ids for enrolled hidden group forums.
					if ( ! empty( $included_hidden_group_forum_ids ) && method_exists( $this, 'nested_child_forum_ids' ) ) {
						foreach ( $included_hidden_group_forum_ids as $forum_id ) {
							$single_forum_child_ids          = $this->nested_child_forum_ids( $forum_id );
							$enrolled_hidden_group_forum_ids = array_merge( $enrolled_hidden_group_forum_ids, $single_forum_child_ids );
						}
					}

					$enrolled_hidden_group_forum_ids_str = implode( ',', array_unique( array_merge( $enrolled_hidden_group_forum_ids, $included_hidden_group_forum_ids ) ) );
				}
			}

			if ( current_user_can( 'read_hidden_forums' ) ) {
				$post_status = array( 'publish', 'private', 'hidden' );
			} elseif ( current_user_can( 'read_private_forums' ) ) {
				$post_status = array( 'publish', 'private' );
			} else {
				$post_status = array( 'publish' );
			}

			$where   = array();
			$where[] = '1=1';

			$search_term_array  = bb_search_get_search_keywords_by_term( $search_term, $this->type );
			$every_word_clauses = array();
			foreach ( $search_term_array as $term ) {
				$every_word_clauses[] = "(post_title LIKE %s OR post_content LIKE %s OR ExtractValue(post_content, '//text()') LIKE %s)";
				$query_placeholder[]  = '%' . $term . '%';
				$query_placeholder[]  = '%' . $term . '%';
				$query_placeholder[]  = '%' . $term . '%';
			}
			$where[] = implode( ' AND ', $every_word_clauses );

			$where[] = "post_type = '{$this->type}'";

			// Build the forum restriction clause.
			if ( ! empty( $excluded_forum_ids ) ) {

				// Exclude replies from restricted forums.
				$excluded_forum_ids = array_map( 'intval', $excluded_forum_ids );

				$excluded_child_forum_ids = array();

				// Get child forum ids where parent forum are restricted.
				if ( method_exists( $this, 'nested_child_forum_ids' ) ) {
					foreach ( $excluded_forum_ids as $forum_id ) {
						$single_forum_child_ids   = $this->nested_child_forum_ids( $forum_id );
						$excluded_child_forum_ids = array_merge( $excluded_child_forum_ids, $single_forum_child_ids );
					}
				}

				$excluded_forum_ids = array_unique( array_merge( $excluded_forum_ids, $excluded_child_forum_ids ) );

				// Remove enrolled hidden group forums from exclusion list since user should have access to them.
				if ( ! empty( $included_hidden_group_forum_ids ) ) {
					$excluded_forum_ids = array_diff( $excluded_forum_ids, $included_hidden_group_forum_ids );
				}

				$excluded_forum_ids_str = implode( ',', $excluded_forum_ids );

				$where[] = "pm.meta_value NOT IN ( $excluded_forum_ids_str )";
			}

			// Only show replies from forums with allowed post status.
			$post_status_str = "'" . implode( "','", $post_status ) . "'";

			$post_status_where = "post_status IN ( $post_status_str )";

			if ( ! empty( $enrolled_hidden_group_forum_ids_str ) ) {
				$post_status_where .= " OR ID IN ( $enrolled_hidden_group_forum_ids_str )";
			}

			$where[] = "pm.meta_value IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'forum' AND ( $post_status_where ) )";

			/**
			 * Filters the MySQL WHERE conditions for the forum's reply Search query.
			 *
             * @since BuddyBoss 1.5.6
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 */
			$where = apply_filters( 'bp_forum_reply_search_where_sql', $where );

			$sql   = 'SELECT ' . $columns . ' FROM ' . $from . ' WHERE ' . implode( ' AND ', $where );
			$query = $wpdb->prepare( $sql, $query_placeholder ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			return apply_filters(
				'Bp_Search_Forums_sql',
				$query,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @return object Bp_Search_Forums
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication.
			static $instance = null;

			// Only run these methods if they haven't been run previously.
			if ( null === $instance ) {
				$instance = new Bp_Search_bbPress_Replies();
			}

			// Always return the instance.
			return $instance;
		}

		/**
		 * A dummy constructor to prevent this class from being loaded more than once.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		private function __construct() {
			/* Do nothing here */
		}
	}

	// End class Bp_Search_bbPress_Replies.

endif;

