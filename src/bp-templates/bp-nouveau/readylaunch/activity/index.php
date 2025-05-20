<?php
/**
 * The ReadyLaunch template for BuddyBoss Activity templates.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_activity_directory_content();
?>
<div class="bb-rl-activity-wrap">
	<div class="bb-rl-content-wrapper">
		<div class="bb-rl-inner-container bb-rl-activity-page">
			<?php
			if ( is_user_logged_in() ) :
				bp_get_template_part( 'activity/post-form' );
			endif;

			bp_nouveau_template_notices();

			?>
			<div class="bb-rl-screen-content">
				<?php
				if ( ! bp_nouveau_is_object_nav_in_sidebar() ) :
					bp_get_template_part( 'common/search-and-filters-bar' );
				endif;
				bp_nouveau_activity_hook( 'before_directory', 'list' );
				?>

				<div id="bb-rl-activity-stream" class="activity" data-bp-list="activity" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bb-rl-ajax-loader">';
						for ( $i = 0; $i < 2; $i++ ) {
							?>
							<div class="bb-rl-activity-placeholder">
								<div class="bb-rl-activity-placeholder_head">
									<div class="bb-rl-activity-placeholder_avatar bb-rl-bg-animation bb-rl-loading-bg"></div>
									<div class="bb-rl-activity-placeholder_details">
										<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>
										<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
									</div>
								</div>
								<div class="bb-rl-activity-placeholder_content">
									<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>
									<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>
								</div>
								<div class="bb-rl-activity-placeholder_actions">
									<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
									<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
									<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
								</div>
							</div>
							<?php
						}
						echo '</div>';
					} else {
						bp_get_template_part( 'activity/activity-loop' );
					}
					?>
				</div><!-- .activity -->

				<?php bp_nouveau_after_activity_directory_content(); ?>
			</div><!-- // .screen-content -->
		</div><!-- // .bb-rl-inner-container -->
	</div>

	<?php
	ob_start();
	bp_get_template_part( 'sidebar/right-sidebar' );
	$sidebar = ob_get_clean();

	if ( ! empty( $sidebar ) ) {
		echo '<div class="bb-rl-secondary-container">' . $sidebar . '</div>';
	}
	?>

</div>
