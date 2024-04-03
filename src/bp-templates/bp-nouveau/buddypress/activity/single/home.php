<?php
/**
 * The template for BuddyBoss - Home
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/single/home.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();
?>

<div id="bp-nouveau-single-activity-edit-form-wrap" style="display: none;">
	<div id="bp-nouveau-activity-form" class="activity-update-form <?php
	echo ( ! bp_is_active( 'media' ) ) ? esc_attr( 'media-off' ) : ''; ?>"></div>
</div>

<?php
bp_nouveau_template_notices();
bp_nouveau_before_single_activity_content();
?>

<div class="activity" data-bp-single="<?php
echo esc_attr( bp_current_action() ); ?>">
	<?php
	do_action( 'bp_before_single_activity_content' ); ?>

	<ul id="activity-stream" class="activity-list item-list bp-list" data-bp-list="activity" data-ajax="<?php
	echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
			echo '<li id="bp-ajax-loader">';
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
			<?php
			echo '</li>';
		} else {
			bp_get_template_part( 'activity/activity-loop' );
		}
		?>
	</ul>

	<?php
	do_action( 'bp_after_single_activity_content' ); ?>
</div>
