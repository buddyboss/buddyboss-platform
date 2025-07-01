<?php
/**
 * ReadyLaunch - Member Notifications template.
 *
 * This template handles displaying member notifications with sorting and filtering.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<header class="entry-header notifications-header flex">
	<h1 class="entry-title flex-1"><?php esc_html_e( 'Notifications', 'buddyboss' ); ?></h1>
	<div class="notifications-header-actions">
		<?php
			bp_get_template_part( 'members/single/parts/item-subnav' );
			bp_get_template_part( 'common/search-and-filters-bar' );
		?>
		<div class="bb-sort-by-date">
			<?php esc_html_e( 'Sort by date', 'buddyboss' ); ?>
			<?php bp_nouveau_notifications_sort_order_links(); ?>
		</div>
	</div>

</header>
<?php
switch ( bp_current_action() ) :
	case 'unread':
	case 'read':
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
