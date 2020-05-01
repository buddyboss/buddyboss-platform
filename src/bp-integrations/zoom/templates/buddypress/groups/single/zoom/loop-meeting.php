<?php
/**
 * BuddyBoss - Groups Zoom Loop Meetings
 *
 * @since BuddyBoss 1.2.10
 */

$group_link = bp_get_group_permalink( buddypress()->groups->current_group );
$url        = trailingslashit( $group_link . 'zoom/meetings/' . bp_get_zoom_meeting_id() );

?>
<div class="meeting-item-wrap">
	<div class="meeting-item" data-id="<?php bp_zoom_meeting_id(); ?>"
		 data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>">
		<div class="meeting-item-col meeting-date">
			<?php echo bp_zoom_get_date_time_label( bp_get_zoom_meeting_start_date(), bp_get_zoom_meeting_timezone() ); ?>
		</div>
		<div class="meeting-item-col meeting-topic">
			<a href="<?php echo $url; ?>"
			   class="sort-headers meeting-link"><?php bp_zoom_meeting_title(); ?></a>
		</div>
		<div class="meeting-item-col meeting-id"><?php bp_zoom_meeting_zoom_meeting_id(); ?></div>
		<div class="meeting-item-col meeting-action">
			<?php if ( bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ) : ?>
				<a role="button" target="_blank" href="<?php bp_zoom_meeting_zoom_start_url(); ?>"
				   class="button small meeting-start"><?php _e( 'Start', 'buddyboss' ); ?></a>
			<?php endif; ?>
			<?php if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) : ?>
				<a role="button" id="bp-zoom-meeting-delete"
				   data-nonce="<?php echo wp_create_nonce( 'bp_zoom_meeting_delete' ); ?>" href="#"
				   class="button small outline bp-zoom-meeting-delete"><?php _e( 'Delete', 'buddyboss' ); ?></a>
			<?php endif; ?>
			<a role="button" id="bp-zoom-meeting-view-recordings" href="#"
			   class="button small outline"><?php _e( 'View Recordings', 'buddyboss' ); ?></a>
		</div>
	</div>
	<div class="form-group recording-list"></div>
</div>
