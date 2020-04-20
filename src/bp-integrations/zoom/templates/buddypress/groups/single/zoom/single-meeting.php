<?php
/**
 * BuddyBoss - Groups Zoom Single Meeting
 *
 * @since BuddyBoss 1.2.10
 */

$meeting = bp_zoom_get_current_meeting();
?>
<div class="meeting-item-container">
	<h2><?php echo $meeting->title; ?></h2>
	<div id="bp-zoom-single-meeting" class="meeting-item-table single-meeting-item-table">
		<div class="single-meeting-item">
			<label class="meeting-item-head"><?php _e( 'Topic', 'buddyboss' ); ?></label>
			<div class="meeting-item-col">
				<?php echo $meeting->title; ?>
			</div>
		</div>
		<div class="single-meeting-item">
			<label class="meeting-item-head"><?php _e( 'Time', 'buddyboss' ); ?></label>
			<div class="meeting-item-col">
				<?php echo bp_core_get_format_date( $meeting->start_date, bp_core_date_format( true, true ) ); ?>
				<?php echo $meeting->timezone; ?>
			</div>
		</div>
		<div class="single-meeting-item">
			<label class="meeting-item-head"><?php _e( 'Meeting ID', 'buddyboss' ); ?></label>
			<div class="meeting-item-col">
				<?php echo $meeting->zoom_meeting_id; ?>
			</div>
		</div>
		<div class="single-meeting-item">
			<label class="meeting-item-head"><?php _e( 'Meeting Password', 'buddyboss' ); ?></label>
			<div class="meeting-item-col">
				<?php if ( ! empty( $meeting->password ) ) : ?>
					<div class="z-form-row-action" style="display: inline-block;">
						<span class="hide-password" style="display:inline-block;"><strong>********</strong></span>
						<span class="show-password" style="display:none;margin-right: 16px;font-size:13px;"><strong><?php echo $meeting->password; ?></strong></span>
						<a href="javascript:;" class="toggle-password show-pass" style="display: inline;"><?php _e( 'Show', 'buddyboss' ); ?></a>
						<a href="javascript:;" class="toggle-password hide-pass" style="display: none;"><?php _e( 'Hide', 'buddyboss' ); ?></a>
					</div>
				<?php else: ?>
					<label id="label_option_password" class="checkbox" style="margin-top: -7px;"><i class="status-icon"></i><?php _e( 'Require meeting password', 'buddyboss' ); ?>
					</label>
				<?php endif; ?>
			</div>
		</div>
		<div class="single-meeting-item">
			<label class="meeting-item-head"><?php _e( 'Invite Attendees', 'buddyboss' ); ?></label>
			<div class="meeting-item-col">
				<?php _e( 'Join URL:', 'buddyboss' ); ?> <a target="_blank" href="<?php echo $meeting->zoom_join_url; ?>"><?php echo $meeting->zoom_join_url; ?></a>
				<span>
					<a id="copyInvitation" class="edit" href="javascript:;" role="button">
						Copy the invitation
					</a>
				</span>
			</div>
		</div>
		<div class="single-meeting-item">
			<div>
				<label class="meeting-item-head"><?php _e( 'Video', 'buddyboss' ); ?></label>
				<div class="meeting-item-col">
					<?php _e( 'Host', 'buddyboss' ); ?>
					 <?php echo $meeting->host_video ? __( ' On', 'buddyboss' ) : __( 'Off', 'buddyboss' ); ?>
				</div>
			</div>
			<div>
				<label class="meeting-item-head"></label>
				<div class="meeting-item-col">
					<?php _e( 'Participant', 'buddyboss' ); ?>
					<?php echo $meeting->participants_video ? __( 'On', 'buddyboss' ) : __( 'Off', 'buddyboss' ); ?>
				</div>
			</div>
			<div>
				<div class="meeting-item-head"><?php _e( 'Meeting Options', 'buddyboss' ); ?></div>
				<div class="meeting-item-col">
					<span>
						<i class="dashicons <?php echo false ? 'dashicons-yes' : 'dashicons-no-alt'; ?>"></i>
						<?php _e( 'Enable join before host', 'buddyboss' ); ?>
					</span>
				</div>
			</div>
			<div>
				<div class="meeting-item-head"></div>
				<div class="meeting-item-col">
					<span>
						<i class="dashicons <?php echo false ? 'dashicons-yes' : 'dashicons-no-alt'; ?>"></i>
						<?php _e( 'Mute participants upon entry', 'buddyboss' ); ?>
					</span>
				</div>
			</div>
			<div>
				<div class="meeting-item-head"></div>
				<div class="meeting-item-col">
					<span>
						<i class="dashicons <?php echo false ? 'dashicons-yes' : 'dashicons-no-alt';?>"></i><?php _e( 'Enable waiting room', 'buddyboss' ); ?>
					</span>
				</div>
			</div>
			<div>
				<div class="meeting-item-head"></div>
				<div class="meeting-item-col">
					<span>
						<i class="dashicons <?php echo false ? 'dashicons-yes' : 'dashicons-no-alt';?>"></i><?php _e( 'Only authenticated users can join', 'buddyboss' ); ?>
					</span>
				</div>
			</div>
			<div class="form-group">
				<div class="meeting-item-head"></div>
				<div class="meeting-item-col">
					<span for="option_autorec">
						<i class="dashicons <?php echo $meeting->auto_recording === 'cloud' ? 'dashicons-yes' : 'dashicons-no-alt';?>"></i>
						<span>Record the meeting automatically in the cloud</span>
					</span>
				</div>
			</div>
		</div>
		<div class="single-meeting-item last-col">
			<div class="meeting-item-col meeting-action last-col full">
				<a role="button" id="btn_Delete_meeting" class="btn delete" href="javascript:;">Delete this Meeting</a>
				<div class="pull-right">
					<a role="button" class="button small outline" href="https://zoom.us/meeting/92551641574/edit">Edit this Meeting</a>
					<a type="button" class="button small outline" href="<?php echo $meeting->zoom_start_url; ?>">Start this Meeting</a>
				</div>
			</div>
		</div>
	</div>

</div>
