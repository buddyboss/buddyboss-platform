<?php
/**
 * ReadyLaunch - The template for displaying activity video.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-video">
	<div class="dropzone closed video-dropzone" id="bb-rl-activity-post-video-uploader"></div>
	<div class="activity-post-video-template" style="display:none;">
		<div class="dz-preview dz-file-preview well" id="dz-preview-template">
			<div class="dz-image">
				<img data-dz-thumbnail />
			</div>
			<div class="dz-details">
				<div class="dz-progress"><span data-dz-progress></span> <?php esc_html_e( 'Complete', 'buddyboss' ); ?></div>
				<div class="dz-filename" data-dz-name></div>
			</div>
			<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
			<div class="dz-progress-ring-wrap">
				<i class="bb-icons-rl-fill bb-icons-rl-video-camera"></i>
				<svg class="dz-progress-ring" width="48" height="48">
				<circle class="progress-ring__circle" stroke="#4946FE" stroke-width="3" fill="transparent" r="21.5" cx="24" cy="24" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
				</svg>
			</div>
			<div class="dz-error-message"><span data-dz-errormessage></span></div>
		</div>
	</div>
</script>
