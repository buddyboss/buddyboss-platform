<script type="text/html" id="tmpl-whats-new-toolbar">
	<?php if ( bp_is_active( 'media' ) && ( bp_is_activity_directory() || bp_is_user_activity() || ( bp_is_group_activity() && bp_is_group_media_support_enabled() ) ) ): ?>
		<div class="post-elements-buttons-item post-media">
			<a href="#" id="activity-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip="<?php _e('Attach a photo', 'buddyboss'); ?>">
				<span class="dashicons dashicons-admin-media"></span>
			</a>
		</div>
	<?php endif; ?>
	<?php if ( bp_is_activity_gif_active() ): ?>
		<div class="post-elements-buttons-item post-gif">
			<div class="gif-media-search">
				<a href="#" id="activity-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip="<?php _e('Post a GIF', 'buddyboss'); ?>"><span class="dashicons dashicons-smiley"></span></a>
				<div class="gif-media-search-dropdown"></div>
			</div>
		</div>
	<?php endif; ?>
	<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip="<?php _e('Insert an emoji', 'buddyboss'); ?>"></div>
</script>
