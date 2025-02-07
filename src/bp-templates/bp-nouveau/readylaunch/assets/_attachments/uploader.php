<?php
/**
 * BuddyPress Uploader templates.
 *
 * This template is used to create the BuddyPress Uploader Backbone views.
 *
 * @since BuddyPress 2.3.0
 * @version 3.1.0
 */

?>
<script type="text/html" id="tmpl-upload-window">
	<?php if ( ! _device_can_upload() ) : ?>
		<h3 class="upload-instructions"><?php esc_html_e( 'The web browser on your device cannot be used to upload files.', 'buddyboss' ); ?></h3>
	<?php elseif ( is_multisite() && ! is_upload_space_available() ) : ?>
		<h3 class="upload-instructions"><?php esc_html_e( 'Upload Limit Exceeded', 'buddyboss' ); ?></h3>
	<?php else : ?>
		<div id="{{data.container}}">
			<div id="{{data.drop_element}}">
				<div class="drag-drop-inside">
					<p class="drag-drop-info"><?php esc_html_e( 'Drop your image here', 'buddyboss' ); ?></p>

					<p class="drag-drop-buttons">
						<label for="{{data.browse_button}}" class="<?php echo is_admin() ? 'screen-reader-text' : 'bp-screen-reader-text'; ?>">
							<?php esc_html_e( 'Select your file', 'buddyboss' ); ?>
						</label>
						<input id="{{data.browse_button}}" type="button" value="<?php esc_attr_e( 'Select your file', 'buddyboss' ); ?>" class="button" />
					</p>
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
		<div class="filename">{{data.filename}}</div>
	</div>
</script>
