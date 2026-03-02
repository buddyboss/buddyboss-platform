<?php
/**
 * Export API: BP_Group_Export class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Group_Export
 */
final class BP_Group_Export extends BP_Export {

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Group_Export();
			$instance->setup( 'bp_groups', __( 'Groups', 'buddyboss' ) );
		}

		return $instance;
	}

	/**
	 * Export member created groups.
	 *
	 * @param $user
	 * @param $page
	 * @param bool $email_address
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function process_data( $user, $page, $email_address = false ) {

		if ( ! $user || is_wp_error( $user ) ) {
			return $this->response( array(), true );
		}

		$export_items = array();

		$data_items = $this->get_data( $user, $page );

		foreach ( $data_items['items'] as $item ) {

			$group_id    = 'bp_groups';
			$group_label = __( 'Groups', 'buddyboss' );
			$item_id     = "{$this->exporter_name}-{$group_id}-{$item->id}";
			$avatar      = false;
			$cover_photo = false;

			if ( function_exists( 'bp_core_fetch_avatar' ) ) {
				$avatar = bp_core_fetch_avatar(
					array(
						'item_id' => $item->id,
						'object'  => 'group',
						'type'    => 'full',
						'html'    => false,
					)
				);
			}

			if ( function_exists( 'bp_attachments_get_attachment' ) ) {
				$cover_photo = bp_attachments_get_attachment(
					'url',
					array(
						'item_id'    => $item->id,
						'type'       => 'cover-image',
						'object_dir' => 'groups',
					)
				);
			}

			if ( empty( $avatar ) || ! $avatar ) {
				$avatar = __( 'N/A', 'buddyboss' );
			}
			if ( empty( $cover_photo ) || ! $cover_photo ) {
				$cover_photo = __( 'N/A', 'buddyboss' );
			}

			$group_permalink = bp_get_group_permalink( $item );

			$data = array(
				array(
					'name'  => __( 'Group Name', 'buddyboss' ),
					'value' => $item->name,
				),
				array(
					'name'  => __( 'Group Description', 'buddyboss' ),
					'value' => $item->description,
				),
				array(
					'name'  => __( 'Group slug', 'buddyboss' ),
					'value' => $item->slug,
				),
				array(
					'name'  => __( 'Created Date (GMT)', 'buddyboss' ),
					'value' => $item->date_created,
				),
				array(
					'name'  => __( 'Group Status', 'buddyboss' ),
					'value' => ucfirst( $item->status ),
				),
				array(
					'name'  => __( 'Group Avatar', 'buddyboss' ),
					'value' => $avatar,
				),
				array(
					'name'  => __( 'Group Cover Photo', 'buddyboss' ),
					'value' => $cover_photo,
				),
				array(
					'name'  => __( 'Group URL', 'buddyboss' ),
					'value' => $group_permalink,
				),
			);

			$metas2export                       = array();
			$metas2export['total_member_count'] = __( 'Total Members', 'buddyboss' );
			$metas2export['last_activity']      = __( 'Last Activity', 'buddyboss' );

			/**
			 * Filter allow to add additional metas without issues.
			 */
			$metas2export = apply_filters( 'buddyboss_bp_gdpr_group_export_metas', $metas2export, $data_items );

			/**
			 * Process the metas.
			 */

			foreach ( $item->metas as $meta ) {
				if ( isset( $metas2export[ $meta->meta_key ] ) ) {
					$value  = $this->easy_readable( $meta->meta_value ); // converting it to user friendly.
					$value  = apply_filters( 'buddyboss_bp_gdpr_group_meta_value_format', $value, $meta );
					$data[] = array(
						'name'  => $metas2export[ $meta->meta_key ],
						'value' => $value,
					);
				}
			}

			$data = apply_filters( 'buddyboss_bp_gdpr_group_after_data_prepare', $data, $item, $data_items );

			$export_items[] = array(
				'group_id'    => $group_id,
				'group_label' => $group_label,
				'item_id'     => $item_id,
				'data'        => $data,
			);

		}

		$done = $data_items['total'] < $data_items['offset'];

		return $this->response( $export_items, $done );
	}

	/**
	 * Delete member created groups.
	 *
	 * @param $user
	 * @param $page
	 * @param bool $email_address
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function process_erase( $user, $page, $email_address ) {

		global $wpdb, $bp;

		if ( ! $user || is_wp_error( $user ) ) {
			return $this->response_erase( array(), true );
		}

		$number         = $this->items_per_batch;
		$page           = (int) $page;
		$items_removed  = true;
		$items_retained = false;

		add_action( 'groups_delete_group', array( $this, 'additional_group_items_delete' ) );

		// Remove Group Data for User.
		groups_remove_data_for_user( $user->ID );

		$group_table = $bp->groups->table_name_members;

		// Delete Invites.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$group_table} WHERE inviter_id=%d", $user->ID ) );

		$done = true;

		return $this->response_erase( $items_removed, $done, array(), $items_retained );
	}

	/**
	 * Delete group avatar and cover photo.
	 *
	 * @param $group_id
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function additional_group_items_delete( $group_id ) {

		/**
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'buddyboss_bp_gdpr_group_additional_group_items_delete', $group_id );

		// delete cover photo
		bp_attachments_delete_file(
			array(
				'item_id'    => $group_id,
				'object_dir' => 'groups',
				'type'       => 'cover-image',
			)
		);

		// delete group avatar
		bp_core_delete_existing_avatar(
			array(
				'item_id' => $group_id,
				'object'  => 'group',
			)
		);

	}

	/**
	 * Get data & count of messages by page and user.
	 *
	 * @param $user
	 * @param $page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function get_data( $user, $page ) {
		global $wpdb, $bp;

		$wpdb->show_errors( false );
		$group_table         = $bp->groups->global_tables['table_name'];
		$group_members_table = $bp->groups->table_name_members;

		$table = "{$group_table} item, {$group_members_table} item2";

		$query_select       = '*';
		$query_select_count = 'COUNT(item.id)';
		$query_where        = 'item2.user_id=%d AND item2.group_id=item.id AND item2.is_admin=1';

		$offset = ( $page - 1 ) * $this->items_per_batch;
		$limit  = "LIMIT {$this->items_per_batch} OFFSET {$offset}";

		$query = "SELECT {$query_select} FROM {$table} WHERE {$query_where} {$limit}";
		$query = $wpdb->prepare( $query, $user->ID );

		$query_count = "SELECT {$query_select_count} FROM {$table} WHERE {$query_where}";
		$query_count = $wpdb->prepare( $query_count, $user->ID );

		$count = (int) $wpdb->get_var( $query_count );
		$items = $wpdb->get_results( $query );
		// Merge the metas.
		$items = $this->merge_metas( $items );

		return array(
			'total'  => $count,
			'offset' => $offset,
			'items'  => $items,
		);

	}

	/**
	 * Fetch all metas and merge into items.
	 *
	 * @param $items
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function merge_metas( $items ) {
		global $wpdb, $bp;

		$group_meta_table = $bp->groups->global_tables['table_name_groupmeta'];

		// get all ids
		$group_ids = array();

		foreach ( $items as $item ) {
			$group_ids[] = $item->id;
		}

		if ( empty( $group_ids ) ) {
			return array();
		}

		$ids_in = array_fill( 0, count( $group_ids ), '%s' );
		$ids_in = join( ',', $ids_in );

		$query = $wpdb->prepare( "SELECT *FROM {$group_meta_table} WHERE group_id in ({$ids_in})", $group_ids );

		$results = $wpdb->get_results( $query );

		$metas_by_group = array();

		foreach ( $results as $result ) {
			$metas_by_group[ $result->group_id ][] = $result;
		}

		// merge into items
		foreach ( $items as $item_key => $item ) {
			$items[ $item_key ]->metas = ( isset( $metas_by_group[ $item->id ] ) ) ? $metas_by_group[ $item->id ] : array();
		}

		return $items;
	}

}
