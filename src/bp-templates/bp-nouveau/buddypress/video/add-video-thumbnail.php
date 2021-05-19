<?php
/**
 * BuddyBoss - Add/Edit Video Thumbnail
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.5.7
 */

?>
<div class="bp-video-thumbnail-uploader <?php echo bb_video_is_ffmpeg_installed() ? 'generating_thumb ' : ''; ?>" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">

					<header class="bb-model-header">
						<a href="#" class="bp-video-thumbnail-upload-tab bp-thumbnail-upload-tab selected bp-video-thumbnail-uploader-modal-title" data-content="bp-video-thumbnail-dropzone-content" id="">
							<?php esc_html_e( 'Add Thumbnail', 'buddyboss' ); ?>
						</a>
						<span class="thumbnail_notice"><?php esc_html_e( '(Generating thumbnails... Please check again in a few minutes.)', 'buddyboss' ); ?></span>

						<span id="bp-video-thumbnail-uploader-modal-status-text" style="display: none;"></span>

						<a class="bb-model-close-button bp-video-thumbnail-uploader-close" id="" href="#">
							<span class="bb-icon bb-icon-close"></span>
						</a>
					</header>

					<p><?php esc_html_e( 'Select or upload a picture that shows what’s in your video. A good thumbnail stands out and draws viewer’s attention.', 'buddyboss' ); ?></p>

					<div class="video-thumbnail-content">

						<div class="bb-dropzone-wrap bp-video-thumbnail-upload-tab-content bp-upload-tab-content bp-video-thumbnail-dropzone-content" id="">
							<div class="bb-field-wrap">
								<div class="video-thumbnail-uploader-wrapper">
									<div class="dropzone video-thumbnail-uploader-dropzone-select" id=""></div>
								</div>
							</div>
							<div class="video-thumbnail-custom" style="display:none;">
								<span class="close-thumbnail-custom"></span>
								<img src="" alt="" />
							</div>
							<div class="bb-action-check-wrap" id="custom_image_ele">
								<input id="bb-video-5711" class="bb-custom-check" type="radio" value="5766" name="bb-video-thumbnail-select">
								<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="Select" for="bb-video-5711">
									<span class="bb-icon bb-icon-check"></span>
								</label>
							</div>
						</div>


						<div class="bp-video-thumbnail-auto-generated">
							<ul class="video-thumb-list loading">
								<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3"></li>
								<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3"></li>
							</ul>
						</div>

					</div>

					<input type="hidden" value="" class="video-edit-thumbnail-hidden-video-id">
					<input type="hidden" value="" class="video-edit-thumbnail-hidden-attachment-id">

					<footer class="bb-model-footer flex align-items-center">
						<a class="button push-right bp-video-thumbnail-submit" id="" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
					</footer>

				</div>
			</div>
		</div>
	</transition>
</div>
