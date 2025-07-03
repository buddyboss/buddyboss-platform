<?php
/**
 * ReadyLaunch - Media Uploader template.
 *
 * This template handles the media upload modal and functionality for the ReadyLaunch theme.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_is_single_album = bp_is_single_album();
?>
<div id="bp-media-uploader" style="display: none;" class="bp-media-photo-uploader">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div class="modal-container has-folderlocationUI">

					<header class="bb-model-header">
						<a href="#" class="bp-media-upload-tab  bp-upload-tab selected" data-content="bp-dropzone-content" id="bp-media-uploader-modal-title"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>

						<?php if ( $bp_is_single_album ) : ?>
							<a href="#" class="bp-media-upload-tab  bp-upload-tab" data-content="bp-existing-media-content" id="bp-media-select-from-existing"><?php esc_html_e( 'Select Photos', 'buddyboss' ); ?></a>
						<?php endif; ?>

						<span id="bp-media-uploader-modal-status-text" style="display: none;"></span>

						<a class="bb-model-close-button" id="bp-media-uploader-close" href="#">
							<span class="bb-icon-l bb-icon-times"></span>
						</a>
					</header>

					<div class="bb-field-steps bb-field-steps-1">
						<div class="bb-dropzone-wrap bp-media-upload-tab-content bp-upload-tab-content" id="bp-dropzone-content">
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

							<div class="bb-field-wrap">
								<div class="media-uploader-wrapper">
									<div class="dropzone media-dropzone" id="media-uploader"></div>
									<div class="uploader-post-media-template" style="display:none;">
										<div class="dz-preview">
											<div class="dz-image">
												<img data-dz-thumbnail />
											</div>
											<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
											<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
											<div class="dz-details">
												<div class="dz-filename"><span data-dz-name></span></div>
												<div class="dz-size" data-dz-size></div>
											</div>
											<div class="dz-progress-ring-wrap">
												<i class="bb-icons-rl-fill bb-icons-rl-camera"></i>
												<svg class="dz-progress-ring" width="48" height="48">
													<circle class="progress-ring__circle" stroke="#4946FE" stroke-width="3" fill="transparent" r="21.5" cx="24" cy="24" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
												</svg>
											</div>
											<div class="dz-error-message"><span data-dz-errormessage></span></div>
										</div>
									</div>
								</div>
								<?php
								if ( bp_is_profile_albums_support_enabled() ) {
									?>
									<a id="bp-media-photo-next" class="bb-field-uploader-next bb-field-uploader-actions" href="#">
										<i class="bb-icons-rl-plus"></i>
										<?php esc_html_e( 'Select Album', 'buddyboss' ); ?>
									</a>
									<?php
								}
								?>
							</div>

						</div>
					</div>

					<?php if ( $bp_is_single_album ) { ?>
						<div class="bp-existing-media-wrap bp-media-upload-tab-content bp-upload-tab-content" id="bp-existing-media-content" style="display: none;">
							<?php if ( bp_has_media( array( 'album_id' => 'existing-media' ) ) ) { ?>
								<ul class="media-list item-list bp-list bb-photo-list grid existing-media-list">
									<?php
									while ( bp_media() ) :
										bp_the_media();

										bp_get_template_part( 'media/entry' );
									endwhile;

									if ( bp_media_has_more_items() ) :
										?>
										<li class="load-more">
											<a class="button outline" href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
										</li>
									<?php endif; ?>
								</ul>
								<?php
							} else {
								bp_get_template_part( 'media/no-media' );
							}
							?>
						</div>
					<?php } ?>

					<div class="bb-field-steps bb-field-steps-2">
						<div class="bb-field-wrap">
							<?php bp_get_template_part( 'media/location-move' ); ?>
							<?php bp_get_template_part( 'media/media-create-album' ); ?>
						</div>
					</div>

					<footer class="bb-model-footer media-uploader-footer">
						<a href="#" class="bb-rl-media-open-create-popup-folder" style="display: none;">
							<i class="bb-icons-rl-plus"></i>
							<?php esc_html_e( 'Create Album', 'buddyboss' ); ?>
						</a>
						<?php if ( ! bp_is_group() ) : ?>
							<div class="bb-dropdown-wrap">
								<select id="bb-media-privacy">
									<?php
									foreach ( bp_media_get_visibility_levels() as $k => $option ) {
										?>
										<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_attr( $option ); ?></option>
										<?php
									}
									?>
								</select>
							</div>
						<?php endif; ?>
						<a class="button push-right" id="bp-media-submit" style="display: none;" href="#"><?php esc_html_e( 'Done', 'buddyboss' ); ?></a>
						<a id="bp-media-prev" class="bb-uploader-steps-prev bb-field-uploader-actions" href="#"><?php esc_html_e( 'Back', 'buddyboss' ); ?></a>
					</footer>

				</div>
			</div>
		</div>
	</transition>
</div>
