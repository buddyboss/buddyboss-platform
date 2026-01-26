<?php
/**
 * LearnDash Group Reports Tables Template
 *
 * @package BuddyBoss\Core
 * @subpackage BP_Integrations\LearnDash\Templates
 * @version 1.0.0
 * @since BuddyBoss 2.9.00
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="ld-report-no-data">
	<aside class="bp-feedback bp-template-notice info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php esc_html_e( 'Sorry, no data was found.', 'buddyboss' ); ?></p>
	</aside>
</div>

<div class="bp_ld_report_table_wrapper">
	<h2><?php echo esc_html( $completed_table_title ); ?></h2>
	<table class="bp_ld_report_table" data-completed="true"></table>
</div>

<div class="bp_ld_report_table_wrapper">
	<h2><?php echo esc_html( $incompleted_table_title ); ?></h2>
	<table class="bp_ld_report_table" data-completed="false"></table>
</div>
