<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Media_WP_User_Export_GDPR' ) ) {

	class Media_WP_User_Export_GDPR {

		/**
		 * Constructor method.
		 */
		function __construct( $args = array() ) {

			add_filter(
				'wp_privacy_personal_data_exporters',
				array( $this, 'register_media_exporter' ),
				10
			);

			add_filter(
				'wp_privacy_personal_data_erasers',
				array( $this, 'erase_media_exporter' ),
				10
			);

		}

		function register_media_exporter( $exporters ) {
			if ( function_exists( 'buddypress' ) ) {
				$exporters['buddyboss-media'] = array(
					'exporter_friendly_name' => __( 'BuddyBoss Media', 'buddyboss-media' ),
					'callback'               => array( $this, 'albums_exporter' ),
				);
			}
			return $exporters;
		}

		function erase_media_exporter( $erasers ) {
			if ( function_exists( 'buddypress' ) ) {
				$erasers['buddyboss-media-albums'] = array(
					'eraser_friendly_name' => __( 'BuddyBoss Media', 'buddyboss-media' ),
					'callback'             => array( $this, 'albums_eraser' ),
				);
				$erasers['buddyboss-media-photos'] = array(
					'eraser_friendly_name' => __( 'BuddyBoss Media Photos', 'buddyboss-media' ),
					'callback'             => array( $this, 'photos_eraser' ),
				);
			}
			return $erasers;
		}

		function albums_exporter( $email_address, $page = 1 ) {
			$per_page = 50; // Limit us to avoid timing out
			$page = (int) $page;

			$export_items = array();

			$user = get_user_by( 'email' , $email_address );
			if ( false === $user ) {
				return array(
					'data' => $export_items,
					'done' => true,
				);
			}

			$albums_items = $this->get_albums( $email_address, $page, $per_page );

			if ( ! $albums_items ) {
				return array(
					'data' => $export_items,
					'done' => true,
				);
			}

			$total_albums	 = isset( $albums_items['total'] ) ? $albums_items['total'] : 0;
			$paged_albums	 = ! empty( $albums_items['albums'] ) ? $albums_items['albums'] : array();

			if ( $total_albums ) {

				foreach ( (array) $paged_albums as $album ) {

					$item_id = "album-{$album->id}";

					$group_id = 'albums';

					$group_label = __( 'Albums', 'buddyboss-media' );

					if ( $album->group_id ) {
						$group      = groups_get_group( array( 'group_id' => $album->group_id ) );
						$group_link = bp_get_group_permalink( $group );
						$permalink  = trailingslashit( $group_link . buddyboss_media_component_slug() . '/albums/' . $album->id . '/' );
					} else {
						$user_id   = $user->ID;
						$permalink = bp_core_get_user_domain( $user_id ) . buddyboss_media_component_slug() . '/albums/' . $album->id . '/';
					}

					// Plugins can add as many items in the item data array as they want
					$data = array(
						array(
							'name'  => __( 'Album Author', 'buddyboss-media' ),
							'value' => $user->display_name
						),
						array(
							'name'  => __( 'Album Author Email', 'buddyboss-media' ),
							'value' => $user->user_email
						),
						array(
							'name'  => __( 'Album Title', 'buddyboss-media' ),
							'value' => $album->title
						),
						array(
							'name'  => __( 'Album Description', 'buddyboss-media' ),
							'value' => $album->description
						),
						array(
							'name'  => __( 'Album Date', 'buddyboss-media' ),
							'value' => $album->date_created
						),
						array(
							'name'  => __( 'Album Privacy', 'buddyboss-media' ),
							'value' => $album->privacy
						),
						array(
							'name'  => __( 'Album URL', 'buddyboss-media' ),
							'value' => $permalink
						),
					);

					$export_items[] = array(
						'group_id'    => $group_id,
						'group_label' => $group_label,
						'item_id'     => $item_id,
						'data'        => $data,
					);

				}

			}

			$offset = ( $page - 1 ) * $per_page;

			// Tell core if we have more comments to work on still
			$done = $total_albums < $offset;
			return array(
				'data' => $export_items,
				'done' => $done,
			);
		}

		function albums_eraser( $email_address, $page = 1 ) {
			$per_page = 50; // Limit us to avoid timing out
			$page = (int) $page;

			$user = get_user_by( 'email' , $email_address );
			if ( false === $user ) {
				return array(
					'items_removed'  => false,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => true,
				);
			}

			$items_removed  = false;
			$items_retained = false;
			$messages    = array();

			$albums_items = $this->get_albums( $email_address, 1, $per_page );

			if ( ! $albums_items ) {
				return array(
					'items_removed'  => false,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => true,
				);
			}

			$total_albums	 = isset( $albums_items['total'] ) ? $albums_items['total'] : 0;
			$paged_albums	 = ! empty( $albums_items['albums'] ) ? $albums_items['albums'] : array();

			if ( $total_albums ) {
				foreach ( (array) $paged_albums as $album ) {
					$photos_items = $this->get_photos( $email_address, $page, -1, $album->id );
					$paged_photos	 = ! empty( $photos_items['photos'] ) ? $photos_items['photos'] : array();
					$photos_to_delete = array();
					$activities_to_delete = array();
					foreach ( (array) $paged_photos as $photo ) {
						wp_delete_post( $photo->media_id, true );
						$photos_to_delete[] = $photo->id;
						$activities_to_delete[] = $photo->activity_id;
					}
					$this->delete_activities( $activities_to_delete );
					$this->delete_photos( $photos_to_delete );
					$this->delete_album( $album->id );
					$items_removed = true;
				}
			}

			$offset = ( $page - 1 ) * $per_page;

			// Tell core if we have more comments to work on still
			$done = $total_albums < $offset;

			return array(
				'items_removed'  => $items_removed,
				'items_retained' => $items_retained,
				'messages'       => $messages,
				'done'           => $done,
			);
		}

		function photos_eraser( $email_address, $page = 1 ) {
			$per_page = 50; // Limit us to avoid timing out
			$page = (int) $page;

			$user = get_user_by( 'email' , $email_address );
			if ( false === $user ) {
				return array(
					'items_removed'  => false,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => true,
				);
			}

			$items_removed  = false;
			$items_retained = false;
			$messages    = array();

			$photos_items = $this->get_photos( $email_address, 1, $per_page );

			if ( ! $photos_items ) {
				return array(
					'items_removed'  => false,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => true,
				);
			}

			$total_photos	 = isset( $photos_items['total'] ) ? $photos_items['total'] : 0;
			$paged_photos	 = ! empty( $photos_items['photos'] ) ? $photos_items['photos'] : array();

			if ( $total_photos ) {
				$photos_to_delete = array();
				$activities_to_delete = array();
				foreach ( (array) $paged_photos as $photo ) {
					wp_delete_post( $photo->media_id );
					$photos_to_delete[] = $photo->id;
					$activities_to_delete[] = $photo->activity_id;
				}
				$this->delete_activities( $activities_to_delete );
				$this->delete_photos( $photos_to_delete );
				$items_removed = true;
			}

			$offset = ( $page - 1 ) * $per_page;

			// Tell core if we have more comments to work on still
			$done = $total_photos < $offset;

			return array(
				'items_removed'  => $items_removed,
				'items_retained' => $items_retained,
				'messages'       => $messages,
				'done'           => $done,
			);
		}

		function get_albums( $email_address, $page = 1, $per_page = 50 ) {
			global $wpdb;

			$user = get_user_by( 'email' , $email_address );
			if ( false === $user ) {
				return false;
			}

			$columns_all	 = 'a.*';
			$columns_count	 = 'COUNT(*)';

			$TABLES = "{$wpdb->prefix}buddyboss_media_albums a";


			$offset = ( $page - 1 ) * $per_page;
			$LIMIT		 = "LIMIT {$per_page} OFFSET {$offset}";

			$sql_results = "SELECT {$columns_all} FROM {$TABLES} WHERE a.user_id=%d {$LIMIT}";
			$sql_count	 = "SELECT {$columns_count} FROM {$TABLES} WHERE a.user_id=%d";

			$sql_results   = $wpdb->prepare( $sql_results, $user->ID );
			$sql_count     = $wpdb->prepare( $sql_count, $user->ID );

			$total_albums	 = $wpdb->get_var( $sql_count );
			$paged_albums	 = $wpdb->get_results( $sql_results );

			return array( 'albums' => $paged_albums, 'total' => $total_albums );
		}

		function delete_album( $album_id ) {
			global $wpdb, $bp;

			//delete record from albums table
			$wpdb->delete(
				$wpdb->prefix . 'buddyboss_media_albums',
				array(
					'id'	=> $album_id,
				),
				array( '%d' )
			);

			//delete records from activity meta
			$wpdb->delete(
				$bp->activity->table_name_meta,
				array(
					'meta_key'		=> 'buddyboss_media_album_id',
					'meta_value'	=> $album_id
				),
				array( '%s', '%d' )
			);

			return true;
		}

		function get_photos( $email_address, $page, $per_page = 50, $album_id = '' ) {
			global $wpdb;

			$user = get_user_by( 'email' , $email_address );
			if ( false === $user ) {
				return false;
			}

			$columns_all	 = 'a.*';
			$columns_count	 = 'COUNT(*)';

			$TABLES = "{$wpdb->prefix}buddyboss_media a";

			$where = array();
			$query_placeholders = array();
			$where[] = 'a.media_author=%d';
			$query_placeholders[]	 = $user->ID;

			if ( ! empty( $album_id ) ) {
				$where[]				 = 'a.album_id=%d';
				$query_placeholders[]	 = $album_id;
			}

			$where = implode( ' AND ', $where );

			if ( $per_page > 0 ) {
				$offset = ( $page - 1 ) * $per_page;
				$LIMIT  = "LIMIT {$per_page} OFFSET {$offset}";
			}

			$sql_results = "SELECT {$columns_all} FROM {$TABLES} WHERE {$where} {$LIMIT}";
			$sql_count	 = "SELECT {$columns_count} FROM {$TABLES} WHERE {$where}";

			$sql_results   = $wpdb->prepare( $sql_results, $query_placeholders );
			$sql_count     = $wpdb->prepare( $sql_count, $query_placeholders );

			$total_photos	 = $wpdb->get_var( $sql_count );
			$paged_photos	 = $wpdb->get_results( $sql_results );

			return array( 'photos' => $paged_photos, 'total' => $total_photos );
		}

		function delete_photos( $ids = array() ) {
			global $wpdb;

			$ids = implode( ',', array_map( 'absint', $ids ) );
			$delete = $wpdb->query( "DELETE FROM {$wpdb->prefix}buddyboss_media WHERE ID IN($ids)" );

			if ( ! $delete ) {
				return false;
			}
			return true;
		}

		function delete_activities( $ids = array() ) {
			$ids = array_unique( $ids );
			foreach ( $ids as $id ) {
				bp_activity_delete( array( 'id' => $id ) );
			}
		}

	}

	new Media_WP_User_Export_GDPR();

}