<?php
/**
 * BuddyBoss - Document Uploader
 *
 * @since BuddyBoss 1.0.0
 * @package BuddyBoss\Core
 */

?>
<div id="bp-media-uploader" class="bp-media-document-uploader" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container has-folderlocationUI">
					<header class="bb-model-header bg-white">
						<a href="#" class="bp-media-upload-tab selected" data-content="bp-dropzone-content" id="bp-media-uploader-modal-title"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>
						<span id="bp-media-uploader-modal-status-text" style="display: none;"></span>
						<a class="bb-model-close-button" id="bp-media-uploader-close" href="#">
							<span class="dashicons dashicons-no-alt"></span>
						</a>
					</header>
					<div class="bb-field-steps bb-field-steps-1">
						<div class="bb-field-wrap">
							<div class="bb-dropzone-wrap bp-media-upload-tab-content" id="bp-dropzone-content">
								<?php if ( bp_is_active( 'forums' ) && ! bbp_is_single_forum() && ! bbp_is_single_topic() && ! bp_is_messages_component() ) : ?>
								<div class="media-uploader-post-content">
									<textarea name="bp-media-post-content" id="bp-media-post-content" placeholder="<?php bp_is_group() ? esc_html_e( 'Write something about your documents, to be shown on the group feed', 'buddyboss' ) : esc_html_e( 'Write something about your documents, to be shown on your timeline', 'buddyboss' ); ?>"></textarea>
								</div>
								<?php endif; ?>
								<div class="media-uploader-wrapper">
									<div class="dropzone" id="media-uploader"></div>
								</div>
							</div>
						</div>
						<a id="bp-media-document-next" class="button bb-field-uploader-next bb-field-uploader-actions pull-right" href="#"><?php esc_html_e( 'Next', 'buddyboss' ); ?></a>
					</div>
					<div class="bb-field-steps bb-field-steps-2">
						<label for="bb-album-child-title" class="bb-label"><?php esc_html_e( 'Destination Folder', 'buddyboss' ); ?></label>
						<div class="bb-field-wrap bb-field-wrap-search">
							<input type="text" class="ac_document_search_folder" value="" placeholder="<?php esc_html_e( 'Search Folders', 'buddyboss' ); ?>" />
						</div>
						<div class="bb-field-wrap">
							<?php bp_get_template_part( 'document/location-move' ); ?>
							<?php bp_get_template_part( 'document/document-create-folder' ); ?>
						</div>
					</div>
					<footer class="flex align-items-center bb-model-footer document-uploader-footer">
						<a href="#" class="bp-document-open-create-popup-folder" style="display: none;" > <i class="bb-icon-plus-square"></i> <?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></a>
						<a id="bp-media-document-prev" class="button bb-uploader-steps-prev bb-field-uploader-actions" href="#"><?php esc_html_e( 'Previous', 'buddyboss' ); ?></a>
						<a class="button" id="bp-media-document-submit" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
