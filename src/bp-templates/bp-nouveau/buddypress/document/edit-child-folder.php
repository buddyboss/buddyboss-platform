<?php
/**
 * The template for document edit child folder
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/edit-child-folder.php.
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
<div id="bp-media-edit-child-folder" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Edit Folder', 'buddyboss' ); ?> '<?php bp_folder_title(); ?>'</h4>
						<a class="bb-model-close-button" id="bp-media-edit-folder-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
					</header>
						<div class="bb-field-wrap">
							<label for="bb-album-child-title" class="bb-label"><?php esc_html_e( 'Rename Folder', 'buddyboss' ); ?></label>
							<input id="bb-album-child-title" type="text" value="<?php bp_folder_title(); ?>" placeholder="<?php esc_html_e( 'Enter Folder Title', 'buddyboss' ); ?>"/>
							<small class="error-box"><?php _e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
						</div>
						<?php
						$current_folder = bp_get_folder_id();
						$folder_id = (int) bp_document_get_root_parent_id( bp_get_folder_id() );
						$folder    = new BP_Document_Folder( $folder_id );
						if ( ! bp_is_group() && (int) $folder->id === $current_folder ) :
							bp_get_template_part( 'document/document-privacy' );
						endif;
						?>
					<footer class="bb-model-footer">
						<input type="hidden" class="parent_id" id="parent_id" name="parent_id" value="<?php echo esc_attr( $folder_id ); ?>">
						<a class="button pull-right" id="bp-media-edit-child-folder-submit" href="#"><?php esc_html_e( 'Save', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
