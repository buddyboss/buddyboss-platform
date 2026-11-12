<?php
/**
 * ReadyLaunch - Member Notifications template.
 *
 * This template handles displaying member notifications with sorting and filtering.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();
?>
<header class="entry-header notifications-header flex">
	<h1 class="entry-title flex-1"><?php esc_html_e( 'Notifications', 'buddyboss' ); ?></h1>
	<div class="notifications-header-actions">
		<?php
			bboss_get_template_part( 'members/single/parts/item-subnav' );
			bboss_get_template_part( 'common/search-and-filters-bar' );
		?>
		<div class="bb-sort-by-date">
			<?php esc_html_e( 'Sort by date', 'buddyboss' ); ?>
			<?php bboss_nouveau_notifications_sort_order_links(); ?>
		</div>
	</div>

</header>
<?php
switch ( bboss_current_action() ) :
	case 'unread':
	case 'read':
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
