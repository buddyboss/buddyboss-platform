<?php
/**
 * The template for document uploader
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/document-uploader.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.8.6
 */

?>

<div id="bp-media-uploader" class="bp-media-document-uploader" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
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
									<textarea name="bp-media-post-content" id="bp-media-post-content" placeholder="<?php bp_is_group() ? esc_html_e( 'Write something about your documents, to be shown on the group feed', 'buddyboss' ) : esc_html_e( 'Write something about your documents, to be shown on your timeline', 'buddyboss' ); ?>"></textarea>
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
												<i class="bb-icon-f bb-icon-file-attach"></i>
												<svg class="dz-progress-ring" width="54" height="54">
													<circle class="progress-ring__circle" stroke="white" stroke-width="3" fill="transparent" r="24.5" cx="27" cy="27" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
												</svg>
											</div>
											<div class="dz-error-message"><span data-dz-errormessage></span></div>
											<div class="dz-error-mark">
												<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>Error</title>
													<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
														<g stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF" fill-opacity="0.816519475">
															<path d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"></path>
														</g>
													</g>
												</svg>
											</div>
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
						<a href="#" class="bp-document-open-create-popup-folder" style="display: none;" >
							<i class="bb-icon-l bb-icon-plus"></i>
							<?php esc_html_e( 'Create new folder', 'buddyboss' ); ?>
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
						<a class="button pull-right" id="bp-media-document-submit" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
						<a id="bp-media-document-prev" class="bb-uploader-steps-prev bb-field-uploader-actions" href="#"><?php esc_html_e( 'Back', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
