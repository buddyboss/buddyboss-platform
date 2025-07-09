<?php
/**
 * ReadyLaunch - Document create child folder template.
 *
 * This template handles the modal interface for creating new child folders
 * with title input and validation.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $document_folder_template;
$folder_id = 0;
if ( function_exists( 'bp_is_group_single' ) && bp_is_group_single() && bp_is_group_folders() ) {
	$action_variables = bp_action_variables();
	$folder_id        = (int) $action_variables[1];
} else {
	$folder_id = (int) bp_action_variable( 0 );
}
?>

<div id="bp-media-create-child-folder" style="display: none;">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper bb-rl-modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
					</header>
					<div class="bb-field-wrap">
						<label for="bb-album-child-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
						<input id="bb-album-child-title" value="" type="text" placeholder="<?php esc_html_e( 'Enter folder title', 'buddyboss' ); ?>" />
						<small class="error-box"><?php esc_html_e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
					</div>
					<footer class="bb-model-footer">
						<a class="button bb-rl-button bb-rl-button--brandFill bb-rl-button--small" id="bp-media-create-child-folder-submit" href="#"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
