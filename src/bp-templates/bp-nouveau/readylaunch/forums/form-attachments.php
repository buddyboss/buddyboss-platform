<?php
/**
 * New/Edit Forum Form Attachments Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$group_id = apply_filters( 'bb_forum_attachment_group_id', 0 );
$forum_id = apply_filters( 'bb_forum_attachment_forum_id', 0 );
$topic_id = apply_filters( 'bb_forum_attachment_topic_id', 0 );

if ( bp_is_active( 'groups' ) && bp_is_group_single() ) {
	$group_id = bp_get_current_group_id();
}
if ( bbp_is_single_forum() ) {
	$forum_id = bbp_get_forum_id();
} elseif ( bbp_is_single_topic() ) {
	$forum_id = bbp_get_topic_forum_id( bbp_get_topic_id() );
} elseif ( bbp_is_single_reply() ) {
	$topic_id = bbp_get_reply_topic_id( bbp_get_reply_id() );
	$forum_id = bbp_get_topic_forum_id( $topic_id );
}
$extensions       = bp_is_active( 'media' ) ? bp_document_get_allowed_extension() : false;
$video_extensions = bp_is_active( 'media' ) ? bp_video_get_allowed_extension() : false;
?>

<?php do_action( 'bbp_theme_before_forums_form_attachments' ); ?>

<div id="whats-new-attachments">

	<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), $forum_id, 0 ) ) : ?>
		<div class="dropzone closed media-dropzone" id="bb-rl-forums-post-media-uploader" data-key="<?php echo esc_attr( bp_unique_id( 'forums_media_uploader_' ) ); ?>"></div>
		<input name="bbp_media" id="bbp_media" type="hidden" value=""/>
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

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), $forum_id, 0 ) ) :
		?>
		<div class="forums-attached-gif-container closed" data-key="<?php echo esc_attr( bp_unique_id( 'forums_attached_gif_container_' ) ); ?>">
			<div class="gif-image-container">
				<img src="" alt="">
			</div>
			<div class="gif-image-remove gif-image-overlay">
				<i class="bb-icons-rl-x"></i>
			</div>
		</div>
		<input name="bbp_media_gif" id="bbp_media_gif" type="hidden" value=""/>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && ! empty( $extensions ) && bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="dropzone closed document-dropzone" id="bb-rl-forums-post-document-uploader" data-key="<?php echo esc_attr( wp_unique_id( 'forums_document_uploader_' ) ); ?>"></div>
		<input name="bbp_document" id="bbp_document" type="hidden" value=""/>
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
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_video( $group_id, bp_loggedin_user_id(), $forum_id, 0 ) ) : ?>
		<div class="dropzone closed video-dropzone" id="bb-rl-forums-post-video-uploader" data-key="<?php echo esc_attr( bp_unique_id( 'forums_video_uploader_' ) ); ?>"></div>
		<input name="bbp_video" id="bbp_video" type="hidden" value=""/>
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
	<?php endif; ?>

</div>

<!-- Medium Editor Toolbar -->
<div id="bb-rl-editor-toolbar"></div>

<div id="whats-new-toolbar" class="<?php echo ( ! bp_is_active( 'media' ) ) ? esc_attr( 'media-off' ) : ''; ?> ">

	<?php

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-media-support">
			<a href="#" id="bb-rl-forums-media-button" class="bb-rl-toolbar-button bp-tooltip" data-bp-tooltip-pos="up-left" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-camera"></i>
			</a>
		</div>

		<?php
	endif;

	if ( bp_is_active( 'media' ) && ! empty( $video_extensions ) && bb_user_has_access_upload_video( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-post-video bb-rl-video-support">
			<a href="#" id="bb-rl-forums-video-button" class="bb-rl-toolbar-button bp-tooltip" data-bp-tooltip-pos="up-left" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-video-camera"></i>
			</a>
		</div>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && ! empty( $extensions ) && bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>

		<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-document-support">
			<a href="#" id="bb-rl-forums-document-button" class="bb-rl-toolbar-button bp-tooltip" data-bp-tooltip-pos="up-left" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-paperclip-horizontal"></i>
			</a>
		</div>

		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-post-gif">
			<div class="bb-rl-gif-media-search">
				<a href="#" id="bb-rl-forums-gif-button" class="bb-rl-toolbar-button bp-tooltip" data-bp-tooltip-pos="up-left" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-gif"></i>
				</a>
				<div class="bb-rl-gif-media-search-dropdown">
					<div class="bb-rl-forums-attached-gif-container">
						<div class="gif-search-content">
							<div class="gif-search-query">
								<input type="search" placeholder="<?php esc_attr_e( 'Search GIPHY...', 'buddyboss' ); ?>" class="search-query-input" />
								<span class="search-icon"></span>
							</div>
							<div class="gif-search-results" id="gif-search-results">
								<ul class="gif-search-results-list" >
								</ul>
								<div class="gif-alert gif-no-results">
									<i class="bb-icon-l bb-icon-image-slash"></i>
									<p><?php esc_html_e( 'No results found', 'buddyboss' ); ?></p>
								</div>

								<div class="gif-alert gif-no-connection">
									<i class="bb-icons-rl bb-icon-cloud-slash"></i>
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

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_emoji( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>"></div>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && bbp_use_wp_editor() ) :
		?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-show-toolbar" data-bp-tooltip-pos="up-left" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>">
			<a href="#" id="bb-rl-show-toolbar-button" class="bb-rl-toolbar-button bp-tooltip" aria-label="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-text-aa"></i>
			</a>
		</div>
	<?php endif; ?>

</div>

<?php do_action( 'bbp_theme_after_forums_form_attachments' ); ?>
