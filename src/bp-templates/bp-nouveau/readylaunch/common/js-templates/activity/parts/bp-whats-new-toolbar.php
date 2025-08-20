<?php
/**
 * ReadyLaunch - The template for displaying what's new toolbar
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-whats-new-toolbar">
	<div class="bb-rl-whats-new-toolbar--inner flex items-center">
	<?php

	/**
	 * Fires at the beginning of the what's new toolbar.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	do_action( 'bb_whats_new_toolbar_before' );

	if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profile_media_support_enabled() || bp_is_group_media_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_media_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_media_support_enabled() ) ) ) :
		?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-media-support">
			<a href="#" id="bb-rl-activity-media-button" class="bb-rl-toolbar-button bp-tooltip" data-bp-tooltip-pos="up-left" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-camera"></i>
			</a>
		</div>
	<?php endif; ?>
	<?php
	$video_extensions = ( function_exists( 'bp_video_get_allowed_extension' ) ) ? bp_video_get_allowed_extension() : '';
	if ( bp_is_active( 'media' ) && ! empty( $video_extensions ) && ( ( bp_is_activity_directory() && ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_video_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_video_support_enabled() ) ) ) :
		?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-post-video bb-rl-video-support">
			<a href="#" id="bb-rl-activity-video-button" class="bb-rl-toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-video-camera"></i>
			</a>
		</div>
	<?php endif; ?>
	<?php
	$extensions = ( function_exists( 'bp_document_get_allowed_extension' ) ) ? bp_document_get_allowed_extension() : '';
	if ( bp_is_active( 'media' ) && ! empty( $extensions ) && ( ( bp_is_activity_directory() && ( bp_is_profile_document_support_enabled() || bp_is_group_document_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_document_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_document_support_enabled() ) ) ) :
		?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-post-media bb-rl-document-support">
			<a href="#" id="bb-rl-activity-document-button" class="bb-rl-toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-paperclip-horizontal"></i>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profiles_gif_support_enabled() || bp_is_groups_gif_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profiles_gif_support_enabled() ) || ( bp_is_group_activity() && bp_is_groups_gif_support_enabled() ) ) ) : ?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-post-gif">
			<div class="bb-rl-gif-media-search">
				<a href="#" id="bb-rl-activity-gif-button" class="bb-rl-toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>"><i class="bb-icons-rl-gif"></i></a>
				<div class="bb-rl-gif-media-search-dropdown"></div>
			</div>
		</div>
		<?php
	endif;

	/**
	 * Fires at the end of the what's new toolbar.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	do_action( 'bb_whats_new_toolbar_after' );
	?>
	</div><!-- .bb-rl-whats-new-toolbar--inner -->

	<div class="bb-rl-whats-new-toolbar--addon flex items-center">
		<?php
		if (
			bp_is_active( 'media' ) &&
			(
				(
					bp_is_activity_directory() &&
					(
						bp_is_profiles_emoji_support_enabled() ||
						bp_is_groups_emoji_support_enabled()
					)
				) ||
				(
					bp_is_user_activity() &&
					bp_is_profiles_emoji_support_enabled()
				) ||
				(
					bp_is_group_activity() &&
					bp_is_groups_emoji_support_enabled()
				)
			)
		) :
			?>
			<div class="bb-rl-post-elements-buttons-item bb-rl-post-emoji bp-tooltip" data-bp-tooltip-pos="up-right" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-smiley"></i>
			</div>
		<?php endif; ?>
		<div class="bb-rl-post-elements-buttons-item bb-rl-show-toolbar" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
			<a href="#" id="bb-rl-show-toolbar-button" class="bb-rl-toolbar-button bp-tooltip" aria-label="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
				<span class="bb-icons-rl-text-aa"></span>
			</a>
		</div>
	</div><!-- .bb-rl-whats-new-toolbar--addon -->
</script>
