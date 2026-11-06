<?php
/**
 * The template for medias
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/index.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();

bboss_nouveau_before_media_directory_content();
bboss_nouveau_template_notices();
?>

<div class="screen-content">
	<?php
	bboss_nouveau_media_hook( 'before_directory', 'list' );

	/**
	 * Fires before the display of the members list tabs.
	 *
	 * @since BuddyPress 1.8.0
	 */
	do_action( 'bp_before_directory_media_tabs' );

	if ( ! bboss_nouveau_is_object_nav_in_sidebar() ) :
		bboss_get_template_part( 'common/nav/directory-nav' );
	endif;

	if ( bboss_enable_content_counts() ) {
		?>
		<div class="bb-item-count">
			<?php
			if ( ! $is_send_ajax_request ) {
				$count = bboss_get_total_media_count();
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
		bboss_get_template_part( 'common/search-and-filters-bar' );
		if ( is_user_logged_in() && bboss_is_profile_media_support_enabled() && bboss_user_can_create_media() ) :
			?>
			<a class="bb-add-photos button small" id="bp-add-media" href="#">
				<i class="bb-icon-l bb-icon-upload"></i>
				<?php esc_html_e( 'Add Photos', 'buddyboss' ); ?>
			</a>

			<?php
			$bp_is_profile_albums_support_enabled = bboss_is_profile_albums_support_enabled();
			if ( $bp_is_profile_albums_support_enabled ) {
				?>
				<a href="#" id="bb-create-album" class="bb-create-album button small">
					<i class="bb-icon-l bb-icon-image-video"></i>
					<?php esc_html_e( 'Create Album', 'buddyboss' ); ?>
				</a>
				<?php
			}

			bboss_get_template_part( 'media/uploader' );

			if ( $bp_is_profile_albums_support_enabled ) {
				bboss_get_template_part( 'media/create-album' );
			}
		endif;
		?>
	</div>

	<?php
	bboss_get_template_part( 'media/theatre' );
	if ( bboss_is_active( 'video' ) && bboss_is_profile_video_support_enabled() ) {
		bboss_get_template_part( 'video/theatre' );
		bboss_get_template_part( 'video/add-video-thumbnail' );
	}
	bboss_get_template_part( 'document/theatre' );
	?>

	<div id="media-stream" class="media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
			echo '<div id="bp-ajax-loader">';
			bboss_nouveau_user_feedback( 'directory-media-loading' );
			echo '</div>';
		} else {
			bboss_get_template_part( 'media/media-loop' );
		}
		?>
	</div><!-- .media -->

	<?php bboss_nouveau_after_media_directory_content(); ?>

</div><!-- // .screen-content -->
