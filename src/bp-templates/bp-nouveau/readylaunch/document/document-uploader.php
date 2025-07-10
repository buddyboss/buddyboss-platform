<?php
/**
 * The template for document uploader
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>

<div id="bp-media-uploader" class="bp-media-document-uploader" style="display: none;">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<a href="#" class="bp-media-upload-tab bp-upload-tab selected" data-content="bp-dropzone-content" id="bp-media-uploader-modal-title"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>
						<span id="bp-media-uploader-modal-status-text" style="display: none;"></span>
						<a class="bb-model-close-button" id="bp-media-uploader-close" href="#">
							<span class="bb-icon-l bb-icon-times"></span>
						</a>
					</header>
					<div class="bb-field-steps bb-field-steps-1">
						<div class="bb-field-wrap">
							<div class="bb-dropzone-wrap bp-media-upload-tab-content" id="bp-dropzone-content">
								<?php
								if (
									(
										! bp_is_active( 'forums' ) ||
										( bp_is_active( 'forums' ) && ! bbp_is_single_forum() && ! bbp_is_single_topic() )
									) &&
									! bp_is_messages_component() &&
									bp_is_active( 'activity' )
								) :
									?>
								<div class="media-uploader-post-content">
									<textarea name="bp-media-post-content" id="bp-media-post-content" placeholder="<?php esc_html_e( 'Write a description', 'buddyboss' ); ?>"></textarea>
								</div>
								<?php endif; ?>
								<div class="media-uploader-wrapper">
									<div class="dropzone document-dropzone" id="media-uploader"></div>
									<div class="uploader-post-document-template" style="display:none;">
										<div class="dz-preview dz-file-preview">
											<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
											<div class="dz-details">
												<div class="dz-icon"><span class="bb-icon-l bb-icon-file"></span></div>
												<div class="dz-filename"><span data-dz-name></span></div>
												<div class="dz-size" data-dz-size></div>
											</div>
											<div class="dz-progress-ring-wrap">
												<i class="bb-icons-rl-fill bb-icons-rl-paperclip"></i>
												<svg class="dz-progress-ring" width="48" height="48">
													<circle class="progress-ring__circle" stroke="#4946FE" stroke-width="3" fill="transparent" r="21.5" cx="24" cy="24" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
												</svg>
											</div>
											<div class="dz-error-message"><span data-dz-errormessage></span></div>
										</div>
									</div>
								</div>
								<a id="bp-media-document-next" class="bb-field-uploader-next bb-field-uploader-actions" href="#">
									<i class="bb-icon-l bb-icon-folder"></i>
									<?php esc_html_e( 'Select Folder', 'buddyboss' ); ?>
								</a>
							</div>
						</div>
					</div>
					<div class="bb-field-steps bb-field-steps-2">
						<div class="bb-field-wrap">
							<?php bp_get_template_part( 'document/location-move' ); ?>
							<?php bp_get_template_part( 'document/document-create-folder' ); ?>
						</div>
					</div>
					<footer class="bb-model-footer document-uploader-footer">
						<a href="#" class="bb-rl-document-open-create-popup-folder" style="display: none;" >
							<i class="bb-icons-rl-plus"></i>
							<?php esc_html_e( 'Create Folder', 'buddyboss' ); ?>
						</a>
						<?php if ( ! bp_is_group() ) : ?>
							<div class="bb-dropdown-wrap">
								<select id="bb-document-privacy">
									<?php
									foreach ( bp_document_get_visibility_levels() as $k => $option ) {
										if ( 'grouponly' === $k ) {
											continue;
										}
										?>
										<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_attr( $option ); ?></option>
										<?php
									}
									?>
								</select>
							</div>
						<?php endif; ?>
						<a class="button pull-right bb-rl-button bb-rl-button--brandFill bb-rl-button--small" id="bp-media-document-submit" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
						<a id="bp-media-document-prev" class="bb-uploader-steps-prev bb-field-uploader-actions" href="#"><?php esc_html_e( 'Back', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
