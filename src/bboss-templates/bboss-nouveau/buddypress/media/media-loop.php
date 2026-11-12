<?php
/**
 * The template for media loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/media-loop.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_before_loop();

// phpcs:ignore WordPress.Security.NonceVerification.Missing
if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
	bboss_get_template_part( 'media/media-move' );
	if ( bboss_is_active( 'video' ) ) :
		bboss_get_template_part( 'video/video-move' );
		bboss_get_template_part( 'video/add-video-thumbnail' );
	endif;
endif;

if ( bboss_has_media( bboss_ajax_querystring( 'media' ) ) ) :
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
		<ul class="media-list item-list bp-list bb-photo-list grid">
		<?php
	endif;

	while ( bboss_media() ) :
		bboss_the_media();

		bboss_get_template_part( 'media/entry' );

	endwhile;

	if ( bboss_media_has_more_items() ) :
		?>
		<li class="load-more">
			<a class="button outline full" href="<?php bboss_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
		</li>
		<?php
	endif;

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
		?>
		</ul>
		<?php
	endif;

else :

	if ( bboss_is_active( 'video' ) && ( ( bboss_is_profile_video_support_enabled() && bboss_is_user_albums() ) || ( bboss_is_group_video_support_enabled() && bboss_is_group_albums() ) ) ) {
		bboss_nouveau_user_feedback( 'media-video-loop-none' );
	} else {
		bboss_nouveau_user_feedback( 'media-loop-none' );
	}

endif;

bboss_nouveau_after_loop();
