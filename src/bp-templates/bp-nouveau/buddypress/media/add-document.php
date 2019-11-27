<?php
/**
 * BuddyBoss - Add Media
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php if ( bp_is_my_profile() || ( bp_is_group() && is_user_logged_in() ) ) : ?>

    <div class="bb-media-actions-wrap">
		<h2 class="bb-title"><?php _e( 'Documents', 'buddyboss' ); ?></h2>
        <div class="bb-media-actions">
            <a href="#" id="bp-add-document" class="bb-add-document button small outline"><?php _e( 'Add Documents', 'buddyboss' ); ?></a>
        </div>
    </div>

<?php bp_get_template_part( 'media/document-uploader' ); ?>

<?php endif; ?>
