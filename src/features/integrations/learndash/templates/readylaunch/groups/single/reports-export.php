<?php
/**
 * LearnDash Group Reports Export Template
 *
 * @package BuddyBoss\Core
 * @subpackage BP_Integrations\LearnDash\Templates
 * @version 1.0.0
 * @since BuddyBoss 2.9.00
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="bp-learndash-group-courses-export-csv">
	<a href="#" class="button small ld-report-export-csv">
		<?php esc_html_e( 'Export CSV', 'buddyboss' ); ?>
	</a>
	<span class="export-indicator">
		<span class="export-current-step"></span>/<span class="export-total-step"></span>
	</span>
</div>
