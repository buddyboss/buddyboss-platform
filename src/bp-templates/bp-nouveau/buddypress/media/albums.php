<?php
/**
 * The template for media albums
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/albums.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

if ( ( ( bp_is_my_profile() && bb_user_can_create_media() ) || ( bp_is_group() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) { ?>

	<div class="bb-media-actions-wrap album-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></h2>
		<?php
		if ( ( bp_is_group() && bp_is_group_albums_support_enabled() ) || bp_is_profile_albums_support_enabled() ) {
			?>
			<div class="bb-media-actions">
				<a href="#" id="bb-create-album" class="bb-create-album button small outline"><i class="bb-icon-l bb-icon-plus"></i> <?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
			</div>
			<?php
		}
		?>
	</div>

	<?php
	if ( ( bp_is_group() && bp_is_group_albums_support_enabled() ) || bp_is_profile_albums_support_enabled() ) {
		bp_get_template_part( 'media/create-album' );
	}
} else {
	?>
	<div class="bb-media-actions-wrap album-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></h2>
	</div>
	<?php
}

bp_nouveau_media_hook( 'before', 'media_album_content' );

if ( bp_has_albums( bp_ajax_querystring( 'albums' ) ) ) :
	?>

	<div id="albums-dir-list" class="bb-albums bb-albums-dir-list">

		<?php
		if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
			?>
			<ul class="bb-albums-list">
			<?php
		endif;


		while ( bp_album() ) :
			bp_the_album();

			bp_get_template_part( 'media/album-entry' );

			endwhile;

		if ( bp_album_has_more_items() ) :
			?>
			<li class="load-more">
				<a class="button outline"
				   href="<?php bp_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
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

	bp_nouveau_user_feedback( 'media-album-none' );

endif;

bp_nouveau_media_hook( 'after', 'media_album_content' );
