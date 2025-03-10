<?php
/**
 * BuddyBoss - Groups Activity
 *
 * This template is used to show group activity.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/activity.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<h2 class="bp-screen-title<?php echo ( ! bp_is_group_home() ) ? ' bp-screen-reader-text' : ''; ?>">
	<?php esc_html_e( 'Group Feed', 'buddyboss' ); ?>
</h2>

<?php bp_nouveau_groups_activity_post_form(); ?>

<div class="subnav-filters filters clearfix">
	<ul>
		<li class="group-act-search"><?php bp_nouveau_search_form(); ?></li>
	</ul>
	<?php bp_get_template_part( 'common/filters/groups-screens-filters' ); ?>
</div><!-- // .subnav-filters -->

<?php bp_nouveau_group_hook( 'before', 'activity_content' ); ?>

<div id="activity-stream" class="activity single-group" data-bp-list="activity" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
	<?php
	if ( $is_send_ajax_request ) {
		echo '<li id="bp-activity-ajax-loader">';
		?>
		<div class="bb-activity-placeholder">
			<div class="bb-activity-placeholder_head">
				<div class="bb-activity-placeholder_avatar bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_details">
					<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
					<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				</div>
			</div>
			<div class="bb-activity-placeholder_content">
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
			</div>
			<div class="bb-activity-placeholder_actions">
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
			</div>
		</div>
		<div class="bb-activity-placeholder">
			<div class="bb-activity-placeholder_head">
				<div class="bb-activity-placeholder_avatar bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_details">
					<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
					<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				</div>
			</div>
			<div class="bb-activity-placeholder_content">
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>
			</div>
			<div class="bb-activity-placeholder_actions">
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
				<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>
			</div>
		</div>
		<?php
		echo '</li>';
	} else {
		bp_get_template_part( 'activity/activity-loop' );
	}
	?>
</div><!-- .activity -->

<?php
bp_nouveau_group_hook( 'after', 'activity_content' );
bp_get_template_part( 'common/js-templates/activity/comments' );
