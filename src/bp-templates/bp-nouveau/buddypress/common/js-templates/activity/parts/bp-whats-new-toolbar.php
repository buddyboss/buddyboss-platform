<script type="text/html" id="tmpl-whats-new-toolbar">
		
	<?php if ( bp_is_active( 'media' ) ): ?>
		<div class="post-elements-buttons-item show-toolbar"  data-bp-tooltip-pos="up-left" data-bp-tooltip="<?php _e('Show formatting', 'buddyboss'); ?>" data-bp-tooltip-hide="<?php _e('Hide formatting', 'buddyboss'); ?>" data-bp-tooltip-show="<?php _e('Show formatting', 'buddyboss'); ?>">
		<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
			<span class="dashicons dashicons-editor-textcolor"></span>
		</a>
	</div>
	<?php endif; ?>
	<?php if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profile_media_support_enabled() || bp_is_group_media_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profile_media_support_enabled() ) || ( bp_is_group_activity() && bp_is_group_media_support_enabled() ) ) ): ?>
		<div class="post-elements-buttons-item post-media">
			<a href="#" id="activity-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Attach a photo', 'buddyboss'); ?>">
				<span class="dashicons dashicons-admin-media"></span>
			</a>
		</div>
	<?php endif; ?>
	<?php if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profiles_gif_support_enabled() || bp_is_groups_gif_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profiles_gif_support_enabled() ) || ( bp_is_group_activity() && bp_is_groups_gif_support_enabled() ) ) ): ?>
		<div class="post-elements-buttons-item post-gif">
			<div class="gif-media-search">
				<a href="#" id="activity-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Post a GIF', 'buddyboss'); ?>"><span class="dashicons dashicons-smiley"></span></a>
				<div class="gif-media-search-dropdown"></div>
			</div>
		</div>
	<?php endif; ?>
	<?php if ( bp_is_active( 'media' ) && ( ( bp_is_activity_directory() && ( bp_is_profiles_emoji_support_enabled() || bp_is_groups_emoji_support_enabled() ) ) || ( bp_is_user_activity() && bp_is_profiles_emoji_support_enabled() ) || ( bp_is_group_activity() && bp_is_groups_emoji_support_enabled() ) ) ): ?>
	    <div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e('Insert an emoji', 'buddyboss'); ?>"></div>
	<?php endif; ?>
</script>
