<?php
/**
 * BuddyBoss Admin Screen.
 *
 * This file contains information about BuddyBoss.
 *
 * @package BuddyBoss
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div id="bp-hello-backdrop" style="display: none;"></div>

<div id="bp-hello-container" class="bp-hello-buddyboss" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss' ); ?>">
				<?php esc_html_e( 'Close', 'buddyboss' ); ?>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'Check Your Document MimeType', 'buddyboss' ); ?></h1>
		</div>
	</div>

	<div class="bp-hello-content">
        <br /><br />
        <form id="document-upload-check-mime-type" action="" method="post">
	        <?php esc_html_e( 'Select document file to upload:', 'buddyboss' ); ?>
            <input type="file" name="bp-document-file-input" id="bp-document-file-input">
            <input type="submit" value="Get MimeType" name="submit">
        </form>
		<br /><br />

		<div style="display: none;" class="show-document-mime-type">
			<span class="info"><?php esc_html_e( 'Your Uploaded Document MimeType is: ', 'buddyboss' ); ?></span>
			<input type="text" class="type" value="" />
			<button class="mime-copy">Copy to Clipboard</button>
		</div>
	</div>

</div>
