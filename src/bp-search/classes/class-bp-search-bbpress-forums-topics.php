<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_bbPress_Topics' ) ) :

	/**
	 * BuddyPress Global Search  - search bbpress forums topics class
	 */
	class Bp_Search_bbPress_Topics extends Bp_Search_bbPress {
		public $type = 'topic';

		function sql( $search_term, $only_totalrow_count = false ) {
			static $bbp_search_forum_ids = array();
			static $bbp_search_group_forum_ids = array();
			global $wpdb;

			$bp_prefix = bp_core_get_table_prefix();

			$query_placeholder = array();

			if ( $only_totalrow_count ) {
				$columns = ' COUNT( DISTINCT p.id ) ';
			} else {
				$columns             = " DISTINCT p.id , '{$this->type}' as type, p.post_title LIKE %s AS relevance, p.post_date as entry_date  ";
				$query_placeholder[] = '%' . $search_term . '%';
			}

			$from = "{$wpdb->posts} p";

			/**
			 * Filter the MySQL JOIN clause for the topic Search query.
			 *
             * @since BuddyBoss 1.5.6
			 *
			 * @param string $join_sql JOIN clause.
			 */
			$from = apply_filters( 'bp_forum_topic_search_join_sql', $from );

			$tax = array();

			if ( bp_is_search_post_type_taxonomy_enable( bbpress()->topic_tag_tax_id, $this->type ) ) {
				$tax[] = bbpress()->topic_tag_tax_id;
			}

			$where_clause = ' WHERE ';

			$tax_sql = '';
			// Tax query left join.
			if ( ! empty( $tax ) ) {
				$tax_sql = " LEFT JOIN {$wpdb->term_relationships} r ON p.ID = r.object_id ";
			}

			// Tax query.
			if ( ! empty( $tax ) ) {

				$tax_in_arr = array_map(
					function ( $t_name ) {
						return "'" . $t_name . "'";
					},
					$tax
				);

				$tax_in = implode( ', ', $tax_in_arr );

				$tax_sql            .= " WHERE r.term_taxonomy_id IN (SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->terms} t ON
					  t.term_id = tt.term_id WHERE ( t.slug LIKE %s OR t.name LIKE %s ) AND  tt.taxonomy IN ({$tax_in}) )";
				$query_placeholder[] = '%' . $search_term . '%';
				$query_placeholder[] = '%' . $search_term . '%';
				$where_clause        = ' OR ';
			}

			if ( current_user_can( 'read_hidden_forums' ) ) {
				$post_status = array( 'publish', 'private', 'hidden' );
			} elseif ( current_user_can( 'read_private_forums' ) ) {
				$post_status = array( 'publish', 'private' );
			} else {
				$post_status = array( 'publish' );
			}

			// Initialize forum exclusion variables.
			$excluded_forum_ids = array();

			if ( bp_is_active( 'groups' ) && ! current_user_can( 'administrator' ) ) {
				// Get user accessible groups using shared method from parent class.
				$groups_data        = $this->get_user_accessible_groups();
				$excluded_group_ids = $groups_data['excluded_group_ids'];

				// Note: We're focusing on exclusion rather than inclusion
				// This ensures we don't accidentally exclude standalone forums.

				// Get forums from excluded groups (forums we need to exclude).
				if ( ! empty( $excluded_group_ids ) ) {
					$excluded_serialized = array_map(
						function ( $group_id ) {
							return maybe_serialize( array( $group_id ) );
						},
						$excluded_group_ids
					);

					$excluded_forum_args = array(
						'fields'      => 'ids',
						'post_status' => $post_status,
						'post_type'   => bbp_get_forum_post_type(),
						'numberposts' => -1,
						'meta_query'  => array(
							'relation' => 'AND',
							array(
								'key'     => '_bbp_group_ids',
								'value'   => $excluded_serialized,
								'compare' => 'IN',
							),
						),
					);
				} else {
					$excluded_forum_args = array();
				}
			} else {
				// Groups not active - no exclusions needed.
				$excluded_forum_args = array();
			}

			// Get excluded forum IDs.
			if ( ! empty( $excluded_forum_args ) ) {
				$excluded_forum_ids_cache_key = 'bbp_search_excluded_forum_ids_' . md5( maybe_serialize( $excluded_forum_args ) );
				if ( ! isset( $bbp_search_group_forum_ids[ $excluded_forum_ids_cache_key ] ) ) {
					$excluded_forum_ids = get_posts( $excluded_forum_args );
					$bbp_search_group_forum_ids[ $excluded_forum_ids_cache_key ] = $excluded_forum_ids;
				} else {
					$excluded_forum_ids = $bbp_search_group_forum_ids[ $excluded_forum_ids_cache_key ];
				}

				// Get child forum IDs for excluded forums.
				$excluded_child_forum_ids = array();
				foreach ( $excluded_forum_ids as $forum_id ) {
					$child_ids                = $this->nested_child_forum_ids( $forum_id );
					$excluded_child_forum_ids = array_merge( $excluded_child_forum_ids, $child_ids );
				}

				// Combine parent and child forum IDs to exclude.
				$excluded_forum_ids = array_merge( $excluded_forum_ids, $excluded_child_forum_ids );
				$excluded_forum_ids = array_unique( $excluded_forum_ids );
			}

			// Get all accessible forums (not in excluded list).
			$forum_args = array(
				'fields'      => 'ids',
				'post_status' => $post_status,
				'post_type'   => bbp_get_forum_post_type(),
				'numberposts' => -1,
			);

			// Add exclusion if we have forums to exclude.
			if ( ! empty( $excluded_forum_ids ) ) {
				$forum_args['post__not_in'] = $excluded_forum_ids;
			}

			$forum_ids_cache_key = 'bbp_search_forum_ids_' . md5( maybe_serialize( $forum_args ) );
			if ( ! isset( $bbp_search_forum_ids[ $forum_ids_cache_key ] ) ) {
				$forum_ids = get_posts( $forum_args );

				$bbp_search_forum_ids[ $forum_ids_cache_key ] = $forum_ids;
			} else {
				$forum_ids = $bbp_search_forum_ids[ $forum_ids_cache_key ];
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

			// Only show topics from accessible forums.
			if ( ! empty( $forum_ids ) ) {
				// Sanitize forum IDs to prevent SQL injection.
				$forum_ids   = array_map( 'intval', $forum_ids );
				$forum_id_in = implode( ',', $forum_ids );
				$where[]     = "p.post_parent IN ( $forum_id_in )";
				// No accessible forums - return no results.
			} elseif ( bp_is_active( 'groups' ) && ! empty( $excluded_forum_ids ) ) {
				// If we have excluded forums but no allowed forums, return empty.
				$where[] = '1=0';
			}

			/**
			 * Filters the MySQL WHERE conditions for the forum topic Search query.
			 *
             * @since BuddyBoss 1.5.6
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 */
			$where = apply_filters( 'bp_forum_topic_search_where_sql', $where );

			$sql = 'SELECT ' . $columns . ' FROM ' . $from . $tax_sql . $where_clause . implode( ' AND ', $where );

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
		 * @return object Bp_Search_Forums
		 * @since BuddyBoss 1.0.0
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication.
			static $instance = null;

			// Only run these methods if they haven't been run previously.
			if ( null === $instance ) {
				$instance = new Bp_Search_bbPress_Topics();
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

	// End class Bp_Search_bbPress_Topics.

endif;
