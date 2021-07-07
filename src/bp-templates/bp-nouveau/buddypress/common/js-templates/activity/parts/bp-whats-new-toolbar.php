<script type="text/html" id="tmpl-whats-new-toolbar">

	<?php if ( bp_is_active( 'media' ) ) : ?>
		<div class="post-elements-buttons-item show-toolbar" data-bp-tooltip-pos="up-left" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
			<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
				<span class="bb-icon bb-icon-text-format"></span>
			</a>
		</div>
	<?php endif; ?>
	<?php if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profile_media_support_enabled() || bp_is_group_media_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_media_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_media_support_enabled() ) ) ) : ?>
		<div class="post-elements-buttons-item post-media media-support">
			<a href="#" id="activity-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach a photo', 'buddyboss' ); ?>">
				<i class="bb-icon bb-icon-camera-small"></i>
			</a>
		</div>
	<?php endif; ?>
	<?php
	$video_extensions = ( function_exists( 'bp_video_get_allowed_extension' ) ) ? bp_video_get_allowed_extension() : '';
	if ( bp_is_active( 'media' ) && ! empty( $video_extensions ) && ( ( bp_is_activity_directory() && ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_video_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_video_support_enabled() ) ) ) :
		?>
		<div class="post-elements-buttons-item post-video video-support">
			<a href="#" id="activity-video-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach a video', 'buddyboss' ); ?>">
				<i class="bb-icon bb-icon-video-alt"></i>
			</a>
		</div>
	<?php endif; ?>
	<?php
	$extensions = ( function_exists( 'bp_document_get_allowed_extension' ) ) ? bp_document_get_allowed_extension() : '';
	if ( bp_is_active( 'media' ) && ! empty( $extensions ) && ( ( bp_is_activity_directory() && ( bp_is_profile_document_support_enabled() || bp_is_group_document_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_document_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_document_support_enabled() ) ) ) :
		?>
		<div class="post-elements-buttons-item post-media document-support">
			<a href="#" id="activity-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach a document', 'buddyboss' ); ?>">
				<i class="bb-icon bb-icon-attach"></i>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profiles_gif_support_enabled() || bp_is_groups_gif_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profiles_gif_support_enabled() ) || ( bp_is_group_activity() && bp_is_groups_gif_support_enabled() ) ) ) : ?>
		<div class="post-elements-buttons-item post-gif">
			<div class="gif-media-search">
				<a href="#" id="activity-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Post a GIF', 'buddyboss' ); ?>"><i class="bb-icon bb-icon-gif"></i></a>
				<div class="gif-media-search-dropdown"></div>
			</div>
		</div>
	<?php endif; ?>
	<?php if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profiles_emoji_support_enabled() || bp_is_groups_emoji_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profiles_emoji_support_enabled() ) || ( bp_is_group_activity() && bp_is_groups_emoji_support_enabled() ) ) ) : ?>
		<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Insert an emoji', 'buddyboss' ); ?>"></div>
	<?php endif; ?>
</script>
