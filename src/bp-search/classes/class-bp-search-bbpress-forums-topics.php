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
			global $wpdb;

			$bp_prefix = bp_core_get_table_prefix();

			$query_placeholder = array();

			if ( $only_totalrow_count ) {
				$columns = ' COUNT( DISTINCT id ) ';
			} else {
				$columns             = " DISTINCT id , '{$this->type}' as type, post_title LIKE %s AS relevance, post_date as entry_date  ";
				$query_placeholder[] = '%' . $search_term . '%';
			}

			$from = "{$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_bbp_forum_id'";

			$tax = array();

			if ( bp_is_search_post_type_taxonomy_enable( bbpress()->topic_tag_tax_id, $this->type ) ) {
				$tax[] = bbpress()->topic_tag_tax_id;
			}

			$where_clause = ' WHERE ';

			$tax_sql = '';
			// Tax query left join
			if ( ! empty( $tax ) ) {
				$tax_sql = " LEFT JOIN {$wpdb->term_relationships} r ON p.ID = r.object_id ";
			}

			// Tax query
			if ( ! empty( $tax ) ) {

				$tax_in_arr = array_map( function ( $t_name ) {
					return "'" . $t_name . "'";
				},
					$tax );

				$tax_in = implode( ', ', $tax_in_arr );

				$tax_sql             .= " WHERE r.term_taxonomy_id IN (SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->terms} t ON 
					  t.term_id = tt.term_id WHERE ( t.slug LIKE %s OR t.name LIKE %s ) AND  tt.taxonomy IN ({$tax_in}) )";
				$query_placeholder[] = '%' . $search_term . '%';
				$query_placeholder[] = '%' . $search_term . '%';
				$where_clause        = ' OR ';
			}

			$group_memberships = '';
			if ( bp_is_active( 'groups' ) ) {
				$group_memberships = bp_get_user_groups( get_current_user_id(),
					array(
						'is_admin' => null,
						'is_mod'   => null,
					) );
			}

			$group_query = '';
			if ( ! empty( $group_memberships ) ) {
				$in = array_reduce( array_keys( $group_memberships ),
					function ( $carry, $group_id ) {
						return $carry . ',\'' . maybe_serialize( array( $group_id ) ) . '\'';
					} );

				$group_query = ' pm.meta_value IN ( SELECT post_id FROM ' . $wpdb->postmeta . ' WHERE meta_key = \'_bbp_group_ids\' AND meta_value IN(' . trim( $in,
						',' ) . ') ) OR ';
			}

			if ( current_user_can( 'read_hidden_forums' ) ) {
				$post_status = array( "'publish'", "'private'", "'hidden'" );
			} elseif ( current_user_can( 'read_private_forums' ) ) {
				$post_status = array( "'publish'", "'private'" );
			} else {
				$post_status = array( "'publish'" );
			}

			$where   = array();
			$where[] = '1=1';
			$where[] = "(post_title LIKE %s OR ExtractValue(post_content, '//text()') LIKE %s)";
			$where[] = "post_type = '{$this->type}'";

			$where[] = '(' . $group_query . '
			pm.meta_value IN ( SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type = \'forum\' AND post_status IN (' . join( ',',
					$post_status ) . ') )
			)';

			$query_placeholder[] = '%' . $search_term . '%';
			$query_placeholder[] = '%' . $search_term . '%';


			$sql = 'SELECT ' . $columns . ' FROM ' . $from . $tax_sql . $where_clause . implode( ' AND ', $where );

			$query = $wpdb->prepare( $sql, $query_placeholder );

			return apply_filters( 'Bp_Search_Forums_sql',
				$query,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				) );
		}

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @return object Bp_Search_Forums
		 * @since BuddyBoss 1.0.0
		 *
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication
			static $instance = null;

			// Only run these methods if they haven't been run previously
			if ( null === $instance ) {
				$instance = new Bp_Search_bbPress_Topics();
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

