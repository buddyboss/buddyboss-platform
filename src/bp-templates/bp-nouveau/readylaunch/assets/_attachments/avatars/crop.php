<?php
/**
 * BuddyPress Avatars crop template.
 *
 * This template is used to create the crop Backbone views.
 *
 * @since BuddyPress 2.3.0
 * @version 3.1.0
 */

?>
<script id="tmpl-bp-avatar-item" type="text/html">
	<div class="bb-rl-avatar-panel-header">
		<h3><?php esc_html_e( 'Crop Photo', 'buddyboss' ); ?></h3>
		<a class="bb-rl-modal-close-button avatar-crop-cancel" href="#">
			<i class="bb-icons-rl-x"></i>
		</a>
	</div>
	<div class="bb-rl-avatar-panel">
		<div id="avatar-to-crop">
			<img data-skip-lazy="" class="skip-lazy" src="{{{data.url}}}"/>
		</div>
		<div class="bb-rl-avatar-zoom-controls">
			<input type="range" class="bb-rl-zoom-slider" min="100" max="200" value="100">
		</div>
	</div>
	<div class="avatar-crop-management">
		<div id="avatar-crop-actions">
			<button type="button" class="button avatar-crop-submit"><?php esc_html_e( 'Crop', 'buddyboss' ); ?></button>
            <a class="avatar-crop-cancel" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
		</div>
	</div>
</script>
