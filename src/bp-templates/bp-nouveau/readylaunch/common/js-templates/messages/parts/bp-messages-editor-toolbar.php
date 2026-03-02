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
			<a href="#" id="messages-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Attach photo', 'buddyboss' ); ?>">
				<i class="bb-icons-rl-camera"></i>
			</a>
		</div>

		<?php
		$video_extensions = bp_video_get_allowed_extension();
		if ( ! empty( $video_extensions ) ) :
			?>
			<div class="post-elements-buttons-item bb-rl-post-video post-media-video-support">
				<a href="#" id="messages-video-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Attach video', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-video-camera"></i>
				</a>
			</div>
		<?php endif; ?>

		<?php
		$extensions = bp_document_get_allowed_extension();
		if ( ! empty( $extensions ) ) :
			?>
			<div class="post-elements-buttons-item bb-rl-post-media post-media-document-support">
				<a href="#" id="messages-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Attach document', 'buddyboss' ); ?>">
					<i class="bb-icons-rl-paperclip-horizontal"></i>
				</a>
			</div>
		<?php endif; ?>

		<div class="post-elements-buttons-item bb-rl-post-gif post-media-gif-support">
			<div class="gif-media-search">
				<a href="#" id="messages-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Choose a GIF', 'buddyboss' ); ?>">
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
