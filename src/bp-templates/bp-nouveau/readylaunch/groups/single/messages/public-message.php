<?php
/**
 * ReadyLaunch - Group's Public Message template.
 *
 * This template provides the interface for sending public messages to group members
 * with media attachments, GIF support, and member selection functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_loggedin_user_id = bp_loggedin_user_id();
$args                = array(
	'page'                => 1,
	'per_page'            => 12,
	'exclude'             => array( $bp_loggedin_user_id ),
	'exclude_admins_mods' => false,
);

$group_members   = groups_get_group_members( $args );
$group_id        = 0;
$is_media_active = bp_is_active( 'media' );
$extensions      = $is_media_active ? bp_document_get_allowed_extension() : false;
if ( bp_is_active( 'groups' ) && bp_is_group_single() ) {
	$group_id = bp_get_current_group_id();
}
?>

<div class="bb-groups-messages-right">
	<form id="send_group_message_form" class="standard-form" data-select2-id="send_group_message_form">
		<div class="bb-groups-messages-right-top">
			<div class="bb-title-wrap">
				<h2 class="bb-title"><?php esc_html_e( 'Message', 'buddyboss' ); ?></h2>
				<a class="group-messages-compose" href="javascript:void(0);"><?php esc_html_e( 'New Group Message', 'buddyboss' ); ?></a>
			</div>
			<div class="bp-select-members-wrap">
				<div class="bp-invites-feedback">
					<div class="bp-messages-feedback bp-messages-feedback-hide">
						<div class="bp-feedback">
							<span class="bp-icon" aria-hidden="true"></span>
							<p></p>
						</div>
					</div>
				</div>
				<?php
				if ( 0 !== $group_members['count'] ) {
					?>
					<span class="group-messages-helper-text"><?php esc_html_e( 'To:', 'buddyboss' ); ?></span>
					<select name="group_messages_send_to[]" class="send-to-input select2-hidden-accessible" id="group-messages-send-to-input" placeholder="<?php esc_html_e( 'Type the names of one or more people', 'buddyboss' ); ?>" autocomplete="off" multiple="" style="width: 100%" data-select2-id="group-messages-send-to-input" tabindex="-1" aria-hidden="true">
						<option value="all" selected="selected"><?php esc_html_e( 'All group members', 'buddyboss' ); ?></option>
					</select>
					<?php
				}
				?>
			</div>
		</div>

		<?php
		if ( 0 !== $group_members['count'] ) {
			?>
			<div class="bb-groups-messages-right-bottom">
				<div id="bp-group-message-content">
					<div id="group_message_content" name="group_message_content" tabindex="3"></div>
					<input type="hidden" id="group_message_content_hidden" name="group_message_content_hidden" value="">
					<div id="whats-new-attachments">
						<?php if ( $is_media_active ) : ?>
							<div class="dropzone closed media-dropzone" id="bp-group-messages-post-media-uploader"></div>
							<input name="bp_group_messages_media" id="bp_group_messages_media" type="hidden" value=""/>
							<div class="forum-post-media-template" style="display:none;">
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
							<?php
						endif;
						if ( $is_media_active && bp_is_group_video_support_enabled() && ( bb_video_user_can_upload( $bp_loggedin_user_id, $group_id ) || bp_is_activity_directory() ) ) :
							?>
							<div class="dropzone closed video-dropzone" id="bp-group-messages-post-video-uploader"></div>
							<input name="bp_group_messages_video" id="bp_group_messages_video" type="hidden" value=""/>
							<div class="forum-post-video-template" style="display:none;">
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
							<?php
						endif;
						if ( $is_media_active ) :
							?>
							<div class="dropzone closed document-dropzone" id="bp-group-messages-post-document-uploader"></div>
							<input name="bp_group_messages_document" id="bp_group_messages_document" type="hidden" value=""/>
							<div class="forum-post-document-template" style="display:none;">
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
							<?php
						endif;
						if ( $is_media_active ) :
							?>
							<div class="bp-group-messages-attached-gif-container closed">
								<div class="gif-image-container">
									<img src="" alt="">
								</div>
								<div class="gif-image-remove gif-image-overlay">
									<span class="bb-icons-rl-x"></span>
								</div>
							</div>
							<input name="bp_group_messages_gif" id="bp_group_messages_gif" type="hidden" value=""/>
						<?php endif; ?>
					</div>
					<div id="whats-new-toolbar" class="
						<?php
						if ( ! $is_media_active ) {
							echo 'media-off';
						}
						?>
						">
						<?php
						if ( $is_media_active && bb_user_has_access_upload_media( $group_id, $bp_loggedin_user_id, 0, 0 ) ) :
							?>
							<div class="post-elements-buttons-item bb-rl-post-media media-support group-message-media-support">
								<a href="#" id="bp-group-messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>">
									<span class="bb-icons-rl-camera"></span>
								</a>
							</div>
							<?php
						endif;

						$video_extensions = ( function_exists( 'bp_video_get_allowed_extension' ) ) ? bp_video_get_allowed_extension() : '';
						if ( $is_media_active && ! empty( $video_extensions ) && bb_user_has_access_upload_video( $group_id, $bp_loggedin_user_id, 0, 0 ) ) :
							?>
							<div class="post-elements-buttons-item bb-rl-post-video video-support">
								<a href="#" id="bp-group-messages-video-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>">
									<i class="bb-icons-rl-video-camera"></i>
								</a>
							</div>
							<?php
						endif;

						if ( $is_media_active && bb_user_has_access_upload_document( $group_id, $bp_loggedin_user_id, 0, 0 ) ) :
							?>
							<div class="post-elements-buttons-item bb-rl-post-media document-support group-message-document-support">
								<a href="#" id="bp-group-messages-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>">
									<span class="bb-icons-rl-paperclip-horizontal"></span>
								</a>
							</div>
							<?php
						endif;

						if ( $is_media_active && bb_user_has_access_upload_gif( $group_id, $bp_loggedin_user_id, 0, 0 ) ) :
							?>
							<div class="post-elements-buttons-item bb-rl-post-gif">
								<div class="gif-media-search">
									<a href="#" id="bp-group-messages-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>">
										<span class="bb-icons-rl-gif"></span>
									</a>
									<div class="bb-rl-gif-media-search-dropdown">
										<div class="bp-group-messages-attached-gif-container">
											<div class="gif-search-content">
												<div class="gif-search-query">
													<input type="search" placeholder="<?php esc_html_e( 'Search GIPHY...', 'buddyboss' ); ?>" class="search-query-input"/>
													<span class="search-icon"></span>
												</div>
												<div class="gif-search-results" id="gif-search-results">
													<ul class="gif-search-results-list">
													</ul>
													<div class="gif-alert gif-no-results">
														<i class="bb-icon-l bb-icon-image-slash"></i>
														<p><?php esc_html_e( 'No results found', 'buddyboss' ); ?></p>
													</div>

													<div class="gif-alert gif-no-connection">
														<i class="bb-icon-l bb-icon-cloud-slash"></i>
														<p><?php esc_html_e( 'Could not connect to GIPHY', 'buddyboss' ); ?></p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<?php
						endif;

						if ( $is_media_active && bb_user_has_access_upload_emoji( $group_id, $bp_loggedin_user_id, 0, 0 ) ) :
							?>
							<div class="bb-rl-separator"></div>
							<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>"></div>
						<?php endif; ?>
						<?php if ( $is_media_active ) : ?>
							<div class="post-elements-buttons-item show-toolbar" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>">
								<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip" aria-label="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
									<span class="bb-icons-rl-text-aa"></span>
								</a>
							</div>
						<?php endif; ?>
						<div id="group-messages-new-submit" class="submit">
							<?php
							$disabled = '';
							if ( empty( $group_members['members'] ) ) {
								$disabled = 'disabled';
							}
							?>
							<button <?php echo esc_attr( $disabled ); ?> type="submit" name="send_group_message_button" id="send_group_message_button" class="small"><?php esc_html_e( 'Send Message', 'buddyboss' ); ?></button>
						</div>
						<div id="bb-rl-editor-toolbar"></div>
					</div>
				</div>
			</div>
			<?php
		}
		?>
	</form>
</div>
