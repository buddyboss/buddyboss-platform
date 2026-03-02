<?php
/**
 * ReadyLaunch - Video Move template.
 *
 * Template for moving videos between albums or locations.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-rl-video-move-file bb-rl-video-move-photo bb-rl-modal-move-file" style="display: none;">
	<transition name="modal">
		<div class="bb-rl-modal-mask bb-white bbm-model-wrap">
			<div class="bb-rl-modal-wrapper">
				<div id="bb-rl-video-create-album-popup" class="bb-rl-modal-container bb-rl-has-folderlocationUI">
					<header class="bb-rl-modal-header">
						<h4><span class="target_name"><?php esc_html_e( 'Move Video to...', 'buddyboss' ); ?></span>
						</h4>
					</header>
					<div class="bb-rl-field-wrap">
						<?php
						bp_get_template_part( 'video/location-move' );
						bp_get_template_part( 'video/video-create-album' );
						?>
					</div>
					<footer class="bb-rl-model-footer">
						<a href="#" class="bb-rl-create-album bb-rl-video-open-create-popup-album"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
						<a class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bb-rl-ac-video-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small bb-rl-video-move bb-rl-video-move-activity" id="" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
