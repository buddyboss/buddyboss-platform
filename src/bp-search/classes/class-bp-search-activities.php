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
			// Store the instance locally to avoid private static replication
			static $instance = null;

			// Only run these methods if they haven't been run previously
			if ( null === $instance ) {
				$instance = new Bp_Search_Activities();
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

			$bp_prefix = bp_core_get_table_prefix();

			$query_placeholder = array();

			$sql = ' SELECT ';

			if ( $only_totalrow_count ) {
				$sql .= ' COUNT( DISTINCT id ) ';
			} else {
				$sql                .= " DISTINCT a.id , 'activity' as type, a.content LIKE %s AS relevance, a.date_recorded as entry_date  ";
				$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			}

			// searching only activity updates, others don't make sense
			$sql                .= " FROM 
						{$bp->activity->table_name} a 
					WHERE 
						1=1 
						AND is_spam = 0 
						AND ExtractValue(a.content, '//text()') LIKE %s 
						AND a.hide_sitewide = 0 
						AND a.type = 'activity_update' 
				";
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
					'include'  => $post_ids,
					'per_page' => count( $post_ids_arr ),
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

	// End class Bp_Search_Posts

endif;

