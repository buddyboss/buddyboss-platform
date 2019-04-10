<?php
/**
 * BuddyBoss - Members Media Albums
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php if ( bp_is_my_profile() ) : ?>

    <div class="bb-media-actions-wrap album-actions-wrap">
		<h2 class="bb-title"><?php _e( 'Albums', 'buddyboss-theme' ); ?></h2>
        <div class="bb-media-actions">
            <a href="#" id="bb-create-album" class="bb-create-album button small outline">+ <?php _e( 'Create Album', 'buddyboss' ); ?></a>
        </div>
    </div>

    <?php bp_get_template_part( 'media/create-album' ); ?>

<?php endif; ?>

<?php bp_nouveau_media_hook( 'before', 'media_album_content' ); ?>

<?php if ( bp_has_albums() ) : ?>

    <div id="albums-dir-list" class="bb-albums bb-albums-dir-list">

		<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        <ul class="bb-albums-list">
			<?php endif; ?>

			<?php
			while ( bp_album() ) :
				bp_the_album();

				bp_get_template_part( 'media/album-entry' );

			endwhile; ?>

			<?php if ( bp_album_has_more_items() ) : ?>

                <li class="load-more">
                    <a href="<?php bp_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
                </li>

			<?php endif; ?>

			<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        </ul>
	<?php endif; ?>

    </div>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'media-album-none' ); ?>

<?php endif; ?>


<?php
bp_nouveau_media_hook( 'after', 'media_album_content' );
