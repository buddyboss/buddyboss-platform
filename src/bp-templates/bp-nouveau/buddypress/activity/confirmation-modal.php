<?php
/**
 * The template for pinpost confirmation modal
 *
 * This template can be overridden by copying it to yourtheme/buddypress/document/confirmation-modal.php.
 *
 * @since   2.4.60
 * @package BuddyBoss\Core
 */

?>

<div id="bb-confirmation-modal" class="bb-confirmation-modal bb-action-popup" style="display: none;">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">
					<header class="bb-model-header">
						<h4><?php esc_html_e( 'Pin Post', 'buddyboss' ); ?></h4>
						<a class="bb-close-action-popup bb-model-close-button" id="bp-confirmation-model-close" href="#">
							<span class="bb-icon-l bb-icon-times"></span>
						</a>
					</header>
					<div class="bb-action-popup-content">
					</div>
				</div>
			</div>
		</div>
	</transition>
</div>
