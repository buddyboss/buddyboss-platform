<?php
/**
 * Export API: BP_Message_Export class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Message_Export
 */
final class BP_Message_Export extends BP_Export {

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Message_Export();
			$instance->setup( 'bp_message', __( 'Private Messages', 'buddyboss' ) );
		}

		return $instance;
	}

	/**
	 * Export member messages.
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

			$group_id    = 'bp_messages';
			$group_label = __( 'Message Threads & Replies', 'buddyboss' );
			$item_id     = "{$this->exporter_name}-{$group_id}-{$item->id}";

			$permalink = bp_get_message_thread_view_link( $item->thread_id, $user->ID );

			// recipients
			$recipients = array();
			if ( ! is_array( $item->recipients ) ) {
				$item->recipients = array();
			}
			foreach ( $item->recipients as $r_user ) {
				if ( ! empty( $r_user ) ) {
					$name = $r_user->display_name;
					if ( empty( $r_user->display_name ) ) {
						$name = $r_user->user_login;
					}
					$recipients[] = $name;
				}
			}
			$recipients = implode( ', ', $recipients );

			$recipients = apply_filters( 'buddyboss_bp_gdpr_bp_message_item_recipients', $recipients, $item, $data_items );

			$data = array(
				array(
					'name'  => __( 'Message Subject', 'buddyboss' ),
					'value' => $item->subject,
				),
				array(
					'name'  => __( 'Message Content', 'buddyboss' ),
					'value' => $item->message,
				),
				array(
					'name'  => __( 'Created Date (GMT)', 'buddyboss' ),
					'value' => $item->date_sent,
				),
				array(
					'name'  => __( 'Message Recipients', 'buddyboss' ),
					'value' => $recipients,
				),
				array(
					'name'  => __( 'Thread URL', 'buddyboss' ),
					'value' => $permalink,
				),
			);

			$data = apply_filters( 'buddyboss_bp_gdpr_message_after_data_prepare', $data, $item, $data_items );

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
	 * Delete user messages and change ownership to anonymous.
	 *
	 * @param      $user
	 * @param      $page
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

		$table            = "{$bp->messages->global_tables["table_name_messages"]}";
		$table_recipients = "{$bp->messages->global_tables["table_name_recipients"]}";
		$page             = (int) $page;
		$items_removed    = false;
		$items_retained   = false;

		$get_data = $this->get_data( $user, $page );

		foreach ( $get_data['items'] as $item ) {

			$item->subject = wp_privacy_anonymize_data( 'text', $item->subject );
			$item->message = wp_privacy_anonymize_data( 'longtext', $item->subject );

			$wpdb->update(
				$table,
				array(
					'subject' => $item->subject,
					'message' => $item->message,
				),
				array( 'id' => $item->id ),
				array( '%s', '%s', '%d' ),
				array( '%d' )
			);

			/**
			 * @todo add title/description
			 *
			 * @since BuddyBoss 1.0.0
			 */
			do_action( 'buddyboss_bp_gdpr_message_after_data_erasers_item', $item, $get_data );

			$items_removed = true;

		}

		$done = $get_data['total'] < $get_data['offset'];

		if ( $done ) {
			// Anonymous user from all recipients
			$wpdb->update(
				$table_recipients,
				array( 'user_id' => 0 ),
				array( 'user_id' => $user->ID ),
				array( '%d' ),
				array( '%d' )
			);

			// Anonymous Sender ID from all messages
			$wpdb->update(
				$table,
				array( 'sender_id' => 0 ),
				array( 'sender_id' => $user->ID ),
				array( '%d' ),
				array( '%d' )
			);

		}

		return $this->response_erase( $items_removed, $done, array(), $items_retained );
	}

	/**
	 * Merge recipients into items.
	 *
	 * @param $items
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function messages_recipients( $items ) {
		global $wpdb, $bp;

		$table = "{$bp->messages->global_tables["table_name_recipients"]}";

		$thread_ids = array();

		foreach ( $items as $item ) {
			$thread_ids[ $item->thread_id ] = $item->thread_id;
		}

		if ( empty( $thread_ids ) ) {
			return array();
		}

		$ids_in = array_fill( 0, count( $thread_ids ), '%s' );
		$ids_in = join( ',', $ids_in );

		$get_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *FROM {$table} WHERE thread_id IN ({$ids_in})",
				$thread_ids
			)
		);

		$thread_recipients = array();

		foreach ( $get_results as $result ) {
			if ( ! isset( $thread_recipients[ $result->thread_id ] ) || ! is_array( $thread_recipients[ $result->thread_id ] ) ) {
				$thread_recipients[ $result->thread_id ] = array();
			}
			if ( ! isset( $thread_recipients[ $result->thread_id ][ $result->user_id ] ) ) {
				$thread_recipients[ $result->thread_id ][ $result->user_id ] = get_userdata( $result->user_id );
			}
		}

		foreach ( $items as $item_key => $item ) {
			if ( ! isset( $thread_recipients[ $item->thread_id ] ) ) {
				$thread_recipients[ $item->thread_id ] = array();
			}
			$item->recipients = $thread_recipients[ $item->thread_id ];
		}

		return $items;
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

		$table = "{$bp->messages->global_tables["table_name_messages"]} item";

		$query_select       = 'item.*';
		$query_select_count = 'COUNT(item.id)';
		$query_where        = 'item.sender_id=%d';

		$offset = ( $page - 1 ) * $this->items_per_batch;
		$limit  = "LIMIT {$this->items_per_batch} OFFSET {$offset}";

		$query       = "SELECT {$query_select} FROM {$table} WHERE {$query_where} {$limit}";
		$query       = $wpdb->prepare( $query, $user->ID );
		$query_count = "SELECT {$query_select_count} FROM {$table} WHERE {$query_where}";
		$query_count = $wpdb->prepare( $query_count, $user->ID );

		$count = (int) $wpdb->get_var( $query_count );
		$items = $wpdb->get_results( $query );

		$items = $this->messages_recipients( $items );

		return array(
			'total'  => $count,
			'offset' => $offset,
			'items'  => $items,
		);

	}

}
