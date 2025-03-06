<?php
/**
 * The template for medias
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/index.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<div class="bb-rl-media-directory-wrapper">
	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<h2><?php esc_html_e( 'Photos', 'buddyboss' ); ?> <span class="bb-rl-heading-count"><?php echo ! $is_send_ajax_request ? bp_core_get_all_member_count() : ''; ?></span></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
				bp_get_template_part( 'common/search-and-filters-bar' );

				if (
                        is_user_logged_in() &&
                        bp_is_profile_media_support_enabled() &&
                        bb_user_can_create_media()
                ) {

					echo '<div class="bb-rl-media-actions flex items-center">';

					$bp_is_profile_albums_support_enabled = bp_is_profile_albums_support_enabled();
					if ( $bp_is_profile_albums_support_enabled ) {
						?>
                        <a href="#" id="bb-create-album" class="bb-create-album button small">
                            <i class="bb-icon-l bb-icon-image-video"></i>
							<?php esc_html_e( 'Create Album', 'buddyboss' ); ?>
                        </a>
						<?php
					}

					?>
                    <a class="bb-add-photos button small" id="bp-add-media" href="#">
                        <i class="bb-icon-l bb-icon-upload"></i>
						<?php esc_html_e( 'Add Photos', 'buddyboss' ); ?>
                    </a>

					<?php

					bp_get_template_part( 'media/uploader' );

					if ( $bp_is_profile_albums_support_enabled ) {
						bp_get_template_part( 'media/create-album' );
					}

                    echo '</div>';
				}
			?>
		</div>
	</div>

	<div class="bb-rl-container-inner">

		<?php
			/**
			 * Fires before the display of the media.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_before_directory_media' );
		?>

		<div class="bb-rl-media-directory-container">

			<?php
				/**
				 * Fires before the display of the media list tabs.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_before_directory_media_tabs' );

				/**
				 * Fires before the display of the media content.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_before_directory_media_content' );
			?>

			<div class="screen-content bb-rl-media-directory-content">

				<?php
					bp_get_template_part( 'media/theatre' );
					if ( bp_is_profile_video_support_enabled() ) {
						bp_get_template_part( 'video/theatre' );
						bp_get_template_part( 'video/add-video-thumbnail' );
					}
					bp_get_template_part( 'document/theatre' );
				?>

                <div id="bb-rl-media-dir-list" class="media dir-list bb-rl-media bb-rl-media-stream" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
						if ( $is_send_ajax_request ) {
							echo '<div id="bp-ajax-loader">';
							bp_nouveau_user_feedback( 'directory-media-loading' );
							echo '</div>';
						} else {
							bp_get_template_part( 'media/media-loop' );
						}
					?>
                </div><!-- .media -->


				<?php
					/**
					 * Fires and displays the media content.
					 *
					 * @since BuddyBoss [BBVERSION]
					 */
					do_action( 'bp_directory_media_content' );
				?>
			</div><!-- // .screen-content -->

			<?php

				bp_get_template_part( 'sidebar/right-sidebar' );

				/**
				 * Fires after the display of the media content.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_after_directory_media_content' );
			?>

		</div>

		<?php
			/**
			 * Fires after the display of the media.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_after_directory_media' );
		?>
	</div>

</div>
