<?php
/**
 * ReadyLaunch - Add Video Thumbnail template.
 *
 * Template for adding or changing video thumbnails.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-rl-video-thumbnail-uploader <?php echo bb_video_is_ffmpeg_installed() ? 'generating_thumb ' : 'no_ffmpeg'; ?>" style="display: none;">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div class="bb-rl-modal-container">
					<header class="bb-rl-modal-header">
						<h4 class="bb-rl-video-thumbnail-upload-tab bb-rl-thumbnail-upload-tab selected bb-rl-video-thumbnail-uploader-modal-title" data-content="bp-video-thumbnail-dropzone-content">
							<?php esc_html_e( 'Change Thumbnail', 'buddyboss' ); ?>
						</h4>
						<span id="bb-rl-video-thumbnail-uploader-modal-status-text" style="display: none;"></span>
						<a class="bb-rl-modal-close-button bb-rl-video-thumbnail-uploader-close" id="" href="#">
							<span class="bb-icons-rl-x"></span>
						</a>
					</header>
					<div class="bb-rl-video-thumbnail-content bb-rl-modal-content">
						<div class="bb-rl-video-thumbnail-auto-generated">
							<ul class="bb-rl-video-thumb-list loading">
								<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb_rl_thumb_loader">
									<div class="bb-rl-video-thumb-block">
										<i class="bb-rl-loader"></i>
										<span><?php esc_html_e( 'Generating thumbnail…', 'buddyboss' ); ?></span>
									</div>
								</li>
								<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb_rl_thumb_loader">
									<div class="bb-rl-video-thumb-block">
										<i class="bb-rl-loader"></i>
										<span><?php esc_html_e( 'Generating thumbnail…', 'buddyboss' ); ?></span>
									</div>
								</li>
							</ul>
						</div>
						<div class="bb-dropzone-wrap bb-rl-video-thumbnail-upload-tab-content bb-rl-upload-tab-content bb-rl-video-thumbnail-dropzone-content" id="bb_rl_custom_image_ele">
							<input id="bb-video-5711" class="bb-rl-custom-check" type="radio" value="5766" name="bb-video-thumbnail-select">
							<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="Select" for="bb-video-5711">
								<span class="bb-icon-l bb-icon-check"></span>
							</label>
							<div class="bb-rl-field-wrap">
								<div class="bb-rl-video-thumbnail-uploader-wrapper">
									<div class="dropzone video-dropzone bb-rl-video-thumbnail-uploader-dropzone-select" id=""></div>
									<div class="bb-rl-uploader-post-video-thumbnail-template" style="display:none;">
										<div class="dz-preview dz-file-preview">
											<div class="dz-image">
												<img data-dz-thumbnail />
											</div>
											<div class="dz-details">
												<div class="dz-filename" data-dz-name=""></div>
											</div>
											<div class="dz-progress-ring-wrap">
												<i class="bb-icons-rl-fill bb-icons-rl-camera"></i>
												<svg class="dz-progress-ring" width="48" height="48">
													<circle class="progress-ring__circle" stroke="#4946FE" stroke-width="3" fill="transparent" r="21.5" cx="24" cy="24" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
												</svg>
											</div>
											<div class="dz-error-message"><span data-dz-errormessage></span></div>
											<div class="dz-error-mark">
												<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
													<title>Error</title>
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
							</div>
							<div class="bb-rl-video-thumbnail-custom" style="display:none;">
								<span class="bb-rl-close-thumbnail-custom"></span>
								<img src="" alt="" />
							</div>
						</div>
					</div>
					<input type="hidden" value="" class="bb-rl-video-edit-thumbnail-hidden-video-id">
					<input type="hidden" value="" class="bb-rl-video-edit-thumbnail-hidden-attachment-id">
					<footer class="bb-rl-model-footer flex align-items-center">
						<a class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small bb-rl-video-thumbnail-submit is-disabled" id="" href="#"><?php esc_html_e( 'Change', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
