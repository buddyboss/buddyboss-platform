<?php
/**
 * BuddyBoss - Members Media Albums
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php global $media_album_template; ?>

<?php if ( bp_is_my_profile() ) : ?>

    <div class="flex">
        <div class="push-right bb-media-actions">
            <a href="#" id="bb-create-album" class="bb-create-album button small outline">+ <?php _e( 'Create Album', 'buddyboss' ); ?></a>
        </div>
    </div>

    <?php bp_get_template_part( 'members/single/media/uploader' ); ?>
    <?php bp_get_template_part( 'members/single/media/create-album' ); ?>

<?php endif; ?>

<?php bp_nouveau_member_hook( 'before', 'media_album_content' ); ?>

<?php if ( bp_has_albums() ) : ?>

    <div id="members-albums-dir-list" class="bb-member-albums bb-albums-dir-list">

		<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        <ul class="bb-album-list grid">
			<?php endif; ?>

			<?php
			while ( bp_album() ) :
				bp_the_album();

				bp_get_template_part( 'members/single/media/album-entry' );

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

	<?php bp_nouveau_user_feedback( 'member-media-album-none' ); ?>

<?php endif; ?>


<?php
bp_nouveau_member_hook( 'after', 'media_album_content' );
