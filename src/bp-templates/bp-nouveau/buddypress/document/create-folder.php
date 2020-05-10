<?php
/**
 * BuddyBoss - Document Folder Create
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

?>

<div id="bp-media-create-folder" style="display: none;">
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
							<label for="bb-album-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
							<input id="bb-album-title" type="text" placeholder="<?php esc_html_e( 'Enter Folder Title', 'buddyboss' ); ?>" />
						</div>
						<div class="bb-field-wrap">
							<div class="media-uploader-wrapper">
								<div class="dropzone" id="media-uploader-folder"></div>
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
						<label for="bb-album-child-title" class="bb-label"><?php esc_html_e( 'Destination Folder', 'buddyboss' ); ?></label>
						<div class="bb-field-wrap bb-field-wrap-search">
							<input type="text" class="ac_document_search_folder" value="" placeholder="<?php esc_html_e( 'Search Folders', 'buddyboss' ); ?>" />
						</div>
						<div class="bb-field-wrap">
							<?php bp_get_template_part( 'document/location-move' ); ?>
						</div>
						<footer class="bb-model-footer">
							<a class="button bb-field-steps-previous bb-field-steps-actions" href="#"><?php esc_html_e( 'Previous', 'buddyboss' ); ?></a>
							<a class="button" id="bp-media-create-folder-submit" href="#"><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></a>
						</footer>
					</div>
				</div>
			</div>
		</div>
	</transition>
</div>
