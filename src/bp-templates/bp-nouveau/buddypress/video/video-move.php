<?php
/**
 * BuddyBoss - Video Activity Album Move
 *
 * This template can be overridden by copying it to yourtheme/buddypress/video/video-move.php.
 *
 * @since   BuddyBoss 1.7.0
 * @package BuddyBoss\Core
 * @version 1.7.0
 */

?>
<div class="bp-video-move-file bp-video-move-photo" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-video-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><span class="target_name"><?php esc_html_e( 'Move Video to...', 'buddyboss' ); ?></span></h4>
					</header>
					<div class="bb-field-wrap">
						<?php bp_get_template_part( 'video/location-move' ); ?>
						<?php bp_get_template_part( 'video/video-create-album' ); ?>
					</div>
					<footer class="bb-model-footer">
						<a href="#" class="bp-video-open-create-popup-album"><?php esc_html_e( 'Create new album', 'buddyboss' ); ?></a>
						<a class="ac-video-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bp-video-move bp-video-move-activity" id="" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
