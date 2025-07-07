<?php
/**
 * ReadyLaunch - Video Uploader template.
 *
 * Video uploader interface for uploading videos.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div id="bp-video-uploader" style="display: none;" class="bp-video-uploader open-popup">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div class="modal-container has-folderlocationUI">

					<header class="bb-model-header">
						<a href="#" class="bp-video-upload-tab bp-upload-tab selected" data-content="bp-video-dropzone-content" id="bp-video-uploader-modal-title"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>

						<span id="bp-video-uploader-modal-status-text" style="display: none;"></span>

						<a class="bb-model-close-button" id="bp-video-uploader-close" href="#">
							<span class="bb-icon-l bb-icon-times"></span>
						</a>
					</header>

					<div class="bb-field-steps bb-field-steps-1">

						<div class="bb-dropzone-wrap bp-video-upload-tab-content bp-upload-tab-content" id="bp-video-dropzone-content">
							<div class="bb-field-wrap">
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
									<div class="video-uploader-post-content">
										<textarea name="bp-video-post-content" id="bp-video-post-content" placeholder="<?php esc_html_e( 'Write a description', 'buddyboss' ); ?>"></textarea>
									</div>
								<?php endif; ?>
								<div class="video-uploader-wrapper">
									<div class="dropzone video-dropzone" id="video-uploader"></div>
									<div class="uploader-post-video-template" style="display:none;">
										<div class="dz-preview dz-file-preview well" id="dz-preview-template">
											<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
											<div class="dz-progress-ring-wrap">
												<i class="bb-icons-rl-fill bb-icons-rl-video-camera"></i>
												<svg class="dz-progress-ring" width="48" height="48">
													<circle class="progress-ring__circle" stroke="#4946FE" stroke-width="3" fill="transparent" r="21.5" cx="24" cy="24" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
												</svg>
											</div>
											<!-- <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div> -->
											<div class="dz-error-message"><span data-dz-errormessage></span></div>
											<div class="dz-details">
												<div class="dz-progress dz-progress-count"><?php esc_html_e( '0% Uploaded', 'buddyboss' ); ?></div>
												<div class="dz-filename"><span data-dz-name></span></div>
											</div>
											<div class="dz-video-thumbnail"></div>
										</div>
									</div>
								</div>
								<?php
								if ( bp_is_active( 'groups' ) && bp_is_group_single() && bp_is_group_albums_support_enabled() ) {
									?>
									<a id="bp-video-next" class="bb-field-uploader-next bb-field-uploader-actions" href="#">
										<i class="bb-icons-rl-plus"></i>
										<?php esc_html_e( 'Select Album', 'buddyboss' ); ?>
									</a>
									<?php
								} elseif ( bp_is_profile_albums_support_enabled() ) {
									?>
									<a id="bp-video-next" class="bb-field-uploader-next bb-field-uploader-actions" href="#">
										<i class="bb-icon-l bb-icon-folder"></i>
										<?php esc_html_e( 'Select Album', 'buddyboss' ); ?>
									</a>
									<?php
								}
								?>
							</div>
						</div>

					</div>

					<div class="bb-field-steps bb-field-steps-2">
						<div class="bb-field-wrap">
							<?php
							bp_get_template_part( 'video/location-move' );
							bp_get_template_part( 'video/video-create-album' );
							?>
						</div>
					</div>

					<footer class="bb-model-footer video-uploader-footer">
						<a href="#" class="bb-rl-video-open-create-popup-album create-album" style="display: none;">
							<i class="bb-icons-rl-plus"></i>
							<?php esc_html_e( 'Create Album', 'buddyboss' ); ?>
						</a>

						<?php if ( ! bp_is_group() && ! bp_is_single_album() ) : ?>
							<div class="bb-dropdown-wrap">
								<select id="bb-video-privacy">
									<?php
									foreach ( bp_video_get_visibility_levels() as $k => $option ) {
										?>
										<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_attr( $option ); ?></option>
										<?php
									}
									?>
								</select>
							</div>
						<?php endif; ?>

						<a class="button push-right bb-rl-button bb-rl-button--brandFill bb-rl-button--small" id="bp-video-submit" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
						<a id="bp-video-prev" class="bb-uploader-steps-prev bb-field-uploader-actions bb-rl-button--secondaryFill bb-rl-button--small" href="#"><?php esc_html_e( 'Back', 'buddyboss' ); ?></a>
					</footer>

				</div>
			</div>
		</div>
	</transition>
</div>
