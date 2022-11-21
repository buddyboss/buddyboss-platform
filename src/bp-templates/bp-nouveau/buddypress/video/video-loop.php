<?php
/**
 * BuddyBoss - Video Loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/video/video-loop.php.
 *
 * @package BuddyBoss\Core
 *
 * @since   BuddyBoss 1.7.0
 * @version 1.7.0
 */

bp_nouveau_before_loop();

// phpcs:ignore WordPress.Security.NonceVerification.Missing
if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
	bp_get_template_part( 'video/add-video-thumbnail' );
	bp_get_template_part( 'video/video-move' );
endif;

if ( bp_has_video( bp_ajax_querystring( 'video' ) ) ) :

	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : // phpcs:ignore ?>
		<ul class="video-list item-list bp-list bb-video-list grid">
		<?php
	endif;

	while ( bp_video() ) :
		bp_the_video();

		bp_get_template_part( 'video/entry' );

	endwhile;

	if ( bp_video_has_more_items() ) :
		?>
		<li class="load-more">
			<a class="button outline full" href="<?php bp_video_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
		</li>
		<?php
	endif;

	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : // phpcs:ignore
		?>
		</ul>
		<?php
	endif;

else :

	bp_nouveau_user_feedback( 'video-loop-none' );

endif;

bp_nouveau_after_loop();
