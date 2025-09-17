<?php
/**
 * The template for document create folder
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/document-create-folder.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

?>

<div class="create-popup-folder-wrap popup-on-fly-create-folder" style="display: none;">

	<div class="bb-field-wrap">
		<label for="new_folder_name_input" class="bb-label"><?php esc_html_e( 'Folder Title', 'buddyboss' ); ?></label>
		<input class="popup-on-fly-create-folder-title" value="" type="text" placeholder="<?php esc_attr_e( 'Enter Folder Title', 'buddyboss' ); ?>">
		<small class="error-box"><?php _e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
	</div>
	<?php
	if ( ! bp_is_group() ) :
		bp_get_template_part( 'document/document-privacy' );
	endif;
	?>
	<div class="db-modal-buttons">
		<a class="close-create-popup-folder" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
		<a class="button bp-document-create-popup-folder-submit" href="#"><?php esc_html_e( 'Create', 'buddyboss' ); ?></a>
	</div>
</div>
