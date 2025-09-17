<?php
/**
 * The template for media activity album move
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/media-move.php.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Core
 * @version 1.5.6
 */

?>
<div class="bp-media-move-file bp-media-move-photo" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI">
					<header class="bb-model-header">
						<h4><span class="target_name"><?php esc_html_e( 'Move Photo to...', 'buddyboss' ); ?></span></h4>
					</header>
					<div class="bb-field-wrap">
						<?php bp_get_template_part( 'media/location-move' ); ?>
						<?php bp_get_template_part( 'media/media-create-album' ); ?>
					</div>
					<footer class="bb-model-footer">
						<a href="#" class="bp-media-open-create-popup-folder"><?php esc_html_e( 'Create new album', 'buddyboss' ); ?></a>
						<a class="ac-media-close-button" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button bp-media-move bp-media-move-activity" id="" href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div>
