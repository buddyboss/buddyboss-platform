<?php
/**
 * @package BuddyBoss\Search
 * @since   BuddyBoss 1.4.0
 * @todo    add description
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
		 * @since BuddyBoss 1.4.0
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
		 * @since BuddyBoss 1.4.0
		 */
		private function __construct() {
			/* Do nothing here */
		}

		public function sql( $search_term, $only_totalrow_count = false ) {

			global $wpdb, $bp;

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

				$user_groups = array();
				if ( is_user_logged_in() ) {

					$groups = groups_get_user_groups( bp_loggedin_user_id() );
					if ( ! empty( $groups['groups'] ) ) {
						$user_groups = $groups['groups'];
					} else {
						$user_groups = array();
					}
				}

				$user_groups = array_unique( array_merge( $user_groups, $public_groups ) );
			}

			$friends = array();
			if ( bp_is_active( 'friends' ) && is_user_logged_in() ) {

				// Determine friends of user.
				$friends = friends_get_friend_user_ids( bp_loggedin_user_id() );
				if ( empty( $friends ) ) {
					$friends = array( 0 );
				}
				array_push( $friends, bp_loggedin_user_id() );
			}

			$sql['select'] = 'SELECT';

			if ( $only_totalrow_count ) {
				$sql['select'] .= ' COUNT( DISTINCT d.id ) ';
			} else {
				$sql['select'] .= $wpdb->prepare( " DISTINCT d.id, 'documents' as type, d.title LIKE %s AS relevance, d.date_created as entry_date  ", '%' . $wpdb->esc_like( $search_term ) . '%' );
			}

			$sql['from'] = "FROM {$bp->document->table_name} d INNER JOIN {$bp->document->table_name_meta} dm ON ( d.id = dm.document_id )";

			/**
			 * Filter the MySQL JOIN clause for the document Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param string $join_sql JOIN clause.
			 */
			$sql['from'] = apply_filters( 'bp_document_search_join_sql_document', $sql['from'] );

			$privacy = array( 'public' );
			if ( is_user_logged_in() ) {
				$privacy[] = 'loggedin';
			}

			$where_conditions   = array( '1=1' );
			$where_conditions[] = $wpdb->prepare(
				" (
					(
						d.title LIKE %s
						OR dm.meta_key = 'extension'
						AND dm.meta_value LIKE %s
						OR dm.meta_key = 'file_name'
						AND dm.meta_value LIKE %s
					)
					AND
					(
							( d.privacy IN ( '" . implode( "','", $privacy ) . "' ) ) " .
				( isset( $user_groups ) && ! empty( $user_groups ) ? " OR ( d.group_id IN ( '" . implode( "','", $user_groups ) . "' ) AND d.privacy = 'grouponly' )" : '' ) .
				( bp_is_active( 'friends' ) && ! empty( $friends ) ? " OR ( d.user_id IN ( '" . implode( "','", $friends ) . "' ) AND d.privacy = 'friends' )" : '' ) .
				( is_user_logged_in() ? " OR ( d.user_id = '" . bp_loggedin_user_id() . "' AND d.privacy = 'onlyme' )" : '' ) .
				')
				)',
				'%' . $wpdb->esc_like( $search_term ) . '%',
				'%' . $wpdb->esc_like( $search_term ) . '%',
				'%' . $wpdb->esc_like( $search_term ) . '%'
			);

			/**
			 * Filters the MySQL WHERE conditions for the document Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 * @param string $search_term      Search Term.
			 */
			$where_conditions = apply_filters( 'bp_document_search_where_conditions_document', $where_conditions, $search_term );

			// Join the where conditions together.
			$sql['where'] = 'WHERE ' . join( ' AND ', $where_conditions );

			$sql = "{$sql['select']} {$sql['from']} {$sql['where']}";

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
				'include'      => implode( ',', $document_ids ),
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

	// End class Bp_Search_Documents.

endif;

