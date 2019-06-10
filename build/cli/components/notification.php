<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Notifications.
 *
 * ## EXAMPLES
 *
 *     # Create notification item.
 *     $ wp bp notification create
 *     Success: Successfully created new notification. (ID #5464)
 *
 *     # Delete a notification item.
 *     $ wp bp notification delete 520
 *     Success: Notification deleted.
 *
 * @since 1.8.0
 */
class Notification extends BuddypressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'id',
		'user_id',
		'item_id',
		'secondary_item_id',
		'component_name',
		'component_action',
		'date_notified',
		'is_new',
	);

	/**
	 * Create a notification item.
	 *
	 * ## OPTIONS
	 *
	 * [--component=<component>]
	 * : The component for the notification item (groups, activity, etc). If
	 * none is provided, a component will be randomly selected from the
	 * active components.
	 *
	 * [--action=<action>]
	 * : Name of the action to associate the notification. (comment_reply, update_reply, etc).
	 *
	 * [--user-id=<user>]
	 * : ID of the user associated with the new notification.
	 *
	 * [--item-id=<item>]
	 * : ID of the associated notification.
	 *
	 * [--secondary-item-id=<item>]
	 * : ID of the secondary associated notification.
	 *
	 * [--date=<date>]
	 * : GMT timestamp, in Y-m-d h:i:s format.
	 * ---
	 * default: Current time
	 * ---
	 *
	 * [--silent]
	 * : Whether to silent the notification creation.
	 *
	 * [--porcelain]
	 * : Output only the new notification id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp notification create --component=messages --action=update_reply --user-id=523
	 *     Success: Successfully created new notification. (ID #5464)
	 *
	 *     $ wp bp notification add --component=groups --action=comment_reply --user-id=10
	 *     Success: Successfully created new notification (ID #48949)
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'component'         => '',
			'action'            => '',
			'user-id'           => 0,
			'item-id'           => 0,
			'secondary-item-id' => 0,
			'date'              => bp_core_current_time(),
		) );

		$id = bp_notifications_add_notification( array(
			'user_id'           => $r['user-id'],
			'item_id'           => $r['item-id'],
			'secondary_item_id' => $r['secondary-item-id'],
			'component_name'    => $r['component'],
			'component_action'  => $r['action'],
			'date_notified'     => $r['date'],
		) );

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! is_numeric( $id ) ) {
			WP_CLI::error( 'Could not create notification.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $id );
		} else {
			WP_CLI::success( sprintf( 'Successfully created new notification (ID #%d)', $id ) );
		}
	}

	/**
	 * Get specific notification.
	 *
	 * ## OPTIONS
	 *
	 * <notification-id>
	 * : Identifier for the notification.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp notification get 500
	 *     $ wp bp notification get 56 --format=json
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$notification = bp_notifications_get_notification( $args[0] );

		if ( empty( $notification->id ) ) {
			WP_CLI::error( 'No notification found by that ID.' );
		}

		if ( ! is_object( $notification ) ) {
			WP_CLI::error( 'Could not find the notification.' );
		}

		$notification_arr = get_object_vars( $notification );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $notification_arr );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $notification_arr );
	}

	/**
	 * Delete a notification.
	 *
	 * ## OPTIONS
	 *
	 * <notification-id>...
	 * : ID or IDs of notification to delete.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp notification delete 520 --yes
	 *     Success: Deleted notification 520.
	 *
	 *     $ wp bp notification delete 55654 54564 --yes
	 *     Success: Deleted notification 55654.
	 *     Success: Deleted notification 54564.
	 *
	 *     $ wp bp notification delete $(wp bp notification list --format=ids) --yes
	 *     Success: Deleted notification 35456465.
	 *     Success: Deleted notification 46546546.
	 *     Success: Deleted notification 46465465.
	 *
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$notifications = $args;

		if ( count( $notifications ) > 1 ) {
			WP_CLI::confirm( 'Are you sure want to delete these notifications?', $assoc_args );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete this notification?', $assoc_args );
		}

		parent::_delete( $notifications, $assoc_args, function( $notification_id ) {

			$notification = bp_notifications_get_notification( $notification_id );

			if ( empty( $notification->id ) ) {
				WP_CLI::error( sprintf( 'No notification found by ID %d.', $notification_id ) );
			}

			if ( ! is_object( $notification ) ) {
				WP_CLI::error( sprintf( 'Could not find the notification %d.', $notification_id ) );
			}

			if ( \BP_Notifications_Notification::delete( array( 'id' => $notification_id ) ) ) {
				return array( 'success', sprintf( 'Deleted notification %d.', $notification_id ) );
			} else {
				return array( 'error', sprintf( 'Could not delete notification %d.', $notification_id ) );
			}
		} );
	}

	/**
	 * Generate random notifications.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many notifications to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp notification generate --count=50
	 */
	public function generate( $args, $assoc_args ) {
		$notify = WP_CLI\Utils\make_progress_bar( 'Generating notifications', $assoc_args['count'] );

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {

			$component = $this->get_random_component();

			$this->create( array(), array(
				'user-id'   => $this->get_random_user_id(),
				'component' => $component,
				'action'    => $this->get_random_action( $component ),
				'silent',
			) );

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Get a list of notifications.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass.
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
	 *
	 * [--user-id=<user>]
	 * : Limit results to a specific member. Accepts either a user_login or a numeric ID.
	 *
	 * [--component=<component>]
	 * : The component to fetch notifications (groups, activity, etc).
	 *
	 * [--action=<action>]
	 * : Name of the action to fetch notifications. (comment_reply, update_reply, etc).
	 *
	 * [--count=<number>]
	 * : How many notification items to list.
	 * ---
	 * default: 50
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - ids
	 *   - csv
	 *   - count
	 *   - haml
	 * ---

	 * ## EXAMPLES
	 *
	 *     $ wp bp notification list --format=ids
	 *     15 25 34 37 198
	 *
	 *     $ wp bp notification list --format=count
	 *     10
	 *
	 *     $ wp bp notification list --fields=id,user_id
	 *     | id     | user_id  |
	 *     | 66546  | 656      |
	 *     | 54554  | 646546   |
	 *
	 * @subcommand list
	 */
	public function _list( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$query_args = wp_parse_args( $assoc_args, array(
			'count' => 50,
		) );

		if ( isset( $assoc_args['user-id'] ) ) {
			$user                  = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
			$query_args['user_id'] = $user->ID;
		}

		if ( isset( $assoc_args['action'] ) ) {
			$query_args['component_action'] = $assoc_args['action'];
		}

		if ( isset( $assoc_args['component'] ) ) {
			$query_args['component_name'] = $assoc_args['component'];
		}

		$query_args['per_page'] = $query_args['count'];

		$query_args = self::process_csv_arguments_to_arrays( $query_args );

		$notifications = \BP_Notifications_Notification::get( $query_args );

		if ( empty( $notifications ) ) {
			WP_CLI::error( 'No notification items found.' );
		}

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', wp_list_pluck( $notifications, 'id' ) ); // WPCS: XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			$formatter->display_items( $notifications );
		} else {
			$formatter->display_items( $notifications );
		}
	}

	/**
	 * Get random notification actions based on component.
	 *
	 * @since 1.8.0
	 *
	 * @param string $component BuddyPress Component.
	 *
	 * @return string
	 */
	protected function get_random_action( $component ) {
		$bp      = buddypress();
		$actions = '';

		// Activity.
		if ( $bp->activity->id === $component ) {
			$actions = [ 'comment_reply', 'update_reply', 'new_at_mention' ];
		}

		// Friendship.
		if ( $bp->friends->id === $component ) {
			$actions = [ 'friendship_request', 'friendship_accepted' ];
		}

		// Groups.
		if ( $bp->groups->id === $component ) {
			$actions = [ 'new_membership_request', 'membership_request_accepted', 'membership_request_rejected', 'member_promoted_to_admin', 'member_promoted_to_mod', 'group_invite' ];
		}

		// Messages.
		if ( $bp->messages->id === $component ) {
			$actions = [ 'new_message' ];
		}

		return array_rand( $actions );
	}
}
