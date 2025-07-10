<?php
/**
 * ReadyLaunch - Template notices template.
 *
 * This template handles the display of system notices, messages,
 * and feedback to users throughout the platform.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<aside class="<?php bp_nouveau_template_message_classes(); ?>">
	<span class="bp-icon" aria-hidden="true"></span>
	<?php bp_nouveau_template_message(); ?>

	<?php if ( bp_nouveau_has_dismiss_button() ) : ?>

		<button type="button" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Close', 'buddyboss' ); ?>" aria-label="<?php esc_attr_e( 'Close this notice', 'buddyboss' ); ?>" data-bp-close="<?php bp_nouveau_dismiss_button_type(); ?>">
			<span class="dashicons dashicons-dismiss" aria-hidden="true"></span>
		</button>

	<?php endif; ?>
</aside>
