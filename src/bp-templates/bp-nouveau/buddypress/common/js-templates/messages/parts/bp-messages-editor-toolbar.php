<script type="text/html" id="tmpl-whats-new-messages-toolbar">

<?php if ( ! bp_is_active( 'media' ) ) : ?>
<div class="media-off">
<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) ) : ?>

		<div class="post-elements-buttons-item show-toolbar"  data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_attr_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
			<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
				<span class="bb-icon bb-icon-text-format"></span>
			</a>
		</div>

		<div class="post-elements-buttons-item post-media post-media-photo-support">
			<a href="#" id="messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Attach a photo', 'buddyboss' ); ?>">
				<i class="bb-icon bb-icon-camera-small"></i>
			</a>
		</div>

		<?php
		$video_extensions = bp_video_get_allowed_extension();
		if ( ! empty( $video_extensions ) ) :
			?>
            <div class="post-elements-buttons-item post-video post-media-video-support">
                <a href="#" id="messages-video-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Attach a video', 'buddyboss' ); ?>">
                    <i class="bb-icon bb-icon-video-alt"></i>
                </a>
            </div>
		<?php endif; ?>

		<?php
		$extensions = bp_document_get_allowed_extension();
		if ( ! empty( $extensions ) ) :
			?>
			<div class="post-elements-buttons-item post-media post-media-document-support">
				<a href="#" id="messages-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Attach a document', 'buddyboss' ); ?>">
					<i class="bb-icon bb-icon-attach"></i>
				</a>
			</div>
		<?php endif; ?>

		<div class="post-elements-buttons-item post-gif post-media-gif-support">
			<div class="gif-media-search">
				<a href="#" id="messages-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Post a GIF', 'buddyboss' ); ?>">
					<i class="bb-icon bb-icon-gif"></i>
				</a>
				<div class="gif-media-search-dropdown"></div>
			</div>
		</div>

		<div class="post-elements-buttons-item post-emoji bp-tooltip post-media-emoji-support" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Insert an emoji', 'buddyboss' ); ?>"></div>


	<?php endif; ?>

<?php if ( ! bp_is_active( 'media' ) ) : ?>
</div>
<?php endif; ?>

</script>
