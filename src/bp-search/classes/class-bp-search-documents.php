<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.3.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Documents' ) ) :

	/**
	 * BuddyPress Global Search  - search document class
	 */
	class Bp_Search_Documents extends Bp_Search_Type {
		private $type = 'documents';

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @return object Bp_Search_Documents
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication.
			static $instance = null;

			// Only run these methods if they haven't been run previously.
			if ( null === $instance ) {
				$instance = new Bp_Search_Documents();
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
				$sql .= ' COUNT( DISTINCT d.id ) ';
			} else {
				$sql                .= " DISTINCT d.id, 'documents' as type, d.title LIKE %s AS relevance, d.date_created as entry_date  ";
				$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			}

			$sql                .= " FROM
						{$bp->document->table_name_meta} dm1, {$bp->document->table_name_meta} dm2, {$bp->document->table_name} d
					WHERE
						1=1
						AND d.id = dm1.document_id
						AND d.id = dm2.document_id
						AND dm2.meta_key = 'file_name'
						AND dm1.meta_key = 'extension'
						AND d.title LIKE %s
				";
			$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';

			$sql = $wpdb->prepare( $sql, $query_placeholder );

			return apply_filters(
				'bp_search_documents_sql',
				$sql,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		protected function generate_html( $template_type = '' ) {
			$document_ids = array();
			foreach ( $this->search_results['items'] as $item_id => $item_html ) {
				$document_ids[] = $item_id;
			}

			// now we have all the posts
			// lets do a documents loop
			$args = array(
				'include'      => implode(',',$document_ids),
				'per_page'     => count( $document_ids ),
				'search_terms' => false,
			);

			do_action( 'bp_before_search_documents_html' );

			if ( bp_has_document( $args ) ) {

				while ( bp_document() ) :
					bp_the_document();

					$result = array(
						'id'    => bp_get_document_id(),
						'type'  => $this->type,
						'title' => bp_get_document_title(),
						'html'  => bp_search_buffer_template_part( 'loop/document', $template_type, false ),
					);

					$this->search_results['items'][ bp_get_document_id() ] = $result;
				endwhile;
			}

			do_action( 'bp_after_search_documents_html' );
		}
	}

	// End class Bp_Search_Documents

endif;

