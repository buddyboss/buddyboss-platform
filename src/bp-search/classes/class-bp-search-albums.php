<?php
/**
 * BuddyBoss Albums Search Class
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Albums' ) ) :

	/**
	 * BuddyPress Global Search  - search albums class
	 */
	class Bp_Search_Albums extends Bp_Search_Type {

		/**
		 * Search item type name
		 *
		 * @var string
		 */
		private $type = 'albums';

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since BuddyBoss 1.5.0
		 *
		 * @return object Bp_Search_Media
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication.
			static $instance = null;

			// Only run these methods if they haven't been run previously.
			if ( null === $instance ) {
				$instance = new Bp_Search_Albums();
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

		/**
		 * Prepare SQL query for albums search.
		 *
		 * @param string $search_term Search terms.
		 * @param false  $only_totalrow_count Total row count.
		 *
		 * @return mixed|void
		 */
		public function sql( $search_term, $only_totalrow_count = false ) {

			global $wpdb, $bp;
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

			$sql = ' SELECT ';

			if ( $only_totalrow_count ) {
				$sql .= ' COUNT( DISTINCT ma.id ) ';
			} else {
				$sql .= $wpdb->prepare( " DISTINCT ma.id, 'albums' as type, ma.title LIKE %s AS relevance, ma.date_created as entry_date  ", '%' . $wpdb->esc_like( $search_term ) . '%' );
			}

			$sql .= " FROM {$bp->media->table_name_albums} ma WHERE";

			$privacy = array( 'public' );
			if ( is_user_logged_in() ) {
				$privacy[] = 'loggedin';
			}

			$sql .= $wpdb->prepare(
				" (
					(
						ma.title LIKE %s
					)
					AND
					(
							( ma.privacy IN ( '" . implode( "','", $privacy ) . "' ) ) " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
							( isset( $user_groups ) && ! empty( $user_groups ) ? " OR ( ma.group_id IN ( '" . implode( "','", $user_groups ) . "' ) AND ma.privacy = 'grouponly' )" : '' ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
							( bp_is_active( 'friends' ) && ! empty( $friends ) ? " OR ( ma.user_id IN ( '" . implode( "','", $friends ) . "' ) AND ma.privacy = 'friends' )" : '' ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
							( is_user_logged_in() ? " OR ( ma.user_id = '" . bp_loggedin_user_id() . "' AND ma.privacy = 'onlyme' )" : '' ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					')
				)',
				'%' . $wpdb->esc_like( $search_term ) . '%'
			);

			return apply_filters(
				'bp_search_albums_sql',
				$sql,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		/**
		 * Generare Html for albums search
		 *
		 * @param string $template_type Template type.
		 */
		protected function generate_html( $template_type = '' ) {
			$document_ids = array();
			foreach ( $this->search_results['items'] as $item_id => $item_html ) {
				$document_ids[] = $item_id;
			}

			// now we have all the posts.
			// lets do a albums loop.
			$args = array(
				'include'      => implode( ',', $document_ids ),
				'per_page'     => count( $document_ids ),
				'search_terms' => false,
			);

			do_action( 'bp_before_search_albums_html' );

			if ( bp_has_albums( $args ) ) {

				while ( bp_album() ) :
					bp_the_album();

					$result = array(
						'id'    => bp_get_album_id(),
						'type'  => $this->type,
						'title' => bp_get_album_title(),
						'html'  => bp_search_buffer_template_part( 'loop/albums', $template_type, false ),
					);

					$this->search_results['items'][ bp_get_album_id() ] = $result;
				endwhile;
			}

			do_action( 'bp_after_search_albums_html' );
		}
	}

	// End class Bp_Search_Albums.

endif;

