<?php
/**
 * ReadyLaunch - Media Loop template.
 *
 * This template handles displaying the media loop listing with pagination.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_before_loop();

// phpcs:ignore WordPress.Security.NonceVerification.Missing
if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
	bp_get_template_part( 'media/media-move' );
	bp_get_template_part( 'video/video-move' );
	bp_get_template_part( 'video/add-video-thumbnail' );
endif;

if ( bp_has_media( bp_ajax_querystring( 'media' ) ) ) :
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
		?>
		<ul class="media-list item-list bp-list bb-photo-list grid">
		<?php
	endif;

	while ( bp_media() ) :
		bp_the_media();

		bp_get_template_part( 'media/entry' );

	endwhile;

	if ( bp_media_has_more_items() ) :
		?>
		<li class="load-more">
			<a class="button outline full" href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Show More', 'buddyboss' ); ?><i class="bb-icons-rl-caret-down"></i></a>
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

	?>
	<div class="bb-rl-media-none">
		<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-image"></i></div>
			<?php
			if ( bp_is_active( 'video' ) && ( bp_is_profile_video_support_enabled() && bp_is_user_albums() ) || ( bp_is_group_video_support_enabled() && bp_is_group_albums() ) ) {
				bp_nouveau_user_feedback( 'media-video-loop-none' );
			} else {
				bp_nouveau_user_feedback( 'media-loop-none' );
				bp_get_template_part( 'media/add-media' );
			}
			?>
	</div>
	<?php

endif;

bp_nouveau_after_loop();
