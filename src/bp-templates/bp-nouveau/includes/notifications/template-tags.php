<?php
/**
 * Notifications template tags
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Display the notifications filter options.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_notifications_filters() {
	echo bp_nouveau_get_notifications_filters();
}

	/**
	 * Get the notifications filter options.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return string HTML output.
	 */
	function bp_nouveau_get_notifications_filters() {
		$output   = '';
		$filters  = bp_nouveau_notifications_sort( bp_nouveau_notifications_get_filters() );
		$selected = 0;

		if ( ! empty( $_REQUEST['type'] ) ) {
			$selected = sanitize_key( $_REQUEST['type'] );
		}

		foreach ( $filters as $filter ) {
			if ( empty( $filter['id'] ) || empty( $filter['label'] ) ) {
				continue;
			}

			$output .= sprintf( '<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $filter['id'] ),
				selected( $selected, $filter['id'], false ),
				esc_html( $filter['label'] )
			) . "\n";
		}

		if ( $output ) {
			$output = sprintf( '<option value="%1$s" %2$s>%3$s</option>',
				0,
				selected( $selected, 0, false ),
				esc_html__( '- View All -', 'buddyboss' )
			) . "\n" . $output;
		}

		/**
		 * Filter to edit the options output.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string $output  The options output.
		 * @param array  $filters The sorted notifications filters.
		 */
		return apply_filters( 'bp_nouveau_get_notifications_filters', $output, $filters );
	}

/**
 * Outputs the order action links.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_notifications_sort_order_links() {
	if ( 'unread' === bp_current_action() ) {
		$link = bp_get_notifications_unread_permalink( bp_displayed_user_id() );
	} else {
		$link = bp_get_notifications_read_permalink( bp_displayed_user_id() );
	}

	$desc = add_query_arg( 'sort_order', 'DESC', $link );
	$asc  = add_query_arg( 'sort_order', 'ASC', $link );
	?>

	<span class="notifications-order-actions">
		<a href="<?php echo esc_url( $desc ); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Newest First', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Newest First', 'buddyboss' ); ?>" data-bp-notifications-order="DESC"><span class="bb-icon-angle-down bb-icon-l" aria-hidden="true"></span></a>
		<a href="<?php echo esc_url( $asc ); ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Oldest First', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Oldest First', 'buddyboss' ); ?>" data-bp-notifications-order="ASC"><span class="bb-icon-angle-up bb-icon-l" aria-hidden="true"></span></a>
	</span>

	<?php
}

/**
 * Output the dropdown for bulk management of notifications.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_notifications_bulk_management_dropdown() {
?>

	<div class="select-wrap">

		<label class="bp-screen-reader-text" for="notification-select"><?php
			esc_html_e( 'Select Bulk Action', 'buddyboss' );
		?></label>

		<select name="notification_bulk_action" id="notification-select">
			<option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'buddyboss' ); ?></option>

			<?php if ( bp_is_current_action( 'unread' ) ) : ?>
				<option value="read"><?php esc_html_e( 'Mark read', 'buddyboss' ); ?></option>
			<?php elseif ( bp_is_current_action( 'read' ) ) : ?>
				<option value="unread"><?php esc_html_e( 'Mark unread', 'buddyboss' ); ?></option>
			<?php endif; ?>
			<option value="delete"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></option>
		</select>

		<span class="select-arrow"></span>

	</div><!-- // .select-wrap -->

	<input type="submit" id="notification-bulk-manage" class="button action" value="<?php esc_attr_e( 'Apply', 'buddyboss' ); ?>">
	<?php
}
