<?php
/**
 * The template for activity document folder move
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/activity-document-folder-move.php.
 *
 * @since   BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 * @version 1.4.0
 */

?>

<div class="bp-media-move-folder" id="bp-media-move-folder" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><span class="target_name"></span></h4>
					</header>
					<div class="bb-field-wrap">
						<?php bp_get_template_part( 'document/location-move' ); ?>
						<?php bp_get_template_part( 'document/document-create-folder' ); ?>
					</div>
					<div class="error" style="display: none;"></div>
					<footer class="bb-model-footer">
						<a href="#" class="bp-document-open-create-popup-folder"><?php esc_html_e( 'Create new folder', 'buddyboss' ); ?></a>
						<a class="ac-folder-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bp-folder-move" id="" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
