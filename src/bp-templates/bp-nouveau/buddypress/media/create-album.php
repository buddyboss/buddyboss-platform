<?php
/**
 * The template for media albums create
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/create-album.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */
?>

<div id="bp-media-create-album" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container">

					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Create Album', 'buddyboss' ); ?></h4>
						<a class="bb-model-close-button" id="bp-media-create-album-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a>
					</header>

					<div class="bb-field-wrap">
						<label for="bb-album-title" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
						<input id="bb-album-title" type="text" placeholder="<?php esc_html_e( 'Enter Album Title', 'buddyboss' ); ?>" />
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
											<i class="bb-icon-f bb-icon-camera"></i>
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
