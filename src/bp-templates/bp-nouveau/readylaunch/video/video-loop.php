<?php
/**
 * ReadyLaunch - Video Loop template.
 *
 * BuddyBoss Video Loop template for displaying video list.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_before_loop();

// phpcs:ignore WordPress.Security.NonceVerification.Missing
if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
	bp_get_template_part( 'video/add-video-thumbnail' );
	bp_get_template_part( 'video/video-move' );
endif;

if ( bp_has_video( bp_ajax_querystring( 'video' ) ) ) :

	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : // phpcs:ignore
		bp_get_template_part( 'video/video-header' );
		?>
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
			<a class="button outline full" href="<?php bp_video_load_more_link(); ?>"><?php esc_html_e( 'Show More', 'buddyboss' ); ?><i class="bb-icons-rl-caret-down"></i></a>
		</li>
		<?php
	endif;

	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : // phpcs:ignore
		?>
		</ul>
		<?php
	endif;

else :
	?>
	<div class="bb-rl-media-none">
		<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-video"></i></div>
		<?php
		bp_nouveau_user_feedback( 'video-loop-none' );
		bp_get_template_part( 'video/add-video' );
		?>
	</div>
	<?php
endif;

bp_nouveau_after_loop();
