<?php
/**
 * LearnDash Group Courses Progress Template
 *
 * @package BuddyBoss\Core
 * @subpackage BP_Integrations\LearnDash\Templates
 * @version 1.0.0
 * @since BuddyBoss 2.9.00
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="bp-learndash-progress-bar">
	<p class="bp-learndash-progress-bar-label"><?php echo esc_html( $label ); ?></p>
	<progress value="<?php echo esc_attr( $progress ); ?>" max="100"></progress>
	<span class="bp-learndash-progress-bar-percentage"><?php echo esc_html( $progress ); ?>% <?php esc_html_e( 'Complete', 'buddyboss' ); ?></span>
</div>
