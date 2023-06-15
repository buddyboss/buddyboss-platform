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

			$group_memberships = '';
			$membership_in     = array();

			if ( bp_is_active( 'groups' ) ) {
				$group_memberships = bp_get_user_groups(
					get_current_user_id(),
					array(
						'is_admin' => null,
						'is_mod'   => null,
					)
				);

				$group_memberships = wp_list_pluck( $group_memberships, 'group_id' );

				$public_groups = groups_get_groups(
					array(
						'fields'   => 'ids',
						'status'   => 'public',
						'per_page' => - 1,
					)
				);

				if ( ! empty( $public_groups ) && ! empty( $public_groups['groups'] ) ) {
					$public_groups = $public_groups['groups'];
				} else {
					$public_groups = array();
				}

				$group_memberships = array_merge( $public_groups, $group_memberships );
				$group_memberships = array_unique( $group_memberships );
			}

			if ( ! empty( $group_memberships ) ) {
				$membership_in = array_map(
					function ( $group_id ) {
						return maybe_serialize( array( $group_id ) );
					},
					$group_memberships
				);
			}

			// Get all private group forum ids where current user is not enrolled.
			$group_forum_args = array(
				'fields'      => 'ids',
				'post_status' => $post_status,
				'post_type'   => bbp_get_forum_post_type(),
				'numberposts' => '-1',
				'meta_query'  => array(
					array(
						'key'     => '_bbp_group_ids',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_bbp_group_ids',
						'value'   => 'a:0:{}',
						'compare' => '!=',
					),
					array(
						'key'     => '_bbp_group_ids',
						'value'   => $membership_in,
						'compare' => 'NOT IN',
					),
				),
			);

			$group_forum_ids_cache_key = 'bbp_search_group_forum_ids_' . md5( maybe_serialize( $group_forum_args ) );
			if ( ! isset( $bbp_search_group_forum_ids[ $group_forum_ids_cache_key ] ) ) {
				$group_forum_ids = get_posts( $group_forum_args );

				$bbp_search_group_forum_ids[ $group_forum_ids_cache_key ] = $group_forum_ids;
			} else {
				$group_forum_ids = $bbp_search_group_forum_ids[ $group_forum_ids_cache_key ];
			}

			$group_forum_child_ids = array();

			// Get child forum ids where parent forum are associated with private group.
			foreach ( $group_forum_ids as $forum_id ) {
				$single_forum_child_ids = $this->nested_child_forum_ids( $forum_id );
				$group_forum_child_ids  = array_merge( $group_forum_child_ids, $single_forum_child_ids );
			}

			// Merge all forum ids and its child forum ids.
			$group_forum_ids = array_merge( $group_forum_ids, $group_forum_child_ids );

			// Get group associated forum ids. Where current user is not connected to those groups.
			$forum_args = array(
				'fields'       => 'ids',
				'post_status'  => $post_status,
				'post_type'    => bbp_get_forum_post_type(),
				'numberposts'  => '-1',
				'post__not_in' => $group_forum_ids
			);

			$forum_ids_cache_key = 'bbp_search_forum_ids_' . md5( maybe_serialize( $forum_args ) );
			if ( ! isset( $bbp_search_forum_ids[ $forum_ids_cache_key ] ) ) {
				$forum_ids = get_posts( $forum_args );

				$bbp_search_forum_ids[ $forum_ids_cache_key ] = $forum_ids;
			} else {
				$forum_ids = $bbp_search_forum_ids[ $forum_ids_cache_key ];
			}

			$where   = array();
			$where[] = '1=1';
			$where[] = "(post_title LIKE %s OR ExtractValue(post_content, '//text()') LIKE %s)";
			$where[] = "post_type = '{$this->type}'";

			if ( ! empty( $forum_ids ) ) {
				$forum_id_in = implode( ',', $forum_ids );
				$where[]     = " p.post_parent IN ( $forum_id_in ) ";
			}

			$query_placeholder[] = '%' . $search_term . '%';
			$query_placeholder[] = '%' . $search_term . '%';

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
		 * Get all nested child forum ids.
		 *
		 * @since BuddyBoss 1.6.3
		 *
		 * @uses bbp_get_forum_post_type() Get forum post type.
		 *
		 * @param int $forum_id
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

			$child_forum_ids = $wpdb->get_col( $wpdb->prepare( $sql, bbp_get_forum_post_type(), $forum_id, $forum_id ) );

				$bp_nested_child_forum_ids[ $cache_key ] = $child_forum_ids;
			} else {
				$child_forum_ids = $bp_nested_child_forum_ids[ $cache_key ];
			}

			return $child_forum_ids;
		}

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @return object Bp_Search_Forums
		 * @since BuddyBoss 1.0.0
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
