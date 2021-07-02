<?php
/**
 * BuddyBoss Media Search Class
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Media' ) ) :

	/**
	 * BuddyPress Global Search  - search media class
	 */
	class Bp_Search_Media extends Bp_Search_Type {

		/**
		 * Search item type name
		 *
		 * @var string
		 */
		private $type = 'photos';

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
				$instance = new Bp_Search_Media();
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
		 * Prepare SQL query for media search.
		 *
		 * @param string $search_term         Search terms.
		 * @param false  $only_totalrow_count Total row count.
		 *
		 * @return mixed|void
		 *
		 * @since BuddyBoss 1.4.0
		 */
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
				$sql['select'] .= ' COUNT( DISTINCT m.id ) ';
			} else {
				$sql['select'] .= $wpdb->prepare( " DISTINCT m.id, 'photos' as type, m.title LIKE %s AS relevance, m.date_created as entry_date  ", '%' . $wpdb->esc_like( $search_term ) . '%' );
			}

			$sql['from'] = " FROM {$bp->media->table_name} m";

			/**
			 * Filter the MySQL JOIN clause for the media Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param string $join_sql JOIN clause.
			 */
			$sql['from'] = apply_filters( 'bp_media_search_join_sql_photo', $sql['from'] );

			$privacy = array( 'public' );
			if ( is_user_logged_in() ) {
				$privacy[] = 'loggedin';
			}

			$where_conditions   = array( '1=1' );
			$where_conditions[] = $wpdb->prepare(
				" (
					(
						m.title LIKE %s
					)
					AND
					(
							( m.type='photo' AND m.privacy IN ( '" . implode( "','", $privacy ) . "' ) ) " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
							( isset( $user_groups ) && ! empty( $user_groups ) ? " OR ( m.type='photo' AND m.group_id IN ( '" . implode( "','", $user_groups ) . "' ) AND m.privacy = 'grouponly' )" : '' ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
							( bp_is_active( 'friends' ) && ! empty( $friends ) ? " OR ( m.type='photo' AND m.user_id IN ( '" . implode( "','", $friends ) . "' ) AND m.privacy = 'friends' )" : '' ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
							( is_user_logged_in() ? " OR ( m.type='photo' AND m.user_id = '" . bp_loggedin_user_id() . "' AND m.privacy = 'onlyme' )" : '' ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					')
				)',
				'%' . $wpdb->esc_like( $search_term ) . '%'
			);

			/**
			 * Filters the MySQL WHERE conditions for the media Search query.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 * @param string $search_term      Search Term.
			 */
			$where_conditions = apply_filters( 'bp_media_search_where_conditions_photo', $where_conditions, $search_term );

			// Join the where conditions together.
			$sql['where'] = 'WHERE ' . join( ' AND ', $where_conditions );

			$sql = "{$sql['select']} {$sql['from']} {$sql['where']}";

			return apply_filters(
				'bp_search_photos_sql',
				$sql,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		/**
		 * Generare Html for media search
		 *
		 * @param string $template_type Template type.
		 */
		protected function generate_html( $template_type = '' ) {
			$media_ids = array();
			foreach ( $this->search_results['items'] as $item_id => $item_html ) {
				$media_ids[] = $item_id;
			}

			// now we have all the posts.
			// lets do a media loop.
			$args = array(
				'include'      => implode( ',', $media_ids ),
				'per_page'     => count( $media_ids ),
				'search_terms' => false,
			);

			do_action( 'bp_before_search_photos_html' );

			if ( bp_has_media( $args ) ) {

				while ( bp_media() ) :
					bp_the_media();

					$result = array(
						'id'    => bp_get_media_id(),
						'type'  => $this->type,
						'title' => bp_get_media_title(),
						'html'  => bp_search_buffer_template_part( 'loop/photos', $template_type, false ),
					);

					$this->search_results['items'][ bp_get_media_id() ] = $result;
				endwhile;
			}

			do_action( 'bp_after_search_photos_html' );
		}
	}

	// End class Bp_Search_Media.

endif;

