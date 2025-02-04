<?php
/**
 * ReadyLaunch - The template for document create folder
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>

<div class="bb-rl-create-popup-folder-wrap bb-rl-popup-on-fly-create-folder" style="display: none;">
	<div class="bb-rl-field-wrap">
		<label for="bb_rl_new_folder_name_input" class="bb-label"><?php esc_html_e( 'Folder Title', 'buddyboss' ); ?></label>
		<input class="bb-rl-popup-on-fly-create-folder-title" value="" type="text" placeholder="<?php esc_attr_e( 'Enter Folder Title', 'buddyboss' ); ?>">
		<small class="error-box"><?php _e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
	</div>
	<?php
	if ( ! bp_is_group() ) :
		bp_get_template_part( 'document/document-privacy' );
	endif;
	?>
	<div class="db-modal-buttons">
		<a class="bb-rl-close-create-popup-folder" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
		<a class="button bb-rl-document-create-popup-folder-submit" href="#"><?php esc_html_e( 'Create', 'buddyboss' ); ?></a>
	</div>
</div>
