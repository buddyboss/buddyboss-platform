<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Activities' ) ) :

	/**
	 * BuddyPress Global Search  - search activities class
	 */
	class Bp_Search_Activities extends Bp_Search_Type {
		private $type = 'activity';

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @return object Bp_Search_Activities
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication.
			static $instance = null;

			// Only run these methods if they haven't been run previously.
			if ( null === $instance ) {
				$instance = new Bp_Search_Activities();
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

		function sql( $search_term, $only_totalrow_count = false ) {

			/**
			 * SELECT DISTINCT a.id
			 * FROM wp_bp_activity a
			 * WHERE
			 *      a.is_spam = 0
			 *  AND a.content LIKE '%nothing%'
			 *  AND a.hide_sitewide = 0
			 *  AND a.type NOT IN ('activity_comment', 'last_activity')
			 *
			 * ORDER BY a.date_recorded DESC LIMIT 0, 21
			 */
			global $wpdb, $bp;

			$bp_prefix   = bp_core_get_table_prefix();
			$search_term = htmlspecialchars( $search_term, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );

			$query_placeholder = array();

			$user_groups = array();
			if ( bp_is_active( 'groups' ) ) {

				// Fetch public groups.
				$public_groups = groups_get_groups(
					array(
						'fields'   => 'ids',
						'status'   => 'public',
						'per_page' => - 1,
					)
				);
				if ( ! empty( $public_groups['groups'] ) ) {
					$public_groups = $public_groups['groups'];
				} else {
					$public_groups = array();
				}

				$groups = groups_get_user_groups( bp_loggedin_user_id() );
				if ( ! empty( $groups['groups'] ) ) {
					$user_groups = $groups['groups'];
				} else {
					$user_groups = array();
				}

				$user_groups = array_unique( array_merge( $user_groups, $public_groups ) );
			}

			$friends = array();
			if ( bp_is_active( 'friends' ) ) {

				// Determine friends of user.
				$friends = friends_get_friend_user_ids( bp_loggedin_user_id() );
				if ( empty( $friends ) ) {
					$friends = array( 0 );
				}
				array_push( $friends, bp_loggedin_user_id() );
			}

			$sql['select'] = 'SELECT';

			if ( $only_totalrow_count ) {
				$sql['select'] .= ' COUNT( DISTINCT a.id ) ';
			} else {
				$sql['select'] .= $wpdb->prepare( " DISTINCT a.id , 'activity' as type, a.content LIKE %s AS relevance, a.date_recorded as entry_date  ", '%' . $wpdb->esc_like( $search_term ) . '%' );
			}

			$privacy = array( 'public' );
			if ( is_user_logged_in() ) {
				$privacy[] = 'loggedin';
			}

			$sql['from'] = "FROM {$bp->activity->table_name} a";

			/**
			 * Filter the MySQL JOIN clause for the activity Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param string $join_sql JOIN clause.
			 */
			$sql['from'] = apply_filters( 'bp_activity_search_join_sql', $sql['from'] );

			// searching only activity updates, others don't make sense.
			$where_conditions   = array( '1=1' );
			$where_conditions[] = "is_spam = 0
				AND ExtractValue(a.content, '//text()') LIKE %s
				AND a.type = 'activity_update'
				AND
				(
					( a.privacy IN ( '" . implode( "','", $privacy ) . "' ) AND a.component != 'groups' AND a.hide_sitewide = 0 ) " .
					( isset( $user_groups ) && ! empty( $user_groups ) ? " OR ( a.item_id IN ( '" . implode( "','", $user_groups ) . "' ) AND a.component = 'groups' AND a.privacy = 'public' )" : '' ) .
					( bp_is_active( 'friends' ) && ! empty( $friends ) ? " OR ( a.user_id IN ( '" . implode( "','", $friends ) . "' ) AND a.privacy = 'friends' AND a.hide_sitewide = 0 )" : '' ) .
					( is_user_logged_in() ? " OR ( a.user_id = '" . bp_loggedin_user_id() . "' AND a.privacy = 'onlyme' )" : '' ) .
				')';

			/**
			 * Filters the MySQL WHERE conditions for the activity Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 * @param string $search_term      Search Term.
			 */
			$where_conditions = apply_filters( 'bp_activity_search_where_conditions', $where_conditions, $search_term );

			// Join the where conditions together.
			$sql['where'] = 'WHERE ' . join( ' AND ', $where_conditions );

			$sql = "{$sql['select']} {$sql['from']} {$sql['where']}";

			$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			$sql                 = $wpdb->prepare( $sql, $query_placeholder );

			return apply_filters(
				'Bp_Search_Activities_sql',
				$sql,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		protected function generate_html( $template_type = '' ) {
			$post_ids_arr = array();
			foreach ( $this->search_results['items'] as $item_id => $item_html ) {
				$post_ids_arr[] = $item_id;
			}

			$post_ids = implode( ',', $post_ids_arr );

			do_action( 'bp_before_search_activity_html' );

			if ( bp_has_activities(
				array(
					'include'     => $post_ids,
					'per_page'    => count( $post_ids_arr ),
					'show_hidden' => true,
					'scope'       => false,
				)
			) ) {
				while ( bp_activities() ) {
					bp_the_activity();

					$result = array(
						'id'    => bp_get_activity_id(),
						'type'  => $this->type,
						'title' => $this->search_term,
						'html'  => bp_search_buffer_template_part( 'loop/activity', $template_type, false ),
					);

					$this->search_results['items'][ bp_get_activity_id() ] = $result;
				}
			}

			do_action( 'bp_after_search_activity_html' );
		}

	}

	// End class Bp_Search_Posts.

endif;

