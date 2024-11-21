<?php
/**
 * The template for users notifications
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/notifications.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

bp_get_template_part( 'members/single/parts/item-subnav' );

if ( bp_is_current_action( 'unread' ) ) {

	// Pending notification requests.
	$count = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
	?>
	<div class="bb-item-count">
		<?php
		if ( ! $is_send_ajax_request ) {

			/* translators: %d is the notification count */
			printf(
				wp_kses( _n( '<span class="bb-count">%d</span> Notification', '<span class="bb-count">%d</span> Notifications', $count, 'buddyboss' ), array( 'span' => array( 'class' => true ) ) ),
				$count
			);
		}
		?>
	</div>
	<?php
}

switch ( bp_current_action() ) :
	case 'unread':
	case 'read':
		bp_get_template_part( 'common/search-and-filters-bar' );
		?>
		<div id="notifications-user-list" class="notifications dir-list" data-bp-list="notifications">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bp_nouveau_user_feedback( 'member-notifications-loading' );
				echo '</div>';
			} else {
				bp_get_template_part( 'members/single/notifications/notifications-loop' );
			}
			?>
		</div><!-- #groups-dir-list -->
		<?php
		break;

	// Any other actions.
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
