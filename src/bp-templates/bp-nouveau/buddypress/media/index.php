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

bp_nouveau_before_media_directory_content();
bp_nouveau_template_notices();
?>

<div class="screen-content">
	<?php
	bp_nouveau_media_hook( 'before_directory', 'list' );

	/**
	 * Fires before the display of the members list tabs.
	 *
	 * @since BuddyPress 1.8.0
	 */
	do_action( 'bp_before_directory_media_tabs' );

	if ( ! bp_nouveau_is_object_nav_in_sidebar() ) :
		bp_get_template_part( 'common/nav/directory-nav' );
	endif;

	if ( bb_enable_content_counts() ) {
		?>
		<div class="bb-item-count">
			<?php
			if ( ! $is_send_ajax_request ) {
				$count = bp_get_total_media_count();
				printf(
					wp_kses(
						/* translators: %d is the photo count */
						_n(
							'<span class="bb-count">%d</span> Photo',
							'<span class="bb-count">%d</span> Photos',
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

	<div class="media-options">
		<?php
		bp_get_template_part( 'common/search-and-filters-bar' );
		if ( is_user_logged_in() && bp_is_profile_media_support_enabled() && bb_user_can_create_media() ) :
			?>
			<a class="bb-add-photos button small" id="bp-add-media" href="#">
				<i class="bb-icon-l bb-icon-upload"></i>
				<?php esc_html_e( 'Add Photos', 'buddyboss' ); ?>
			</a>

			<?php
			$bp_is_profile_albums_support_enabled = bp_is_profile_albums_support_enabled();
			if ( $bp_is_profile_albums_support_enabled ) {
				?>
				<a href="#" id="bb-create-album" class="bb-create-album button small">
					<i class="bb-icon-l bb-icon-image-video"></i>
					<?php esc_html_e( 'Create Album', 'buddyboss' ); ?>
				</a>
				<?php
			}

			bp_get_template_part( 'media/uploader' );

			if ( $bp_is_profile_albums_support_enabled ) {
				bp_get_template_part( 'media/create-album' );
			}
		endif;
		?>
	</div>

	<?php
	bp_get_template_part( 'media/theatre' );
	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
		bp_get_template_part( 'video/add-video-thumbnail' );
	}
	bp_get_template_part( 'document/theatre' );
	?>

	<div id="media-stream" class="media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
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

	<?php bp_nouveau_after_media_directory_content(); ?>

</div><!-- // .screen-content -->
