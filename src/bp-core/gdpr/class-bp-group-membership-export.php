<?php
/**
 * Export API: BP_Group_Membership_Export class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Group_Membership_Export
 */
final class BP_Group_Membership_Export extends BP_Export {

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Group_Membership_Export();
			$instance->setup( 'bp_group_memberships', __( 'Group Memberships', 'buddyboss' ) );
		}

		return $instance;
	}

	/**
	 * Export member group memberships.
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

			$group = groups_get_group( $item->group_id );

			$group_id    = 'bp_group_membership';
			$group_label = __( 'Group Membership', 'buddyboss' );
			$item_id     = "{$this->exporter_name}-{$group_id}-{$item->id}";

			$group_permalink = bp_get_group_permalink( $group );

			$membership_type = false;

			if ( $item->user_id === $user->ID && '0' === $item->is_confirmed && '0' === $item->inviter_id ) {
				$group_label     = __( 'Group Pending Requests', 'buddyboss' );
				$membership_type = 'pending_request';
			} elseif ( $item->user_id === $user->ID && '0' === $item->is_confirmed && '0' === $item->inviter_id ) {
				$group_label     = __( 'Group Pending Received Invitation Requests', 'buddyboss' );
				$membership_type = 'pending_received_invitation';
			} elseif ( $item->inviter_id === $user->ID && '0' === $item->is_confirmed ) {
				$group_label     = __( 'Group Pending Sent Invitation Requests', 'buddyboss' );
				$membership_type = 'pending_sent_invitation';
			} elseif ( $item->user_id === $user->ID && '1' === $item->is_confirmed ) {
				$group_label     = __( 'Group Membership', 'buddyboss' );
				$membership_type = 'membership';
			}

			$group_id .= "_{$membership_type}"; // force to create separate group for each type.

			$data = array(
				array(
					'name'  => __( 'Group Name', 'buddyboss' ),
					'value' => bp_get_group_name( $group ),
				),
				array(
					'name'  => __( 'Sent Date (GMT)', 'buddyboss' ),
					'value' => $item->date_modified,
				),
				array(
					'name'  => __( 'Group URL', 'buddyboss' ),
					'value' => $group_permalink,
				),
			);

			if ( 'pending_received_invitation' === $membership_type ) {
				$get_user = get_userdata( $item->inviter_id );
				$data[]   = array(
					'name'  => __( 'Sent by', 'buddyboss' ),
					'value' => $get_user->display_name,
				);
			}

			if ( 'pending_sent_invitation' === $membership_type ) {
				$get_user = get_userdata( $item->user_id );
				$data[]   = array(
					'name'  => __( 'Sent to', 'buddyboss' ),
					'value' => $get_user->display_name,
				);
			}

			if ( ! empty( $item->comments ) ) {
				$data[] = array(
					'name'  => __( 'Group Comments', 'buddyboss' ),
					'value' => $item->comments,
				);
			}

			$data = apply_filters(
				'buddyboss_bp_gdpr_group_membership_after_data_prepare',
				$data,
				$item,
				$data_items,
				$membership_type
			);

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
	 * Get data & count of groups by page and user.
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
		$group_table = $bp->groups->table_name_members;

		$table = "{$group_table} item";

		$query_select       = 'item.*';
		$query_select_count = 'COUNT(item.id)';
		$query_where        = 'item.user_id=%d OR item.inviter_id=%d';

		$offset = ( $page - 1 ) * $this->items_per_batch;
		$limit  = "LIMIT {$this->items_per_batch} OFFSET {$offset}";

		$query       = "SELECT {$query_select} FROM {$table} WHERE {$query_where} {$limit}";
		$query       = $wpdb->prepare( $query, $user->ID, $user->ID );
		$query_count = "SELECT {$query_select_count} FROM {$table} WHERE {$query_where}";
		$query_count = $wpdb->prepare( $query_count, $user->ID, $user->ID );

		$count = (int) $wpdb->get_var( $query_count );
		$items = $wpdb->get_results( $query );

		return array(
			'total'  => $count,
			'offset' => $offset,
			'items'  => $items,
		);

	}

}
