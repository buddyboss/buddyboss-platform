<?php
/**
 * ReadyLaunch - BuddyBoss - Video Activity Album Moves.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>
<div class="bb-rl-video-move-file bb-rl-video-move-photo" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="bb-rl-video-create-album-popup" class="modal-container bb-rl-has-folderlocationUI">
					<header class="bb-model-header">
						<h4><span class="target_name"><?php esc_html_e( 'Move Video to...', 'buddyboss' ); ?></span>
						</h4>
					</header>
					<div class="bb-rl-field-wrap">
						<?php
						bp_get_template_part( 'video/location-move' );
						bp_get_template_part( 'video/video-create-album' );
						?>
					</div>
					<footer class="bb-model-footer">
						<a href="#" class="bb-rl-video-open-create-popup-album"><?php esc_html_e( 'Create new album', 'buddyboss' ); ?></a>
						<a class="bb-rl-ac-video-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bb-rl-video-move bb-rl-video-move-activity" id="" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
