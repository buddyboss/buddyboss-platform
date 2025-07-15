<?php
/**
 * BuddyBoss Uploader templates.
 *
 * This template is used to create the BuddyBoss Uploader Backbone views.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<script type="text/html" id="tmpl-upload-window">
	<?php if ( ! _device_can_upload() ) : ?>
		<h3 class="upload-instructions"><?php esc_html_e( 'The web browser on your device cannot be used to upload files.', 'buddyboss' ); ?></h3>
	<?php elseif ( is_multisite() && ! is_upload_space_available() ) : ?>
		<h3 class="upload-instructions"><?php esc_html_e( 'Upload Limit Exceeded', 'buddyboss' ); ?></h3>
	<?php else : ?>
		<div id="{{data.container}}">
			<div id="{{data.drop_element}}">
				<div class="drag-drop-inside bb-rl-drag-drop-wrapper">

					<p class="drag-drop-buttons">
						<div class="bb-rl-drag-drop-button-wrap">
							<i class="bb-icons-rl-camera"></i>
							<label for="{{data.browse_button}}" class="<?php echo is_admin() ? 'screen-reader-text' : 'bp-screen-reader-text'; ?>">
								<?php esc_html_e( 'Select your file', 'buddyboss' ); ?>
							</label>
							<input id="{{data.browse_button}}" type="button" value="<?php esc_attr_e( 'Select your file', 'buddyboss' ); ?>" class="button" />
						</div>
					</p>

					<p class="drag-drop-info"><?php esc_html_e( 'Add Photos', 'buddyboss' ); ?></p>
					<p class="drag-drop-subtitle"><?php esc_html_e( 'Or drag and drop', 'buddyboss' ); ?></p>
				</div>
			</div>
		</div>
	<?php endif; ?>
</script>

<script type="text/html" id="tmpl-progress-window">
	<div id="{{data.id}}">
		<div class="bp-progress">
			<div class="bp-bar"></div>
		</div>
		<div class="file-progress"><span class="percent-value">0</span><?php esc_html_e( '% complete', 'buddyboss' ); ?></div>
		<div class="filename">{{data.filename}}</div>
	</div>
</script>
