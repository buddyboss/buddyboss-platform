<?php
/**
 * ReadyLaunch - The template for activity document folder move.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>

<div class="bb-rl-media-move-folder" id="bb-rl-media-move-folder" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI">
					<header class="bb-model-header">
						<h4><span class="target_name"></span></h4>
					</header>
					<div class="bb-rl-field-wrap">
						<?php bp_get_template_part( 'document/location-move' ); ?>
						<?php bp_get_template_part( 'document/document-create-folder' ); ?>
					</div>
					<div class="error" style="display: none;"></div>
					<footer class="bb-model-footer">
						<a href="#" class="bb-rl-document-open-create-popup-folder"><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></a>
						<a class="bb-rl-ac-folder-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bb-rl-folder-move" id="" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
