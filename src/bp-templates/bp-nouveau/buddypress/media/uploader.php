<?php
/**
 * BuddyBoss - Media Uploader
 *
 * @since BuddyBoss 1.0.0
 */
?>
<div id="bp-media-uploader" style="display: none;" class="bp-media-photo-uploader">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container has-folderlocationUI">

					<header class="bb-model-header">
						<a href="#" class="bp-media-upload-tab selected" data-content="bp-dropzone-content" id="bp-media-uploader-modal-title"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>

						<?php if ( bp_is_single_album() ) : ?>
							<a href="#" class="bp-media-upload-tab" data-content="bp-existing-media-content" id="bp-media-select-from-existing"><?php esc_html_e( 'Select Photos', 'buddyboss' ); ?></a>
						<?php endif; ?>

						<span id="bp-media-uploader-modal-status-text" style="display: none;"></span>

						<a class="bb-model-close-button" id="bp-media-uploader-close" href="#">
							<span class="bb-icon bb-icon-close"></span>
						</a>
					</header>

					<div class="bb-field-steps bb-field-steps-1">

						<div class="bb-dropzone-wrap bp-media-upload-tab-content" id="bp-dropzone-content">
							<?php if ( bp_is_active( 'forums' ) && ! bbp_is_single_forum() && ! bbp_is_single_topic() && ! bp_is_messages_component() && bp_is_active( 'activity' ) ) : ?>
								<div class="media-uploader-post-content">
									<textarea name="bp-media-post-content" id="bp-media-post-content" placeholder="<?php bp_is_group() ? esc_html_e( 'Write something about your photos, to be shown on the group feed', 'buddyboss' ) : esc_html_e( 'Write something about your photos, to be shown on your timeline', 'buddyboss' ); ?>"></textarea>
								</div>
							<?php endif; ?>

								<div class="bb-field-wrap">
									<div class="media-uploader-wrapper">
										<div class="dropzone" id="media-uploader"></div>
									</div>
									<?php
									if ( bp_is_profile_albums_support_enabled() ) {
										?>
										<a id="bp-media-photo-next" class="bb-field-uploader-next bb-field-uploader-actions" href="#">
											<i class="bb-icon-folder"></i>
											<?php esc_html_e( 'Select Album', 'buddyboss' ); ?>
										</a>
										<?php
									}
									?>
								</div>
							</div>
					</div>

					<?php if ( bp_is_single_album() ) : ?>
						<div class="bp-existing-media-wrap bp-media-upload-tab-content" id="bp-existing-media-content" style="display: none;">

							<?php if ( bp_has_media( array( 'album_id' => 'existing-media' ) ) ) : ?>

								<ul class="media-list item-list bp-list bb-photo-list grid existing-media-list">

									<?php
									while ( bp_media() ) :
										bp_the_media();

										bp_get_template_part( 'media/entry' );
										?>

									<?php endwhile; ?>

									<?php if ( bp_media_has_more_items() ) : ?>

										<li class="load-more">
											<a class="button outline" href="<?php bp_media_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
										</li>

									<?php endif; ?>

								</ul>

							<?php else : ?>

								<?php bp_nouveau_user_feedback( 'media-loop-none' ); ?>

							<?php endif; ?>

						</div>



					<?php endif; ?>

					<div class="bb-field-steps bb-field-steps-2">
						<div class="bb-field-wrap">
							<?php bp_get_template_part( 'media/location-move' ); ?>
							<?php bp_get_template_part( 'media/media-create-album' ); ?>
						</div>
					</div>

					<footer class="bb-model-footer media-uploader-footer">
						<a href="#" class="bp-media-open-create-popup-folder" style="display: none;">
							<i class="bb-icon-plus"></i>
							Create new album
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
