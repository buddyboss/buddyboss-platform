<div class="ld-report-no-data">
	<aside class="bp-feedback bp-template-notice info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php _e( 'Sorry, no data was found.', 'buddyboss' ); ?></p>
	</aside>
</div>

<div class="bp_ld_report_table_wrapper">
	<h2><?php echo $completed_table_title; ?></h2>
	<table class="bp_ld_report_table" data-completed="true"></table>
</div>

<div class="bp_ld_report_table_wrapper">
	<h2><?php echo $incompleted_table_title; ?></h2>
	<table class="bp_ld_report_table" data-completed="false"></table>
</div>
