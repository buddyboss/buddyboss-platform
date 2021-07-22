<?php
/**
 * Content report form.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss
 */

?>

<div id="content-report" class="content-report-popup moderation-popup mfp-hide">
	<div class="modal-mask bb-white bbm-model-wrap bbm-uploader-model-wrap">
		<div class="modal-wrapper">
			<div class="modal-container">
				<header class="bb-model-header">
					<h4><?php esc_html_e( 'Report', 'buddyboss' ); ?> <span class="bp-reported-type"></span></h4>
					<button title="<?php esc_html_e( 'Close (Esc)', 'buddyboss' ); ?>" type="button" class="mfp-close">
						<span class="bb-icon bb-icon-close"></span>
					</button>
				</header>
				<?php
				$reports_terms = get_terms(
					'bpm_category',
					array(
						'hide_empty' => false,
					)
				);
				?>
				<div class="bb-report-type-wrp">
					<form id="bb-report-content" action="javascript:void(0);">

						<?php
						if ( ! empty( $reports_terms ) ) {
							$count = 1;
							foreach ( $reports_terms as $reports_term ) {
								$checked = ( 1 === $count ) ? 'checked' : '';
								?>
								<div class="form-item">
									<label for="report-category-<?php echo esc_attr( $reports_term->term_id ); ?>">
										<input type="radio" id="report-category-<?php echo esc_attr( $reports_term->term_id ); ?>" name="report_category" value="<?php echo esc_attr( $reports_term->term_id ); ?>" <?php echo esc_attr( $checked ); ?>>
										<span><?php echo esc_html( $reports_term->name ); ?></span>
									</label>
									<span><?php echo esc_html( $reports_term->description ); ?></span>
								</div>
								<?php
								$count ++;
							}
						}
						?>

						<div class="form-item">
							<label for="report-category-other">
								<input type="radio" id="report-category-other" name="report_category" value="other">
								<span><?php esc_html_e( 'Other', 'buddyboss' ); ?></span>
							</label>
						</div>
						<div class="form-item bp-hide">
							<label for="report-note">
								<textarea id="report-note" type="text" name="note" class="bp-other-report-cat"></textarea> </label>
						</div>
						<footer class="bb-model-footer">
							<input type="button" class="bb-cancel-report-content button" value="<?php esc_attr_e( 'Cancel', 'buddyboss' ); ?>"/>
							<button type="submit" class="report-submit button"><?php esc_attr_e( 'Report', 'buddyboss' ); ?></button>
							<input type="hidden" name="content_id" class="bp-content-id"/>
							<input type="hidden" name="content_type" class="bp-content-type"/>
							<input type="hidden" name="_wpnonce" class="bp-nonce"/>
						</footer>
					</form>
					<div class="bp-report-form-err"></div>
				</div>
			</div>
		</div>
	</div>

</div>
