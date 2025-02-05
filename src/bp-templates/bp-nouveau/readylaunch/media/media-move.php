<?php
/**
 * ReadyLaunch - The template for media activity album move.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>
<div class="bb-rl-media-move-file bb-rl-media-move-photo" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI">
					<header class="bb-model-header">
						<h4><span class="target_name"><?php esc_html_e( 'Move Photo to...', 'buddyboss' ); ?></span>
						</h4>
					</header>
					<div class="bb-rl-field-wrap">
						<?php bp_get_template_part( 'media/location-move' ); ?>
						<?php bp_get_template_part( 'media/media-create-album' ); ?>
					</div>
					<footer class="bb-model-footer">
						<a href="#" class="bb-rl-media-open-create-popup-folder"><?php esc_html_e( 'Create new album', 'buddyboss' ); ?></a>
						<a class="bb-rl-ac-media-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bb-rl-media-move bb-rl-media-move-activity" id="" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
