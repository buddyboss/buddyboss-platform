<?php
/**
 * ReadyLaunch - Activity Media JS Templates.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<script type="text/html" id="tmpl-activity-media">
	<div class="dropzone closed media-dropzone" id="bb-rl-activity-post-media-uploader"></div>
	<div class="activity-post-default-template" style="display:none;">
		<div class="dz-preview">
			<div class="dz-image">
				<img data-dz-thumbnail />
			</div>
			<div class="dz-error-title"><?php esc_html_e( 'Upload Failed', 'buddyboss' ); ?></div>
			<div class="dz-details">
				<div class="dz-progress"><span data-dz-progress></span> <?php esc_html_e( 'Complete', 'buddyboss' ); ?></div>
				<div class="dz-filename" data-dz-name></div>
			</div>
			<div class="dz-progress-ring-wrap">
				<i class="bb-icons-rl-fill bb-icons-rl-camera"></i>
				<svg class="dz-progress-ring" width="48" height="48">
					<circle class="progress-ring__circle" stroke="#4946FE" stroke-width="3" fill="transparent" r="21.5" cx="24" cy="24" stroke-dasharray="185.354, 185.354" stroke-dashoffset="185" />
				</svg>
			</div>
			<div class="dz-error-message"><span data-dz-errormessage></span></div>
		</div>
	</div>
</script>
