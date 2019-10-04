<div class="ld-report-no-data">
	<aside class="bp-feedback bp-template-notice info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php _e( 'Sorry, no report data was found.', 'buddyboss' ); ?></p>
	</aside>
</div>

<div class="ld-report-user-stats">
	<div class="user-info">
		<div class="user-avatar">
			<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/mystery-group.png' ); ?>" class="avatar avatar-300 photo" width="300" height="300" alt="">
		</div>
		<div class="user-name">
			<h5 class="list-title member-name"><?php echo __( 'All Students', 'buddyboss' ); ?></h5>
		</div>
	</div>
</div>

<div class="bp_ld_report_table_wrapper">
	<h2><?php echo $completed_table_title; ?></h2>
	<table class="bp_ld_report_table" data-completed="true"></table>
</div>

<div class="bp_ld_report_table_wrapper">
	<h2><?php echo $incompleted_table_title; ?></h2>
	<table class="bp_ld_report_table" data-completed="false"></table>
</div>

