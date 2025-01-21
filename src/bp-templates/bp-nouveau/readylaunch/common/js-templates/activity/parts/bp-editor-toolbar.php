<?php
/**
 * ReadyLaunch - The template for displaying edit toolbar.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-editor-toolbar">
	<div class="bb-rl-post-elements-buttons-item bb-rl-show-toolbar" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
		<a href="#" id="bb-rl-show-toolbar-button" class="bb-rl-toolbar-button bp-tooltip" aria-label="<?php esc_attr_e( 'Show formatting', 'buddyboss' ); ?>">
			<span class="bb-icon-l bb-icon-font"></span>
		</a>
	</div>
	<div class="bb-rl-post-elements-buttons-item bb-rl-post-mention bp-tooltip" data-bp-tooltip-pos="up-right" data-bp-tooltip="<?php esc_attr_e( 'Mention someone', 'buddyboss' ); ?>">
		<span class="bb-rl-toolbar-button">
			<i class="bb-icon-l bb-icon-at"></i>
		</span>
	</div>
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
		<div class="bb-rl-post-elements-buttons-item bb-rl-post-emoji bp-tooltip" data-bp-tooltip-pos="up-right" data-bp-tooltip="<?php esc_attr_e( 'Emoji', 'buddyboss' ); ?>"></div>
	<?php endif; ?>
</script>
