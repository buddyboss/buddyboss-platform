<?php
/**
 * The template for displaying activity post case heading
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-activity-schedule-details.php.
 *
 * @since   BuddyBoss 1.8.6
 * @version 1.8.6
 */

?>
<script type="text/html" id="tmpl-activity-schedule-details">
	
	<# if ( data.activity_schedule_date !== null && data.activity_schedule_time !== null ) {  #>
		<span class="activity-post-schedule-details">
			<i class="bb-icon-f bb-icon-clock"></i><strong><?php esc_html_e( 'Posting:', 'buddyboss' ); ?></strong> {{{data.activity_schedule_date}}} <?php esc_html_e( 'at', 'buddyboss' ); ?> {{{data.activity_schedule_time}}} {{{data.activity_schedule_meridiem}}}
		</span>
	<# } #>
</script>
