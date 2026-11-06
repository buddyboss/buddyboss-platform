<?php
/**
 * The template for media albums
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/albums.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_is_group = bboss_is_group();
if ( ( ( bboss_is_my_profile() && bboss_user_can_create_media() ) || ( $bp_is_group && groups_can_user_manage_albums( bboss_loggedin_user_id(), bboss_get_current_group_id() ) ) ) ) {
	$bp_is_group_albums_support_enabled   = bboss_is_group_albums_support_enabled();
	$bp_is_profile_albums_support_enabled = bboss_is_profile_albums_support_enabled();
	?>

	<div class="bb-media-actions-wrap album-actions-wrap">
		<?php
		if ( ( $bp_is_group && $bp_is_group_albums_support_enabled ) || $bp_is_profile_albums_support_enabled ) {
			?>
			<div class="bb-media-actions">
				<a href="#" id="bb-create-album" class="bb-create-album button small outline"><i class="bb-icon-l bb-icon-plus"></i> <?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
			</div>
			<?php
		}
		?>
	</div>

	<?php
	if ( ( $bp_is_group && $bp_is_group_albums_support_enabled ) || $bp_is_profile_albums_support_enabled ) {
		bboss_get_template_part( 'media/create-album' );
	}
}

bboss_nouveau_media_hook( 'before', 'media_album_content' );

if ( bboss_has_albums( bboss_ajax_querystring( 'albums' ) ) ) :
	?>
	<div id="albums-dir-list" class="bb-albums bb-albums-dir-list">
		<?php
		if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
			?>
			<ul class="bb-albums-list">
			<?php
		endif;


		while ( bboss_album() ) :
			bboss_the_album();

			bboss_get_template_part( 'media/album-entry' );

		endwhile;

		if ( bboss_album_has_more_items() ) :
			?>
			<li class="load-more">
				<a class="button outline" href="<?php bboss_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
			</li>
			<?php
		endif;

		if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
			?>
			</ul>
			<?php
		endif;
		?>
	</div>
	<?php

else :
	bboss_nouveau_user_feedback( 'media-album-none' );
endif;

bboss_nouveau_media_hook( 'after', 'media_album_content' );
