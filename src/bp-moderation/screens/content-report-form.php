<?php
/**
 * Content report form
 */
?>

<div id="content-report" class="content-report-popup bb-modal mfp-hide">
	<h2>
		<?php
		esc_html_e( 'Report Content', 'buddyboss' );
		?>
	</h2>
	<?php
	$reports_terms = get_terms( 'bpm_category', array(
		'hide_empty' => false,
		'fields'     => 'id=>name',
	) );
	?>
	<div class="bb-report-type-wrp">
		<form id="bb-report-content" action="javascript:void(0);">
			<div class="form-item">
				<?php
				if ( ! empty( $reports_terms ) ) {
					foreach ( $reports_terms as $key => $reports_term ) {
						?>
						<label for="report-type-<?php echo esc_attr( $key ) ?>">
							<input type="radio" id="report-type-<?php echo esc_attr( $key ); ?>" name="report_type"
								   value="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $reports_term ); ?>
						</label>
						<br/>
						<?php
					}
					?>
					<?php
				}
				?>
				<label for="report-type-other">
					<input type="radio" id="report-type-other" name="report_type"
						   value="other">
					<?php esc_html_e( 'Other', 'buddyboss' ); ?>
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
	</div>
</div>
