<?php
/**
 * Readylaunch - Messages editor toolbar.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-whats-new-formatting-toolbar">

<?php if ( ! bp_is_active( 'media' ) ) : ?>
<div class="media-off">
<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) ) : ?>

		<div class="bb-rl-separator"></div>

		<div class="post-elements-buttons-item post-emoji bp-tooltip post-media-emoji-support" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>"></div>

		<div class="post-elements-buttons-item show-toolbar"  data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_attr_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
			<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip" aria-label="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
				<span class="bb-icons-rl-text-aa"></span>
			</a>
		</div>

	<?php endif; ?>

<?php if ( ! bp_is_active( 'media' ) ) : ?>
</div>
<?php endif; ?>

</script>
