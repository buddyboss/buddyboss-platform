<?php
/**
 * BuddyBoss - Members Media Albums
 *
 * @since BuddyBoss 1.0.0
 */
?>

	<h2 class="screen-heading member-media-screen"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></h2>



<?php bp_nouveau_member_hook( 'before', 'media_album_content' ); ?>

<?php if ( bp_has_albums() ) : ?>

    <div id="members-photos-dir-list" class="bb-member-photos bb-photos-dir-list">

		<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        <ul class="bb-photo-list grid">
			<?php endif; ?>

			<?php
			while ( bp_album() ) :
				bp_the_album();
				?>

				<?php bp_album_title(); ?>

			<?php endwhile; ?>

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
