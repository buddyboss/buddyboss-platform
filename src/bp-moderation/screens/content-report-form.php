<?php
/**
 * Content report form
 *
 * @package BuddyBoss
 */

?>

<div id="content-report" class="content-report-popup bb-modal mfp-hide">
	<h2>
		<?php
		esc_html_e( 'Report Content', 'buddyboss' );
		?>
		<button title="Close (Esc)" type="button" class="mfp-close"></button>
	</h2>
	<?php
	$reports_terms = get_terms(
		'bpm_category',
		array(
			'hide_empty' => false,
			'fields'     => 'id=>name',
		)
	);
	?>
	<div class="bb-report-type-wrp">
		<form id="bb-report-content" action="javascript:void(0);">
			<div class="form-item">
				<?php
				if ( ! empty( $reports_terms ) ) {
					$count = 1;
					foreach ( $reports_terms as $key => $reports_term ) {
						$checked = ( 1 === $count ) ? 'checked' : '';
						?>
						<label for="report-category-<?php echo esc_attr( $key ); ?>">
							<input type="radio" id="report-category-<?php echo esc_attr( $key ); ?>"
								   name="report_category"
								   value="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $checked ); ?>>
							<?php echo esc_html( $reports_term ); ?>
						</label>
						<br/>
						<?php
						$count ++;
					}
					?>
					<?php
				}
				?>
				<label for="report-category-other">
					<input type="radio" id="report-category-other" name="report_category"
						   value="other">
					<?php esc_html_e( 'Other', 'buddyboss' ); ?>
				</label>

				<label for="report-note">
					<input id="report-note" type="text" name="note" class="bp-other-report-cat" style="display: none;"/>
				</label>
			</div>
			<div class="form-item">
				<input type="button" class="bb-cancel-report-content"
					   value="<?php esc_attr_e( 'Cancel', 'buddyboss' ); ?>"/>
				<input type="submit" value="<?php esc_attr_e( 'Send Report', 'buddyboss' ); ?>" class="report-submit"/>
				<input type="hidden" name="content_id" class="bp-content-id"/>
				<input type="hidden" name="content_type" class="bp-content-type"/>
				<input type="hidden" name="_wpnonce" class="bp-nonce"/>
			</div>
		</form>
		<div class="bp-report-form-err"></div>
	</div>
</div>
