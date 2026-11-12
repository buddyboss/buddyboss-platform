<?php
/**
 * ReadyLaunch - Media Loop template.
 *
 * This template handles displaying the media loop listing with pagination.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
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
	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
		?>
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
			<a class="button outline full" href="<?php bboss_media_load_more_link(); ?>"><?php esc_html_e( 'Show More', 'buddyboss' ); ?><i class="bb-icons-rl-caret-down"></i></a>
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

	bboss_get_template_part( 'media/no-media' );

endif;

bboss_nouveau_after_loop();
