<?php
/**
 * ReadyLaunch - The template for BP Nouveau Activity Comment form.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $activities_template;

if ( ! bp_nouveau_current_user_can( 'comment_activity' ) || ! bp_activity_can_comment() ) {
	return;
}

$activity_id  = bp_get_activity_id();
$media_active = bp_is_active( 'media' );
?>

<form action="<?php bp_activity_comment_form_action(); ?>" method="post" id="ac-form-<?php echo esc_attr( $activity_id ); ?>" class="ac-form not-initialized"<?php bp_activity_comment_form_nojs_display(); ?>>
	<div class="bb-rl-ac-form-container">
		<div class="bb-rl-ac-reply-content">
			<div class="bb-rl-ac-reply-avatar">
				<?php bp_loggedin_user_avatar( array( 'type' => 'thumb' ) ); ?>
			</div>
			<div class="ac-textarea">
				<label for="ac-input-<?php echo esc_attr( $activity_id ); ?>" class="bp-screen-reader-text">
					<?php esc_html_e( 'Comment', 'buddyboss' ); ?>
				</label>
				<div contenteditable="true" id="ac-input-<?php echo esc_attr( $activity_id ); ?>" class="ac-input bp-suggestions" name="ac_input_<?php echo esc_attr( $activity_id ); ?>" data-placeholder="<?php esc_attr_e( 'Write a comment...', 'buddyboss' ); ?>"></div>
			</div>
		</div><!-- .bb-rl-ac-reply-content -->
		<?php
		if ( 'blogs' !== $activities_template->activity->component ) {
			?>
			<div id="bb-rl-ac-reply-attachments-<?php echo esc_attr( $activity_id ); ?>" class="bb-rl-ac-reply-attachments attachments--small">
				<?php if ( $media_active ) : ?>
					<div class="dropzone closed media media-dropzone" id="bb-rl-ac-reply-post-media-uploader-<?php echo esc_attr( $activity_id ); ?>"></div>
					<div class="bb-rl-ac-reply-post-default-template" style="display:none;">					
						<div class="dz-preview">
							<div class="dz-image">
								<img data-dz-thumbnail />
							</div>
							<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
							<div class="dz-details">
								<div class="dz-progress"><span data-dz-progress></span> <?php esc_html_e( 'Complete', 'buddyboss' ); ?></div>
								<div class="dz-filename" data-dz-name></div>
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

					<div class="dropzone closed document document-dropzone" id="bb-rl-ac-reply-post-document-uploader-<?php echo esc_attr( $activity_id ); ?>"></div>
					<div class="bb-rl-ac-reply-post-document-template" style="display:none;">
						<div class="dz-preview dz-file-preview">
							<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
							<div class="dz-details">
								<div class="dz-progress"><span data-dz-progress></span> <?php esc_html_e( 'Complete', 'buddyboss' ); ?></div>
								<div class="dz-icon"><span class="bb-icons-rl bb-icons-rl-file"></span></div>
								<div class="dz-filename"><span data-dz-name></span></div>
							</div>
							<div class="dz-progress-ring-wrap">
								<i class="bb-icons-rl-fill bb-icons-rl-link"></i>
								<svg class="dz-progress-ring" width="48" height="48">
									<circle class="progress-ring__circle" stroke="#4946FE" stroke-width="3" fill="transparent" r="21.5" cx="24" cy="24" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
								</svg>
							</div>
							<div class="dz-error-message"><span data-dz-errormessage></span></div>
						</div>
					</div>

					<div class="dropzone closed video video-dropzone" id="bb-rl-ac-reply-post-video-uploader-<?php echo esc_attr( $activity_id ); ?>"></div>
					<div class="bb-rl-ac-reply-post-video-template" style="display:none;">
						<div class="dz-preview dz-file-preview well" id="dz-preview-template">
							<div class="dz-image">
								<img data-dz-thumbnail />
							</div>
							<div class="dz-details">
								<div class="dz-progress"><span data-dz-progress></span> <?php esc_html_e( 'Complete', 'buddyboss' ); ?></div>
								<div class="dz-filename" data-dz-name></div>
							</div>
							<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
							<div class="dz-progress-ring-wrap">
								<i class="bb-icons-rl-fill bb-icons-rl-video-camera"></i>
								<svg class="dz-progress-ring" width="48" height="48">
								<circle class="progress-ring__circle" stroke="#4946FE" stroke-width="3" fill="transparent" r="21.5" cx="24" cy="24" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
								</svg>
							</div>
							<div class="dz-error-message"><span data-dz-errormessage></span></div>
						</div>
					</div>
					<div id="bb-rl-ac-reply-post-gif-<?php echo esc_attr( $activity_id ); ?>"></div>
				<?php endif; ?>
			</div>
			<?php
		}
		?>
		<div class="bb-rl-ac-reply-footer">
			<input type="hidden" name="comment_form_id" value="<?php echo esc_attr( $activity_id ); ?>" />
			<?php
			if ( 'blogs' !== $activities_template->activity->component ) {
				?>
					<div id="bb-rl-ac-reply-toolbar-<?php echo esc_attr( $activity_id ); ?>" class="bb-rl-ac-reply-toolbar">
					<?php
					if ( $media_active ) :
						?>
							<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-media-support">
								<a href="#" id="bb-rl-ac-reply-media-button-<?php echo esc_attr( $activity_id ); ?>" class="toolbar-button bp-tooltip bb-rl-ac-reply-media-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>" data-ac-id="<?php echo esc_attr( $activity_id ); ?>">
									<i class="bb-icons-rl-camera"></i>
								</a>
							</div>
							<div class="bb-rl-post-elements-buttons-item bb-rl-post-video bb-rl-video-support">
								<a href="#" id="bb-rl-ac-reply-video-button-<?php echo esc_attr( $activity_id ); ?>" class="toolbar-button bp-tooltip bb-rl-ac-reply-video-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>" data-ac-id="<?php echo esc_attr( $activity_id ); ?>">
									<i class="bb-icons-rl-video-camera"></i>
								</a>
							</div>
							<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-document-support">
								<a href="#" id="bb-rl-ac-reply-document-button-<?php echo esc_attr( $activity_id ); ?>" class="toolbar-button bp-tooltip bb-rl-ac-reply-document-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>" data-ac-id="<?php echo esc_attr( $activity_id ); ?>">
									<i class="bb-icons-rl-paperclip-horizontal"></i>
								</a>
							</div>
							<div class="bb-rl-post-elements-buttons-item bb-rl-post-gif">
								<div class="bb-rl-gif-media-search">
									<a href="#" id="bb-rl-ac-reply-gif-button-<?php echo esc_attr( $activity_id ); ?>" class="toolbar-button bp-tooltip bb-rl-ac-reply-gif-button" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>">
										<i class="bb-icons-rl-gif"></i>
									</a>
									<div class="bb-rl-gif-media-search-dropdown"></div>
								</div>
							</div>
							<span class="bb-rl-separator"></span>
							<div class="bb-rl-post-elements-buttons-item bb-rl-post-emoji bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>" id="bb-rl-ac-reply-emoji-button-<?php echo esc_attr( $activity_id ); ?>"></div>
						<?php endif; ?>
					</div>
					<?php
			}
			?>
			<?php
			printf(
				'&nbsp; <button type="button" class="bb-rl-button bb-rl-button--secondaryFill ac-reply-cancel">%s</button>',
				esc_html__( 'Cancel', 'buddyboss' )
			);
			?>
			<div class="bb-rl-ac-submit-wrap">
				<?php
				bp_nouveau_submit_button( 'activity-new-comment' );
				?>
			</div>
		</div>
	</div>
</form>
