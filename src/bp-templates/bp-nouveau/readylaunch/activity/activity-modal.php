<?php
/**
 * ReadyLaunch - The template for activity modal.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */
?>
<div class="bb-rl-activity-model-wrapper bb-rl-internal-model bb-rl-activity-theatre bb-rl-wrap" style="display: none;" id="buddypress">
	<div id="bb-rl-activity-modal" class="bb-rl-activity-modal activity">
		<div class="bb-rl-modal-activity-header">
			<h2></h2>
			<a class="bb-rl-close-action-popup bb-rl-model-close-button" href="#">
				<span class="bb-icons-rl-x"></span>
			</a>
		</div>
		<div class="bb-rl-modal-activity-body">
			<ul class="bb-rl-activity-list bb-rl-item-list bb-rl-list"></ul>
		</div>
		<div class="bb-rl-footer-overflow">
			<div class="bb-rl-modal-activity-footer activity-item">
				<div class="bb-rl-ac-form-placeholder">
					<div class="bb-rl-ac-form-container">
						<div class="bb-rl-ac-reply-avatar">
							<?php bp_loggedin_user_avatar( array( 'type' => 'thumb' ) ); ?>
						</div>
						<div class="bb-rl-ac-reply-content">
							<div class="bb-rl-ac-reply-toolbar">
								<?php
								if ( bp_is_active( 'media' ) ) {
									?>
									<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-media-support">
										<a href="#" class="toolbar-button bp-tooltip bb-rl-ac-reply-media-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>" data-ac-id="536">
											<i class="bb-icon-l bb-icon-camera"></i>
										</a>
									</div>

									<div class="bb-rl-post-elements-buttons-item bb-rl-post-video bb-rl-video-support">
										<a href="#" class="toolbar-button bp-tooltip bb-rl-ac-reply-video-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>" data-ac-id="536">
											<i class="bb-icon-l bb-icon-video"></i>
										</a>
									</div>

									<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-document-support">
										<a href="#" class="toolbar-button bp-tooltip bb-rl-ac-reply-document-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>" data-ac-id="536">
											<i class="bb-icon-l bb-icon-attach"></i>
										</a>
									</div>

									<div class="bb-rl-post-elements-buttons-item bb-rl-post-gif">
										<div class="bb-rl-gif-media-search">
											<a href="#" class="toolbar-button bp-tooltip bb-rl-ac-reply-gif-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>">
												<i class="bb-icon-l bb-icon-gif"></i>
											</a>
											<div class="bb-rl-gif-media-search-dropdown"></div>
										</div>
									</div>

									<div class="bb-rl-post-elements-buttons-item bb-rl-post-emoji bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>" id="bb-rl-ac-reply-emoji-button-536" data-nth-child="5">
										<div class="emojionearea emojionearea-standalone ac-input bp-suggestions medium-editor-element" role="application">
											<div class="emojionearea-editor has-placeholder" contenteditable="false" placeholder="<?php esc_attr_e( 'Write a comment...', 'buddyboss' ); ?>" tabindex="0" dir="ltr" spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"></div>
											<div class="emojionearea-button" title="<?php esc_attr_e( 'Use the TAB key to insert emoji faster', 'buddyboss' ); ?>">
												<div class="emojionearea-button-open"></div>
											</div>
										</div>
									</div>
									<?php
								}
								?>
							</div>
							<div class="bb-rl-ac-submit-wrap">
								<input type="submit" name="ac_form_submit" value="<?php esc_attr_e( 'Post', 'buddyboss' ); ?>" data-add-edit-label="<?php esc_attr_e( 'Save', 'buddyboss' ); ?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
