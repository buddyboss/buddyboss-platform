<?php
/**
 * The template for displaying whats new toolbar
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-whats-new-toolbar.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-whats-new-toolbar">

	<?php if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profile_media_support_enabled() || bp_is_group_media_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_media_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_media_support_enabled() ) ) ) : ?>
		<div class="post-elements-buttons-item post-media media-support">
			<a href="#" id="activity-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up-left" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>">
				<i class="bb-icon-l bb-icon-camera"></i>
			</a>
		</div>
	<?php endif; ?>
	<?php
	$video_extensions = ( function_exists( 'bp_video_get_allowed_extension' ) ) ? bp_video_get_allowed_extension() : '';
	if ( bp_is_active( 'media' ) && ! empty( $video_extensions ) && ( ( bp_is_activity_directory() && ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_video_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_video_support_enabled() ) ) ) :
		?>
		<div class="post-elements-buttons-item post-video video-support">
			<a href="#" id="activity-video-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>">
				<i class="bb-icon-l bb-icon-video"></i>
			</a>
		</div>
	<?php endif; ?>
	<?php
	$extensions = ( function_exists( 'bp_document_get_allowed_extension' ) ) ? bp_document_get_allowed_extension() : '';
	if ( bp_is_active( 'media' ) && ! empty( $extensions ) && ( ( bp_is_activity_directory() && ( bp_is_profile_document_support_enabled() || bp_is_group_document_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_document_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_document_support_enabled() ) ) ) :
		?>
		<div class="post-elements-buttons-item post-media document-support">
			<a href="#" id="activity-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>">
				<i class="bb-icon-l bb-icon-attach"></i>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profiles_gif_support_enabled() || bp_is_groups_gif_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profiles_gif_support_enabled() ) || ( bp_is_group_activity() && bp_is_groups_gif_support_enabled() ) ) ) : ?>
		<div class="post-elements-buttons-item post-gif">
			<div class="gif-media-search">
				<a href="#" id="activity-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>"><i class="bb-icon-l bb-icon-gif"></i></a>
				<div class="gif-media-search-dropdown"></div>
			</div>
		</div>
	<?php endif; ?>
</script>
