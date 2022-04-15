<?php
/**
 * The template for users notifications
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/notifications.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */
?>

<?php bp_get_template_part( 'members/single/parts/item-subnav' ); ?>

<?php
switch ( bp_current_action() ) :

	case 'unread':
	case 'read':
	?>


	<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>


		<div id="notifications-user-list" class="notifications dir-list" data-bp-list="notifications">
			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'member-notifications-loading' ); ?></div>
		</div><!-- #groups-dir-list -->

		<?php
		break;

	// Any other actions.
	default:
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
