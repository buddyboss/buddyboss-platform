<?php
/**
 * BuddyBoss Video templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/video/index.php.
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss 1.7.01.7.0
 * @version 1.7.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_video_directory_content();
bp_nouveau_template_notices();
?>

<div class="screen-content">
	<?php
	bp_nouveau_video_hook( 'before_directory', 'list' );

	/**
	 * Fires before the display of the members list tabs.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_before_directory_video_tabs' );

	if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
		bp_get_template_part( 'common/nav/directory-nav' );
	}

	if ( bb_enable_content_counts() ) {
		?>
		<div class="bb-item-count">
			<?php
			if ( ! $is_send_ajax_request ) {
				$count = bp_get_total_video_count();
				printf(
					wp_kses(
						/* translators: %d is the video count */
						_n(
							'<span class="bb-count">%d</span> Video',
							'<span class="bb-count">%d</span> Videos',
							$count,
							'buddyboss'
						), array( 'span' => array( 'class' => true ) )
					),
					(int) $count
				);

				unset( $count );
			}
			?>
		</div>
		<?php
	}
	?>

	<div class="video-options">
		<?php
		bp_get_template_part( 'common/search-and-filters-bar' );

		if ( is_user_logged_in() ) :

			if ( ( bp_is_profile_video_support_enabled() && bb_user_can_create_video() ) ) {
				?>
				<a class="bb-add-videos button small" id="bp-add-video" href="#" ><i class="bb-icon-l bb-icon-upload"></i><?php esc_html_e( 'Add Videos', 'buddyboss' ); ?></a>
				<?php
			}

			if ( ( bp_is_profile_albums_support_enabled() ) ) {
				?>
				<a href="#" id="bb-create-video-album" class="bb-create-video-album button small"><i class="bb-icon-l bb-icon-image-video"></i><?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
				<?php
			}

			bp_get_template_part( 'video/uploader' );

			bp_get_template_part( 'video/create-album' );

		endif;
		?>
	</div>

	<?php
	bp_get_template_part( 'media/theatre' );
	bp_get_template_part( 'video/theatre' );
	bp_get_template_part( 'document/theatre' );
	?>

	<div id="video-stream" class="video" data-bp-list="video" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
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

	<?php bp_nouveau_after_video_directory_content(); ?>

</div><!-- // .screen-content -->

