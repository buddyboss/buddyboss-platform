<?php
/**
 * BuddyBoss Video templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/video/index.php.
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss [BBVERSION]
 * @version [BBVERSION]
 */

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<div class="bb-rl-video-directory-wrapper">
	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<h2><?php esc_html_e( 'Videos', 'buddyboss' ); ?> <span class="bb-rl-heading-count"><?php echo ! $is_send_ajax_request ? bp_core_get_all_member_count() : ''; ?></span></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
				bp_get_template_part( 'common/search-and-filters-bar' );

				if ( is_user_logged_in() ) {

					echo '<div class="bb-rl-video-actions flex items-center">';

					if ( bp_is_profile_albums_support_enabled() ) {
						?>
						<a href="#" id="bb-create-video-album" class="bb-create-video-album button small"><i class="bb-icon-l bb-icon-image-video"></i><?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
						<?php
					}

					if (
						bp_is_profile_video_support_enabled() &&
						bb_user_can_create_video()
					) {
						?>
						<a class="bb-add-videos button small" id="bp-add-video" href="#" ><i class="bb-icon-l bb-icon-upload"></i><?php esc_html_e( 'Add Videos', 'buddyboss' ); ?></a>
						<?php
					}

					bp_get_template_part( 'video/uploader' );

					bp_get_template_part( 'video/create-album' );

					echo '</div>';
				}

				bp_get_template_part( 'media/theatre' );
				bp_get_template_part( 'video/theatre' );
				bp_get_template_part( 'document/theatre' );
			?>
		</div>
	</div>

	<div class="bb-rl-container-inner">

		<?php
			/**
			 * Fires before the display of the video.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_before_directory_video' );
		?>

		<div class="bb-rl-video-directory-container">

			<?php
				/**
				 * Fires before the display of the video list tabs.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_before_directory_video_tabs' );

				/**
				 * Fires before the display of the video content.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_before_directory_video_content' );
			?>

			<div class="screen-content bb-rl-video-directory-content">

				<div id="bb-rl-video-dir-list" class="video dir-list bb-rl-video bb-rl-media-stream" data-bp-list="video" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
						if ( $is_send_ajax_request ) {
							echo '<div id="bp-ajax-loader">';
							bp_nouveau_user_feedback( 'directory-video-loading' );
							echo '</div>';
						} else {
							bp_get_template_part( 'video/video-loop' );
						}
					?>
				</div><!-- .video -->


				<?php
					/**
					 * Fires and displays the video content.
					 *
					 * @since BuddyBoss [BBVERSION]
					 */
					do_action( 'bp_directory_video_content' );
				?>
			</div><!-- // .screen-content -->

			<?php

				bp_get_template_part( 'sidebar/right-sidebar' );

				/**
				 * Fires after the display of the video content.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_after_directory_video_content' );
			?>

		</div>

		<?php
			/**
			 * Fires after the display of the video.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_after_directory_video' );
		?>
	</div>

</div>

