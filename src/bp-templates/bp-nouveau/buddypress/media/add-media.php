<?php
/**
 * BuddyBoss - Add Media
 *
 * @since BuddyBoss 1.0.0
 */

if ( bp_is_my_profile() || ( bp_is_group() && is_user_logged_in() ) ) : ?>

	<div class="bb-media-actions">
		<a href="#" id="bp-add-media" class="bb-add-media button small outline"><?php _e( 'Add Photos', 'buddyboss' ); ?></a>
	</div>

	<?php
	bp_get_template_part( 'media/uploader' );

endif;
