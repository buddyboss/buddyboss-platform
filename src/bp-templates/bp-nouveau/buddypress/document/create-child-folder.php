<?php
/**
 * BuddyBoss - Document Create Child Folder
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

global $media_album_template;
$album_id = 0;
if ( function_exists( 'bp_is_group_single' ) && bp_is_group_single() && bp_is_group_folders() ) {
	$action_variables = bp_action_variables();
	$album_id         = (int) $action_variables[1];
} else {
	$album_id = (int) bp_action_variable( 0 );
}
?>

<div id="bp-media-create-child-folder" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="dashicons dashicons-no-alt"></span></a>
					</header>
					<div class="bb-field-steps bb-field-steps-1">
						<div class="bb-field-wrap">
							<label for="bb-album-child-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
							<input id="bb-album-child-title" type="text" placeholder="<?php esc_html_e( 'Enter Folder Title', 'buddyboss' ); ?>" />
						</div>
						<div class="bb-field-wrap">
							<div class="media-uploader-wrapper">
								<div class="dropzone" id="media-uploader-child-folder"></div>
							</div>
						</div>
						<?php
						if ( ! bp_is_group() ) :
							bp_get_template_part( 'document/document-privacy' );
						endif;
						?>
						<a class="button bb-field-steps-next bb-field-steps-actions" href="#"><?php esc_html_e( 'Next', 'buddyboss' ); ?></a>
					</div>
					<div class="bb-field-steps bb-field-steps-2">
						<div class="bb-field-wrap bb-field-wrap-search">
							<label for="bb-album-child-title" class="bb-label"><?php esc_html_e( 'Destination Folder', 'buddyboss' ); ?></label>
							<input type="text" class="ac_document_search_folder" value="" placeholder="<?php esc_html_e( 'Search Folders', 'buddyboss' ); ?>" />
						</div>
						<div class="bb-field-wrap">
							<?php bp_get_template_part( 'document/location-move' ); ?>
						</div>
						<footer class="bb-model-footer">
							<a class="button bb-field-steps-previous bb-field-steps-actions" href="#"><?php esc_html_e( 'Previous', 'buddyboss' ); ?></a>
							<input type="hidden" class="parent_id" id="parent_id" name="parent_id" value="<?php echo esc_attr( $album_id ); ?>">
							<a class="button" id="bp-media-create-child-folder-submit" href="#"><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></a>
						</footer>
					</div>
				</div>
			</div>
		</div>
	</transition>
</div>
