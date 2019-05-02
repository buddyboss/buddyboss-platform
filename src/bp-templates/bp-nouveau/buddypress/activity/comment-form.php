<?php
/**
 * BP Nouveau Activity Comment form template.
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

if ( ! bp_nouveau_current_user_can( 'comment_activity' ) || ! bp_activity_can_comment() ) {
	return;
} ?>

<form action="<?php bp_activity_comment_form_action(); ?>" method="post" id="ac-form-<?php bp_activity_id(); ?>" class="ac-form"<?php bp_activity_comment_form_nojs_display(); ?>>

	<div class="bp-ac-form-cotainer">

		<div class="ac-reply-avatar"><?php bp_loggedin_user_avatar( array( 'type' => 'thumb' ) ); ?></div>

		<div class="ac-reply-content">
			<div class="ac-textarea">
				<label for="ac-input-<?php bp_activity_id(); ?>" class="bp-screen-reader-text">
					<?php esc_html_e( 'Comment', 'buddyboss' ); ?>
				</label>
				<div contenteditable="true" id="ac-input-<?php bp_activity_id(); ?>" class="ac-input bp-suggestions" name="ac_input_<?php bp_activity_id(); ?>"></div>

				<div id="ac-reply-attachments-<?php bp_activity_id(); ?>" class="ac-reply-attachments">

					<?php if ( bp_is_active( 'media' ) ) : ?>
						<div class="dropzone closed" id="ac-reply-post-media-uploader-<?php bp_activity_id(); ?>"></div>
					<?php endif; ?>

					<?php if ( bp_is_activity_gif_active() ): ?>
						<div id="ac-reply-post-gif-<?php bp_activity_id(); ?>"></div>
					<?php endif; ?>
				</div>

				<div id="ac-reply-toolbar-<?php bp_activity_id(); ?>" class="ac-reply-toolbar">

					<?php if ( bp_is_active( 'media' ) ) : ?>

						<div class="post-elements-buttons-item post-media">
							<a href="#" id="ac-reply-media-button-<?php bp_activity_id(); ?>" class="toolbar-button bp-tooltip ac-reply-media-button"
							   data-bp-tooltip="<?php _e( 'Attach a photo', 'buddyboss' ); ?>" data-ac-id="<?php bp_activity_id(); ?>">
								<span class="dashicons dashicons-admin-media"></span>
							</a>
						</div>

					<?php endif; ?>

					<?php if ( bp_is_activity_gif_active() ): ?>
						<div class="post-elements-buttons-item post-gif">
							<div class="gif-media-search">
								<a href="#" id="ac-reply-gif-button-<?php bp_activity_id(); ?>" class="toolbar-button bp-tooltip ac-reply-gif-button" data-bp-tooltip="<?php _e('Post a GIF', 'buddyboss'); ?>"><span class="dashicons dashicons-smiley"></span></a>
								<div class="gif-media-search-dropdown"></div>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( bp_is_activity_emoji_active() ): ?>
						<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip="<?php _e('Insert an emoji', 'buddyboss'); ?>" id="ac-reply-emoji-button-<?php bp_activity_id() ?>"></div>
					<?php endif; ?>
				</div>
			</div>
			<input type="hidden" name="comment_form_id" value="<?php bp_activity_id(); ?>" />

			<?php
			bp_nouveau_submit_button( 'activity-new-comment' );
			printf(
				'&nbsp; <button type="button" class="ac-reply-cancel">%s</button>',
				esc_html__( 'Cancel', 'buddyboss' )
			);
			?>
		</div>

	</div>
</form>
