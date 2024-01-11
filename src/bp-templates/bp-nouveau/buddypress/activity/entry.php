<?php
/**
 * The template for BuddyBoss - Activity Feed (Single Item)
 *
 * This template is used by activity-loop.php and AJAX functions to show
 * each activity.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/entry.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_activity_hook( 'before', 'entry' );

$activity_id = bp_get_activity_id();

$link_preview_string = '';
$link_url            = '';

$link_preview_data = bp_activity_get_meta( $activity_id, '_link_preview_data' );
if ( ! empty( $link_preview_data ) && count( $link_preview_data ) ) {
	$link_preview_string = wp_json_encode( $link_preview_data );
	$link_url            = ! empty( $link_preview_data['url'] ) ? $link_preview_data['url'] : '';
}

$link_embed = bp_activity_get_meta( $activity_id, '_link_embed' );
if ( ! empty( $link_embed ) ) {
	$link_url = $link_embed;
}

?>

<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php echo esc_attr( $activity_id ); ?>" data-bp-activity-id="<?php echo esc_attr( $activity_id ); ?>" data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>" data-bp-activity="<?php bp_nouveau_edit_activity_data(); ?>" data-link-preview='<?php echo $link_preview_string; ?>' data-link-url='<?php echo $link_url; ?>'>

	<?php bb_nouveau_activity_entry_bubble_buttons(); ?>

	<div class="bb-pin-action">
		<span class="bb-pin-action_button" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Pinned Post', 'buddyboss' ); ?>">
			<i class="bb-icon-f bb-icon-thumbtack"></i>
		</span>
	</div>

	<div class="activity-avatar item-avatar">
		<a href="<?php bp_activity_user_link(); ?>">
			<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>
		</a>
	</div>

	<div class="activity-content <?php bp_activity_entry_css_class(); ?>">
		<div class="activity-header">
			<?php
				bp_activity_action();
				bp_nouveau_activity_is_edited();
				bp_nouveau_activity_privacy();
			?>
		</div>

		<?php
		bp_nouveau_activity_hook( 'before', 'activity_content' );

		if ( bp_nouveau_activity_has_content() ) :
			?>
			<div class="activity-inner"><?php bp_nouveau_activity_content(); ?></div>
			<?php
		endif;

		bp_nouveau_activity_hook( 'after', 'activity_content' );
		bp_nouveau_activity_state();
		bp_nouveau_activity_entry_buttons();
		?>
	</div>

	<?php
	bp_nouveau_activity_hook( 'before', 'entry_comments' );

	if ( bp_activity_get_comment_count() || ( is_user_logged_in() && ( bp_activity_can_comment() || bp_is_single_activity() ) ) ) :
		?>
		<div class="activity-comments">
			<?php
			bp_activity_comments();
			bp_nouveau_activity_comment_form();
			?>
		</div>
		<?php
	endif;

	bp_nouveau_activity_hook( 'after', 'entry_comments' );
	?>
</li>

<?php
bp_nouveau_activity_hook( 'after', 'entry' );
