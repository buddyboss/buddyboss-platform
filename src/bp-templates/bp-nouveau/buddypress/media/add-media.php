<?php
/**
 * BuddyBoss - Add Media
 *
 * @since BuddyBoss 1.0.0
 */

if ( ( ( bp_is_my_profile() && bp_user_can_create_media() ) || ( bp_is_group() && is_user_logged_in() ) ) ) { ?>

	<div class="bb-media-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Photos', 'buddyboss' ); ?></h2>
		<div class="bb-media-actions">
			<a href="#" id="bp-add-media" class="bb-add-media button small outline"><?php esc_html_e( 'Add Photos', 'buddyboss' ); ?></a>
		</div>
	</div>

	<?php
	bp_get_template_part( 'media/uploader' );

} else {
	?>
	<div class="bb-media-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Photos', 'buddyboss' ); ?></h2>
	</div>
	<?php
}
