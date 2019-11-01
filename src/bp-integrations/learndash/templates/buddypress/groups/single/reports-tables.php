<?php
if ( ( groups_is_user_mod( bp_loggedin_user_id(), bp_get_current_group_id() ) || groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() ) || bp_current_user_can( 'bp_moderate' ) ) && isset( $_GET ) && isset( $_GET['user'] ) && '' === $_GET['user'] ) { ?>
		<div class="ld-report-user-stats">
			<div class="user-info">
				<div class="user-avatar">
					<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/all-students.svg' ); ?>" class="avatar avatar-300 photo" width="300" height="300" alt="">
				</div>
				<div class="user-name">
					<h5 class="list-title member-name"><?php echo __( 'All Students', 'buddyboss' ); ?></h5>
				</div>
			</div>
		</div> 
		<?php
} else {
	?>
		<?php if ( ! bp_current_user_can( 'bp_moderate' ) || ! groups_is_user_mod( bp_loggedin_user_id(), bp_get_current_group_id() ) || ! groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() ) ) { ?>
			<?php if ( ! bp_current_user_can( 'bp_moderate' ) ) { ?>
			<div class="ld-report-user-stats">
				<div class="user-info">
					<div class="user-avatar">
						<a href="<?php echo bp_core_get_user_domain( bp_loggedin_user_id() ); ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => bp_loggedin_user_id() ) ); ?></a>
					</div>
					<div class="user-name">
						<h5 class="list-title member-name"><a href="<?php echo bp_core_get_user_domain( bp_loggedin_user_id() ); ?>"><?php echo bp_core_get_user_displayname( bp_loggedin_user_id() ); ?></a></h5>
						<p class="item-meta"><?php echo __( 'Student', 'buddyboss' ); ?></p>
					</div>
				</div>
			</div>
		<?php } ?>
		<?php } ?>
		<?php
}
?>

<?php
/**
 * @todo Should we be labeling Teacher and Student here?
 */
if ( $courseId ) {
	?>
	<h3 class="ld-report-course-name"><?php echo $course->post_title; ?></h3>
	<?php
} elseif ( isset( $_GET ) && isset( $_GET['course'] ) && '' !== $_GET['course'] && ! groups_is_user_mod( bp_loggedin_user_id(), bp_get_current_group_id() ) && ! groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() ) && ! bp_current_user_can( 'bp_moderate' ) ) {
	$course = get_post( (int) $_GET['course'] );
	?>
	<h3 class="ld-report-course-name"><?php echo $course->post_title; ?></h3>
	<?php
}
?>

<div class="ld-report-no-data">
	<aside class="bp-feedback bp-template-notice info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php _e( 'Sorry, no report data was found.', 'buddyboss' ); ?></p>
	</aside>
</div>

<div class="bp_ld_report_table_wrapper">
	<table class="bp_ld_report_table" data-completed="true"></table>
</div>

<!--<div class="bp_ld_report_table_wrapper">-->
<!--	<h2>--><?php //echo $incompleted_table_title; ?><!--</h2>-->
<!--	<table class="bp_ld_report_table" data-completed="false"></table>-->
<!--</div>-->

