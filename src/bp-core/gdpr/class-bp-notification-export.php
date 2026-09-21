<?php
/**
 * Export API: BP_Notification_Export class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Notification_Export
 */
final class BP_Notification_Export extends BP_Export {

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Notification_Export();
			$instance->setup( 'bp_notification', __( 'Notifications', 'buddyboss' ) );
		}

		return $instance;
	}

	/**
	 * The member this export is being produced for, while a batch is being rendered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	private $bb_export_viewer_id = 0;

	/**
	 * Resolve member names for the data subject while their export is rendered.
	 *
	 * Filters the viewer bp_core_get_user_displayname() falls back to when no viewer is passed.
	 * Only name resolution is re-pointed - capabilities and the logged-in user are untouched - so a
	 * notification the subject received reads with the names the subject is allowed to see, not the
	 * names the administrator running the export can see.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $viewer_id The viewer BuddyBoss resolved for this request.
	 * @return int The data subject while a batch is rendering, otherwise the value unchanged.
	 */
	public function bb_filter_export_viewer_id( $viewer_id ) {
		return $this->bb_export_viewer_id ? (int) $this->bb_export_viewer_id : $viewer_id;
	}

	/**
	 * Export member notifications.
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

		// A notification's text is built by the component that created it, and those callbacks
		// resolve the actor's name for whoever is browsing - here the administrator running the
		// export. Point name resolution at the data subject for the duration of the batch, so a
		// surname they may not see does not reach a report produced on their behalf.
		$this->bb_export_viewer_id = (int) $user->ID;
		add_filter( 'bb_core_get_viewer_user_id', array( $this, 'bb_filter_export_viewer_id' ) );

		try {
			$export_items = $this->bb_prepare_export_items( $data_items );
		} finally {
			remove_filter( 'bb_core_get_viewer_user_id', array( $this, 'bb_filter_export_viewer_id' ) );
			$this->bb_export_viewer_id = 0;
		}

		$done = $data_items['total'] < $data_items['offset'];

		return $this->response( $export_items, $done );
	}

	/**
	 * Build the export rows for one batch of notifications.
	 *
	 * Split out of process_data() so the viewer scoping around it cannot be bypassed by an early
	 * return added inside the loop later.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $data_items The batch returned by get_data().
	 * @return array Export items for this batch.
	 */
	private function bb_prepare_export_items( $data_items ) {

		$export_items = array();

		foreach ( $data_items['items'] as $item ) {

			$group_id    = 'bp_notifications';
			$group_label = __( 'Notifications', 'buddyboss' );
			$item_id     = "{$this->exporter_name}-{$group_id}-{$item->id}";

			$notification = bp_notifications_get_notification( $item->id );

			$action = $this->bp_get_the_notification_description( $notification );
			$action = wp_strip_all_tags( $action );
			$action = apply_filters( 'buddyboss_bp_gdpr_bp_notification_item_action', $action, $item, $data_items );

			$mark_as_read = __( 'No', 'buddyboss' );
			if ( '0' === $item->is_new ) {
				$mark_as_read = __( 'Yes', 'buddyboss' );
			}

			$data = array(
				array(
					'name'  => __( 'Notification Action', 'buddyboss' ),
					'value' => $action,
				),
				array(
					'name'  => __( 'Notified Date (GMT)', 'buddyboss' ),
					'value' => $item->date_notified,
				),
				array(
					'name'  => __( 'Mark as Read', 'buddyboss' ),
					'value' => $mark_as_read,
				),
			);

			$data = apply_filters( 'buddyboss_bp_gdpr_notification_after_data_prepare', $data, $item, $notification, $data_items );

			$export_items[] = array(
				'group_id'    => $group_id,
				'group_label' => $group_label,
				'item_id'     => $item_id,
				'data'        => $data,
			);

		}

		return $export_items;
	}

	/**
	 * Delete member notifications.
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

		$items_removed  = true;
		$items_retained = false;

		/**
		 * Make use of buddypress default data remover.
		 */
		bp_notifications_delete_notifications_on_user_delete( $user->ID );

		$done = true;

		return $this->response_erase( $items_removed, $done, array(), $items_retained );
	}

	/**
	 * Returns the data & count of notifications by page and user.
	 *
	 * @param $user
	 * @param $page
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function get_data( $user, $page ) {
		global $wpdb;
		global $bp;

		$wpdb->show_errors( false );

		$table              = bp_core_get_table_prefix() . 'bp_notifications item';
		$query_select       = 'item.*';
		$query_select_count = 'COUNT(item.id)';
		$query_where        = 'item.user_id=%d';

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

	/**
	 * Get full-text description for a specific notification.
	 *
	 * @param $notification
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return mixed|void
	 */
	function bp_get_the_notification_description( $notification ) {
		$bp = buddypress();

		// Callback function exists.
		if ( isset( $bp->{$notification->component_name}->notification_callback ) && is_callable( $bp->{$notification->component_name}->notification_callback ) ) {
			$description = call_user_func(
				$bp->{$notification->component_name}->notification_callback,
				$notification->component_action,
				$notification->item_id,
				$notification->secondary_item_id,
				1,
				'string',
				$notification->id,
				'web'
			);

			// @deprecated format_notification_function - 1.5
		} elseif ( isset( $bp->{$notification->component_name}->format_notification_function ) && function_exists( $bp->{$notification->component_name}->format_notification_function ) ) {
			$description = call_user_func(
				$bp->{$notification->component_name}->format_notification_function,
				$notification->component_action,
				$notification->item_id,
				$notification->secondary_item_id,
				1
			);

			// Allow non BuddyPress components to hook in.
		} else {

			/** This filter is documented in bp-notifications/bp-notifications-functions.php */
			$description = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', array( $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1, 'string', $notification->component_action, $notification->component_name, $notification->id, 'web' ) );
		}

		/**
		 * Filters the full-text description for a specific notification.
		 *
		 * @since BuddyPress 1.9.0
		 * @since BuddyPress 2.3.0 Added the `$notification` parameter.
		 *
		 * @param string $description Full-text description for a specific notification.
		 * @param object $notification Notification object.
		 */
		return apply_filters( 'bp_get_the_notification_description', $description, $notification );
	}

}
