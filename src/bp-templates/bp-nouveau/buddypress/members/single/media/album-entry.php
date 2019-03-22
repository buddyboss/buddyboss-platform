<?php
/**
 * BuddyBoss - Members Media Entry
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php global $media_album_template; ?>

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
