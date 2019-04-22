<script type="text/html" id="tmpl-whats-new-messages-toolbar">
	<?php if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ): ?>
		<div class="post-elements-buttons-item post-media">
			<a href="#" id="messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip="<?php _e('Attach a photo', 'buddyboss'); ?>">
				<span class="dashicons dashicons-admin-media"></span>
			</a>
		</div>
	<?php endif; ?>
</script>
