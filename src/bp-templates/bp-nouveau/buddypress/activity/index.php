<?php
/**
 * The template for BuddyBoss Activity templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/index.php.
 *
 * @since   BuddyPress 2.3.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_activity_directory_content();

if ( is_user_logged_in() ) :
	bp_get_template_part( 'activity/post-form' );
endif;

bp_nouveau_template_notices();

if ( ! bp_nouveau_is_object_nav_in_sidebar() ) :
	bp_get_template_part( 'common/nav/directory-nav' );
endif;
?>
<div class="screen-content">
	<?php
	bp_get_template_part( 'common/search-and-filters-bar' );
	bp_nouveau_activity_hook( 'before_directory', 'list' );
	?>

	<div id="activity-stream" class="activity" data-bp-list="activity" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
			echo '<div id="bp-ajax-loader">';
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
			echo '</div>';
		} else {
			bp_get_template_part( 'activity/activity-loop' );
		}
		?>
	</div><!-- .activity -->

	<?php bp_nouveau_after_activity_directory_content(); ?>
</div><!-- // .screen-content -->
