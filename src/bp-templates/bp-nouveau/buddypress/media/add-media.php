<?php
/**
 * BuddyBoss - Members Media List
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php if ( bp_is_my_profile() ) : ?>

    <div class="bb-media-actions-wrap">
        <div class="bb-media-actions">
            <a href="#" id="bp-add-media" class="bb-add-media button small outline"><?php _e( 'Add Media', 'buddyboss' ); ?></a>
        </div>
    </div>

<?php bp_get_template_part( 'media/uploader' ); ?>

<?php endif; ?>