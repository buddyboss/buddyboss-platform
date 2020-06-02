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

			$user_root_folder_ids = bp_document_get_user_root_folders( bp_loggedin_user_id() );
			$folder_ids           = array();
			if ( $user_root_folder_ids ) {
				foreach ( $user_root_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $folder_ids, $single_folder_ids );
					}
					array_push( $folder_ids, $single_folder );
				}
			}

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
				if ( $user_groups ) {
					foreach ( $user_groups as $single_group ) {
						$fetch_folder_ids = bp_document_get_group_root_folders( (int) $single_group );
						if ( $fetch_folder_ids ) {
							foreach ( $fetch_folder_ids as $single_folder ) {
								$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
								if ( $single_folder_ids ) {
									array_merge( $folder_ids, $single_folder_ids );
								}
								array_push( $folder_ids, $single_folder );
							}
						}
					}
				}
			}
			$folder_ids[] = 0;

			if ( bp_is_active( 'friends' ) ) {

				// Determine friends of user.
				$friends = friends_get_friend_user_ids( bp_loggedin_user_id() );
				if ( empty( $friends ) ) {
					$friends = array( 0 );
				}
				array_push( $friends, bp_loggedin_user_id() );

				$friend_folder_ids = array();
				if ( $friends ) {
					foreach ( $friends as $friend ) {
						$user_root_folder_ids = bp_document_get_user_root_folders( (int) $friend );
						if ( $user_root_folder_ids ) {
							foreach ( $user_root_folder_ids as $single_folder ) {
								$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
								if ( $single_folder_ids ) {
									array_merge( $friend_folder_ids, $single_folder_ids );
								}
								array_push( $friend_folder_ids, $single_folder );
							}
						}
					}
				}

				$friend_folder_ids[] = 0;

			}

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

				// Determine groups of user.
				$groups = groups_get_user_groups( bp_loggedin_user_id() );
				if ( ! empty( $groups['groups'] ) ) {
					$groups = $groups['groups'];
				} else {
					$groups = array();
				}

				$group_ids = false;
				if ( ! empty( $groups ) && ! empty( $public_groups ) ) {
					$group_ids = array( 'groups' => array_unique( array_merge( $groups, $public_groups ) ) );
				} elseif ( empty( $groups ) && ! empty( $public_groups ) ) {
					$group_ids = array( 'groups' => $public_groups );
				} elseif ( ! empty( $groups ) && empty( $public_groups ) ) {
					$group_ids = array( 'groups' => $groups );
				}

				if ( empty( $group_ids ) ) {
					$group_ids = array( 'groups' => 0 );
				}

				$group_folder_ids = array();
				$user_groups      = $group_ids['groups'];
				if ( $user_groups ) {
					foreach ( $user_groups as $single_group ) {
						$fetch_folder_ids = bp_document_get_group_root_folders( (int) $single_group );
						if ( $fetch_folder_ids ) {
							foreach ( $fetch_folder_ids as $single_folder ) {
								$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
								if ( $single_folder_ids ) {
									array_merge( $group_folder_ids, $single_folder_ids );
								}
								array_push( $group_folder_ids, $single_folder );
							}
						}
					}
				}
				$group_folder_ids[] = 0;
			}

			$user_root_folder_ids = bp_document_get_user_root_folders( bp_loggedin_user_id() );
			$user_folder_ids      = array();
			if ( $user_root_folder_ids ) {
				foreach ( $user_root_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $user_folder_ids, $single_folder_ids );
					}
					array_push( $user_folder_ids, $single_folder );
				}
			}
			$user_folder_ids[] = 0;

			$sql = ' SELECT ';

			if ( $only_totalrow_count ) {
				$sql .= ' COUNT( DISTINCT d.id ) ';
			} else {
				$sql                .= " DISTINCT d.id, 'documents' as type, d.title LIKE %s AS relevance, d.date_created as entry_date  ";
				$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			}

			$privacy = array( 'public' );
			if( is_user_logged_in() ) {
				$privacy[] = 'loggedin';
				$privacy[] = 'onlyme';
				if ( bp_is_active( 'friends' ) ) {
					$privacy[] = 'friends';
				}
			}

			$sql .= " FROM
						{$bp->document->table_name} d, {$bp->document->table_name_meta} dm
						WHERE
							( d.id = dm.document_id )
							AND
                  (
									   ( 
									   d.title LIKE %s 
									   OR dm.meta_key = 'extension' 
									   AND dm.meta_value LIKE %s 
									   OR dm.meta_key = 'file_name' 
									   AND dm.meta_value LIKE %s 
									   ) AND 
									   ( 
											( d.folder_id IN ( " . implode( ',', $folder_ids ) . " ) AND d.privacy IN ( '" . implode( "','", $privacy ) . "' ) ) " .
			                                ( isset( $group_ids['groups'] ) && ! empty( $group_ids['groups'] ) ? ' OR ( d.group_id IN ( \'' . implode( "','", $group_ids['groups'] ) . '\' ) AND d.privacy IN (\'grouponly\') )' : '' ) .
			                            ")
							    ) ";

			if ( bp_is_active( 'friends' ) || bp_is_active( 'groups' ) ) {
				$sql .= ' OR (
							            ( ';
			}
			if ( bp_is_active( 'friends' ) ) {
				$sql .= ' ( (
								                        d.user_id IN ( ' . implode( ',', $friends ) . " ) AND d.privacy = 'friends' AND d.folder_id IN ( " . implode( ',', $friend_folder_ids ) . '
								                    ) AND ( d.title LIKE %s )
								                  )
								                ) ';
			}

			if ( bp_is_active( 'groups' ) ) {
				$sql .= ' OR
								                (
								                    (
								                        d.group_id IN ( ' . implode( ',', $group_ids['groups'] ) . " ) AND d.privacy = 'grouponly' AND d.folder_id IN ( " . implode( ',', $group_folder_ids ) . ' ) AND d.title LIKE %s
								                    )
								                ) ';
			}

			$sql .= ' OR (
                                      (
                                          d.user_id = ' . bp_loggedin_user_id() . " AND d.privacy = 'onlyme' AND d.folder_id IN ( " . implode( ',', $user_folder_ids ) . ' ) AND d.title LIKE %s
                                      )
                                  )';
			if ( bp_is_active( 'friends' ) || bp_is_active( 'groups' ) ) {
				$sql .= ' )
		                 )';
			}
			$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			if ( bp_is_active( 'friends' ) ) {
				$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			}
			if ( bp_is_active( 'groups' ) ) {
				$query_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			}
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

