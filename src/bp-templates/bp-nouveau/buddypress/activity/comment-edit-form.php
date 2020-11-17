<?php
/**
 * BP Nouveau Activity Comment Edit form template.
 *
 * @since BuddyBoss 1.5.4
 * @version 3.1.0
 */

if ( ! bp_nouveau_current_user_can( 'comment_activity' ) || ! bp_activity_can_comment() ) {
	return;
} ?>

<form action="<?php bp_activity_comment_form_action(); ?>" method="post" id="ac-edit-form-<?php bp_activity_comment_id(); ?>" class="ac-form ac-edit-form"<?php bp_activity_comment_form_nojs_display(); ?>>

	<div class="bp-ac-form-cotainer">

		<div class="ac-reply-content">
			<div id="whats-new-avatar">

				<a href="<?php bp_activity_comment_user_link(); ?>" class="activity-post-avatar">
					<?php
					bp_activity_avatar(
						array(
							'type'    => 'thumb',
							'user_id' => bp_get_activity_comment_user_id(),
						)
					);
					?>
					<span class="user-name"><?php bp_activity_comment_name(); ?></span>
				</a>

			</div>
			<div class="ac-textarea">
				<label for="ac-input-<?php bp_activity_comment_id(); ?>" class="bp-screen-reader-text">
					<?php esc_html_e( 'Comment', 'buddyboss' ); ?>
				</label>

				<div class="edit-activity-comment-wrap">

					<div contenteditable="true" id="ac-input-<?php bp_activity_comment_id(); ?>" class="ac-input bp-suggestions" name="ac_input_<?php bp_activity_comment_id(); ?>"><?php bp_activity_comment_content(); ?></div>

					<div id="ac-reply-attachments-<?php bp_activity_comment_id(); ?>" class="ac-reply-attachments">

						<?php if ( bp_is_active( 'media' ) ) : ?>

							<div class="dropzone closed media" id="ac-reply-post-media-uploader-<?php bp_activity_comment_id(); ?>"></div>

							<div class="dropzone closed document" id="ac-reply-post-document-uploader-<?php bp_activity_comment_id(); ?>"></div>

							<div id="ac-reply-post-gif-<?php bp_activity_comment_id(); ?>"></div>

						<?php endif; ?>
					</div>

				</div>
				

				<div id="ac-reply-toolbar-<?php bp_activity_comment_id(); ?>" class="ac-reply-toolbar">

					<?php if ( bp_is_active( 'media' ) ) : ?>

                        <div class="post-elements-buttons-item post-media media-support">
                            <a href="#" id="ac-reply-media-button-<?php bp_activity_comment_id(); ?>" class="toolbar-button bp-tooltip ac-reply-media-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Attach a photo', 'buddyboss' ); ?>" data-ac-id="<?php bp_activity_comment_id(); ?>">
								<i class="bb-icon bb-icon-camera-small"></i>
                            </a>
                        </div>

						<?php if ( bp_is_active( 'media' ) ): ?>
							<div class="post-elements-buttons-item post-media document-support">
								<a href="#" id="ac-reply-document-button-<?php bp_activity_comment_id(); ?>" class="toolbar-button bp-tooltip ac-reply-document-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Attach a document', 'buddyboss'); ?>" data-ac-id="<?php bp_activity_comment_id(); ?>">
									<i class="bb-icon bb-icon-attach"></i>
								</a>
							</div>
						<?php endif; ?>

                        <div class="post-elements-buttons-item post-gif">
                            <div class="gif-media-search">
                                <a href="#" id="ac-reply-gif-button-<?php bp_activity_comment_id(); ?>" class="toolbar-button bp-tooltip ac-reply-gif-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Post a GIF', 'buddyboss'); ?>">
									<i class="bb-icon bb-icon-gif"></i>
								</a>
                                <div class="gif-media-search-dropdown"></div>
                            </div>
                        </div>

                        <div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Insert an emoji', 'buddyboss'); ?>" id="ac-reply-emoji-button-<?php bp_activity_comment_id() ?>"></div>

					<?php endif; ?>
				</div>
			</div>
			<input type="hidden" name="comment_form_id" value="<?php bp_activity_comment_id(); ?>" />

			<?php
			bp_nouveau_submit_button( 'activity-edit-comment' );
			printf(
				'&nbsp; <button type="button" class="ac-reply-cancel">%s</button>',
				esc_html__( 'Cancel', 'buddyboss' )
			);
			?>
		</div>

	</div>
</form>
