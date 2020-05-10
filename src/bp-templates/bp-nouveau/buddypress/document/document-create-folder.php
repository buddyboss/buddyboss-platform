<?php

/**
 * BuddyBoss - Document Create Folder
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

?>

<div class="create-popup-folder-wrap popup-on-fly-create-folder" style="display: none;">
	<input class="popup-on-fly-create-folder-title" value="" type="text" placeholder="<?php esc_attr_e( 'Enter Folder Title', 'buddyboss' ); ?>" id="new_folder_name_input">
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
