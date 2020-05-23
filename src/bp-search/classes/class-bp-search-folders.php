<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.3.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Folders' ) ) :

	/**
	 * BuddyPress Global Search  - search folder class
	 */
	class Bp_Search_Folders extends Bp_Search_Type {
		private $type = 'folders';

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @return object Bp_Search_Folders
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication.
			static $instance = null;

			// Only run these methods if they haven't been run previously.
			if ( null === $instance ) {
				$instance = new Bp_Search_Folders();
			}

			// Always return the instance.
			return $instance;
		}

		/**
		 * A dummy constructor to prevent this class from being loaded more than once.
		 *
		 * @since BuddyBoss 1.3.6
		 */
		private function __construct() {
			/* Do nothing here */
		}

		public function sql( $search_term, $only_totalrow_count = false ) {

			global $wpdb, $bp;
			$query_placeholder = array();

			$sql = ' SELECT ';

			if ( $only_totalrow_count ) {
				$sql .= ' COUNT( DISTINCT f.id ) ';
			} else {
				$sql                .= " DISTINCT f.id, 'folders' as type, f.title LIKE %s AS relevance, f.date_created as entry_date  ";
				$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			}

			$sql                .= " FROM
						{$bp->document->table_name_folder} f
					WHERE
						1=1
						AND f.title LIKE %s
				";
			$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';

			$sql = $wpdb->prepare( $sql, $query_placeholder );

			return apply_filters(
				'bp_search_folders_sql',
				$sql,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		protected function generate_html( $template_type = '' ) {
			$folder_ids = array();
			foreach ( $this->search_results['items'] as $item_id => $item_html ) {
				$folder_ids[] = $item_id;
			}

			// now we have all the posts
			// lets do a documents loop
			$args = array(
				'include'      => implode(',',$folder_ids),
				'per_page'     => count( $folder_ids ),
				'search_terms' => false,
			);

			do_action( 'bp_before_search_folders_html' );

			if ( bp_has_folders( $args ) ) {

				while ( bp_folder() ) :
					bp_the_folder();

					$result = array(
						'id'    => bp_get_folder_id(),
						'type'  => $this->type,
						'title' => bp_get_folder_title(),
						'html'  => bp_search_buffer_template_part( 'loop/folder', $template_type, false ),
					);

					$this->search_results['items'][ bp_get_folder_id() ] = $result;
				endwhile;
			}

			do_action( 'bp_after_search_folders_html' );
		}
	}

	// End class Bp_Search_Folders

endif;

