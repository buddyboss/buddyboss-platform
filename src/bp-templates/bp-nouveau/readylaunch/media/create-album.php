<?php
/**
 * ReadyLaunch - Create Album template.
 *
 * This template handles the create album modal and functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div id="bp-media-create-album" style="display: none;">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container">

					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Create new album', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button" id="bp-media-create-album-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
					</header>

					<div class="bb-field-wrap">
						<label for="bb-album-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
						<input id="bb-album-title" type="text" placeholder="<?php esc_html_e( 'Enter album title', 'buddyboss' ); ?>" />
					</div>

					<?php
					if (
						( bp_is_group() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_group_id() ) ) ||
						( ( bp_is_my_profile() || bp_is_user_media() ) && bb_user_can_create_media() )
					) {
						?>
						<div class="bb-field-wrap">
							<div class="media-uploader-wrapper">
								<div class="dropzone media-dropzone" id="media-uploader"></div>
								<div class="uploader-post-media-template" style="display:none;">
									<div class="dz-preview">
										<div class="dz-image">
											<img data-dz-thumbnail />
										</div>
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
						</div>
					<?php } ?>

					<footer class="bb-model-footer">
						<?php if ( ! bp_is_group() ) : ?>
							<div class="bb-dropdown-wrap">
								<select id="bb-album-privacy">
									<?php
									foreach ( bp_media_get_visibility_levels() as $k => $option ) {
										?>
										<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $option ); ?></option>
										<?php
									}
									?>
								</select>
							</div>
						<?php endif; ?>
						<a class="button" id="bp-media-create-album-submit" href="#"><?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
					</footer>

				</div>
			</div>
		</div>
	</transition>
</div>
