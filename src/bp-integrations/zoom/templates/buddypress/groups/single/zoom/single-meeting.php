<?php
/**
 * BuddyBoss - Groups Zoom Single Meeting
 *
 * @since BuddyBoss 1.2.10
 */

$meeting = bp_zoom_get_current_meeting();
?>
<div id="bp-zoom-single-meeting">
	<div class="z-form-row" style="padding-top: 10px;">
		<div class="form-group">
			<label class="meeting-label col-md-2"><?php _e( 'Topic', 'buddyboss' ); ?></label>
			<div class="control col-md-10">
				<?php echo $meeting->title; ?>
			</div>
		</div>
	</div>
	<div class="z-form-row">
		<div class="form-group">
			<label class="meeting-label col-md-2"><?php _e( 'Time', 'buddyboss' ); ?></label>
			<div class="controls col-md-10">
				<div style="margin-bottom:15px;"><?php echo bp_core_get_format_date( $meeting->start_date, bp_core_date_format( true, true ) ); ?><?php echo $meeting->timezone; ?></div>
			</div>
		</div>
	</div>
	<div class="z-form-row">
		<div class="form-group">
			<label class="meeting-label col-md-2"><?php _e( 'Meeting ID', 'buddyboss' ); ?></label>
			<div class="control col-md-10">
				<?php echo $meeting->zoom_meeting_id; ?>
			</div>
		</div>
	</div>
	<div class="z-form-row">
		<div class="form-group">
			<label class="meeting-label col-md-2"
				   style="padding-top: 7px; margin-bottom: -7px;"><?php _e( 'Meeting Password', 'buddyboss' ); ?></label>
			<div class="control col-md-10">
				password
			</div>
		</div>
	</div>
	<div class="z-form-row">
		<div class="form-group">
			<label class="meeting-label col-md-2"><?php _e( 'Invite Attendees', 'buddyboss' ); ?></label>
			<div class="controls col-md-offset-2">
				<label style="margin-left: 16px;"><?php _e( 'Join URL:', 'buddyboss' ); ?></label> <a target="_blank"
																									  style="word-wrap: break-word;"
																									  href="<?php echo $meeting->zoom_join_url; ?>"><?php echo $meeting->zoom_join_url; ?></a>
				<span style="float: right;margin-right: 32px;">
<a id="copyInvitation" class="edit" href="javascript:;" role="button" aria-modal="true"
   aria-labelledby="copyInvitation copy-invite-title"><i class="glyphicon glyphicon-share"></i>&nbsp;Copy the invitation</a>
</span>
			</div>
		</div>
	</div>
	<div class="z-form-row">
		<div class="form-group clearfix">
			<label class="meeting-label col-md-2"><?php _e( 'Video', 'buddyboss' ); ?></label>
			<div class="controls col-md-10">
				<label class="col-md-1" style="margin-left: 0px;padding-left: 0px;">
					<?php _e( 'Host', 'buddyboss' ); ?></label>
				<label class="col-md-offset-1">
					<?php echo $meeting->host_video ? __( 'On', 'buddyboss' ) : __( 'Off', 'buddyboss' ); ?></label>
			</div>
		</div>
		<div class="form-group clearfix">
			<label class="meeting-label col-md-2">&nbsp;</label>
			<div class="controls col-md-10">
				<label class="col-md-1"
					   style="margin-left: 0px;padding-left: 0px;"><?php _e( 'Participant', 'buddyboss' ); ?></label>
				<label class="col-md-offset-1"><?php echo $meeting->participants_video ? __( 'On', 'buddyboss' ) : __( 'Off', 'buddyboss' ); ?></label></label>
			</div>
		</div>
		<div class="form-group clearfix">
			<div class="meeting-label col-md-2"><?php _e( 'Meeting Options', 'buddyboss' ); ?></div>
			<div class="controls col-md-10">
				<label class="checkbox <?php echo $meeting->join_before_host ? 'checked' : ''; ?>">
					<i class="status-icon"></i>
					<?php _e( 'Enable join before host', 'buddyboss' ); ?></label>
			</div>
		</div>
		<div class="form-group clearfix">
			<div class="meeting-label col-md-2"></div>
			<div class="controls col-md-10">
				<label for="option_mute_upon_entry"
					   class="checkbox <?php echo $meeting->mute_participants ? 'checked' : ''; ?>">
					<i class="status-icon"></i><?php _e( 'Mute participants upon entry', 'buddyboss' ); ?>
				</label>
			</div>
		</div>
		<div class="form-group clearfix">
			<div class="meeting-label col-md-2"></div>
			<div class="controls col-md-10">
				<label class="checkbox"><i class="status-icon"></i><?php _e( 'Enable waiting room', 'buddyboss' ); ?>
				</label>
			</div>
		</div>
		<div class="form-group clearfix">
			<div class="meeting-label col-md-2"></div>
			<div class="controls col-md-10">
				<label class="checkbox"><i
							class="status-icon"></i><?php _e( 'Only authenticated users can join', 'buddyboss' ); ?>
				</label>
			</div>
		</div>
		<div class="form-group" id="meet-autorec">
			<div class="meeting-label col-md-2"></div>
			<div class="controls col-md-10">
				<label class="checkbox  checked" for="option_autorec">
					<i class="status-icon"></i>
					<span>Record the meeting automatically in the cloud</span>
				</label>
			</div>
		</div>
	</div>
	<div class="z-form-row">
		<div class="form-group" style="margin-bottom: 0px;">
			<div class="controls col-md-2">
				<a role="button" id="btn_Delete_meeting" class="btn delete" href="javascript:;" data-id="92551641574"
				   data-topic="My Meeting" data-s="" data-t="2" data-time="Apr 18, 2020 12:00 AM" data-duration="30">Delete
					this Meeting</a>
			</div>
			<div class="controls col-md-offset-3">
				<div style="float: right;margin-right: 32px;">
					<a role="button" class="btn btn-default" href="https://zoom.us/meeting/92551641574/edit">Edit this
						Meeting</a>
					<a type="button" style="margin-left:16px;" class="btn_Start_meeting btn btn-primary start "
					   href="<?php echo $meeting->zoom_start_url; ?>">
						Start this Meeting</a>
				</div>
			</div>
		</div>
	</div>
</div>
