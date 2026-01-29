<?php
/**
 *  Template for showing the progress.
 */

?>
<div class="bp-learndash-progress-bar">
	<p class="bp-learndash-progress-bar-label"><?php echo esc_html( $label ); ?></p>
	<progress value="<?php echo esc_attr( $progress ); ?>" max="100"></progress>
	<span class="bp-learndash-progress-bar-percentage"><?php echo esc_html( $progress ); ?>% <?php esc_attr_e( 'Complete', 'buddyboss' ); ?></span>
</div>
