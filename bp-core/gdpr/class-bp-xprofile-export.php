<?php
/**
 * Export API: BP_Xprofile_Export class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Xprofile_Export
 *
 * @since BuddyBoss 1.0.0
 */
final class BP_Xprofile_Export extends BP_Export {

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Xprofile_Export();
			$instance->setup( 'bp_xprofile', __( 'User Profile Information', 'buddyboss' ) );
		}

		return $instance;
	}

	/**
	 * Export member extended profile fields.
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

		/**
		 * Cover Photo & Avatar
		 */
		if ( function_exists( 'bp_core_fetch_avatar' ) ) {
			$avatar = bp_core_fetch_avatar(
				array(
					'item_id' => $user->ID,
					'object'  => 'user',
					'type'    => 'full',
					'html'    => false,
				)
			);
		}

		if ( function_exists( 'bp_attachments_get_attachment' ) ) {
			$cover_photo = bp_attachments_get_attachment(
				'url',
				array(
					'item_id'    => $user->ID,
					'type'       => 'cover-image',
					'object_dir' => 'members',
				)
			);
		}

		if ( empty( $avatar ) || ! $avatar ) {
			$avatar = __( 'N/A', 'buddyboss' );
		}
		if ( empty( $cover_photo ) || ! $cover_photo ) {
			$cover_photo = __( 'N/A', 'buddyboss' );
		}

		$data[] = array(
			'name'  => __( 'Avatar', 'buddyboss' ),
			'value' => $avatar,
		);
		$data[] = array(
			'name'  => __( 'Cover Photo', 'buddyboss' ),
			'value' => $cover_photo,
		);

		$export_items[] = array(
			'group_id'    => "{$this->exporter_name}-cover-avatar",
			'group_label' => __( 'User Profile - Avatar & Cover Photos', 'buddyboss' ),
			'item_id'     => "{$this->exporter_name}-assets-{$user->ID}",
			'data'        => $data,
		);

		/**
		 * Xprofile Fields
		 */

		$data_items = $this->get_data( $user, $page );

		foreach ( $data_items['items'] as $xgroup => $items ) {

			$group_id    = $xgroup;
			$group_label = __( "User Profile - {$xgroup}", 'buddyboss' );
			$item_id     = "{$this->exporter_name}-{$group_id}";

			$data = array();

			foreach ( $items as $item ) {
				$val = trim( $item['value'] );
				if ( empty( $val ) ) {
					$val = __( 'N/A', 'buddyboss' );
				}
				$data[] = array(
					'name'  => $item['name'],
					'value' => $val,
				);
			}

			$data = apply_filters( 'buddyboss_bp_gdpr_xprofile_after_data_prepare', $data, $items, $data_items );

			$export_items[] = array(
				'group_id'    => $group_id,
				'group_label' => $group_label,
				'item_id'     => $item_id,
				'data'        => $data,
			);

		}

		$done = true; // on this we are processing everything at once.

		return $this->response( $export_items, $done );
	}

	/**
	 * Delete member extended profile fields.
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

		global $wpdb;

		if ( ! $user || is_wp_error( $user ) ) {
			return $this->response_erase( array(), true );
		}

		$number         = $this->items_per_batch;
		$page           = (int) $page;
		$items_removed  = true;
		$items_retained = false;

		/**
		 * Make use of buddypress default data remover.
		 */
		xprofile_remove_data( $user->ID );

		// delete avatar
		bp_core_delete_avatar_on_user_delete( $user->ID );

		// delete cover photo
		bp_attachments_delete_file(
			array(
				'item_id' => $user->ID,
				'object'  => 'members',
				'type'    => 'cover-image',
			)
		);

		$done = true;

		return $this->response_erase( $items_removed, $done, array(), $items_retained );
	}

	/**
	 * Returns the data & count of activities by page and user.
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

		$items = array();

		$xprofile_groups = bp_xprofile_get_groups();

		$field_table = $bp->profile->global_tables['table_name_fields'];

		foreach ( $xprofile_groups as $xgroup ) {

			$fields = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT *FROM {$field_table} WHERE group_id=%d AND parent_id=0",
					$xgroup->id
				)
			);

			foreach ( $fields as $key => $field ) {
				$field          = (array) $field;
				$field['value'] = xprofile_get_field_data( $field['id'], $user->ID, 'comma' );
				$fields[ $key ] = $field;
			}
			$items[ $xgroup->name ] = $fields;
		}

		return array(
			'total'  => 1,
			'offset' => 0,
			'items'  => $items,
		);

	}

}
