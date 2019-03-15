<?php
/**
 * BuddyBoss - Members Media List
 *
 * @since BuddyBoss 1.0.0
 */
?>

    <h2 class="screen-heading member-media-screen"><?php esc_html_e( 'Media', 'buddyboss' ); ?></h2>

    <div class="media-uploader-wrapper">
        <div class="dropzone" id="media-uploader"></div>
    </div>

<?php bp_nouveau_member_hook( 'before', 'media_list_content' ); ?>

<?php if ( bp_has_media() ) : ?>

    <div id="members-photos-dir-list" class="bb-member-photos bb-photos-dir-list">

		<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        <ul class="bb-photo-list grid">
			<?php endif; ?>

			<?php
			while ( bp_media() ) :
				bp_the_media();
				?>

				<?php bp_get_template_part( 'members/single/media/entry' ); ?>

			<?php endwhile; ?>

			<?php if ( bp_media_has_more_items() ) : ?>

                <li class="load-more">
                    <a href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
                </li>

			<?php endif; ?>

			<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        </ul>
	<?php endif; ?>

    </div>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-media-none' ); ?>

<?php endif; ?>

<?php
bp_nouveau_member_hook( 'after', 'media_list_content' );
