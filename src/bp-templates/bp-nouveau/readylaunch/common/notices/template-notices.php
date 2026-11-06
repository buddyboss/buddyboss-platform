<?php
/**
 * ReadyLaunch - Template notices template.
 *
 * This template handles the display of system notices, messages,
 * and feedback to users throughout the platform.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<aside class="<?php bboss_nouveau_template_message_classes(); ?>">
	<span class="bp-icon" aria-hidden="true"></span>
	<?php bboss_nouveau_template_message(); ?>

	<?php if ( bboss_nouveau_has_dismiss_button() ) : ?>

		<button type="button" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Close', 'buddyboss-platform' ); ?>" aria-label="<?php esc_attr_e( 'Close this notice', 'buddyboss-platform' ); ?>" data-bp-close="<?php bboss_nouveau_dismiss_button_type(); ?>">
			<span class="dashicons dashicons-dismiss" aria-hidden="true"></span>
		</button>

	<?php endif; ?>
</aside>
