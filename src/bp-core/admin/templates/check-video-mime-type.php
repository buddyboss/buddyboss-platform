<?php
/**
 * BuddyBoss Admin Screen.
 *
 * This file contains information about BuddyBoss.
 *
 * @package BuddyBoss
 * @since BuddyBoss 1.7.0
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
			<h1 id="bp-hello-title" tabindex="-1"><?php esc_html_e( 'Upload a file', 'buddyboss' ); ?></h1>
		</div>
	</div>

	<div class="bp-hello-content">
		<h2><?php esc_html_e( 'What is a MIME type?', 'buddyboss' ); ?></h2>
		<p><?php esc_html_e( 'A MIME type is a way of identifying files on the Internet according to their nature and format. Your server needs this information to determine what kind of file has been uploaded, otherwise it will reject the file.', 'buddyboss' ); ?></p>
		<h2><?php esc_html_e( 'Upload a file to check its MIME type', 'buddyboss' ); ?></h2>
		<p><?php esc_html_e( 'We\'ve made it easy to figure out your file\'s MIME type. Upload any sample file below and click "Get MIME Type" and we will display the MIME type for that file. For example, if you wanted to check the MIME type for the .zip extension, you would need to upload any random ZIP file. Once we have determined the correct MIME Type, click to enter that MIME type into the table cell.', 'buddyboss' ); ?></p>
		<form id="document-upload-check-mime-type" action="" method="post">
			<?php esc_html_e( 'Choose a file to upload:', 'buddyboss' ); ?>
			<label for="bp-document-file-input" class="screen-reader-text"><?php esc_html_e( 'Add file to check MIME Type', 'buddyboss' ); ?></label>
			<input type="file" name="bp-document-file-input" id="bp-document-file-input">
			<label for="input-mime-type-submit-check" class="screen-reader-text"><?php esc_html_e( 'Get MIME Type', 'buddyboss' ); ?></label>
			<input type="submit" id="input-mime-type-submit-check" value="<?php esc_html_e( 'Get MIME Type', 'buddyboss' ); ?>" name="submit">
		</form>
		<br /><br />

		<div style="display: none;" class="show-document-mime-type">
			<span class="info"><?php esc_html_e( 'Your uploaded file\'s MIME type is: ', 'buddyboss' ); ?></span>
			<label for="mime-type" class="screen-reader-text"><?php esc_html_e( 'MIME Type', 'buddyboss' ); ?></label>
			<input type="text" class="type" id="mime-type" value="" />
			<button class="mime-copy" id="mime-copy"><?php esc_html_e( 'Use this MIME type', 'buddyboss' ); ?></button>
		</div>
	</div>

</div>
