<?php
/**
 * BP Nouveau messages editor toolbar
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-formatting-toolbar.php.
 *
 * @since   BuddyBoss 2.1.4
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-whats-new-formatting-toolbar">

<?php if ( ! bp_is_active( 'media' ) ) : ?>
<div class="media-off">
<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) ) : ?>

		<div class="post-elements-buttons-item show-toolbar"  data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_attr_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
			<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
				<span class="bb-icon-l bb-icon-font"></span>
			</a>
		</div>

		<div class="post-elements-buttons-item post-emoji bp-tooltip post-media-emoji-support" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>"></div>

	<?php endif; ?>

<?php if ( ! bp_is_active( 'media' ) ) : ?>
</div>
<?php endif; ?>

</script>
