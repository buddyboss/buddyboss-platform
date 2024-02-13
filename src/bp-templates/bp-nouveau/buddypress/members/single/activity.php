<?php
/**
 * The template for users activity
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/activity.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

bp_get_template_part( 'members/single/parts/item-subnav' );
bp_nouveau_activity_member_post_form();
bp_get_template_part( 'common/search-and-filters-bar' );
bp_nouveau_member_hook( 'before', 'activity_content' );
?>

<div id="activity-stream" class="activity single-user" data-bp-list="activity"data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
	<?php
	if ( $is_send_ajax_request ) {
		echo '<div id="bp-ajax-loader">';
		bp_nouveau_user_feedback( 'member-activity-loading' );
		echo '</div>';
	}
	?>
	<ul class="<?php bp_nouveau_loop_classes(); ?>">
		<?php
		if ( ! $is_send_ajax_request ) {
			bp_get_template_part( 'activity/activity-loop' );
		}
		?>
	</ul>
	</div><!-- .activity -->

<?php
bp_nouveau_member_hook( 'after', 'activity_content' );
bp_get_template_part( 'common/js-templates/activity/comments' );
