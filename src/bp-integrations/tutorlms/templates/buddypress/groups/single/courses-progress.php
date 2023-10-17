<?php
/**
 *  Template for showing the progress.
 */

?>
<div class="bb-tutorlms-progress-bar">
	<p class="bb-tutorlms-progress-bar-label"><?php echo esc_html( $label ); ?></p>
	<progress value="<?php echo esc_attr( $progress ); ?>" max="100"></progress>
	<span class="bb-tutorlms-progress-bar-percentage"><?php echo esc_html( $progress ); ?>% <?php esc_attr_e( 'Complete', 'buddyboss' ); ?></span>
</div>
