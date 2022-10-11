<?php
/**
 * Reported content popup.
 *
 * @since   BuddyBoss 1.7.3
 * @package BuddyBoss
 */

?>

<div id="reported-content" class="content-report-popup moderation-popup mfp-hide">
	<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
		<div class="modal-wrapper">
			<div class="modal-container">
				<header class="bb-model-header">
					<h4>
						<?php printf( '%s <span class="bp-reported-type"></span>', esc_html__( 'Report', 'buddyboss' ) ); ?>
					</h4>
					<button title="<?php esc_html_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close">
						<span class="bb-icon-l bb-icon-times"></span>
					</button>
				</header>

				<div class="bb-report-type-wrp">
					<?php printf( '%s <span class="bp-reported-type"></span>.', esc_html__( 'You have already reported this', 'buddyboss' ) ); ?>
				</div>
			</div>
		</div>
	</div>
</div>
