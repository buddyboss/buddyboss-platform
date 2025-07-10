<?php
/**
 * ReadyLaunch - The template for activity document folder move.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>

<div class="bb-rl-media-move-folder bb-rl-modal-move-file" id="bb-rl-media-move-folder" style="display: none;">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div id="bb-rl-media-create-album-popup" class="bb-rl-modal-container bb-rl-has-folderlocationUI">
					<header class="bb-rl-modal-header">
						<h4><span class="target_name"></span></h4>
					</header>
					<div class="bb-rl-field-wrap">
						<?php bp_get_template_part( 'document/location-move' ); ?>
						<?php bp_get_template_part( 'document/document-create-folder' ); ?>
					</div>
					<div class="error" style="display: none;"></div>
					<footer class="bb-rl-model-footer">
						<a href="#" class="bb-rl-create-album bb-rl-document-open-create-popup-folder"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Create Folder', 'buddyboss' ); ?></a>
						<a class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bb-rl-ac-folder-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small bb-rl-folder-move" id="" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
