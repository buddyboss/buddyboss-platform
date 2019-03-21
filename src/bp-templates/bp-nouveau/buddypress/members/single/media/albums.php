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

    <div id="members-photos-dir-list" class="bb-member-photos bb-photos-dir-list">

		<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        <ul class="bb-photo-list grid">
			<?php endif; ?>

			<?php
			while ( bp_album() ) :
				bp_the_album();
				?>

                <div id="members-albums-dir-list" class="bb-member-albums bb-albums-dir-list">
                    <ul class="bb-member-albums-items" ref="albumsList" aria-live="assertive" aria-relevant="all">
                        <li>
                            <ul class="bb-albums-list">
                                <li class="bb-album-list-item">
                                    <div class="bb-album-cover-wrap">
                                        <div class="bb-album-content-wrap">
                                            <a href="<?php echo esc_url( trailingslashit( bp_displayed_user_domain() . bp_get_media_slug() . '/albums/' . bp_get_album_id() ) ); ?>">
                                                <h4><?php bp_album_title(); ?></h4>
                                                <span><?php echo bp_core_format_date( $media_album_template->album->date_created ); ?></span> <span>&middot;</span> <span><?php echo $media_album_template->album->media['total']; ?> <?php _e( 'photos', 'buddyboss' ); ?></span>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>

                </div>

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
