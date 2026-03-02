<?php
/**
 * BuddyBoss Video Search Class
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_Video' ) ) :

	/**
	 * BuddyPress Global Search  - search video class
	 */
	class Bp_Search_Video extends Bp_Search_Type {

		/**
		 * Search item type name
		 *
		 * @var string
		 */
		private $type = 'videos';

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @return object Bp_Search_Video
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication.
			static $instance = null;

			// Only run these methods if they haven't been run previously.
			if ( null === $instance ) {
				$instance = new Bp_Search_Video();
			}

			// Always return the instance.
			return $instance;
		}

		/**
		 * A dummy constructor to prevent this class from being loaded more than once.
		 *
		 * @since BuddyBoss 1.7.0
		 */
		private function __construct() {
			/* Do nothing here */
		}

		/**
		 * Prepare SQL query for video search.
		 *
		 * @param string $search_term         Search terms.
		 * @param false  $only_totalrow_count Total row count.
		 *
		 * @return mixed|void
		 *
		 * @since BuddyBoss 1.7.0
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
				$sql['select'] .= $wpdb->prepare( " DISTINCT m.id, 'videos' as type, m.title LIKE %s AS relevance, m.date_created as entry_date  ", '%' . $wpdb->esc_like( $search_term ) . '%' );
			}

			$sql['from'] = " FROM {$bp->video->table_name} m";

			/**
			 * Filter the MySQL JOIN clause for the video Search query.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 * @param string $join_sql JOIN clause.
			 */
			$sql['from'] = apply_filters( 'bp_video_search_join_sql_video', $sql['from'] );

			$privacy = array( 'public' );
			if ( is_user_logged_in() ) {
				$privacy[] = 'loggedin';
			}

			$where_conditions   = array( '1=1' );
			$where_conditions[] = $wpdb->prepare(
				" (
					(
						m.title LIKE %s
						OR
						m.description LIKE %s
					)
					AND
					(
							( m.type = 'video' AND m.privacy IN ( '" . implode( "','", $privacy ) . "' ) ) " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
							( isset( $user_groups ) && ! empty( $user_groups ) ? " OR ( m.type = 'video' AND m.group_id IN ( '" . implode( "','", $user_groups ) . "' ) AND m.privacy = 'grouponly' )" : '' ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
							( bp_is_active( 'friends' ) && ! empty( $friends ) ? " OR ( m.type = 'video' AND m.user_id IN ( '" . implode( "','", $friends ) . "' ) AND m.privacy = 'friends' )" : '' ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
							( is_user_logged_in() ? " OR ( m.type = 'video' AND m.user_id = '" . bp_loggedin_user_id() . "' AND m.privacy = 'onlyme' )" : '' ) . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					')
				)',
				'%' . $wpdb->esc_like( $search_term ) . '%',
				'%' . $wpdb->esc_like( $search_term ) . '%'
			);

			/**
			 * Filters the MySQL WHERE conditions for the video Search query.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 * @param string $search_term      Search Term.
			 */
			$where_conditions = apply_filters( 'bp_video_search_where_conditions_video', $where_conditions, $search_term );

			// Join the where conditions together.
			$sql['where'] = 'WHERE ' . join( ' AND ', $where_conditions );

			$sql = "{$sql['select']} {$sql['from']} {$sql['where']}";

			return apply_filters(
				'bp_search_videos_sql',
				$sql,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		/**
		 * Generate Html for video search.
		 *
		 * @param string $template_type Template type.
		 *
		 * @since BuddyBoss 1.7.0
		 */
		protected function generate_html( $template_type = '' ) {
			$video_ids = array();
			foreach ( $this->search_results['items'] as $item_id => $item_html ) {
				$video_ids[] = $item_id;
			}

			// now we have all the posts.
			// lets do a video loop.
			$args = array(
				'include'      => implode( ',', $video_ids ),
				'per_page'     => count( $video_ids ),
				'search_terms' => false,
			);

			/**
			 * Fires before the search videos html.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 */
			do_action( 'bp_before_search_videos_html' );

			if ( bp_has_video( $args ) ) {

				while ( bp_video() ) :
					bp_the_video();

					$result = array(
						'id'    => bp_get_video_id(),
						'type'  => $this->type,
						'title' => bp_get_video_title(),
						'html'  => bp_search_buffer_template_part( 'loop/videos', $template_type, false ),
					);

					$this->search_results['items'][ bp_get_video_id() ] = $result;
				endwhile;
			}

			/**
			 * Fires after the search videos html.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 */
			do_action( 'bp_after_search_videos_html' );
		}
	}

	// End class Bp_Search_Video.

endif;

