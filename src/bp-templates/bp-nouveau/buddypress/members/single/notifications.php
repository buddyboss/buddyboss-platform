<?php
/**
 * The template for users notifications
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/notifications.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();

bboss_get_template_part( 'members/single/parts/item-subnav' );

switch ( bboss_current_action() ) :
	case 'unread':
	case 'read':
		bboss_get_template_part( 'common/search-and-filters-bar' );
		?>
		<div id="notifications-user-list" class="notifications dir-list" data-bp-list="notifications">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bboss_nouveau_user_feedback( 'member-notifications-loading' );
				echo '</div>';
			} else {
				bboss_get_template_part( 'members/single/notifications/notifications-loop' );
			}
			?>
		</div><!-- #groups-dir-list -->
		<?php
		break;

	// Any other actions.
	default:
		bboss_get_template_part( 'members/single/plugins' );
		break;
endswitch;
