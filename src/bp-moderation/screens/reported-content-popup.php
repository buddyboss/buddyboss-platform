<?php
/**
 * Reported content popup
 *
 * @since   BuddyBoss X.X.X
 * @package BuddyBoss
 */

?>

<div id="reported-content" class="content-report-popup moderation-popup mfp-hide">
	<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
		<div class="modal-wrapper">
			<div class="modal-container">
				<header class="bb-model-header">
					<h4>
                        <?php printf('%s <span class="bp-reported-type"></span>', esc_html__('Report', 'buddyboss') ); ?>
                    </h4>
					<button title="<?php esc_html_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"><path fill="none" stroke="#FFF" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 1L1 13m12 0L1 1" opacity=".7"></path></svg>
                    </button>
				</header>

				<div class="bb-report-type-wrp">
					<?php printf('%s <span class="bp-reported-type"></span>', esc_html__('You have already reported this', 'buddyboss') ); ?>
				</div>
			</div>
		</div>
	</div>
</div>
