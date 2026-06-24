<?php
/**
 * Readylaunch - Messages editor toolbar.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-whats-new-messages-toolbar">

<?php if ( ! bp_is_active( 'media' ) ) : ?>
<div class="media-off">
<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) ) : ?>

		<div class="post-elements-buttons-item bb-rl-post-media post-media-photo-support">
			<a href="#" id="messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss-platform' ); ?>" aria-label="<?php esc_attr_e( 'Attach photo', 'buddyboss-platform' ); ?>">
				<i class="bb-icons-rl-camera"></i>
			</a>
		</div>

		<?php
		// `bp_video_get_allowed_extension()` and
		// `bp_document_get_allowed_extension()` are loaded by their own
		// (separate) components. Media can be on while video/document is
		// off — gate each toolbar button on its specific component
		// rather than assuming "media on => video/document on".
		$video_extensions = ( bp_is_active( 'video' ) && function_exists( 'bp_video_get_allowed_extension' ) ) ? bp_video_get_allowed_extension() : array();
		if ( ! empty( $video_extensions ) ) :
			?>
			<div class="post-elements-buttons-item bb-rl-post-video post-media-video-support">
				<a href="#" id="messages-video-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss-platform' ); ?>" aria-label="<?php esc_attr_e( 'Attach video', 'buddyboss-platform' ); ?>">
					<i class="bb-icons-rl-video-camera"></i>
				</a>
			</div>
		<?php endif; ?>

		<?php
		$extensions = ( bp_is_active( 'document' ) && function_exists( 'bp_document_get_allowed_extension' ) ) ? bp_document_get_allowed_extension() : array();
		if ( ! empty( $extensions ) ) :
			?>
			<div class="post-elements-buttons-item bb-rl-post-media post-media-document-support">
				<a href="#" id="messages-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss-platform' ); ?>" aria-label="<?php esc_attr_e( 'Attach document', 'buddyboss-platform' ); ?>">
					<i class="bb-icons-rl-paperclip-horizontal"></i>
				</a>
			</div>
		<?php endif; ?>

		<div class="post-elements-buttons-item bb-rl-post-gif post-media-gif-support">
			<div class="gif-media-search">
				<a href="#" id="messages-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss-platform' ); ?>" aria-label="<?php esc_attr_e( 'Choose a GIF', 'buddyboss-platform' ); ?>">
					<i class="bb-icons-rl-gif"></i>
				</a>
				<div class="bb-rl-gif-media-search-dropdown"></div>
			</div>
		</div>

	<?php endif; ?>

<?php if ( ! bp_is_active( 'media' ) ) : ?>
</div>
<?php endif; ?>

</script>
