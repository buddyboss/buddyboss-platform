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
<div id="" class="bp-hello-mime" role="dialog" aria-labelledby="bp-hello-title" style="display: none;">
	<div class="bp-hello-header" role="document">
		<div class="bp-hello-close">
			<button type="button" class="close-modal button bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_attr_e( 'Close pop-up', 'buddyboss' ); ?>">
				<?php esc_html_e( 'Close', 'buddyboss' ); ?>
			</button>
		</div>

		<div class="bp-hello-title">
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'Upload a sample file to determine it\'s MIME Type', 'buddyboss' ); ?></h1>
		</div>
	</div>

	<div class="bp-hello-content">
        <br /><br />
        <form id="document-upload-check-mime-type" action="" method="post">
	        <?php esc_html_e( 'Choose a file to upload:', 'buddyboss' ); ?>
            <input type="file" name="bp-document-file-input" id="bp-document-file-input">
            <input type="submit" id="input-mime-type-submit-check" value="<?php esc_html_e( 'Get MIME Type', 'buddyboss' ); ?>" name="submit">
        </form>
		<br /><br />

		<div style="display: none;" class="show-document-mime-type">
			<span class="info"><?php esc_html_e( 'Your Uploaded Document MIME Type is: ', 'buddyboss' ); ?></span>
			<label for="mime-type" class="screen-reader-text"><?php esc_html_e( 'MIME Type', 'buddyboss' ); ?></label>
			<input type="text" class="type" id="mime-type" value="" />
			<button class="mime-copy" id="mime-copy"><?php esc_html_e( 'Use this mime type', 'buddyboss' ); ?></button>
		</div>
	</div>

</div>
