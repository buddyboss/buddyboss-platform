<?php
/**
 * BuddyBoss - Video Uploader
 *
 * @since BuddyBoss 1.0.0
 */
?>
<div id="bp-video-uploader" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">

					<header class="bb-model-header">
						<a href="#" class="bp-video-upload-tab bp-upload-tab selected" data-content="bp-video-dropzone-content" id="bp-video-uploader-modal-title"><?php esc_html_e( 'Upload', 'buddyboss' ); ?></a>

						<?php if ( bp_is_single_album() ) : ?>
							<a href="#" class="bp-video-upload-tab bp-upload-tab" data-content="bp-existing-video-content" id="bp-video-select-from-existing"><?php esc_html_e( 'Select Videos', 'buddyboss' ); ?></a>
						<?php endif; ?>

						<span id="bp-video-uploader-modal-status-text" style="display: none;"></span>

						<a class="bb-model-close-button" id="bp-video-uploader-close" href="#">
							<span class="bb-icon bb-icon-close"></span>
						</a>
					</header>

					<div class="bb-dropzone-wrap bp-video-upload-tab-content bp-upload-tab-content" id="bp-video-dropzone-content">
						<?php if ( bp_is_active( 'forums' ) && ! bbp_is_single_forum() && ! bbp_is_single_topic() && ! bp_is_messages_component() && bp_is_active( 'activity' ) ) : ?>
							<div class="video-uploader-post-content">
								<textarea name="bp-video-post-content" id="bp-video-post-content" placeholder="<?php bp_is_group() ? esc_html_e( 'Write something about your videos, to be shown on the group feed', 'buddyboss' ) : esc_html_e( 'Write something about your videos, to be shown on your timeline', 'buddyboss' ); ?>"></textarea>
							</div>
						<?php endif; ?>

						<div class="bb-field-wrap">
							<div class="video-uploader-wrapper">
								<div class="dropzone" id="video-uploader"></div>
							</div>
						</div>
					</div>

					<?php if ( bp_is_single_album() ) : ?>
						<div class="bp-existing-video-wrap bp-video-upload-tab-content bp-upload-tab-content" id="bp-existing-video-content" style="display: none;">

							<?php if ( bp_has_video( array( 'album_id' => 'existing-video' ) ) ) : ?>

								<ul class="video-list item-list bp-list bb-video-list grid existing-video-list">

									<?php
									while ( bp_video() ) :
										bp_the_video();

										bp_get_template_part( 'video/entry' );
										?>

									<?php endwhile; ?>

									<?php if ( bp_video_has_more_items() ) : ?>

										<li class="load-more">
											<a class="button outline" href="<?php bp_video_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
										</li>

									<?php endif; ?>

								</ul>

							<?php else : ?>

								<?php bp_nouveau_user_feedback( 'video-loop-none' ); ?>

							<?php endif; ?>

						</div>
					<?php endif; ?>

					<footer class="bb-model-footer flex align-items-center">
						<!--<a class="button outline" id="bp-video-add-more" style="display: none;" href="#">+ <?php // _e( 'Add More Video', 'buddyboss' ); ?></a>-->

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
					</footer>

				</div>
			</div>
		</div>
	</transition>
</div>
