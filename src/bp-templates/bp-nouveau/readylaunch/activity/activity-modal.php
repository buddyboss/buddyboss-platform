<?php
/**
 * ReadyLaunch - The template for activity modal.
 *
 * This template handles the display of the activity modal/popup
 * for viewing and commenting on activity items.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
						<div class="bb-rl-ac-reply-content">
							<div class="bb-rl-ac-reply-avatar">
								<?php bp_loggedin_user_avatar( array( 'type' => 'thumb' ) ); ?>
							</div>
							<div class="ac-textarea">
								<div contenteditable="true" id="ac-input-00000" class="ac-input bp-suggestions medium-editor-element" name="ac_input_00000" data-placeholder="Write a comment..." spellcheck="true" data-medium-editor-element="true" role="textbox" aria-multiline="true"><p>Write a comment...</p></div>
							</div>
						</div>
						<div class="bb-rl-ac-reply-footer">
							<div class="bb-rl-ac-reply-toolbar">
								<?php
								if ( bp_is_active( 'media' ) ) {
									?>
									<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-media-support">
										<a href="#" class="toolbar-button bp-tooltip bb-rl-ac-reply-media-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss-platform' ); ?>" data-ac-id="536">
											<i class="bb-icons-rl-camera"></i>
										</a>
									</div>

									<?php
									// Only offer the video/document attach buttons when their component is
									// active and video/document support is enabled for at least one context.
									// This modal is a generic shell, so both profile and group support are checked.
									if ( bp_is_active( 'video' ) && ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() ) ) {
										?>
									<div class="bb-rl-post-elements-buttons-item bb-rl-post-video bb-rl-video-support">
										<a href="#" class="toolbar-button bp-tooltip bb-rl-ac-reply-video-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss-platform' ); ?>" data-ac-id="536">
											<i class="bb-icons-rl-video-camera"></i>
										</a>
									</div>
										<?php
									}
									if ( bp_is_active( 'document' ) && ( bp_is_profile_document_support_enabled() || bp_is_group_document_support_enabled() ) ) {
										?>
									<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-document-support">
										<a href="#" class="toolbar-button bp-tooltip bb-rl-ac-reply-document-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss-platform' ); ?>" data-ac-id="536">
											<i class="bb-icons-rl-paperclip-horizontal"></i>
										</a>
									</div>
										<?php
									}
									?>

									<div class="bb-rl-post-elements-buttons-item bb-rl-post-gif">
										<div class="bb-rl-gif-media-search">
											<a href="#" class="toolbar-button bp-tooltip bb-rl-ac-reply-gif-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss-platform' ); ?>">
												<i class="bb-icons-rl-gif"></i>
											</a>
											<div class="bb-rl-gif-media-search-dropdown"></div>
										</div>
									</div>

									<span class="bb-rl-separator"></span>

									<div class="bb-rl-post-elements-buttons-item bb-rl-post-emoji bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss-platform' ); ?>" id="bb-rl-ac-reply-emoji-button-536" data-nth-child="5">
										<div class="emojionearea emojionearea-standalone ac-input bp-suggestions medium-editor-element" role="application">
											<div class="emojionearea-editor has-placeholder" contenteditable="false" placeholder="<?php esc_attr_e( 'Write a comment...', 'buddyboss-platform' ); ?>" tabindex="0" dir="ltr" spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="off"></div>
											<div class="emojionearea-button" title="<?php esc_attr_e( 'Use the TAB key to insert emoji faster', 'buddyboss-platform' ); ?>">
												<div class="emojionearea-button-open"></div>
											</div>
										</div>
									</div>
									<?php
								}
								?>
							</div>
							<button type="button" class="bb-rl-button bb-rl-button--secondaryFill ac-reply-cancel">Cancel</button>
							<div class="bb-rl-ac-submit-wrap">
								<input type="submit" name="ac_form_submit" value="<?php esc_attr_e( 'Comment', 'buddyboss-platform' ); ?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
