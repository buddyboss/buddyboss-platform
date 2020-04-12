<?php
/**
 * BuddyBoss - Groups Create Zoom Meetings
 *
 * @since BuddyBoss 1.2.10
 */
?>
<div id="bp-new-zoom-meeting" class="bp-new-zoom-meeting-form">
	<form id="bp-new-zoom-meeting-form" name="bp-new-zoom-meeting-form" method="post" action="">
		<fieldset class="bp-zoom-meeting-form">
			<legend></legend>
			<div>
				<p>
					<label for="bp-zoom-meeting-title"><?php _e( 'Title', 'buddyboss' ); ?></label><br />
					<input type="text" id="bp-zoom-meeting-title" value="<?php _e( 'My Meeting', 'buddyboss' ); ?>" tabindex="1" name="bp-zoom-meeting-title" />
				</p>
				<p>
					<?php $users = bp_zoom_get_users(); ?>
					<label for="bp-zoom-meeting-host"><?php _e( 'Host', 'buddyboss' ); ?></label><br />
					<select id="bp-zoom-meeting-host" name="bp-zoom-meeting-host" tabindex="2">
						<?php

						foreach ( $users as $user ): ?>
							<option
								value="<?php echo $user->id; ?>"><?php echo $user->first_name . ' ( ' . $user->email . ' )'; ?></option>
						<?php endforeach;
						?>
					</select>
				</p>
				<p>
					<label for="bp-zoom-meeting-start-date"><?php _e( 'When', 'buddyboss' ); ?></label><br />
					<input type="text" id="bp-zoom-meeting-start-date" value="" tabindex="3" name="bp-zoom-meeting-start-date" />
				</p>
				<p>
					<label for="bp-zoom-meeting-duration"><?php _e( 'Duration', 'buddyboss' ); ?></label><br />
					<input type="number" id="bp-zoom-meeting-duration" value="" tabindex="4" name="bp-zoom-meeting-duration" />
				</p>
				<p>
					<label for="bp-zoom-meeting-timezone"><?php _e( 'Timezone', 'buddyboss' ); ?></label><br />
					<select id="bp-zoom-meeting-timezone" name="bp-zoom-meeting-timezone" tabindex="5">
						<?php $timezones = bp_zoom_get_timezone_options(); ?>
						<?php foreach ( $timezones as $k => $timezone ) { ?>
							<option value="<?php echo $k; ?>"><?php echo $timezone; ?></option>
						<?php } ?>
					</select>
				</p>
				<p>
					<label for="bp-zoom-meeting-registration"><?php _e( 'Registration', 'buddyboss' ); ?></label><br/>
					<input type="checkbox" name="bp-zoom-meeting-registration" id="bp-zoom-meeting-registration" value="1" tabindex="6"/><?php _e( 'Required', 'buddyboss' ); ?>
				</p>
				<p>
					<label for="bp-zoom-meeting-password"><?php _e( 'Meeting Password', 'buddyboss' ); ?></label><br />
					<input type="text" id="bp-zoom-meeting-password" value="" tabindex="7" name="bp-zoom-meeting-password" />
				</p>
				<p>
					<label for="bp-zoom-meeting-host-video"><?php _e( 'Host Video', 'buddyboss' ); ?></label><br />
					<input type="checkbox" id="bp-zoom-meeting-host-video" value="" tabindex="8" name="bp-zoom-meeting-host-video" /><?php _e( 'Start video when host join meeting.', 'buddyboss' ); ?>
				</p>
				<p>
					<label for="bp-zoom-meeting-participants-video"><?php _e( 'Participants Video', 'buddyboss' ); ?></label><br />
					<input type="checkbox" id="bp-zoom-meeting-participants-video" value="" tabindex="9" name="bp-zoom-meeting-participants-video" /><?php _e( 'Start video when participants join meeting.', 'buddyboss' ); ?>
				</p>
				<p>
					<label for="bp-zoom-meeting-join-before-host"><?php _e( 'Join Before Host', 'buddyboss' ); ?></label><br />
					<input type="checkbox" id="bp-zoom-meeting-join-before-host" value="" tabindex="10" name="bp-zoom-meeting-join-before-host" /><?php _e( 'Enable join before host.', 'buddyboss' ); ?>
				</p>
				<p>
					<label for="bp-zoom-meeting-mute-participants"><?php _e( 'Mute Participants', 'buddyboss' ); ?></label><br />
					<input type="checkbox" id="bp-zoom-meeting-mute-participants" value="" tabindex="11" name="bp-zoom-meeting-mute-participants" /><?php _e( 'Mute participants upon entry.', 'buddyboss' ); ?>
				</p>
				<p>
					<label for="bp-zoom-meeting-waiting-room"><?php _e( 'Waiting Room', 'buddyboss' ); ?></label><br />
					<input type="checkbox" id="bp-zoom-meeting-waiting-room" value="" tabindex="12" name="bp-zoom-meeting-waiting-room" /><?php _e( 'Enable waiting room.', 'buddyboss' ); ?>
				</p>
				<p>
					<label for="bp-zoom-meeting-authentication"><?php _e( 'Authenticated Users', 'buddyboss' ); ?></label><br />
					<input type="checkbox" id="bp-zoom-meeting-authentication" value="" tabindex="13" name="bp-zoom-meeting-authentication" /><?php _e( 'Only authenticated users can join.', 'buddyboss' ); ?>
				</p>
				<p>
					<label for="bp-zoom-meeting-recording"><?php _e( 'Auto Recording', 'buddyboss' ); ?></label><br />
					<select id="bp-zoom-meeting-recording" name="bp-zoom-meeting-recording" tabindex="14">
						<option value="none" selected="selected"><?php _e( 'No Recordings', 'buddyboss' ); ?></option>
						<option value="local"><?php _e( 'Local', 'buddyboss' ); ?></option>
						<option value="cloud"><?php _e( 'Cloud', 'buddyboss' ); ?></option>
					</select><?php _e( 'Set what type of auto recording feature you want to add. Default is none.', 'buddyboss' ); ?>
				</p>
<!--				<p>-->
<!--					<label for="bp-zoom-meeting-alt-hosts">--><?php //_e( 'Alternative Hosts', 'buddyboss' ); ?><!--</label><br />-->
<!--					<select id="bp-zoom-meeting-alt-hosts" name="bp-zoom-meeting-alt-hosts" tabindex="15">-->
<!--					</select>-->
<!--				</p>-->

				<div class="bp-zoom-meeting-form-submit-wrapper">
					<?php wp_nonce_field( 'bp_zoom_new_meeting' ); ?>
					<input type="hidden" id="bp-zoom-meeting-group-id" name="bp-zoom-meeting-group-id" value="<?php echo bp_get_group_id(); ?>"/>
					<button type="submit" tabindex="15" id="bp-zoom-meeting-form-submit" name="bp-zoom-meeting-form-submit" class="button submit"><?php _e( 'Create Meeting', 'buddyboss' ); ?></button>
				</div>

			</div>
		</fieldset>
	</form>
</div>
