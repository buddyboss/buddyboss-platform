<?php
/**
 * Export API: BP_Activity_Export class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Activity_Export
 *
 * @since BuddyBoss 1.0.0
 */
final class BP_Activity_Export extends BP_Export {


	/**
	 * BP_Activity_Export instance.
	 *
	 * @return BP_Activity_Export|null
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Activity_Export();
			$instance->setup( 'bp_activity', __( 'Activities', 'buddyboss' ) );
		}

		return $instance;
	}

	/**
	 * Export all the user activity.
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

			$group_id    = 'bp_activities';
			$group_label = __( 'Activities & Comments', 'buddyboss' );
			$item_id     = "{$this->exporter_name}-{$group_id}-{$item->id}";

			$activity_type = __( 'Profile Update', 'buddyboss' );

			if ( 'groups' === $item->component ) {
				$activity_type = __( 'Group Update', 'buddyboss' );
			} elseif ( 'activity_comment' === $item->type ) {
				$activity_type = __( 'Comment', 'buddyboss' );

			}

			$activity_type = apply_filters( 'buddyboss_bp_gdpr_bp_activity_item_activity_type', $activity_type, $item, $data_items );

			$permalink = bp_activity_get_permalink( $item->id );

			$data = array(
				array(
					'name'  => __( 'Activity Action', 'buddyboss' ),
					'value' => wp_strip_all_tags( $item->action ),
				),
				array(
					'name'  => __( 'Activity Content', 'buddyboss' ),
					'value' => $item->content,
				),
				array(
					'name'  => __( 'Created Date (GMT)', 'buddyboss' ),
					'value' => $item->date_recorded,
				),
				array(
					'name'  => __( 'Activity Type', 'buddyboss' ),
					'value' => $activity_type,
				),
				array(
					'name'  => __( 'Activity URL', 'buddyboss' ),
					'value' => $permalink,
				),
			);

			$data = apply_filters( 'buddyboss_bp_gdpr_activity_after_data_prepare', $data, $item, $data_items );

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
	 * To erase user data.
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

		$table          = "{$wpdb->base_prefix}bp_activity";
		$number         = $this->items_per_batch;
		$page           = (int) $page;
		$items_removed  = true;
		$items_retained = false;

		/**
		 * Make use of buddypress default data remover.
		 */
		bp_activity_remove_all_user_data( $user->ID );

		$done = true;

		return $this->response_erase( $items_removed, $done, array(), $items_retained );
	}

	/**
	 * Returns the data & count of activities by page and user.
	 *
	 * @param $user
	 * @param $page
	 * @param string $mode
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function get_data( $user, $page, $mode = 'erase' ) {
		global $wpdb;
		global $bp;

		$wpdb->show_errors( false );

		$table              = bp_core_get_table_prefix() . 'bp_activity item';
		$query_select       = 'item.*';
		$query_select_count = 'COUNT(item.id)';

		$query_where = "item.user_id=%d AND item.type IN ('activity_update','activity_comment') && is_spam=0";

		$offset = ( $page - 1 ) * $this->items_per_batch;
		$limit  = "LIMIT {$this->items_per_batch} OFFSET {$offset}";

		$query       = "SELECT {$query_select} FROM {$table} WHERE {$query_where} {$limit}";
		$query       = $wpdb->prepare( $query, $user->ID );
		$query_count = "SELECT {$query_select_count} FROM {$table} WHERE {$query_where}";
		$query_count = $wpdb->prepare( $query_count, $user->ID );

		$count = (int) $wpdb->get_var( $query_count );
		$items = $wpdb->get_results( $query );

		return array(
			'total'  => $count,
			'offset' => $offset,
			'items'  => $items,
		);

	}

}
