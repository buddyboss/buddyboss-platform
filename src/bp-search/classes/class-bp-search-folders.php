<?php
/**
 * @package BuddyBoss\Search
 * @since   BuddyBoss 1.4.0
 * @todo    add description
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
		 * @since BuddyBoss 1.4.0
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
				$sql['select'] .= ' COUNT( DISTINCT f.id ) ';
			} else {
				$sql['select'] .= $wpdb->prepare( " DISTINCT f.id, 'folders' as type, f.title LIKE %s AS relevance, f.date_created as entry_date  ", '%' . $wpdb->esc_like( $search_term ) . '%' );
			}

			$privacy = array( 'public' );
			if ( is_user_logged_in() ) {
				$privacy[] = 'loggedin';
			}

			$sql['from'] = "FROM {$bp->document->table_name_folder} f";

			/**
			 * Filter the MySQL JOIN clause for the folder Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param string $join_sql JOIN clause.
			 */
			$sql['from'] = apply_filters( 'bp_document_search_join_sql_folder', $sql['from'] );

			$where_conditions   = array( '1=1' );
			$where_conditions[] = $wpdb->prepare(
				"(
                    f.title LIKE %s AND
                    (
                        f.privacy IN ( '" . implode( "','", $privacy ) . "' ) " .
				( isset( $user_groups ) && ! empty( $user_groups ) ? " OR ( f.group_id IN ( '" . implode( "','", $user_groups ) . "' ) AND f.privacy = 'grouponly' )" : '' ) .
				( bp_is_active( 'friends' ) && ! empty( $friends ) ? " OR ( f.user_id IN ( '" . implode( "','", $friends ) . "' ) AND f.privacy = 'friends' )" : '' ) .
				( is_user_logged_in() ? " OR ( f.user_id = '" . bp_loggedin_user_id() . "' AND f.privacy = 'onlyme' )" : '' ) .
				')
				)',
				'%' . $wpdb->esc_like( $search_term ) . '%'
			);

			/**
			 * Filters the MySQL WHERE conditions for the folder Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 * @param string $search_term      Search Term.
			 */
			$where_conditions = apply_filters( 'bp_document_search_where_conditions_folder', $where_conditions, $search_term );

			// Join the where conditions together.
			$sql['where'] = 'WHERE ' . join( ' AND ', $where_conditions );

			$sql = "{$sql['select']} {$sql['from']} {$sql['where']}";

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
				'include'      => implode( ',', $folder_ids ),
				'per_page'     => count( $folder_ids ),
				'search_terms' => false,
			);

			do_action( 'bp_before_search_folders_html' );

			if ( bp_has_folders( $args ) ) {

				while ( bp_folder() ) :
					bp_the_folder();

					$result = array(
						'id'    => bp_get_folder_folder_id(),
						'type'  => $this->type,
						'title' => bp_get_folder_title(),
						'html'  => bp_search_buffer_template_part( 'loop/folder', $template_type, false ),
					);

					$this->search_results['items'][ bp_get_folder_folder_id() ] = $result;
				endwhile;
			}

			do_action( 'bp_after_search_folders_html' );
		}
	}

	// End class Bp_Search_Folders

endif;

