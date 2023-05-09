<?php
/**
 * The template for document create child folder
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/create-child-folder.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

global $document_folder_template;
$folder_id = 0;
if ( function_exists( 'bp_is_group_single' ) && bp_is_group_single() && bp_is_group_folders() ) {
	$action_variables = bp_action_variables();
	$folder_id         = (int) $action_variables[1];
} else {
	$folder_id = (int) bp_action_variable( 0 );
}
?>

<div id="bp-media-create-child-folder" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
					</header>
					<div class="bb-field-wrap">
						<label for="bb-album-child-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
						<input id="bb-album-child-title" value="" type="text" placeholder="<?php esc_html_e( 'Enter Folder Title', 'buddyboss' ); ?>" />
						<small class="error-box"><?php _e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
					</div>
					<footer class="bb-model-footer">
						<a class="button" id="bp-media-create-child-folder-submit" href="#"><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
