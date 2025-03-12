<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_bbPress_Forums' ) ) :

	/**
	 * BuddyPress Global Search  - search bbpress forums class
	 */
	class Bp_Search_bbPress_Forums extends Bp_Search_bbPress {
		public $type = 'forum';

		function sql( $search_term, $only_totalrow_count = false ) {
			global $wpdb;

			$query_placeholder = array();

			if ( $only_totalrow_count ) {
				$columns = ' COUNT( DISTINCT p.id ) ';
			} else {
				$columns             = " DISTINCT p.id , '{$this->type}' as type, p.post_title LIKE %s AS relevance, p.post_date as entry_date  ";
				$query_placeholder[] = '%' . $search_term . '%';
			}

			$from = "{$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_bbp_group_ids'";

			/**
			 * Filter the MySQL JOIN clause for the forum Search query.
			 *
             * @since BuddyBoss 1.5.6
			 *
			 * @param string $join_sql JOIN clause.
			 */
			$from = apply_filters( 'bp_forums_search_join_sql', $from );

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

			if ( current_user_can( 'read_hidden_forums' ) ) {
				$post_status = array( 'publish', 'private', 'hidden' );
			} elseif ( current_user_can( 'read_private_forums' ) ) {
				$post_status = array( 'publish', 'private' );
			} else {
				$post_status = array( 'publish' );
			}

			// Create the post_status SQL condition once.
			$post_status_sql = "post_status IN ('" . join( "','", $post_status ) . "')";

			// Different logic for group-enabled sites with non-admin users.
			if ( bp_is_active( 'groups' ) && ! current_user_can( 'administrator' ) ) {
				$bp      = buddypress();
				$user_id = get_current_user_id();

				// For forums not associated with groups, apply post_status filter
				$where_sql = '( ( pm.meta_value IS NULL AND ' . $post_status_sql . ' )';

				// For forums associated with groups, check group membership.
				$group_query = "SELECT DISTINCT CONCAT('a:1:{i:0;i:', g.id, ';}')
					FROM {$bp->groups->table_name} g
					LEFT JOIN {$bp->groups->table_name_members} m 
						ON g.id = m.group_id 
						AND m.user_id = {$user_id} 
						AND m.is_confirmed = 1
					WHERE g.status = 'public' OR m.id IS NOT NULL";

				$where_sql .= " OR pm.meta_value IN ({$group_query}) )";
			} else {
				// For admins or sites without groups, just use post_status.
				$where_sql = '( ' . $post_status_sql . ' )';
			}

			$where[] = $where_sql;

			/**
			 * Filters the MySQL WHERE conditions for the forum Search query.
			 *
             * @since BuddyBoss 1.5.6
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 */
			$where = apply_filters( 'bp_forums_search_where_sql', $where );

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
			// Store the instance locally to avoid private static replication
			static $instance = null;

			// Only run these methods if they haven't been run previously
			if ( null === $instance ) {
				$instance = new Bp_Search_bbPress_Forums();
			}

			// Always return the instance
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

	// End class Bp_Search_Posts

endif;

