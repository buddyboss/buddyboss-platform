<?php
/**
 * The template for activity modal.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/activity-modal.php.
 *
 * @since   BuddyBoss 2.5.80
 * @version 1.0.0
 */
?>
<div class="bb-activity-model-wrapper bb-internal-model activity-theatre buddypress-wrap" style="display: none;" id="buddypress">

	<div id="activity-modal" class="activity-modal activity">
		<div class="bb-modal-activity-header">
			<h2></h2>
			<a class="bb-close-action-popup bb-model-close-button" href="#">
				<span class="bb-icon-l bb-icon-times"></span>
			</a>
		</div>
		<div class="bb-modal-activity-body">
			<ul class="activity-list item-list bp-list"></ul>
		</div>
		<div class="footer-overflow">
			<div class="bb-modal-activity-footer activity-item">
				<div class="ac-form-placeholder">
					<div class="bp-ac-form-container">
						<div class="ac-reply-avatar">
							<?php bp_loggedin_user_avatar( array( 'type' => 'thumb' ) ); ?>
						</div>
						<div class="ac-reply-content">
							<div class="ac-reply-toolbar">
								<?php
								if ( bp_is_active( 'media' ) ) {
									?>
									<div class="post-elements-buttons-item post-media media-support">
										<a href="#" class="toolbar-button bp-tooltip ac-reply-media-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>" data-ac-id="536">
											<i class="bb-icon-l bb-icon-camera"></i>
										</a>
									</div>

									<div class="post-elements-buttons-item post-video video-support">
										<a href="#" class="toolbar-button bp-tooltip ac-reply-video-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>" data-ac-id="536">
											<i class="bb-icon-l bb-icon-video"></i>
										</a>
									</div>

									<div class="post-elements-buttons-item post-media document-support">
										<a href="#" class="toolbar-button bp-tooltip ac-reply-document-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>" data-ac-id="536">
											<i class="bb-icon-l bb-icon-attach"></i>
										</a>
									</div>

									<div class="post-elements-buttons-item post-gif">
										<div class="gif-media-search">
											<a href="#" class="toolbar-button bp-tooltip ac-reply-gif-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>">
												<i class="bb-icon-l bb-icon-gif"></i>
											</a>
											<div class="gif-media-search-dropdown"></div>
										</div>
									</div>

									<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>" id="ac-reply-emoji-button-536" data-nth-child="5">
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

							<div class="ac-submit-wrap">
								<input type="submit" name="ac_form_submit" value="<?php esc_attr_e( 'Post', 'buddyboss' ); ?>" data-add-edit-label="<?php esc_attr_e( 'Save', 'buddyboss' ); ?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>

</div>
