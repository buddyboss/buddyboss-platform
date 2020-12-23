<?php
/**
 * BuddyBoss - Add/Edit Video Thumbnail
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

?>
<div id="bp-video-thumbnail-uploader" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">

					<header class="bb-model-header">
						<a href="#" class="bp-video-thumbnail-upload-tab bp-thumbnail-upload-tab selected" data-content="bp-video-thumbnail-dropzone-content" id="bp-video-thumbnail-uploader-modal-title">
							<?php esc_html_e( 'Add Thumbnail', 'buddyboss' ); ?>
						</a>

						<span id="bp-video-thumbnail-uploader-modal-status-text" style="display: none;"></span>

						<a class="bb-model-close-button" id="bp-video-thumbnail-uploader-close" href="#">
							<span class="bb-icon bb-icon-close"></span>
						</a>
					</header>

					<div class="bb-dropzone-wrap bp-video-thumbnail-upload-tab-content bp-upload-tab-content" id="bp-video-thumbnail-dropzone-content">
						<div class="bb-field-wrap">
							<div class="video-thumbnail-uploader-wrapper">
								<div class="dropzone" id="video-thumbnail-uploader"></div>
							</div>
						</div>
					</div>


					<div class="bp-video-thumbnail-auto-generated">
						<ul class="video-thumb-list"></ul>
					</div>

					<footer class="bb-model-footer flex align-items-center">
						<a class="button push-right" id="bp-video-thumbnail-submit" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
					</footer>

				</div>
			</div>
		</div>
	</transition>
</div>
