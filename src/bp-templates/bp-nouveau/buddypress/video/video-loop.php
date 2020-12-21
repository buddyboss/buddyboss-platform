<?php
/**
 * BuddyBoss - Video Loop
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

bp_nouveau_before_loop();

if ( bp_has_video( bp_ajax_querystring( 'video' ) ) ) :

	$paged_page = filter_input( INPUT_POST, 'page', FILTER_SANITIZE_NUMBER_INT );
	if ( empty( $paged_page ) || 1 === $paged_page ) : ?>
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

	if ( empty( $paged_page ) || 1 === $paged_page ) :
		?>
		</ul>
		<?php
	endif;

else :

	bp_nouveau_user_feedback( 'video-loop-none' );

endif;

bp_nouveau_after_loop();
