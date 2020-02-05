<script type="text/html" id="tmpl-whats-new-messages-toolbar">
	<?php if ( bp_is_active( 'media' ) ): ?>

        <?php if ( bp_is_messages_media_support_enabled() ) : ?>
            <div class="post-elements-buttons-item post-media">
                <a href="#" id="messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php _e('Attach a photo', 'buddyboss'); ?>">
                    <i class="bb-icon bb-icon-camera-small"></i>
                </a>
            </div>
		<?php endif; ?>

		<?php if ( bp_is_messages_document_support_enabled() ) : ?>
			<div class="post-elements-buttons-item post-media">
				<a href="#" id="messages-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php _e('Attach a document', 'buddyboss'); ?>">
                    <i class="bb-icon bb-icon-attach"></i>
				</a>
			</div>
		<?php endif; ?>

		<?php if ( bp_is_messages_gif_support_enabled() ): ?>
            <div class="post-elements-buttons-item post-gif">
                <div class="gif-media-search">
                    <a href="#" id="messages-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php _e('Post a GIF', 'buddyboss'); ?>">
                        <i class="bb-icon bb-icon-gif"></i>
                    </a>
                    <div class="gif-media-search-dropdown"></div>
                </div>
            </div>
		<?php endif; ?>

	    <?php if ( bp_is_messages_emoji_support_enabled() ): ?>
         <div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php _e('Insert an emoji', 'buddyboss'); ?>"></div>
		<?php endif; ?>

	<?php endif; ?>
</script>
