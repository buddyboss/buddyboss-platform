<?php
/**
 * BuddyBoss - Video Albums
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.7.0
 */

if ( bp_is_my_profile() || ( bp_is_group() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) : ?>

	<div class="bb-video-actions-wrap album-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></h2>
		<div class="bb-video-actions">
			<a href="#" id="bb-create-album" class="bb-create-album button small outline"><i class="bb-icon-plus"></i> <?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
		</div>
	</div>

	<?php bp_get_template_part( 'video/create-album' ); ?>

	<?php
endif;

bp_nouveau_video_hook( 'before', 'video_album_content' );

if ( bp_has_video_albums( bp_ajax_querystring( 'albums' ) ) ) :
	?>

	<div id="albums-dir-list" class="bb-albums bb-albums-dir-list">

	<?php
	$paged_page = filter_input( INPUT_POST, 'page', FILTER_SANITIZE_NUMBER_INT );
	if ( empty( $paged_page ) || 1 === $paged_page ) :
		?>
		<ul class="bb-albums-list">
			<?php endif; ?>

			<?php
			while ( bp_video_album() ) :
				bp_the_video_album();

				bp_get_template_part( 'video/album-entry' );

			endwhile;
			?>

			<?php if ( bp_video_album_has_more_items() ) : ?>

				<li class="load-more">
					<a class="button outline" href="<?php bp_video_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
				</li>

			<?php endif; ?>

			<?php if ( empty( $paged_page ) || 1 === $paged_page ) : ?>
		</ul>
	<?php endif; ?>

	</div>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'video-album-none' ); ?>

<?php endif;

bp_nouveau_video_hook( 'after', 'video_album_content' );
