<?php
/**
 * The template for medias
 *
 * This template handles the media directory page layout for the ReadyLaunch theme.
 * It includes search filters, create album button, add photos functionality, and media listing.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<div class="bb-rl-media-directory-wrapper">
	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<h2><?php esc_html_e( 'Photos', 'buddyboss' ); ?> <span class="bb-rl-heading-count"><?php echo ! $is_send_ajax_request ? esc_html( bp_core_number_format( bp_get_total_media_count() ) ) : ''; ?></span></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
			bp_get_template_part( 'common/search-and-filters-bar' );

			if (
				is_user_logged_in() &&
				bp_is_profile_media_support_enabled() &&
				bb_user_can_create_media()
			) {

				echo '<div class="bb-rl-media-actions bb-rl-actions-buttons flex items-center">';

				$bp_is_profile_albums_support_enabled = bp_is_profile_albums_support_enabled();
				if ( $bp_is_profile_albums_support_enabled ) {
					?>
					<a href="#" id="bb-create-album" class="action-secondary bb-create-album button small">
						<i class="bb-icons-rl-images"></i>
						<?php esc_html_e( 'Create Album', 'buddyboss' ); ?>
					</a>
					<?php
				}

				?>
				<a class="bb-add-photos button small action-primary" id="bp-add-media" href="#">
					<i class="bb-icons-rl-plus"></i>
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
			 * @since BuddyBoss 2.9.00
			 */
			do_action( 'bp_before_directory_media' );
		?>

		<div class="bb-rl-media-directory-container">

			<?php
				/**
				 * Fires before the display of the media list tabs.
				 *
				 * @since BuddyBoss 2.9.00
				 */
				do_action( 'bp_before_directory_media_tabs' );

				/**
				 * Fires before the display of the media content.
				 *
				 * @since BuddyBoss 2.9.00
				 */
				do_action( 'bp_before_directory_media_content' );
			?>

			<div class="screen-content bb-rl-media-directory-content">
				<div class="bb-rl-media-stream">

				<?php
					bp_get_template_part( 'media/theatre' );
				if ( bp_is_profile_video_support_enabled() ) {
					bp_get_template_part( 'video/theatre' );
					bp_get_template_part( 'video/add-video-thumbnail' );
				}
					bp_get_template_part( 'document/theatre' );
				?>

					<div id="media-stream" class="media dir-list bb-rl-media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
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
				</div>

				<?php
					/**
					 * Fires and displays the media content.
					 *
					 * @since BuddyBoss 2.9.00
					 */
					do_action( 'bp_directory_media_content' );
				?>
			</div><!-- // .screen-content -->

			<?php

				bp_get_template_part( 'sidebar/right-sidebar' );

				/**
				 * Fires after the display of the media content.
				 *
				 * @since BuddyBoss 2.9.00
				 */
				do_action( 'bp_after_directory_media_content' );
			?>

		</div>

		<?php
			/**
			 * Fires after the display of the media.
			 *
			 * @since BuddyBoss 2.9.00
			 */
			do_action( 'bp_after_directory_media' );
		?>
	</div>

</div>
