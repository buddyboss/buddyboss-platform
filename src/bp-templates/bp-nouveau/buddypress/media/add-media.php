<?php
/**
 * The template for add media
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/add-media.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

if ( ( ( bp_is_my_profile() && bb_user_can_create_media() ) || ( bp_is_group() && is_user_logged_in() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) { ?>

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
