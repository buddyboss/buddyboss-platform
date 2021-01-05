<?php
/**
 * BuddyBoss - Video Uploader
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

?>
<div id="bp-video-uploader" style="display: none;" class="bp-video-uploader open-popup">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container has-folderlocationUI">

					<header class="bb-model-header">
						<a href="#" class="bp-video-upload-tab bp-upload-tab selected" data-content="bp-video-dropzone-content" id="bp-video-uploader-modal-title"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>

						<span id="bp-video-uploader-modal-status-text" style="display: none;"></span>

						<a class="bb-model-close-button" id="bp-video-uploader-close" href="#">
							<span class="bb-icon bb-icon-close"></span>
						</a>
					</header>

					<div class="bb-field-steps bb-field-steps-1">

						<div class="bb-dropzone-wrap bp-video-upload-tab-content bp-upload-tab-content" id="bp-video-dropzone-content">
							<div class="bb-field-wrap">
								<?php if ( bp_is_active( 'forums' ) && ! bbp_is_single_forum() && ! bbp_is_single_topic() && ! bp_is_messages_component() && bp_is_active( 'activity' ) ) : ?>
									<div class="video-uploader-post-content">
										<textarea name="bp-video-post-content" id="bp-video-post-content" placeholder="<?php bp_is_group() ? esc_html_e( 'Write something about your videos, to be shown on the group feed', 'buddyboss' ) : esc_html_e( 'Write something about your videos, to be shown on your timeline', 'buddyboss' ); ?>"></textarea>
									</div>
								<?php endif; ?>
								<div class="video-uploader-wrapper">
									<div class="dropzone" id="video-uploader"></div>
								</div>
								<?php
								if ( bp_is_active( 'groups' ) && bp_is_group_single() && bp_is_group_albums_support_enabled() ) {
									?>
									<a id="bp-video-next" class="bb-field-uploader-next bb-field-uploader-actions" href="#">
										<i class="bb-icon-folder"></i>
										<?php esc_html_e( 'Select Album', 'buddyboss' ); ?>
									</a>
									<?php
								} elseif ( bp_is_profile_albums_support_enabled() ) {
									?>
									<a id="bp-video-next" class="bb-field-uploader-next bb-field-uploader-actions" href="#">
										<i class="bb-icon-folder"></i>
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
							<?php bp_get_template_part( 'video/location-move' ); ?>
							<?php bp_get_template_part( 'video/video-create-album' ); ?>
						</div>
					</div>

					<footer class="bb-model-footer video-uploader-footer">
						<a href="#" class="bp-video-open-create-popup-album" style="display: none;">
							<i class="bb-icon-plus"></i>
							Create new album
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

						<a class="button push-right" id="bp-video-submit" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
						<a id="bp-video-prev" class="bb-uploader-steps-prev bb-field-uploader-actions" href="#"><?php esc_html_e( 'Back', 'buddyboss' ); ?></a>
					</footer>

				</div>
			</div>
		</div>
	</transition>
</div>
