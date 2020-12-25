<?php
/**
 * BuddyBoss Admin Screen.
 *
 * This file contains information about BuddyBoss.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div id="bp-hello-backdrop" style="display: none;"></div>
<div id="bp-hello-container" class="bp-hello-buddyboss" role="dialog" aria-labelledby="bp-hello-title"
style="display: none;">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="left"
					data-bp-tooltip="
					<?php
					esc_attr_e(
						'Close pop-up',
						'buddyboss'
					);
					?>
						">
				<?php esc_html_e( 'Close', 'buddyboss' ); ?>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'Notice', 'buddyboss' ); ?></h1>
		</div>
	</div>
	<div class="bp-hello-content">

	</div>

	<div class="bp-hello-footer">
		<button class="close-modal button">
			<?php
			esc_html_e( 'Cancel', 'buddyboss' );
			?>
		</button>
		<button class="component-deactivate button">
			<?php
			esc_html_e( 'Yes I understand, Deactivate', 'buddyboss' );
			?>
		</button>
	</div>
</div>
